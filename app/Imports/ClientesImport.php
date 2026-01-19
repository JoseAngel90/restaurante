<?php

namespace App\Imports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\ToModel;

class ClientesImport implements ToModel
{
    public function model(array $row)
    {
        // Evita importar el encabezado
        if ($row[0] === 'nombre' || $row[0] === null) {
            return null;
        }

        return new Cliente([
            'nombre' => $row[0],
            'telefono' => $row[1],
        ]);
    }
}
