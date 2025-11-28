@extends('layouts.app')

@section('title', 'Pedidos Pendientes')

@section('content')
<div class="container-fluid py-5">
    <h2 class="mb-4 text-center">Pedidos a pagar</h2>

    @php
        use Carbon\Carbon;
        $hoy = Carbon::today()->format('Y-m-d');

        // Tickets pendientes
        $tickets = App\Models\Ticket::with('pedido.cliente', 'pedido.usuario', 'pedido.tipoPedido', 'tipoPago', 'tipoTicket', 'pedido.detalles.comida.tipoComida')
            ->whereHas('tipoTicket', fn($q) => $q->where('nombre', '!=', 'Pagado')->where('nombre', '!=', 'Cancelado'))
            ->orderBy('id', 'asc')
            ->get();

        // Separar por tipo de pedido
        $ventasDirectasPendientes = $tickets->filter(function($ticket) {
            return stripos($ticket->pedido->notas, 'Venta directa') !== false;
        });

        $pedidosDomicilioPendientes = $tickets->filter(function($ticket) {
            $tipoPedido = strtolower($ticket->pedido->tipoPedido->nombre ?? '');
            return $tipoPedido === 'domicilio' || $tipoPedido === 'a domicilio';
        });

        $pedidosNormalesPendientes = $tickets->filter(function($ticket) {
            $esVentaDirecta = stripos($ticket->pedido->notas, 'Venta directa') !== false;
            $tipoPedido = strtolower($ticket->pedido->tipoPedido->nombre ?? '');
            $esDomicilio = $tipoPedido === 'domicilio' || $tipoPedido === 'a domicilio';
            return !$esVentaDirecta && !$esDomicilio;
        });

        $totalPendientes = $tickets->count();
        $totalVentasPendientes = $tickets->sum('total');

        // Tickets pagados HOY
        $ticketsPagadosHoy = App\Models\Ticket::whereDate('fecha_ticket', $hoy)
            ->whereHas('tipoTicket', fn($q) => $q->where('nombre', 'Pagado'))
            ->with('tipoPago')
            ->get();

        $totalPagadosHoy = $ticketsPagadosHoy->count();
        $totalVentasPagadasHoy = $ticketsPagadosHoy->sum('total');

        // Agrupar pagos por método
        $reportePagos = $ticketsPagadosHoy->groupBy(fn($t) => $t->tipoPago->nombre ?? 'Sin tipo');

        // Colores bonitos para los métodos
        $colores = [
            'Efectivo' => 'success',
            'Tarjeta' => 'primary',
            'Transferencia' => 'info',
            'Sin tipo' => 'secondary'
        ];

        // Comidas disponibles para los modales de editar
        $comidasDisponiblesHoy = \App\Models\DisponibilidadComidaDia::with('comida.tipoComida')
            ->where('fecha', $hoy)
            ->whereHas('comida', fn($q) => $q->where('disponible', '>', 0))
            ->get()
            ->map(fn($disp) => [
                'id' => $disp->comida->id,
                'nombre' => $disp->comida->nombre,
                'abreviatura_op' => $disp->comida->abreviatura_op,
                'disponible' => $disp->comida->disponible,
                'precio' => $disp->comida->precio ?? 0,
            ]);
    @endphp

    <!-- Bloques de Totales -->
    <div class="row g-3 mb-4">
        <!-- Totales Pendientes -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-light h-100">
                <div class="card-body text-center">
                    <h6 class="text-warning mb-1"><strong>Pendientes de Pago:</strong></h6>
                    <h5 class="text-warning mb-0">{{ $totalPendientes }} tickets</h5>
                    <small class="text-muted">${{ number_format($totalVentasPendientes, 2) }}</small>
                    <hr class="my-1">
                    <small class="text-info">Ventas directas: {{ $ventasDirectasPendientes->count() }}</small><br>
                    <small class="text-success">A domicilio: {{ $pedidosDomicilioPendientes->count() }}</small><br>
                    <small class="text-primary">Pedidos normales: {{ $pedidosNormalesPendientes->count() }}</small>
                </div>
            </div>
        </div>

        <!-- Totales Pagados HOY -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-light h-100">
                <div class="card-body text-center">
                    <h6 class="text-success mb-1"><strong>Pagados Hoy:</strong></h6>
                    <h5 class="text-success mb-0">{{ $totalPagadosHoy }} tickets</h5>
                    <small class="text-muted">${{ number_format($totalVentasPagadasHoy, 2) }}</small>
                </div>
            </div>
        </div>

        <!-- Reporte por Método de Pago -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 bg-light h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2 text-dark">
                        <i class="bi bi-bar-chart-line me-1"></i> Métodos de Pago (Hoy)
                    </h6>
                    @forelse($reportePagos as $metodo => $tickets_pagos)
                        @php
                            $total = $tickets_pagos->sum('total');
                            $cantidad = $tickets_pagos->count();
                            $color = $colores[$metodo] ?? 'secondary';
                        @endphp
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom">
                            <span class="fw-semibold text-{{ $color }}">
                                <i class="bi bi-circle-fill me-1"></i> {{ $metodo }}
                            </span>
                            <small class="text-muted">({{ $cantidad }})</small>
                            <span class="fw-bold text-dark">${{ number_format($total, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No hay pagos registrados hoy.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ================= TABLA DE TICKETS PENDIENTES ================= --}}
    @if($tickets->count() > 0)
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-task me-2"></i>
                    Tickets Pendientes de Pago
                    <small id="contadorTickets" class="ms-3 opacity-75">({{ $tickets->count() }} tickets)</small>
                </h5>
                
                {{-- BUSCADOR EN LA MISMA LÍNEA --}}
                <div class="d-flex align-items-center gap-2" style="max-width: 400px;">
                    <input type="text" 
                           id="buscadorNombre" 
                           class="form-control form-control-sm" 
                           placeholder="🔍 Buscar por nombre..."
                           autocomplete="off"
                           style="min-width: 250px;">
                    <button type="button" class="btn btn-sm btn-light" id="limpiarBusqueda" title="Limpiar búsqueda">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0" id="tablaTickets">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center"># Ticket</th>
                                <th>Cliente</th>
                                <th class="text-center">Teléfono</th>
                                <th class="text-center">Entrega</th>
                                <th>Detalles del Pedido</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Fecha/Hora</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $ticket)
                                @php
                                    $esVentaDirecta = stripos($ticket->pedido->notas, 'Venta directa') !== false;
                                    $tipoPedido = strtolower($ticket->pedido->tipoPedido->nombre ?? '');
                                    $esDomicilio = $tipoPedido === 'domicilio' || $tipoPedido === 'a domicilio';
                                    
                                    // Organizar detalles por paquetes
                                    $paquetes = [];
                                    $currentPaquete = 0;
                                    foreach($ticket->pedido->detalles as $item) {
                                        $tipo = strtoupper($item->comida->tipoComida->descripcion ?? 'SIN TIPO');
                                        if($tipo === 'PLATO FUERTE') $currentPaquete++;
                                        $paquetes[$currentPaquete][] = $item;
                                    }
                                @endphp
                                
                                <tr class="{{ $esVentaDirecta ? 'table-info' : ($esDomicilio ? 'table-success' : '') }} ticket-row" 
                                    data-cliente-nombre="{{ strtolower($ticket->pedido->cliente->nombre ?? '') }}">
                                    
                                    <!-- # Ticket -->
                                    <td class="text-center">
                                        <span class="fw-bold text-primary fs-5">#{{ $ticket->id }}</span>
                                        @if($esVentaDirecta)
                                            <br><small class="badge bg-info text-dark">VENTA DIRECTA</small>
                                        @elseif($esDomicilio)
                                            <br><small class="badge bg-success">A DOMICILIO</small>
                                        @endif
                                    </td>

                                    <!-- Cliente -->
                                    <td>
                                        <div>
                                            <strong class="cliente-nombre">{{ $ticket->pedido->cliente->nombre ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-person-badge me-1"></i>
                                                @if($esVentaDirecta)
                                                    Registrado por:
                                                @elseif($esDomicilio)
                                                    Domicilio por:
                                                @else
                                                    Atendido por:
                                                @endif
                                                <span class="fw-semibold">{{ $ticket->pedido->usuario->nombre ?? 'N/A' }}</span>
                                            </small>
                                        </div>
                                    </td>

                                    <!-- Teléfono -->
                                    <td class="text-center">
                                        @if($ticket->pedido->cliente->telefono === 'walking')
                                            <span class="badge bg-secondary fs-6">WALKING</span>
                                        @else
                                            <span class="badge bg-primary">{{ $ticket->pedido->cliente->telefono ?? 'N/A' }}</span>
                                        @endif
                                    </td>

                                    <!-- Tipo -->
                                    <td class="text-center">
                                        @if($esVentaDirecta)
                                            <span class="badge bg-info text-dark fs-6">
                                                <i class="bi bi-lightning-fill me-1"></i>COMEDOR
                                            </span>
                                        @elseif($esDomicilio)
                                            <span class="badge bg-success fs-6">
                                                <i class="bi bi-house-door me-1"></i>A DOMICILIO
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark fs-6">
                                                <i class="bi bi-clock me-1"></i>PASAR A RECOGER
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Detalles del Pedido -->
                                    <td>
                                        <div class="detalles-pedido" style="max-width: 300px;">
                                            @foreach($paquetes as $numPaquete => $items)
                                                <div class="mb-1 p-2 border rounded bg-light">
                                                    <small class="fw-bold text-primary">Paquete {{ $numPaquete }}:</small>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach($items as $item)
                                                            <span class="badge bg-secondary small">
                                                                {{ $item->comida->abreviatura_op ?? '?' }}
                                                                @if($item->cantidad > 1)
                                                                    <span class="badge bg-danger ms-1">{{ $item->cantidad }}</span>
                                                                @endif
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>

                                    <!-- Total -->
                                    <td class="text-center">
                                        <span class="fs-5 fw-bold text-success">
                                            ${{ number_format($ticket->total, 2) }}
                                        </span>
                                    </td>

                                    <!-- Fecha/Hora -->
                                    <td class="text-center">
                                        <div>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($ticket->fecha_ticket)->format('d/m/Y') }}
                                            </small>
                                            <br>
                                            <span class="badge bg-dark">
                                                {{ \Carbon\Carbon::parse($ticket->fecha_ticket)->format('H:i') }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Acciones -->
                                    <td class="text-center">
                                        <div class="btn-group-vertical gap-1" style="width: 120px;">
                                            <!-- Botón Pagar -->
                                            <button class="btn btn-success btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#pagarModal{{ $ticket->id }}">
                                                <i class="bi bi-cash-stack me-1"></i>Pagar
                                            </button>

                                            <!-- Botón Editar (solo si no es venta directa) -->
                                            @if(!$esVentaDirecta)
                                                <button class="btn btn-warning btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editarPedidoModal{{ $ticket->pedido->id }}">
                                                    <i class="bi bi-pencil me-1"></i>Agregar
                                                </button>
                                            @endif

                                            <!-- Botón Cancelar -->
                                            <button class="btn btn-danger btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#cancelarModal{{ $ticket->id }}">
                                                <i class="bi bi-x-circle me-1"></i>Cancelar
                                            </button>

                                            <!-- Botón Imprimir -->
                                            <!-- <button class="btn btn-info btn-sm btn-imprimir-ticket" 
                                                    data-ticket-id="{{ $ticket->id }}"
                                                    title="Imprimir ticket">
                                                <i class="bi bi-printer me-1"></i>Imprimir
                                            </button> -->
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Mensaje cuando no hay resultados -->
                <div id="sinResultados" class="text-center py-5" style="display: none;">
                    <i class="bi bi-search display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No se encontró ningún cliente</h4>
                    <p class="text-muted">Intenta con otro nombre.</p>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h4 class="text-muted mt-3">No hay tickets pendientes</h4>
            <p class="text-muted">Todos los tickets han sido procesados.</p>
        </div>
    @endif
</div>

{{-- ================= TODOS LOS MODALES FUERA DEL BUCLE ================= --}}
@foreach($tickets as $ticket)
    @php
        $esVentaDirecta = stripos($ticket->pedido->notas, 'Venta directa') !== false;
        $tipoPedido = strtolower($ticket->pedido->tipoPedido->nombre ?? '');
        $esDomicilio = $tipoPedido === 'domicilio' || $tipoPedido === 'a domicilio';
    @endphp

    {{-- MODAL PAGAR --}}
    <div class="modal fade" id="pagarModal{{ $ticket->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-stack me-2"></i>Pagar Ticket #{{ $ticket->id }}
                        @if($esDomicilio)
                            <small class="badge bg-light text-success ms-2">A Domicilio</small>
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('ticket.pagar', $ticket->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="text-center mb-4 p-3 bg-light rounded">
                            <h6 class="text-muted mb-1">Total a Pagar:</h6>
                            <h2 class="text-success mb-0 fw-bold total-pagar" data-total="{{ $ticket->total }}">
                                ${{ number_format($ticket->total, 2) }}
                            </h2>
                        </div>
                        
                        <div class="mb-3">
                            <label for="id_tipo_pago_{{ $ticket->id }}" class="form-label fw-bold">
                                <i class="bi bi-credit-card me-1"></i>Método de Pago
                            </label>
                            <select name="id_tipo_pago" 
                                    id="id_tipo_pago_{{ $ticket->id }}" 
                                    class="form-select select-tipo-pago" 
                                    data-ticket-id="{{ $ticket->id }}"
                                    required>
                                @foreach(App\Models\TipoPago::all() as $tipoPago)
                                    <option value="{{ $tipoPago->id }}" 
                                            data-metodo="{{ strtolower($tipoPago->nombre) }}"
                                            {{ $ticket->id_tipo_pago == $tipoPago->id ? 'selected' : '' }}>
                                        {{ $tipoPago->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- CAMPOS PARA EFECTIVO (con cálculo de cambio) --}}
                        <div id="camposEfectivo{{ $ticket->id }}" class="campos-efectivo" style="display: none;">
                            <div class="mb-3">
                                <label for="monto_recibido_{{ $ticket->id }}" class="form-label fw-bold">
                                    <i class="bi bi-cash me-1"></i>Monto Recibido
                                </label>
                                <input type="number" 
                                       id="monto_recibido_{{ $ticket->id }}" 
                                       class="form-control form-control-lg text-center input-monto-recibido" 
                                       placeholder="$0.00"
                                       step="0.01"
                                       min="0"
                                       data-ticket-id="{{ $ticket->id }}"
                                       data-total="{{ $ticket->total }}">
                            <small class="text-muted">Ingresa el dinero que te dio el cliente</small>
                        </div>

                        <div class="alert alert-info mb-3" id="alertaCambio{{ $ticket->id }}" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">
                                    <i class="bi bi-arrow-return-left me-1"></i>Cambio a devolver:
                                </span>
                                <span class="fs-4 fw-bold cambio-calculado" id="cambioCalculado{{ $ticket->id }}">
                                    $0.00
                                </span>
                            </div>
                        </div>

                        <div class="alert alert-danger mb-3" id="alertaInsuficiente{{ $ticket->id }}" style="display: none;">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Monto insuficiente.</strong> Faltan: 
                            <span class="fw-bold faltante-calculado" id="faltanteCalculado{{ $ticket->id }}">$0.00</span>
                        </div>

                        <input type="hidden" name="cambio" id="cambioHidden{{ $ticket->id }}" value="0">
                        <input type="hidden" name="monto_recibido" id="montoRecibidoHidden{{ $ticket->id }}" value="0">
                    </div>

                    @if($esVentaDirecta)
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Venta Directa:</strong> Los productos ya fueron entregados al cliente.
                        </div>
                    @elseif($esDomicilio)
                        <div class="alert alert-success">
                            <i class="bi bi-house-door me-2"></i>
                            <strong>Pedido a Domicilio:</strong> El pedido será entregado en el domicilio del cliente.
                        </div>
                    @endif
                    
                    <div class="alert alert-secondary mb-0">
                        <small>
                            <i class="bi bi-person-badge me-1"></i>
                            <strong>
                                @if($esVentaDirecta)
                                    Registrado
                                @elseif($esDomicilio)
                                    Domicilio
                                @else
                                    Atendido
                                @endif
                                por:
                            </strong> 
                            {{ $ticket->pedido->usuario->nombre ?? 'N/A' }}
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" 
                            class="btn btn-success px-4 btn-confirmar-pago" 
                            id="btnConfirmarPago{{ $ticket->id }}"
                            data-ticket-id="{{ $ticket->id }}">
                        <i class="bi bi-check-circle me-2"></i>Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    {{-- MODAL CANCELAR --}}
    <div class="modal fade" id="cancelarModal{{ $ticket->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>Cancelar Ticket #{{ $ticket->id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-3">¿Estás seguro de cancelar este ticket?</p>
                    <p class="text-danger">
                        <strong>Total: ${{ number_format($ticket->total, 2) }}</strong>
                    </p>
                    
                    <div class="alert alert-warning text-start">
                        <small>
                            <i class="bi bi-person-badge me-1"></i>
                            <strong>
                                @if($esVentaDirecta)
                                    Registrado
                                @elseif($esDomicilio)
                                    Domicilio
                                @else
                                    Atendido
                                @endif
                                por:
                            </strong> 
                            {{ $ticket->pedido->usuario->nombre ?? 'N/A' }}
                        </small>
                    </div>
                    
                    @if($esVentaDirecta)
                        <div class="alert alert-info text-start">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Venta Directa:</strong> Los productos se devolverán al inventario.
                        </div>
                    @elseif($esDomicilio)
                        <div class="alert alert-success text-start">
                            <i class="bi bi-house-door me-2"></i>
                            <strong>Pedido a Domicilio:</strong> Los productos se devolverán al inventario.
                        </div>
                    @endif
                    
                    <small class="text-muted">Esta acción no se puede deshacer.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, volver</button>
                    <form action="{{ route('ticket.cancelar', $ticket->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="bi bi-x-circle me-2"></i>Sí, cancelar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL EDITAR PEDIDO (solo para pedidos normales y domicilio) --}}
    @if(!$esVentaDirecta)
        <div class="modal fade" id="editarPedidoModal{{ $ticket->pedido->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('pedido.update', $ticket->pedido->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="modal-header {{ $esDomicilio ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                            <h5 class="modal-title">
                                Editar Pedido #{{ $ticket->pedido->id }}
                                @if($esDomicilio)
                                    <span class="badge bg-light text-success ms-2">
                                        <i class="bi bi-house-door me-1"></i>A Domicilio
                                    </span>
                                @endif
                                <small class="ms-2 opacity-75">
                                    <i class="bi bi-person-badge"></i>
                                    {{ $esDomicilio ? 'Domicilio' : 'Atendido' }} por: {{ $ticket->pedido->usuario->nombre ?? 'N/A' }}
                                </small>
                            </h5>
                            <button type="button" class="btn-close {{ $esDomicilio ? 'btn-close-white' : '' }}" data-bs-dismiss="modal"></button>
                        </div>
                        
                        <div class="modal-body">
                            {{-- DATOS DEL CLIENTE --}}
                            <h6 class="fw-bold">Datos del Cliente</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" name="cliente_nombre" class="form-control" value="{{ $ticket->pedido->cliente->nombre ?? '' }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" name="cliente_telefono" class="form-control" value="{{ $ticket->pedido->cliente->telefono ?? '' }}" required>
                                </div>
                            </div>

                            {{-- PRODUCTOS EXISTENTES --}}
                            <h6 class="fw-bold">Productos del Pedido</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered text-center align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Comida</th>
                                            <th>Disponible</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unit.</th>
                                            <th>Subtotal</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detalle-pedido-body-{{ $ticket->pedido->id }}">
                                        @foreach($ticket->pedido->detalles as $item)
                                            @php
                                                $comida = $item->comida;
                                                $disponible = $comida ? $comida->disponible + $item->cantidad : 0;
                                            @endphp
                                            <tr data-id="{{ $item->id }}">
                                                <td class="text-start ps-2">{{ $comida->nombre ?? 'N/A' }}</td>
                                                <td>{{ $comida->disponible ?? 0 }}</td>
                                                <td>
                                                    <input type="number" 
                                                           name="detalle[{{ $item->id }}][cantidad]" 
                                                           class="form-control cantidad-input" 
                                                           value="{{ $item->cantidad }}" 
                                                           min="0" 
                                                           max="{{ $disponible }}"
                                                           data-precio="{{ $item->precio_unitario }}">
                                                    <input type="hidden" name="detalle[{{ $item->id }}][id_comida]" value="{{ $comida->id ?? 0 }}">
                                                    <input type="hidden" name="detalle[{{ $item->id }}][precio]" value="{{ $item->precio_unitario }}">
                                                </td>
                                                <td>${{ number_format($item->precio_unitario, 2) }}</td>
                                                <td class="subtotal">${{ number_format($item->subtotal, 2) }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger btn-remove-comida" data-detalle-id="{{ $item->id }}">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                    <input type="hidden" name="detalle_eliminado[]" value="" class="detalle-eliminado">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- AGREGAR NUEVA COMIDA --}}
                            <h6 class="fw-bold">Agregar Nueva Comida</h6>
                            <div class="row g-2 align-items-end mb-3 position-relative">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Buscar Comida</label>
                                    <input type="text" 
                                           id="buscarComida{{ $ticket->pedido->id }}" 
                                           class="form-control buscar-comida-input" 
                                           placeholder="Escribe abreviatura o nombre"
                                           autocomplete="off"
                                           data-pedido-id="{{ $ticket->pedido->id }}">
                                    <div id="resultadosComida{{ $ticket->pedido->id }}" 
                                         class="list-group position-absolute w-100" 
                                         style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" 
                                           id="nuevaCantidad{{ $ticket->pedido->id }}" 
                                           class="form-control" 
                                           min="1" 
                                           value="1">
                                </div>
                                <div class="col-6 col-md-3">
                                    <button type="button" 
                                            class="btn btn-success w-100 btn-agregar-comida" 
                                            data-pedido-id="{{ $ticket->pedido->id }}">
                                        <i class="bi bi-plus-circle"></i> Agregar
                                    </button>
                                </div>
                            </div>

                            {{-- TOTAL DEL PEDIDO --}}
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                    <strong>Total del Pedido:</strong>
                                    <strong class="fs-5 text-success" id="totalPedido{{ $ticket->pedido->id }}">${{ number_format($ticket->total, 2) }}</strong>
                                </div>
                            </div>

                            {{-- NOTAS --}}
                            <div class="mb-3">
                                <label class="form-label">Notas</label>
                                <textarea name="notas" class="form-control" rows="2">{{ $ticket->pedido->notas }}</textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn {{ $esDomicilio ? 'btn-success' : 'btn-warning' }} px-4">
                                <i class="bi bi-save me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- ================= MODALES DE CONFIRMACIÓN ================= --}}
@if(session('ticket_pagado_id'))
<div class="modal fade" id="ticketPagadoModal" tabindex="-1" aria-labelledby="ticketPagadoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="ticketPagadoModalLabel">¡Ticket Pagado!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-check-circle-fill text-success display-1"></i>
                <p class="mt-3">El Ticket #{{ session('ticket_pagado_id') }} ha sido pagado correctamente.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('ticket_cancelado_id'))
<div class="modal fade" id="ticketCanceladoModal" tabindex="-1" aria-labelledby="ticketCanceladoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="ticketCanceladoModalLabel">¡Ticket Cancelado!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-x-circle-fill text-danger display-1"></i>
                <p class="mt-3">El ticket #{{ session('ticket_cancelado_id') }} ha sido cancelado correctamente.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODAL DE CONFIRMACIÓN DE PAGO CON IMPRESIÓN --}}
@foreach($tickets as $ticket)
    <div class="modal fade" id="confirmacionPagoModal{{ $ticket->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-success">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle me-2"></i>¡Pago Confirmado!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Resumen del Ticket -->
                    <div class="text-center mb-4 p-3 bg-light rounded">
                        <h6 class="text-muted mb-2">Ticket #<span class="ticket-numero-resumen">-</span></h6>
                        <h3 class="text-success fw-bold mb-3">Total Pagado</h3>
                        <h2 class="text-success fw-bold mb-0 total-pagado-resumen">$0.00</h2>
                    </div>

                    <!-- Detalles del Pago -->
                    <div class="alert alert-info mb-3">
                        <div class="row text-center">
                            <div class="col-6 border-end">
                                <small class="text-muted d-block">Método de Pago</small>
                                <strong class="metodo-pago-resumen">-</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Hora</small>
                                <strong class="hora-pago-resumen">-</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Cambio (solo si aplica) -->
                    <div id="cambioResumenContainer{{ $ticket->id }}" style="display: none;">
                        <div class="alert alert-warning mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">
                                    <i class="bi bi-arrow-return-left me-1"></i>Cambio a Devolver:
                                </span>
                                <span class="fs-4 fw-bold cambio-resumen">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles Monto Recibido (solo si aplica) -->
                    <div id="montoRecibidoResumenContainer{{ $ticket->id }}" style="display: none;">
                        <div class="mb-3 p-2 border rounded">
                            <small class="text-muted d-block">Monto Recibido</small>
                            <strong class="monto-recibido-resumen">$0.00</strong>
                        </div>
                    </div>

                    <!-- Cliente Info -->
                    <div class="alert alert-secondary mb-0">
                        <small>
                            <i class="bi bi-person-badge me-1"></i>
                            <strong>Cliente:</strong> <span class="cliente-nombre-resumen">-</span>
                        </small>
                        <br>
                        <small>
                            <i class="bi bi-telephone me-1"></i>
                            <strong>Teléfono:</strong> <span class="cliente-telefono-resumen">-</span>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" 
                            class="btn btn-primary btn-imprimir-confirmacion" 
                            data-ticket-id="{{ $ticket->id }}">
                        <i class="bi bi-printer me-2"></i>Imprimir Ticket
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<style>
    .detalles-pedido {
        font-size: 0.85rem;
    }
    
    .detalles-pedido .badge {
        font-size: 0.7rem;
    }
    
    .table-responsive {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .btn-group-vertical .btn {
        margin-bottom: 2px;
    }
    
    .table-info {
        background-color: rgba(13, 202, 240, 0.1) !important;
    }
    
    .table-success {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }
    
    .ticket-row.highlight {
        background-color: rgba(255, 193, 7, 0.3) !important;
        box-shadow: inset 4px 0 0 #ffc107;
    }
    
    @media (max-width: 768px) {
        .detalles-pedido {
            max-width: 200px !important;
        }
        
        .btn-group-vertical {
            width: 100px !important;
        }
        
        .btn-group-vertical .btn {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
    }
</style>

{{-- ================= JAVASCRIPT GLOBAL ================= --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables del buscador
    const buscadorInput = document.getElementById('buscadorNombre');
    const limpiarBtn = document.getElementById('limpiarBusqueda');
    const sinResultados = document.getElementById('sinResultados');
    const contadorTickets = document.getElementById('contadorTickets');
    const tablaTickets = document.getElementById('tablaTickets');
    const ticketRows = document.querySelectorAll('.ticket-row');
    const totalTickets = ticketRows.length;

    // Función de búsqueda por nombre
    function buscarPorNombre() {
        const termino = buscadorInput.value.toLowerCase().trim();
        let ticketsVisibles = 0;

        ticketRows.forEach(row => {
            const clienteNombre = row.dataset.clienteNombre;
            const coincide = !termino || clienteNombre.includes(termino);

            if (coincide) {
                row.style.display = '';
                ticketsVisibles++;
                
                if (termino) {
                    row.classList.add('highlight');
                } else {
                    row.classList.remove('highlight');
                }
            } else {
                row.style.display = 'none';
                row.classList.remove('highlight');
            }
        });

        // Actualizar contador
        contadorTickets.textContent = termino 
            ? `(${ticketsVisibles} de ${totalTickets} tickets)` 
            : `(${totalTickets} tickets)`;

        // Mostrar/ocultar mensaje "Sin resultados"
        if (ticketsVisibles === 0) {
            tablaTickets.style.display = 'none';
            sinResultados.style.display = 'block';
        } else {
            tablaTickets.style.display = 'table';
            sinResultados.style.display = 'none';
        }
    }

    // Evento de búsqueda en tiempo real
    buscadorInput.addEventListener('input', buscarPorNombre);

    // Limpiar búsqueda
    limpiarBtn.addEventListener('click', function() {
        buscadorInput.value = '';
        buscarPorNombre();
        buscadorInput.focus();
    });

    // Enter para buscar
    buscadorInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarPorNombre();
        }
    });

    // Mostrar modales de confirmación si existen
    @if(session('ticket_pagado_id'))
        new bootstrap.Modal(document.getElementById('ticketPagadoModal')).show();
    @endif

    @if(session('ticket_cancelado_id'))
        new bootstrap.Modal(document.getElementById('ticketCanceladoModal')).show();
    @endif

    // Datos de comidas disponibles
    window.comidasDisponibles = @json($comidasDisponiblesHoy);

    // Funcionalidad de búsqueda para todos los modales de editar
    document.querySelectorAll('.buscar-comida-input').forEach(input => {
        const pedidoId = input.dataset.pedidoId;
        const resultados = document.getElementById(`resultadosComida${pedidoId}`);
        
        input.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            resultados.innerHTML = '';
            
            if (!query) return;

            const filtradas = window.comidasDisponibles.filter(c =>
                c.abreviatura_op.toLowerCase().includes(query) ||
                c.nombre.toLowerCase().includes(query)
            );

            filtradas.forEach(c => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action text-start';
                item.innerHTML = `<strong>${c.abreviatura_op}</strong> — ${c.nombre} <small class="text-muted">(Disp: ${c.disponible}) $${c.precio}</small>`;
                item.dataset.id = c.id;
                item.dataset.nombre = c.nombre;
                item.dataset.abreviatura = c.abreviatura_op;
                item.dataset.disponible = c.disponible;
                item.dataset.precio = c.precio;

                item.addEventListener('click', () => {
                    input.value = c.abreviatura_op;
                    input.dataset.selectedId = c.id;
                    input.dataset.selectedPrecio = c.precio;
                    input.dataset.selectedDisponible = c.disponible;
                    input.dataset.selectedNombre = c.nombre;
                    resultados.innerHTML = '';
                });

                resultados.appendChild(item);
            });
        });

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !resultados.contains(e.target)) {
                resultados.innerHTML = '';
            }
        });
    });

    // Funcionalidad de agregar comida
    document.querySelectorAll('.btn-agregar-comida').forEach(btn => {
        btn.addEventListener('click', function() {
            const pedidoId = this.dataset.pedidoId;
            agregarComidaPorBusqueda(pedidoId);
        });
    });

    // Actualizar subtotales cuando cambie la cantidad
    document.querySelectorAll('.cantidad-input').forEach(input => {
        input.addEventListener('input', function() {
            const precio = parseFloat(this.dataset.precio);
            const cantidad = parseInt(this.value) || 0;
            const subtotal = precio * cantidad;
            
            const row = this.closest('tr');
            const subtotalCell = row.querySelector('.subtotal');
            if (subtotalCell) {
                subtotalCell.textContent = `$${subtotal.toFixed(2)}`;
            }
            
            const tbody = this.closest('tbody');
            const pedidoId = tbody.id.replace('detalle-pedido-body-', '');
            actualizarTotalPedido(pedidoId);
        });
    });

    // Funcionalidad de eliminar comida
    document.querySelectorAll('.btn-remove-comida').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const detalleId = this.dataset.detalleId;
            
            const hiddenInput = row.querySelector('.detalle-eliminado');
            if (hiddenInput) {
                hiddenInput.value = detalleId;
            }
            
            row.style.display = 'none';
            
            const tbody = this.closest('tbody');
            const pedidoId = tbody.id.replace('detalle-pedido-body-', '');
            actualizarTotalPedido(pedidoId);
        });
    });
});

function agregarComidaPorBusqueda(pedidoId) {
    const input = document.getElementById(`buscarComida${pedidoId}`);
    const cantidadInput = document.getElementById(`nuevaCantidad${pedidoId}`);
    
    const selectedId = input.dataset.selectedId;
    const selectedPrecio = parseFloat(input.dataset.selectedPrecio || 0);
    const selectedDisponible = parseInt(input.dataset.selectedDisponible || 0);
    const selectedNombre = input.dataset.selectedNombre;
    const cantidad = parseInt(cantidadInput.value, 10);

    if (!selectedId) {
        alert('Selecciona una comida de la lista.');
        return;
    }
    
    if (isNaN(cantidad) || cantidad <= 0) {
        alert('Selecciona una cantidad válida.');
        return;
    }
    
    if (cantidad > selectedDisponible) {
        alert('No hay suficiente stock disponible.');
        return;
    }

    const tbody = document.getElementById(`detalle-pedido-body-${pedidoId}`);
    const newId = 'nuevo_' + Date.now();
    const subtotal = selectedPrecio * cantidad;

    const tr = document.createElement('tr');
    tr.dataset.id = newId;
    tr.innerHTML = `
        <td class="text-start ps-2">${selectedNombre}</td>
        <td>${selectedDisponible}</td>
        <td>
            <input type="number" 
                   name="detalle[${newId}][cantidad]" 
                   class="form-control cantidad-input" 
                   value="${cantidad}" 
                   min="0" 
                   max="${selectedDisponible}"
                   data-precio="${selectedPrecio}">
            <input type="hidden" name="detalle[${newId}][id_comida]" value="${selectedId}">
            <input type="hidden" name="detalle[${newId}][precio]" value="${selectedPrecio}">
        </td>
        <td>$${selectedPrecio.toFixed(2)}</td>
        <td class="subtotal">$${subtotal.toFixed(2)}</td>
        <td>
            <button type="button" class="btn btn-sm btn-danger btn-remove-new">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(tr);

    const nuevaCantidadInput = tr.querySelector('.cantidad-input');
    nuevaCantidadInput.addEventListener('input', function() {
        const precio = parseFloat(this.dataset.precio);
        const cantidad = parseInt(this.value) || 0;
        const subtotal = precio * cantidad;
        
        const subtotalCell = tr.querySelector('.subtotal');
        subtotalCell.textContent = `$${subtotal.toFixed(2)}`;
        
        actualizarTotalPedido(pedidoId);
    });

    tr.querySelector('.btn-remove-new').addEventListener('click', function() {
        tr.remove();
        actualizarTotalPedido(pedidoId);
    });

    input.value = '';
    cantidadInput.value = 1;
    delete input.dataset.selectedId;
    delete input.dataset.selectedPrecio;
    delete input.dataset.selectedDisponible;
    delete input.dataset.selectedNombre;
    
    actualizarTotalPedido(pedidoId);
}

function actualizarTotalPedido(pedidoId) {
    let total = 0;
    
    document.querySelectorAll(`#detalle-pedido-body-${pedidoId} tr:not([style*="display: none"])`).forEach(row => {
        const cantidadInput = row.querySelector('.cantidad-input');
        const precio = parseFloat(cantidadInput?.dataset.precio || 0);
        const cantidad = parseInt(cantidadInput?.value || 0);
        total += precio * cantidad;
    });
    
    const totalElement = document.getElementById(`totalPedido${pedidoId}`);
    if (totalElement) {
        totalElement.textContent = `$${total.toFixed(2)}`;
    }
}

// ==================== FUNCIONALIDAD DE CÁLCULO DE CAMBIO ====================

// Mostrar/ocultar campos de efectivo según método de pago seleccionado
document.querySelectorAll('.select-tipo-pago').forEach(select => {
    const ticketId = select.dataset.ticketId;
    const camposEfectivo = document.getElementById(`camposEfectivo${ticketId}`);
    
    // Verificar al cargar
    verificarMetodoPago(select, camposEfectivo);
    
    // Verificar al cambiar
    select.addEventListener('change', function() {
        verificarMetodoPago(this, camposEfectivo);
    });
});

function verificarMetodoPago(select, camposEfectivo) {
    const selectedOption = select.options[select.selectedIndex];
    const metodo = selectedOption.dataset.metodo;
    
    if (metodo === 'efectivo') {
        camposEfectivo.style.display = 'block';
        
        // Hacer focus en el input después de mostrar
        setTimeout(() => {
            const input = camposEfectivo.querySelector('.input-monto-recibido');
            if (input) input.focus();
        }, 100);
    } else {
        camposEfectivo.style.display = 'none';
        
        // Limpiar campos cuando no es efectivo
        const ticketId = select.dataset.ticketId;
        const montoInput = document.getElementById(`monto_recibido_${ticketId}`);
        if (montoInput) montoInput.value = '';
        
        ocultarAlertas(ticketId);
    }
}

// Calcular cambio en tiempo real
document.querySelectorAll('.input-monto-recibido').forEach(input => {
    input.addEventListener('input', function() {
        const ticketId = this.dataset.ticketId;
        const total = parseFloat(this.dataset.total);
        const montoRecibido = parseFloat(this.value) || 0;
        
        calcularCambio(ticketId, total, montoRecibido);
    });
    
    // También calcular al presionar Enter
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const ticketId = this.dataset.ticketId;
            const btnConfirmar = document.getElementById(`btnConfirmarPago${ticketId}`);
            if (btnConfirmar && !btnConfirmar.disabled) {
                btnConfirmar.click();
            }
        }
    });
});

function calcularCambio(ticketId, total, montoRecibido) {
    const alertaCambio = document.getElementById(`alertaCambio${ticketId}`);
    const alertaInsuficiente = document.getElementById(`alertaInsuficiente${ticketId}`);
    const cambioCalculado = document.getElementById(`cambioCalculado${ticketId}`);
    const faltanteCalculado = document.getElementById(`faltanteCalculado${ticketId}`);
    const btnConfirmar = document.getElementById(`btnConfirmarPago${ticketId}`);
    const cambioHidden = document.getElementById(`cambioHidden${ticketId}`);
    const montoRecibidoHidden = document.getElementById(`montoRecibidoHidden${ticketId}`);
    
    if (montoRecibido === 0) {
        // No mostrar nada si no ha ingresado monto
        ocultarAlertas(ticketId);
        btnConfirmar.disabled = true;
        return;
    }
    
    if (montoRecibido >= total) {
        // Monto suficiente - calcular cambio
        const cambio = montoRecibido - total;
        
        alertaCambio.style.display = 'block';
        alertaInsuficiente.style.display = 'none';
        cambioCalculado.textContent = `$${cambio.toFixed(2)}`;
        
        // Actualizar campos hidden
        cambioHidden.value = cambio.toFixed(2);
        montoRecibidoHidden.value = montoRecibido.toFixed(2);
        
        // Habilitar botón de confirmar
        btnConfirmar.disabled = false;
        btnConfirmar.classList.remove('btn-secondary');
        btnConfirmar.classList.add('btn-success');
        
    } else {
        // Monto insuficiente
        const faltante = total - montoRecibido;
        
        alertaCambio.style.display = 'none';
        alertaInsuficiente.style.display = 'block';
        faltanteCalculado.textContent = `$${faltante.toFixed(2)}`;
        
        // Deshabilitar botón de confirmar
        btnConfirmar.disabled = true;
        btnConfirmar.classList.remove('btn-success');
        btnConfirmar.classList.add('btn-secondary');
    }
}

function ocultarAlertas(ticketId) {
    const alertaCambio = document.getElementById(`alertaCambio${ticketId}`);
    const alertaInsuficiente = document.getElementById(`alertaInsuficiente${ticketId}`);
    const btnConfirmar = document.getElementById(`btnConfirmarPago${ticketId}`);
    
    if (alertaCambio) alertaCambio.style.display = 'none';
    if (alertaInsuficiente) alertaInsuficiente.style.display = 'none';
    
    // Para métodos distintos a efectivo, habilitar el botón
    const select = document.querySelector(`.select-tipo-pago[data-ticket-id="${ticketId}"]`);
    if (select) {
        const selectedOption = select.options[select.selectedIndex];
        const metodo = selectedOption.dataset.metodo;
        
        if (metodo !== 'efectivo') {
            btnConfirmar.disabled = false;
            btnConfirmar.classList.remove('btn-secondary');
            btnConfirmar.classList.add('btn-success');
        }
    }
}

// Limpiar campos al cerrar el modal
document.querySelectorAll('[id^="pagarModal"]').forEach(modal => {
    modal.addEventListener('hidden.bs.modal', function() {
        const ticketId = this.id.replace('pagarModal', '');
        const montoInput = document.getElementById(`monto_recibido_${ticketId}`);
        if (montoInput) {
            montoInput.value = '';
        }
        ocultarAlertas(ticketId);
    });
});

// ==================== FUNCIONALIDAD DE IMPRESIÓN ====================
// Reemplaza toda la sección de FUNCIONALIDAD DE IMPRESIÓN por este código:

// ==================== FUNCIONALIDAD DE IMPRESIÓN ====================
document.querySelectorAll('.btn-imprimir-ticket').forEach(btn => {
    btn.addEventListener('click', function() {
        const ticketId = this.dataset.ticketId;
        
        // Mostrar loading
        const originalHTML = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Imprimiendo...';
        
        // Obtener token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Imprimir directamente en impresora térmica
        fetch(`/ticket/${ticketId}/imprimir`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Ticket impreso correctamente');
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error al conectar con la impresora. Verifica que esté encendida y conectada.');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = originalHTML;
        });
    });
});

</script>
@endsection
