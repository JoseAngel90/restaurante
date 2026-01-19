<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comida;
use App\Models\PedidoDetalle;
use App\Models\SubtipoComida;
use Illuminate\Support\Facades\Storage;

class ComidaController extends Controller
{
    public function index() {
        $comidas = Comida::with(['tipoComida', 'subtipoComida'])->get();
        return view('comidas.index', compact('comidas'));
    }

    public function store(Request $request) {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'abreviatura_op' => 'required|string|max:10',
            'precio' => 'required|numeric',
            'id_subtipo_comida' => 'nullable|exists:subtipo_comida,id',
            'disponible' => 'nullable|integer|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $subtipo = $request->id_subtipo_comida ? SubtipoComida::find($request->id_subtipo_comida) : null;

        $rutaImagen = null;
        if ($request->hasFile('imagen')) {
            $rutaImagen = $request->file('imagen')->store('comidas', 'public');
        }

        Comida::create([
            'nombre' => $request->nombre,
            'abreviatura_op' => $request->abreviatura_op,
            'precio' => $request->precio,
            'id_subtipo_comida' => $subtipo?->id,
            'id_tipo_comida' => $subtipo?->id_tipo_comida,
            'disponible' => $request->disponible ?? 0,
            'imagen' => $rutaImagen,
        ]);

        return redirect()->back()->with('success', 'Comida agregada correctamente');
    }

    public function update(Request $request, $id) {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'abreviatura_op' => 'required|string|max:10',
            'precio' => 'required|numeric',
            'id_subtipo_comida' => 'nullable|exists:subtipo_comida,id',
            'disponible' => 'nullable|integer|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $comida = Comida::findOrFail($id);
        $subtipo = $request->id_subtipo_comida ? SubtipoComida::find($request->id_subtipo_comida) : null;

        // Si hay nueva imagen, eliminar la anterior
        if ($request->hasFile('imagen')) {
            if ($comida->imagen && Storage::disk('public')->exists($comida->imagen)) {
                Storage::disk('public')->delete($comida->imagen);
            }
            $comida->imagen = $request->file('imagen')->store('comidas', 'public');
        }

        $comida->update([
            'nombre' => $request->nombre,
            'abreviatura_op' => $request->abreviatura_op,
            'precio' => $request->precio,
            'id_subtipo_comida' => $subtipo?->id,
            'id_tipo_comida' => $subtipo?->id_tipo_comida,
            'disponible' => $request->disponible ?? 0,
            'imagen' => $comida->imagen,
        ]);

        return redirect()->back()->with('success', 'Comida actualizada correctamente');
    }

    public function destroy($id) {
        $comida = Comida::findOrFail($id);

        if ($comida->pedidoDetalles()->count() > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar esta comida porque está asociada a pedidos pendientes.');
        }

        if ($comida->imagen && Storage::disk('public')->exists($comida->imagen)) {
            Storage::disk('public')->delete($comida->imagen);
        }

        $comida->delete();
        return redirect()->back()->with('success', 'Comida eliminada correctamente.');
    }

    public function buscar(Request $request) {
        $q = $request->query('q', '');
        
        $comidas = Comida::with('subtipoComida.tipoComida')
            ->where('nombre', 'like', "%{$q}%")
            ->orWhere('abreviatura_op', 'like', "%{$q}%")
            ->get();

        $resultado = $comidas->map(function($comida) {
            return [
                'id' => $comida->id,
                'nombre' => $comida->nombre,
                'abreviatura_op' => $comida->abreviatura_op,
                'precio' => $comida->precio,
                'disponible' => $comida->disponible,
                'imagen' => $comida->imagen,
                'id_subtipo_comida' => $comida->id_subtipo_comida,
                'tipo_comida' => $comida->subtipoComida?->tipoComida?->descripcion ?? 'Sin categoría',
                'subtipo_comida' => $comida->subtipoComida?->descripcion ?? 'Sin subcategoría',
            ];
        });

        return response()->json(['comidas' => $resultado]);
    }
}
