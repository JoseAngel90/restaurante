<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Registro de usuario
   // Registro de usuario
public function registro(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:100',
        'email' => 'required|email|unique:usuario,email',
        'password' => 'required|string|min:6',
        'id_rol' => 'required|integer',
    ]);

    $usuario = Usuario::create([
        'nombre' => $request->nombre,
        'email' => $request->email,
        'password' => $request->password, // No usar bcrypt aquí
        'id_rol' => $request->id_rol,
        'activo' => 1
    ]);

    return redirect()->route('admin_usuarios')->with('success', 'Usuario registrado correctamente');
}

// Login de usuario
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $credenciales = $request->only('email', 'password');

    // Solo usuarios activos
    $credenciales['activo'] = 1;
    if (Auth::attempt($credenciales)) {
        $usuario = Auth::user()->load('rol');

        if ($usuario->id_rol == 1) {
            return redirect()->route('administrador');
        } elseif ($usuario->id_rol == 2) {
            return redirect()->route('empleados');
        } elseif ($usuario->id_rol == 6) {
            return redirect()->route('empleados_p-pedidos');
        } elseif ($usuario->id_rol == 5) {
            return redirect()->route('administrador.admin_pedidos');
        } else {
            Auth::logout();
            return redirect('/')->with('error', 'Rol no válido');
        }
    }


    return redirect('/')->with('error', 'Credenciales incorrectas o usuario inactivo');
}


    // Logout
    public function logout(Request $request)
    {
        Auth::logout(); // Cierra sesión del usuario autenticado

        $request->session()->invalidate(); // Invalida la sesión
        $request->session()->regenerateToken(); // Regenera el token CSRF

        return redirect('/')->with('success', 'Sesión cerrada correctamente');
    }

}
