<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TipoComidaController;
use App\Http\Controllers\ComidaController;
use App\Http\Controllers\HacerPedidoController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\DisponibilidadComidaDiaController;
use App\Http\Controllers\SubtipoComidaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TicketPrintController;










/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('login');
});

//Autenticadores
Route::post('/registro', [AuthController::class, 'registro']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);


// Paneles según rol
// Rutas para administrador
Route::get('/administrador', function () {return view('administrador.administrador'); })->name('administrador')->middleware('auth');
Route::get('/admin_usuarios', function () {return view('administrador.admin_usuarios'); })->name('admin_usuarios')->middleware('auth');
Route::get('/admin_comidas', function () {return view('administrador.admin_comidas'); })->name('admin_comidas')->middleware('auth');
Route::get('/admin_pedidos', function () {return view('administrador.admin_pedidos'); })->name('admin_pedidos')->middleware('auth');
Route::get('/admin_tickets', function () {return view('administrador.admin_tickets'); })->name('admin_tickets')->middleware('auth');
Route::get('/admin_menu', [MenuController::class, 'index'])->name('admin.menu');


// Creacion de menu
Route::post('/menu_semanal/guardar', [App\Http\Controllers\MenuController::class, 'guardar'])->name('menu_semanal.guardar');
Route::post('/menu_semanal/quitar', [MenuController::class, 'quitar'])->name('menu_semanal.quitar');
Route::get('/menu-semanal/exportar-pdf', [MenuController::class, 'exportarPDF'])->name('menu_semanal.exportar_pdf');
Route::post('/menu-semanal/guardar-bulk', [MenuController::class, 'guardarBulk'])->name('menu_semanal.guardar_bulk');


//Venta directa
Route::post('/venta-directa', [PedidoController::class, 'ventaDirecta'])->name('venta.directa');


//Rutas para activar/descativar / eliminar / editar usuarios
Route::post('/usuarios/toggle/{id}', [UsuarioController::class, 'toggleActivo']);
Route::delete('/usuarios/eliminar/{id}', [UsuarioController::class, 'eliminar']);
Route::put('/usuarios/editar/{id}', [UsuarioController::class, 'editar']);


//Guardar los tipos de comidas
Route::get('/tipo_comida', [TipoComidaController::class, 'index'])->name('tipo_comida.index');
Route::post('/tipo_comida', [TipoComidaController::class, 'store'])->name('tipo_comida.store');
Route::put('/tipo_comida/{id}', [TipoComidaController::class, 'update'])->name('tipo_comida.update');
Route::resource('tipo_comida', TipoComidaController::class);

//Guardar subcategorai de comidas
Route::post('/subtipo_comida', [SubtipoComidaController::class, 'store'])->name('subtipo_comida.store');
Route::put('/subtipo_comida/{id}', [SubtipoComidaController::class, 'update'])->name('subtipo_comida.update');
Route::delete('/subtipo_comida/{id}', [SubtipoComidaController::class, 'destroy'])->name('subtipo_comida.destroy');

//Ruta para guardar clientes
Route::middleware('auth')->group(function () {
    Route::get('/empleados_registro_clientes', [ClienteController::class, 'index'])->name('empleados_registro_clientes');
    Route::post('/clientes/registrar', [ClienteController::class, 'store'])->name('clientes.store');
    Route::put('/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
});
Route::post('/clientes/import', [ClienteController::class, 'import'])->name('clientes.import');



//Comidas
Route::get('/comidas', [ComidaController::class, 'index'])->name('comidas.index');
Route::post('/comidas', [ComidaController::class, 'store'])->name('comidas.store');
Route::put('/comidas/{id}', [ComidaController::class, 'update'])->name('comidas.update');
Route::delete('/comidas/{id}', [ComidaController::class, 'destroy'])->name('comidas.destroy');


//Hacer pedido
Route::get('/hacer-pedido', [HacerPedidoController::class, 'create'])->name('pedido.create');
Route::get('/pedido/{pedido}/entregar', [HacerPedidoController::class, 'entregar'])->name('pedido.entregar');
Route::post('/pedido', [HacerPedidoController::class, 'store'])->name('pedido.store');
Route::put('/pedido/{pedido}', [HacerPedidoController::class, 'update'])->name('pedido.update');
Route::put('/pedido/{id}/cancelar', [PedidoController::class, 'cancelar'])->name('pedido.cancelar');



//Generar ticket / de la mano del pedido
Route::get('/pedido/{pedido}/entregar', [PedidoController::class, 'entregarPedido'])->name('pedido.entregar');
Route::post('/pedido/{pedido}/ticket', [TicketController::class, 'generarTicket']);


//ticket pagar
Route::post('/ticket/{ticket}/pagar', [TicketController::class, 'pagar'])->name('ticket.pagar');
Route::put('/ticket/{ticket}/cancelar', [TicketController::class, 'cancelar'])->name('ticket.cancelar');


//Rutas para empleados
Route::get('/empleados', function () {return view('empleados.empleados'); })->name('empleados')->middleware('auth');
Route::get('/empleados_h-pedido', function () {return view('empleados.empleados_h-pedido'); })->name('empleados_h-pedido')->middleware('auth');

Route::get('/empleados_p-pedidos', function () {return view('empleados.empleado_despachador.empleados_p-pedidos'); })->name('empleados_p-pedidos')->middleware('auth');

Route::get('/empleados_c-disponible', function () {return view('empleados.empleados_c-disponible'); })->name('empleados_c-disponible')->middleware('auth');
Route::get('/empleados_tickets', function () {return view('empleados.empleados_tickets'); })->name('empleados_tickets')->middleware('auth');

Route::get('/empleados_registro_clientes', function () {return view('empleados.empleados_registro_clientes');})->name('empleados_registro_clientes')->middleware('auth');


Route::prefix('administrador')->middleware('auth')->group(function () {
    Route::view('/pedidos', 'administrador.admin_pedidos')->name('administrador.admin_pedidos');
    Route::view('/usuarios', 'administrador.admin_usuarios')->name('administrador.admin_usuarios');
});

Route::middleware(['auth'])->group(function () {
    // ... tus rutas existentes ...

    // Solo ruta de impresión térmica
    Route::post('/ticket/{id}/imprimir', [TicketPrintController::class, 'imprimirTicket'])->name('ticket.imprimir');
    
    // ELIMINA ESTA LÍNEA si la tienes:
    // Route::get('/ticket/{id}/print', [TicketPrintController::class, 'imprimirDirecto'])->name('ticket.print');
});




