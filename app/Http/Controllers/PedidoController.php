<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Ticket;
use App\Models\TipoPago;
use App\Models\TipoTicket;
use App\Models\Cliente;
use App\Models\PedidoDetalle; // Cambio aquí
use App\Models\TipoPedido;
use App\Models\Comida;
use Carbon\Carbon;

class PedidoController extends Controller
{
    public function entregarPedido($pedidoId)
    {
        $pedido = Pedido::with('cliente', 'detalles', 'tipoPedido')->findOrFail($pedidoId);

        // Actualizar fecha de entrega Y id_usuario
        $pedido->fecha_entrega = now();
        $pedido->id_usuario = auth()->id(); // ⬅️ AGREGAR ESTA LÍNEA
        $pedido->save();

        // Calcular total del pedido
        $total = $pedido->detalles->sum(fn($item) => $item->subtotal);

        // Obtener un tipo de ticket predeterminado (Pendiente)
        $tipoTicketPendiente = TipoTicket::firstOrCreate(['nombre' => 'Pendiente']);

        // Obtener un método de pago predeterminado (Efectivo)
        $tipoPagoPredeterminado = TipoPago::firstOrCreate(['nombre' => 'Efectivo']);

        // Crear ticket automáticamente CON id_usuario
        $ticket = Ticket::create([
            'id_pedido' => $pedido->id,
            'id_tipo_pago' => $tipoPagoPredeterminado->id,
            'id_cliente' => $pedido->id_cliente,
            'id_tipo_ticket' => $tipoTicketPendiente->id,
            'id_usuario' => auth()->id(),
            'total' => $total,
            'fecha_ticket' => now(),
        ]);

        return redirect()->back()->with('success', "Pedido entregado y ticket #{$ticket->id} generado correctamente.");
    }

    public function ventaDirecta(Request $request)
    {
        try {
            // Validar datos básicos
            $request->validate([
                'cliente_nombre' => 'required|string|max:255',
                'cliente_telefono' => 'nullable|string|max:20',
                'comidas' => 'required|array',
                'comidas.*' => 'integer|min:0'
            ]);

            // Verificar que al menos se haya seleccionado una comida
            $comidasSeleccionadas = array_filter($request->comidas, function($cantidad) {
                return $cantidad > 0;
            });

            if (empty($comidasSeleccionadas)) {
                return redirect()->back()->with('error', 'Debe seleccionar al menos una comida para procesar la venta.');
            }

            // Para venta directa, usar "walking" como teléfono predeterminado
            $telefono = $request->cliente_telefono ?: 'walking';

            // Crear o buscar cliente
            $cliente = Cliente::firstOrCreate([
                'telefono' => $telefono
            ], [
                'nombre' => $request->cliente_nombre
            ]);

            // Si el cliente existe pero con diferente nombre, actualizar
            if ($cliente->nombre !== $request->cliente_nombre) {
                $cliente->update(['nombre' => $request->cliente_nombre]);
            }

            // Cambiar a "Walking" para que aparezca como pendiente pero identificado como venta directa
            $tipoPedido = TipoPedido::firstOrCreate(['nombre' => 'Walking']);

            // Crear el pedido CON id_usuario - COMO WALKING (no entregado aún)
            $pedido = Pedido::create([
                'id_cliente' => $cliente->id,
                'id_tipo_pedido' => $tipoPedido->id, // Walking en lugar de Entregado
                'id_usuario' => auth()->id(),
                'fecha_pedido' => now(),
                'fecha_entrega' => now(), // Fecha de entrega inmediata
                'notas' => 'Venta directa - Pendiente de pago' // Nota que indica venta directa
            ]);

            $totalVenta = 0;

            // Procesar cada comida seleccionada
            foreach ($comidasSeleccionadas as $comidaId => $cantidad) {
                $comida = Comida::findOrFail($comidaId);

                // Verificar disponibilidad
                if ($comida->disponible < $cantidad) {
                    // Si no hay suficiente stock, revertir el pedido
                    $pedido->delete();
                    return redirect()->back()->with('error', "No hay suficiente stock de {$comida->nombre}. Disponible: {$comida->disponible}, Solicitado: {$cantidad}");
                }

                // Calcular subtotal
                $precio = $comida->precio ?? 0;
                $subtotal = $precio * $cantidad;
                $totalVenta += $subtotal;

                // Crear detalle del pedido
                PedidoDetalle::create([
                    'id_pedido' => $pedido->id,
                    'id_comida' => $comida->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal
                ]);

                // Reducir stock disponible INMEDIATAMENTE (como antes)
                $comida->decrement('disponible', $cantidad);
            }

            // CREAR TICKET PENDIENTE - Para que aparezca en la cola de pago del cajero
            $tipoTicketPendiente = TipoTicket::firstOrCreate(['nombre' => 'Pendiente']);
            $tipoPagoEfectivo = TipoPago::firstOrCreate(['nombre' => 'Efectivo']); // Predeterminado

            $ticket = Ticket::create([
                'id_pedido' => $pedido->id,
                'id_cliente' => $cliente->id,
                'id_tipo_ticket' => $tipoTicketPendiente->id, // PENDIENTE
                'id_tipo_pago' => $tipoPagoEfectivo->id,
                'id_usuario' => auth()->id(),
                'fecha_ticket' => now(),
                'total' => $totalVenta,
                'cambio' => 0
            ]);

            // Retornar con mensaje indicando que debe pasar a caja
            return redirect()->back()->with('success', "Venta directa registrada exitosamente. Total: $" . number_format($totalVenta, 2) . " | Ticket #{$ticket->id} - PASAR A CAJA PARA PAGAR");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al procesar la venta: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $pedido = Pedido::with('detalles', 'cliente')->findOrFail($id);
        $cliente = $pedido->cliente;

        // Actualizar datos del cliente
        $cliente->update([
            'nombre'   => $request->input('cliente_nombre'),
            'telefono' => $request->input('cliente_telefono'),
        ]);

        // Actualizar notas del pedido
        $pedido->update([
            'notas' => $request->input('notas'),
        ]);

        $normalizarTipo = function ($descripcion) {
            $t = strtoupper($descripcion ?? '');
            if (str_contains($t, 'PLATO FUERTE') || str_contains($t, 'PLATO')) {
                return 'PLATO FUERTE';
            }
            if (str_contains($t, 'SOPA')) {
                return 'SOPA';
            }
            if (str_contains($t, 'ENSAL')) {
                return 'ENSALADAS';
            }
            return $t;
        };

        $detallesExistentes = PedidoDetalle::with('comida.tipoComida')
            ->where('id_pedido', $pedido->id)
            ->get();
        $paquetesExistentes = $detallesExistentes->groupBy(function ($detalle) {
            return $detalle->numero_paquete ?? 1;
        });

        // Nuevos productos del modal se agregan como paquete extra
        $maxPaquete = $paquetesExistentes->keys()->max();
        $numeroPaqueteDefault = $maxPaquete ? intval($maxPaquete) + 1 : 1;

        $detallesRequest = $request->input('detalle', []);

        // Crear / actualizar detalles (sin usar precios del formulario)
        foreach ($detallesRequest as $key => $detalle) {
            $idComida = $detalle['id_comida'] ?? null;
            $cantidad = max(0, (int)($detalle['cantidad'] ?? 0));

            if (is_numeric($key)) {
                $detalleExistente = PedidoDetalle::find($key);
                if ($detalleExistente) {
                    if ($cantidad === 0) {
                        $detalleExistente->delete();
                    } else {
                        $precioUnitario = $detalleExistente->cantidad > 0
                            ? ($detalleExistente->subtotal / $detalleExistente->cantidad)
                            : ($detalleExistente->precio_unitario ?? 0);
                        $detalleExistente->update([
                            'id_comida' => $idComida,
                            'cantidad'  => $cantidad,
                            'precio_unitario' => round($precioUnitario, 2),
                            'subtotal'  => $cantidad * $precioUnitario,
                        ]);
                    }
                }
            } else {
                if (!$idComida || $cantidad === 0) {
                    continue;
                }
                $comida = Comida::find($idComida);
                $precioBase = $comida->precio ?? 0;
                $pedido->detalles()->create([
                    'id_comida'       => $idComida,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioBase,
                    'subtotal'        => $cantidad * $precioBase,
                    'numero_paquete'  => $numeroPaqueteDefault,
                ]);
            }
        }

        // Eliminar detalles marcados
        $eliminados = array_filter($request->input('detalle_eliminado', []));
        foreach ($eliminados as $detalleId) {
            PedidoDetalle::where('id', $detalleId)->delete();
        }

        // Recalcular subtotales respetando combo perfecto por paquete
        // Recalcular total desde BD y actualizar ticket
        $nuevoTotal = PedidoDetalle::where('id_pedido', $pedido->id)->sum('subtotal');
        $ticket = Ticket::where('id_pedido', $pedido->id)->first();
        if ($ticket) {
            $ticket->update(['total' => $nuevoTotal]);
        }

        return back()->with('success', 'Pedido actualizado correctamente.');
    }

    public function cancelar($id)
    {
        $pedido = Pedido::with('detalles', 'tipoPedido')->findOrFail($id);

        // Cambiar tipo a "Cancelado" (ajusta el ID real de Cancelado)
        $pedido->update(['id_tipo_pedido' => 2]);

        // Devolver stock de todos los detalles
        foreach ($pedido->detalles as $detalle) {
            if ($detalle->comida) {
                $detalle->comida->increment('disponible', $detalle->cantidad);
            }
        }

        return back()->with('success', 'El pedido fue cancelado correctamente.');
    }
}
