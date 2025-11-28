<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubtipoComida;

class SubtipoComidaController extends Controller
{
    public function index()
    {
        $subtipos = SubtipoComida::with('tipoComida')->get();
        return view('comidas.subtipos', compact('subtipos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:100',
            'id_tipo_comida' => 'nullable|exists:tipo_comida,id', // ahora opcional
        ]);

        SubtipoComida::create([
            'descripcion' => $request->descripcion,
            'id_tipo_comida' => $request->id_tipo_comida ?? null,
        ]);

        return redirect()->back()->with('success', 'Subcategoría agregada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'descripcion' => 'required|string|max:100',
            'id_tipo_comida' => 'nullable|exists:tipo_comida,id',
        ]);

        $subtipo = SubtipoComida::findOrFail($id);
        $subtipo->update([
            'descripcion' => $request->descripcion,
            'id_tipo_comida' => $request->id_tipo_comida ?? null,
        ]);

        return redirect()->back()->with('success', 'Subcategoría actualizada correctamente.');
    }

    public function destroy($id)
    {
        $subtipo = SubtipoComida::findOrFail($id);

        if ($subtipo->comidas()->count() > 0) {
            return redirect()->back()->with('error', 'No puedes eliminar esta subcategoría porque tiene comidas asociadas.');
        }

        $subtipo->delete();

        return redirect()->back()->with('success', 'Subcategoría eliminada correctamente.');
    }
}
