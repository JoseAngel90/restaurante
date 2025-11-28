<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Rol;

class UsuarioController extends Controller
{
    public function toggleActivo($id)
    {
        $usuario = \App\Models\Usuario::find($id);

        if (!$usuario) {
            return response()->json(['success' => false], 404);
        }

        $usuario->activo = !$usuario->activo;
        $usuario->save();

        return response()->json([
            'success' => true,
            'activo' => $usuario->activo
        ]);
    }

    public function eliminar($id)
    {
        $usuario = \App\Models\Usuario::find($id);

        if (!$usuario) {
            return response()->json(['success' => false], 404);
        }

        $usuario->delete();
        return response()->json(['success' => true]);
    }

    public function editar(Request $request, $id)
    {
        $usuario = Usuario::find($id);
        if (!$usuario) return response()->json(['success' => false], 404);

        $usuario->nombre = $request->nombre;
        $usuario->email = $request->email;
        $usuario->id_rol = $request->id_rol;
        $usuario->save();

        return response()->json([
            'success' => true,
            'usuario' => [
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol->nombre ?? 'Sin rol'
            ]
        ]);
    }

    

}
