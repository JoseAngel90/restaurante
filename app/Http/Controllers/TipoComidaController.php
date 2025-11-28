<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoComida;

class TipoComidaController extends Controller
{
    public function index()
    {
        $tipos = TipoComida::with('subtipos')->get(); // traemos subtipos también
        return view('tipo_comida.index', compact('tipos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|max:100|unique:tipo_comida,descripcion',
        ], [
            'descripcion.unique' => 'Ya existe un tipo de comida con esa descripción.'
        ]);

        TipoComida::create([
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->back()->with('success', 'Categoría agregada correctamente');
    }

    public function update(Request $request, $id)
    {
        $tipo = TipoComida::findOrFail($id);

        $request->validate([
            'descripcion' => 'required|max:100|unique:tipo_comida,descripcion,' . $tipo->id,
        ], [
            'descripcion.unique' => 'Ya existe un tipo de comida con esa descripción.'
        ]);

        $tipo->update([
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->back()->with('success', 'Tipo de comida actualizado correctamente');
    }

    public function destroy($id)
    {
        $tipo = TipoComida::findOrFail($id);

        // Evitar eliminar si tiene subtipos o comidas asociadas
        if ($tipo->subtipos()->count() > 0 || $tipo->comidas()->count() > 0) {
            return redirect()->back()->with('error', 'No puedes eliminar este tipo de comida porque tiene subtipos o comidas asociadas.');
        }

        $tipo->delete();

        return redirect()->back()->with('success', 'Tipo de comida eliminado correctamente.');
    }
}
