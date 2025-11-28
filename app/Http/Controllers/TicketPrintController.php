<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\ThermalPrinterService;
use Illuminate\Http\Request;

class TicketPrintController extends Controller
{
    public function imprimirTicket($id)
    {
        try {
            $ticket = Ticket::with([
                'pedido.cliente',
                'pedido.usuario',
                'pedido.tipoPedido',
                'tipoPago',
                'pedido.detalles.comida.tipoComida'
            ])->findOrFail($id);

            // Imprimir en impresora térmica
            $printerService = new ThermalPrinterService();
            $printerService->imprimirTicket($ticket);
            
            return response()->json([
                'success' => true,
                'message' => 'Ticket impreso correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al imprimir ticket #' . $id . ': ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al imprimir: ' . $e->getMessage()
            ], 500);
        }
    }
}
