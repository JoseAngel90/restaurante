<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ClientesImport;

class ClienteController extends Controller
{
    // Mostrar formulario + lista
    public function index()
    {
        $clientes = Cliente::orderBy('id', 'desc')->get();
        return view('empleados.empleados_registro_clientes', compact('clientes'));
    }

    // Registrar nuevo cliente
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'telefono' => 'required|string|max:20|unique:cliente,telefono',
        ]);

        Cliente::create($request->only('nombre', 'telefono'));

        return redirect()->route('empleados_registro_clientes')
            ->with('success', 'Cliente registrado correctamente.');
    }

    // Actualizar cliente
    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100',
            'telefono' => 'required|string|max:20|unique:cliente,telefono,' . $cliente->id,
        ]);

        $cliente->update($request->only('nombre', 'telefono'));

        return redirect()->route('empleados_registro_clientes')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    // Eliminar cliente
   public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);

        if ($cliente->pedidos()->exists()) {
            return back()->with('error', 'No se puede eliminar el cliente porque tiene pedidos registrados.');
        }

        $cliente->delete();
        return back()->with('success', 'Cliente eliminado correctamente.');
    }


     public function import(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,csv',
        ]);

        try {
            Excel::import(new ClientesImport, $request->file('archivo'));
            return back()->with('success', 'Clientes importados correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

}
