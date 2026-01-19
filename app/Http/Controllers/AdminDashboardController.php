<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TipoPago;
use App\Models\TipoTicket;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // ID del tipo "Pagado"
        $tipoPagado = TipoTicket::where('nombre', 'Pagado')->value('id');

        // 🔥 Tickets PAGADOS HOY (USANDO fecha_pago)
        $ticketsHoy = Ticket::where('id_tipo_ticket', $tipoPagado)
            ->whereDate('fecha_pago', $hoy)
            ->get();

        $totalPagadosHoy = $ticketsHoy->sum('total');
        $conteoPagadosHoy = $ticketsHoy->count();

        // 🔥 Métodos de pago HOY (USANDO fecha_pago)
        $metodosPago = TipoPago::with(['tickets' => function ($q) use ($hoy, $tipoPagado) {
            $q->where('id_tipo_ticket', $tipoPagado)
              ->whereDate('fecha_pago', $hoy);
        }])->get()->map(function ($tipo) {
            return [
                'nombre'   => $tipo->nombre,
                'cantidad' => $tipo->tickets->count(),
                'total'    => $tipo->tickets->sum('total'),
            ];
        });

        return view('administrador.administrador', compact(
            'conteoPagadosHoy',
            'totalPagadosHoy',
            'metodosPago'
        ));
    }
}
