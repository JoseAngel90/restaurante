<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DisponibilidadComidaDia;
use App\Models\TipoComida;
use App\Models\Comida;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index()
    {
        // Cargamos todos los tipos con sus comidas disponibles directamente
        $tiposComida = TipoComida::with(['comidas' => function($query) {
            $query->where('disponible', '>', 0); // solo comidas disponibles
        }])->get();

        // Preparamos los días de la semana (lunes a viernes)
        $diasSemana = [];
        $fechaInicioSemana = Carbon::now()->startOfWeek(); // lunes

        for ($i = 0; $i < 5; $i++) {
            $fechaDia = $fechaInicioSemana->copy()->addDays($i);
            $comidasDiaSeleccionadas = DisponibilidadComidaDia::where('fecha', $fechaDia->format('Y-m-d'))
                ->pluck('id_comida')
                ->toArray();

            $diasSemana[] = [
                'nombre' => ucfirst($fechaDia->locale('es')->dayName),
                'fecha' => $fechaDia->format('Y-m-d'),
                'comidasSeleccionadas' => $comidasDiaSeleccionadas
            ];
        }

        return view('administrador.admin_menu', compact('tiposComida', 'diasSemana'));
    }

    public function guardar(Request $request)
    {
        $fecha = $request->input('fecha');
        $comidasSeleccionadas = $request->input('comidas', []);

        if (empty($fecha) || empty($comidasSeleccionadas)) {
            return redirect()->back()->with('error', 'Debe seleccionar una fecha y al menos una comida.');
        }

        foreach ($comidasSeleccionadas as $idComida) {
            DisponibilidadComidaDia::firstOrCreate([
                'fecha' => $fecha,
                'id_comida' => $idComida,
            ]);
        }

        return redirect()->back()->with('success', 'Comidas agregadas correctamente para el día ' . $fecha);
    }

    public function quitar(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'comida_id' => 'required|exists:comidas,id',
        ]);

        DisponibilidadComidaDia::where('fecha', $request->fecha)
            ->where('id_comida', $request->comida_id)
            ->delete();

        return back()->with('success', 'Comida eliminada correctamente.');
    }

    public function exportarPDF()
    {
        $diasSemana = [];
        $fechaInicioSemana = Carbon::now()->startOfWeek();

        for ($i = 0; $i < 5; $i++) {
            $fechaDia = $fechaInicioSemana->copy()->addDays($i);
            $comidasDiaSeleccionadas = DisponibilidadComidaDia::where('fecha', $fechaDia->format('Y-m-d'))
                ->pluck('id_comida')
                ->toArray();

            $comidas = Comida::whereIn('id', $comidasDiaSeleccionadas)->with('tipoComida')->get();

            $diasSemana[] = [
                'nombre' => ucfirst($fechaDia->locale('es')->dayName),
                'fecha' => $fechaDia->format('d/m/Y'),
                'comidas' => $comidas,
            ];
        }

        $pdf = Pdf::loadView('administrador.menu_pdf', compact('diasSemana'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download('menu_semanal.pdf');
    }

    /**
     * Guardar múltiples cambios del menú semanal
     */
    public function guardarBulk(Request $request)
    {
        try {
            $cambios = $request->input('cambios', []);

            if (empty($cambios)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay cambios para guardar'
                ], 400);
            }

            DB::beginTransaction();

            foreach ($cambios as $fecha => $operaciones) {
                // Validar formato de fecha
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    continue;
                }

                // Procesar comidas a agregar
                if (!empty($operaciones['agregar'])) {
                    foreach ($operaciones['agregar'] as $comidaId) {
                        DisponibilidadComidaDia::firstOrCreate([
                            'fecha' => $fecha,
                            'id_comida' => $comidaId,
                        ]);
                    }
                }

                // Procesar comidas a quitar
                if (!empty($operaciones['quitar'])) {
                    DisponibilidadComidaDia::where('fecha', $fecha)
                        ->whereIn('id_comida', $operaciones['quitar'])
                        ->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Menú actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los cambios: ' . $e->getMessage()
            ], 500);
        }
    }
}
