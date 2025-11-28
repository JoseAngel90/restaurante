<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Ticket;
use App\Models\TipoPago;
use App\Models\TipoTicket;
use Carbon\Carbon;

class TicketController extends Controller
{
    public function generarTicket(Request $request, $pedidoId)
    {
        $pedido = Pedido::with('cliente', 'detalles')->findOrFail($pedidoId);

        // Validar que el tipo de pago exista
        $request->validate([
            'id_tipo_pago' => 'required|exists:tipo_pago,id',
        ]);

        // Obtener tipo de ticket pendiente
        $tipoTicketPendiente = TipoTicket::firstOrCreate(['nombre' => 'Pendiente']);

        // Total calculado sumando subtotal de detalles
        $total = $pedido->detalles->sum(fn($item) => $item->subtotal);

        // Crear ticket
        $ticket = Ticket::create([
            'id_pedido' => $pedido->id,
            'id_tipo_pago' => $request->id_tipo_pago,
            'id_cliente' => $pedido->id_cliente,
            'id_usuario' => auth()->id(), // Agregar usuario autenticado
            'id_tipo_ticket' => $tipoTicketPendiente->id,
            'total' => $total,
            'fecha_ticket' => now(),
            'cambio' => 0 // Agregar campo cambio
        ]);

        // Retornar vista de ticket
        return view('ticket.ver', compact('ticket'));
    }

    public function pagar(Request $request, $id)
    {
        $ticket = Ticket::with('pedido.detalles.comida', 'tipoTicket')->findOrFail($id);

        // Verificar que no esté pagado ya
        if ($ticket->tipoTicket->nombre === 'Pagado') {
            return redirect()->back()->with('error', 'El ticket ya está pagado.');
        }

        // Validar que el tipo de pago exista
        $request->validate([
            'id_tipo_pago' => 'required|exists:tipo_pago,id',
        ]);

        // Restar del stock de cada comida SOLO SI AÚN NO SE HA DESCONTADO
        if ($ticket->tipoTicket->nombre === 'Pendiente') {
            foreach ($ticket->pedido->detalles as $detalle) {
                $comida = $detalle->comida;
                if ($comida) {
                    $comida->decrement('disponible', $detalle->cantidad);
                }
            }
        }

        // Actualizar el ticket a "Pagado"
        $tipoPagado = TipoTicket::firstOrCreate(['nombre' => 'Pagado']);
        
        // Preparar datos para actualizar
        $datosActualizar = [
            'id_tipo_ticket' => $tipoPagado->id,
            'id_tipo_pago' => $request->id_tipo_pago,
        ];

        // Si es efectivo, guardar monto recibido y cambio
        $tipoPago = \App\Models\TipoPago::find($request->id_tipo_pago);
        if ($tipoPago && strtolower($tipoPago->nombre) === 'efectivo') {
            // Validar que vengan los datos
            if ($request->filled('monto_recibido') && floatval($request->monto_recibido) > 0) {
                $datosActualizar['monto_recibido'] = floatval($request->monto_recibido);
                $datosActualizar['cambio'] = floatval($request->cambio ?? 0);
            } else {
                // Si no se proporcionó, asumir pago exacto
                $datosActualizar['monto_recibido'] = $ticket->total;
                $datosActualizar['cambio'] = 0;
            }
        } else {
            // Para otros métodos de pago, establecer en null
            $datosActualizar['monto_recibido'] = null;
            $datosActualizar['cambio'] = null;
        }
        
        $ticket->update($datosActualizar);

        return redirect()->back()->with('ticket_pagado_id', $ticket->id);
    }

    public function cancelar($id)
    {
        $ticket = Ticket::with('pedido.detalles.comida', 'tipoTicket')->findOrFail($id);

        // Verificar que no esté cancelado ya
        if ($ticket->tipoTicket->nombre === 'Cancelado') {
            return redirect()->back()->with('error', 'El ticket ya está cancelado.');
        }

        // Devolver la cantidad al stock SEGÚN EL ESTADO ACTUAL
        if ($ticket->tipoTicket->nombre === 'Pagado') {
            // Si estaba pagado, devolver el stock que se descontó al pagar
            foreach ($ticket->pedido->detalles as $detalle) {
                $comida = $detalle->comida;
                if ($comida) {
                    $comida->increment('disponible', $detalle->cantidad);
                }
            }
        } elseif ($ticket->tipoTicket->nombre === 'Pendiente') {
            // Si estaba pendiente, también devolver el stock
            // (En caso de que se haya reservado al crear el ticket)
            foreach ($ticket->pedido->detalles as $detalle) {
                $comida = $detalle->comida;
                if ($comida) {
                    $comida->increment('disponible', $detalle->cantidad);
                }
            }
        }

        // Actualizar el ticket a "Cancelado"
        $tipoCancelado = TipoTicket::firstOrCreate(['nombre' => 'Cancelado']);
        
        $ticket->update([
            'id_tipo_ticket' => $tipoCancelado->id,
        ]);

        return redirect()->back()->with('success', 'Ticket cancelado y stock devuelto correctamente.');
    }
}
