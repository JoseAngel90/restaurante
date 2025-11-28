<?php

namespace App\Services;

class ThermalPrinterService
{
    protected string $printerName = 'Star BSC10';

    public function imprimirTicket($ticket)
    {
        try {
            return $this->imprimirConWindows($ticket);
        } catch (\Exception $e) {
            \Log::error('Error al imprimir ticket: ' . $e->getMessage());
            throw new \Exception("Error al imprimir el ticket: " . $e->getMessage());
        }
    }

    private function imprimirConWindows($ticket)
    {
        $content = view('tickets.print_content', compact('ticket'))->render();

        $raw = $this->envolverEscPosCentradoOscuro($content);

        $binFile = tempnam(sys_get_temp_dir(), 'ticket_') . '.bin';
        file_put_contents($binFile, $raw);

        $ps1 = $this->escribirScriptRaw();
        $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File "'
            . $ps1 . '" "' . $this->printerName . '" "' . $binFile . '"';

        $out = []; $code = 0;
        exec($cmd . ' 2>&1', $out, $code);
        \Log::info('RAW print cmd', ['cmd' => $cmd, 'code' => $code, 'out' => implode("\n", $out)]);

        @unlink($binFile);

        if ($code !== 0) {
            throw new \Exception("No se pudo enviar RAW a la impresora (código $code).");
        }
        return true;
    }

    private function envolverEscPosCentradoOscuro(string $content): string
    {
        $ESC = chr(27);
        $GS  = chr(29);

        $buf  = $ESC . "@";                              // init
        $buf .= $GS  . "L" . chr(0) . chr(0);           // margen izq = 0
        $buf .= $ESC . "a" . chr(1);                    // centrar
        $buf .= $ESC . "G" . chr(1);                    // doble golpe
        $buf .= $ESC . "E" . chr(1);                    // negrita
        $buf .= $ESC . "7" . chr(255) . chr(255) . chr(255); // densidad

        // Texto del ticket
        $buf .= $content . "\n\n";

        // reset + corte
        $buf .= $ESC . "E" . chr(0);
        $buf .= $ESC . "G" . chr(0);
        $buf .= $ESC . "a" . chr(0);
        $buf .= "\n\n\n";
        $buf .= $GS  . "V" . chr(66) . chr(0);

        return $buf;
    }

    private function escribirScriptRaw(): string
    {
        $script = <<<POW
param([string]\$PrinterName, [string]\$FilePath)

Write-Host "Impresora solicitada: \$PrinterName"
try {
  \$p = Get-Printer -Name \$PrinterName -ErrorAction Stop
  Write-Host "Impresora encontrada: \$(\$p.Name) | Puerto: \$(\$p.PortName) | Driver: \$(\$p.DriverName)"
} catch {
  Write-Host "ERROR: La impresora '\$PrinterName' no existe. Disponibles:"
  Get-Printer | Select-Object -ExpandProperty Name | ForEach-Object { Write-Host " - \$_" }
  exit 2
}

Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;
public class RawPrinterHelper {
  [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Ansi)]
  public class DOCINFOA {
    [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
    [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
    [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
  }
  [DllImport("winspool.Drv", SetLastError=true, CharSet=CharSet.Ansi)]
  public static extern bool OpenPrinter(string pPrinterName, out IntPtr phPrinter, IntPtr pDefault);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool ClosePrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", SetLastError=true, CharSet=CharSet.Ansi)]
  public static extern bool StartDocPrinter(IntPtr hPrinter, int Level, [In] DOCINFOA di);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool EndDocPrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool StartPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool EndPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool WritePrinter(IntPtr hPrinter, byte[] pBytes, int dwCount, out int dwWritten);
}
"@

\$h = [IntPtr]::Zero
if (-not [RawPrinterHelper]::OpenPrinter(\$PrinterName, [ref]\$h, [IntPtr]::Zero)) {
  \$err = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
  Write-Host ("ERROR OpenPrinter: " + ([ComponentModel.Win32Exception]::new(\$err).Message))
  exit 3
}

try {
  \$di = New-Object RawPrinterHelper+DOCINFOA
  \$di.pDocName = "RAW ESCPOS"
  \$di.pDataType = "RAW"

  if (-not [RawPrinterHelper]::StartDocPrinter(\$h, 1, \$di)) {
    \$err = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
    Write-Host ("ERROR StartDocPrinter: " + ([ComponentModel.Win32Exception]::new(\$err).Message))
    exit 4
  }
  if (-not [RawPrinterHelper]::StartPagePrinter(\$h)) {
    \$err = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
    Write-Host ("ERROR StartPagePrinter: " + ([ComponentModel.Win32Exception]::new(\$err).Message))
    exit 5
  }

  \$bytes = [System.IO.File]::ReadAllBytes(\$FilePath)
  \$written = 0
  if (-not [RawPrinterHelper]::WritePrinter(\$h, \$bytes, \$bytes.Length, [ref]\$written)) {
    \$err = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
    Write-Host ("ERROR WritePrinter: " + ([ComponentModel.Win32Exception]::new(\$err).Message))
    exit 6
  }
  if (\$written -le 0) {
    Write-Host "ERROR: 0 bytes escritos"
    exit 7
  }
  Write-Host "OK: Enviado \$written bytes"
} finally {
  [RawPrinterHelper]::EndPagePrinter(\$h) | Out-Null
  [RawPrinterHelper]::EndDocPrinter(\$h)  | Out-Null
  [RawPrinterHelper]::ClosePrinter(\$h)    | Out-Null
}
exit 0
POW;

        $ps1 = tempnam(sys_get_temp_dir(), 'rawprint_') . '.ps1';
        file_put_contents($ps1, $script);
        return $ps1;
    }
}
