<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\TipoPedido;
use App\Models\Comida;
use App\Models\PedidoDetalle;
use App\Models\DisponibilidadComidaDia;
use App\Models\Ticket;
use App\Models\TipoTicket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HacerPedidoController extends Controller
{
    /**
     * Mostrar formulario para crear un nuevo pedido
     */
    public function create()
    {
        $hoy = Carbon::today()->format('Y-m-d');

        // Traer todas las comidas disponibles hoy con stock > 0
        $comidasHoy = DisponibilidadComidaDia::with('comida.tipoComida')
            ->where('fecha', $hoy)
            ->whereHas('comida', function ($q) {
                $q->where('disponible', '>', 0);
            })
            ->get();

        return view('hacer-pedido', compact('comidasHoy'));
    }

    /**
     * Guardar un nuevo pedido
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_nombre' => 'required|string|max:100',
            'cliente_telefono' => 'required|string|max:20',
            'estado_pedido' => 'required|string|max:50',
            'detalle' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // Crear o actualizar cliente
            $cliente = Cliente::firstOrCreate(
                ['telefono' => $request->cliente_telefono],
                ['nombre' => $request->cliente_nombre]
            );

            // Tipo de pedido
            $tipo = TipoPedido::firstOrCreate(['nombre' => $request->estado_pedido]);

            // Crear pedido
            $pedido = Pedido::create([
                'id_cliente' => $cliente->id,
                'id_usuario' => auth()->id() ?? 1,
                'id_tipo_pedido' => $tipo->id,
                'fecha_pedido' => now(),
                'notas' => $request->notas,
            ]);

            // Guardar detalle por paquetes
            foreach ($request->detalle as $comidaId => $arraysCantidades) {
                if (!is_array($arraysCantidades['cantidad'])) continue;

                foreach ($arraysCantidades['cantidad'] as $cantidad) {
                    $cantidad = intval($cantidad);
                    if ($cantidad <= 0) continue;

                    $comida = Comida::find($comidaId);
                    if (!$comida || $cantidad > $comida->disponible) {
                        DB::rollBack();
                        return redirect()->back()->with('error', "No hay suficiente stock para {$comida->nombre}.");
                    }

                    PedidoDetalle::create([
                        'id_pedido' => $pedido->id,
                        'id_comida' => $comidaId,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $comida->precio,
                        'subtotal' => $cantidad * $comida->precio,
                    ]);

                    // Actualizar stock
                    $comida->decrement('disponible', $cantidad);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Pedido registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al registrar el pedido: ' . $e->getMessage());
        }
    }

    /**
     * Marcar un pedido como entregado
     */
    public function entregar($id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->fecha_entrega = now();
        $pedido->save();

        return redirect()->back()->with('success', 'Pedido entregado correctamente.');
    }

    /**
     * Actualizar un pedido existente
     */
    public function update(Request $request, Pedido $pedido)
    {
        $request->validate([
            'cliente_nombre' => 'required|string|max:100',
            'cliente_telefono' => 'required|string|max:20',
            'detalle' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // Actualizar cliente
            Cliente::updateOrCreate(
                ['id' => $pedido->id_cliente],
                [
                    'nombre' => $request->cliente_nombre,
                    'telefono' => $request->cliente_telefono
                ]
            );

            // Actualizar estado si viene
            if ($request->filled('estado_pedido')) {
                $tipo = TipoPedido::firstOrCreate(['nombre' => $request->estado_pedido]);
                $pedido->id_tipo_pedido = $tipo->id;
            }

            $pedido->notas = $request->notas;
            $pedido->save();

            // Procesar detalle
            foreach ($request->detalle as $key => $item) {
                $cantidad = intval($item['cantidad'] ?? 0);
                $precio = $item['precio'] ?? null;
                $idComida = $item['id_comida'] ?? null;

                // Si es nuevo detalle
                if (is_string($key) && str_starts_with($key, 'nuevo_')) {
                    if (!$idComida || $cantidad <= 0) continue;

                    $comida = Comida::find($idComida);
                    if (!$comida || $cantidad > $comida->disponible) {
                        DB::rollBack();
                        return redirect()->back()->with('error', "No hay suficiente stock para {$comida->nombre}.");
                    }

                    PedidoDetalle::create([
                        'id_pedido' => $pedido->id,
                        'id_comida' => $idComida,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio ?? $comida->precio,
                        'subtotal' => $cantidad * ($precio ?? $comida->precio),
                    ]);

                    $comida->decrement('disponible', $cantidad);
                    continue;
                }

                // Detalles existentes
                $detalle = PedidoDetalle::find(intval($key)) ?? PedidoDetalle::where('id_pedido', $pedido->id)->where('id_comida', $idComida)->first();
                if (!$detalle) continue;

                $comida = Comida::find($detalle->id_comida);
                if (!$comida) continue;

                // Ajustar stock y eliminar si cantidad es 0
                if ($cantidad <= 0) {
                    $comida->increment('disponible', $detalle->cantidad);
                    $detalle->delete();
                    continue;
                }

                // Ajustar diferencia
                $diff = $cantidad - $detalle->cantidad;
                if ($diff > 0) {
                    if ($diff > $comida->disponible) {
                        DB::rollBack();
                        return redirect()->back()->with('error', "No hay suficiente stock para {$comida->nombre}.");
                    }
                    $comida->decrement('disponible', $diff);
                } elseif ($diff < 0) {
                    $comida->increment('disponible', abs($diff));
                }

                // Actualizar detalle
                $detalle->cantidad = $cantidad;
                if ($precio) $detalle->precio_unitario = $precio;
                $detalle->subtotal = $detalle->cantidad * $detalle->precio_unitario;
                $detalle->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Pedido actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al actualizar el pedido: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar un pedido y devolver el stock
     */
    public function cancelar($id)
    {
        DB::beginTransaction();
        try {
            $pedido = Pedido::with('detalles.comida')->findOrFail($id);

            // Devolver el stock de todos los productos del pedido
            foreach ($pedido->detalles as $detalle) {
                $comida = $detalle->comida;
                if ($comida) {
                    // Incrementar el stock disponible
                    $comida->increment('disponible', $detalle->cantidad);
                }
            }

            // Cancelar el ticket asociado si existe
            $ticket = Ticket::where('id_pedido', $pedido->id)->first();
            if ($ticket) {
                $tipoCancelado = TipoTicket::firstOrCreate(['nombre' => 'Cancelado']);
                $ticket->update(['id_tipo_ticket' => $tipoCancelado->id]);
            }

            // Marcar el pedido como cancelado
            $tipoCancelado = TipoPedido::firstOrCreate(['nombre' => 'Cancelado']);
            $pedido->update(['id_tipo_pedido' => $tipoCancelado->id]);

            DB::commit();
            return redirect()->back()->with('success', 'Pedido cancelado y stock devuelto correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al cancelar el pedido: ' . $e->getMessage());
        }
    }
}
