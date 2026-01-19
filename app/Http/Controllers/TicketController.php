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
            'id_usuario' => auth()->id(),
            'id_tipo_ticket' => $tipoTicketPendiente->id,
            'total' => $total,
            'fecha_ticket' => now(),
            'cambio' => 0
        ]);

        return view('ticket.ver', compact('ticket'));
    }

    public function pagar(Request $request, $id)
    {
        $ticket = Ticket::with('pedido.detalles.comida', 'tipoTicket')->findOrFail($id);

        if ($ticket->tipoTicket->nombre === 'Pagado') {
            return redirect()->back()->with('error', 'El ticket ya está pagado.');
        }

        // ✅ VALIDACIÓN AGREGADA
        $request->validate([
            'id_tipo_pago'     => 'required|exists:tipo_pago,id',
            'requiere_factura' => 'nullable|boolean',
            'total_final'      => 'nullable|numeric|min:0',
        ]);

        // Restar del stock SOLO si sigue pendiente
        if ($ticket->tipoTicket->nombre === 'Pendiente') {
            foreach ($ticket->pedido->detalles as $detalle) {
                if ($detalle->comida) {
                    $detalle->comida->decrement('disponible', $detalle->cantidad);
                }
            }
        }

        $tipoPagado = TipoTicket::firstOrCreate(['nombre' => 'Pagado']);
        $tipoPago = TipoPago::find($request->id_tipo_pago);

        // ✅ DATOS ACTUALIZADOS
        $datosActualizar = [
            'id_tipo_ticket'   => $tipoPagado->id,
            'id_tipo_pago'     => $tipoPago->id,
            'total'            => $request->total_final ?? $ticket->total,
            'requiere_factura' => $request->boolean('requiere_factura'),
            'fecha_pago'       => now(),
        ];

        // Si es efectivo, guardar monto recibido y cambio
        if ($tipoPago && strtolower($tipoPago->nombre) === 'efectivo') {

            if ($request->filled('monto_recibido') && floatval($request->monto_recibido) > 0) {
                $datosActualizar['monto_recibido'] = floatval($request->monto_recibido);
                $datosActualizar['cambio'] = floatval($request->cambio ?? 0);
            } else {
                $datosActualizar['monto_recibido'] = $datosActualizar['total'];
                $datosActualizar['cambio'] = 0;
            }

        } else {
            $datosActualizar['monto_recibido'] = null;
            $datosActualizar['cambio'] = null;
        }

        $ticket->update($datosActualizar);

        return redirect()->back()->with('ticket_pagado_id', $ticket->id);
    }

    public function cancelar($id)
    {
        $ticket = Ticket::with('pedido.detalles.comida', 'tipoTicket')->findOrFail($id);

        if ($ticket->tipoTicket->nombre === 'Cancelado') {
            return redirect()->back()->with('error', 'El ticket ya está cancelado.');
        }

        // Devolver stock
        if (in_array($ticket->tipoTicket->nombre, ['Pagado', 'Pendiente'])) {
            foreach ($ticket->pedido->detalles as $detalle) {
                if ($detalle->comida) {
                    $detalle->comida->increment('disponible', $detalle->cantidad);
                }
            }
        }

        $tipoCancelado = TipoTicket::firstOrCreate(['nombre' => 'Cancelado']);

        $ticket->update([
            'id_tipo_ticket' => $tipoCancelado->id,
        ]);

        return redirect()->back()->with('success', 'Ticket cancelado y stock devuelto correctamente.');
    }
}
