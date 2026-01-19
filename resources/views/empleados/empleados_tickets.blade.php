@extends('layouts.app')

@section('title', 'Tickets Completados')

@section('content')
@php
    use Carbon\Carbon;
    use App\Models\Ticket;
    use App\Models\Pedido;

    $fechaSeleccionada = Carbon::now()->format('Y-m-d');

    // Tickets (Pagado o Cancelado) - ORDENADOS DEL MÁS RECIENTE AL ANTIGUO
    $tickets = Ticket::with('pedido.cliente', 'pedido.usuario', 'tipoPago', 'tipoTicket', 'pedido.detalles.comida')
        ->whereHas('tipoTicket', fn($q) => $q->whereIn('nombre', ['Pagado', 'Cancelado']))
        ->whereDate('fecha_ticket', $fechaSeleccionada)
        ->orderBy('fecha_ticket', 'desc') // ✅ MÁS RECIENTE PRIMERO
        ->get();

    // Pedidos Cancelados - ORDENADOS DEL MÁS RECIENTE AL ANTIGUO
    $pedidosCancelados = Pedido::with('cliente','usuario','tipoPedido','detalles.comida')
        ->whereHas('tipoPedido', fn($q) => $q->where('nombre', 'Cancelado'))
        ->whereDate('fecha_pedido', $fechaSeleccionada)
        ->orderBy('fecha_pedido', 'desc') // ✅ MÁS RECIENTE PRIMERO
        ->get();
@endphp

<div class="tickets-container">
    
    <!-- Header -->
    <div class="tickets-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1 class="tickets-title">
                        <i class="bi bi-receipt-cutoff"></i>
                        Tickets Completados
                    </h1>
                    <p class="tickets-subtitle">Historial de tickets del día</p>
                </div>
                <div class="date-badge-tickets">
                    <i class="bi bi-calendar-check me-2"></i>
                    {{ Carbon::parse($fechaSeleccionada)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">

        <!-- Stats Grid -->
        <div class="stats-tickets-grid">
            <div class="stat-ticket-card stat-success">
                <div class="stat-ticket-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-ticket-content">
                    <div class="stat-ticket-label">Tickets Pagados</div>
                    <div class="stat-ticket-value">{{ $tickets->where('tipoTicket.nombre', 'Pagado')->count() }}</div>
                    <div class="stat-ticket-info">
                        <i class="bi bi-cash-stack me-1"></i>
                        ${{ number_format($tickets->where('tipoTicket.nombre', 'Pagado')->sum('total'), 2) }}
                    </div>
                </div>
            </div>

            <div class="stat-ticket-card stat-danger">
                <div class="stat-ticket-icon">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-ticket-content">
                    <div class="stat-ticket-label">Tickets Cancelados</div>
                    <div class="stat-ticket-value">{{ $tickets->where('tipoTicket.nombre', 'Cancelado')->count() }}</div>
                    <div class="stat-ticket-info">
                        <i class="bi bi-receipt me-1"></i>
                        Tickets anulados
                    </div>
                </div>
            </div>

            <div class="stat-ticket-card stat-warning">
                <div class="stat-ticket-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="stat-ticket-content">
                    <div class="stat-ticket-label">Pedidos Cancelados</div>
                    <div class="stat-ticket-value">{{ $pedidosCancelados->count() }}</div>
                    <div class="stat-ticket-info">
                        <i class="bi bi-basket me-1"></i>
                        Sin procesar
                    </div>
                </div>
            </div>

            <div class="stat-ticket-card stat-primary">
                <div class="stat-ticket-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="stat-ticket-content">
                    <div class="stat-ticket-label">Total del Día</div>
                    <div class="stat-ticket-value">${{ number_format($tickets->where('tipoTicket.nombre', 'Pagado')->sum('total'), 2) }}</div>
                    <div class="stat-ticket-info">
                        <i class="bi bi-currency-dollar me-1"></i>
                        Ingresos totales
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tickets-tabs">
            <button class="tab-btn active" onclick="cambiarTab('pagados')">
                <i class="bi bi-check-circle-fill me-2"></i>
                Tickets Pagados
                <span class="tab-count">{{ $tickets->where('tipoTicket.nombre', 'Pagado')->count() }}</span>
            </button>
            <button class="tab-btn" onclick="cambiarTab('cancelados')">
                <i class="bi bi-x-circle-fill me-2"></i>
                Tickets Cancelados
                <span class="tab-count">{{ $tickets->where('tipoTicket.nombre', 'Cancelado')->count() }}</span>
            </button>
            <button class="tab-btn" onclick="cambiarTab('pedidos-cancelados')">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Pedidos Cancelados
                <span class="tab-count">{{ $pedidosCancelados->count() }}</span>
            </button>
        </div>

        <!-- Content Tabs -->
        <div class="tab-content-tickets">
            
            <!-- Tickets Pagados -->
            <div id="tab-pagados" class="tab-pane-tickets active">
                @php
                    $ticketsPagados = $tickets->where('tipoTicket.nombre', 'Pagado');
                @endphp

                @if($ticketsPagados->isEmpty())
                    <div class="empty-state-tickets">
                        <div class="empty-icon-tickets">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h3 class="empty-title-tickets">No hay tickets pagados</h3>
                        <p class="empty-desc-tickets">No se encontraron tickets pagados para esta fecha</p>
                    </div>
                @else
                    <div class="tickets-grid">
                        @foreach($ticketsPagados as $ticket)
                            <div class="ticket-card ticket-pagado">
                                <div class="ticket-card-header">
                                    <div class="ticket-badge">
                                        <i class="bi bi-receipt"></i>
                                        TICKET #{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div class="ticket-status status-pagado">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Pagado
                                    </div>
                                </div>

                                <div class="ticket-card-body">
                                    <div class="ticket-info-group">
                                        <div class="info-row">
                                            <i class="bi bi-person-fill"></i>
                                            <span class="info-label">Cliente:</span>
                                            <span class="info-value">{{ $ticket->pedido->cliente->nombre ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <i class="bi bi-telephone-fill"></i>
                                            <span class="info-label">Teléfono:</span>
                                            <span class="info-value">{{ $ticket->pedido->cliente->telefono ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <i class="bi bi-person-badge"></i>
                                            <span class="info-label">Empleado:</span>
                                            <span class="info-value">{{ $ticket->pedido->usuario->nombre ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <i class="bi bi-clock-fill"></i>
                                            <span class="info-label">Hora:</span>
                                            <span class="info-value">{{ \Carbon\Carbon::parse($ticket->fecha_ticket)->format('H:i A') }}</span>
                                        </div>
                                    </div>

                                    <div class="ticket-divider"></div>

                                    {{-- INFORMACIÓN DE PAGO --}}
                                    <div class="ticket-pago-info">
                                        <div class="pago-header">
                                            <i class="bi bi-credit-card-fill"></i>
                                            Información de Pago
                                        </div>
                                        <div class="pago-details">
                                            <div class="pago-row">
                                                <span class="pago-label">
                                                    <i class="bi bi-wallet2"></i>
                                                    Método:
                                                </span>
                                                <span class="pago-value pago-metodo">
                                                    @if($ticket->tipoPago)
                                                        @if(strtolower($ticket->tipoPago->nombre) === 'efectivo')
                                                            <i class="bi bi-cash-coin me-1"></i>
                                                        @elseif(strtolower($ticket->tipoPago->nombre) === 'tarjeta')
                                                            <i class="bi bi-credit-card me-1"></i>
                                                        @elseif(strtolower($ticket->tipoPago->nombre) === 'transferencia')
                                                            <i class="bi bi-bank me-1"></i>
                                                        @endif
                                                    @endif
                                                    {{ $ticket->tipoPago->nombre ?? 'N/A' }}
                                                </span>
                                            </div>
                                            
                                            @if($ticket->tipoPago && strtolower($ticket->tipoPago->nombre) === 'efectivo')
                                                @php
                                                    // Convertir a float para asegurar comparación correcta
                                                    $montoRecibido = floatval($ticket->monto_recibido ?? 0);
                                                    $total = floatval($ticket->total);
                                                    $cambio = floatval($ticket->cambio ?? 0);
                                                @endphp
                                                
                                                <div class="pago-row pago-efectivo-row">
                                                    <span class="pago-label">
                                                        <i class="bi bi-cash"></i>
                                                        Recibido:
                                                    </span>
                                                    <span class="pago-value pago-recibido">
                                                        ${{ number_format($montoRecibido > 0 ? $montoRecibido : $total, 2) }}
                                                    </span>
                                                </div>
                                                
                                                <div class="pago-row pago-cambio-row">
                                                    <span class="pago-label">
                                                        <i class="bi bi-arrow-return-left"></i>
                                                        Cambio:
                                                    </span>
                                                    <span class="pago-value pago-cambio">
                                                        ${{ number_format($cambio, 2) }}
                                                    </span>
                                                </div>
                                                
                                                @if($cambio == 0 && $montoRecibido == $total)
                                                    <div class="pago-row" style="background: #d1fae5; border-left: 3px solid var(--color-success);">
                                                        <span class="pago-label">
                                                            <i class="bi bi-check-circle"></i>
                                                            Nota:
                                                        </span>
                                                        <span class="pago-value" style="color: var(--color-success);">Pago Exacto</span>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <div class="ticket-divider"></div>

                                    @if(auth()->check() && auth()->user()->id_rol === 5)
                                        <div class="ticket-imprimir-btn-container">
                                            <button type="button" 
                                                    class="btn-imprimir-ticket" 
                                                    data-ticket-id="{{ $ticket->id }}"
                                                    title="Imprimir ticket en térmica">
                                                <i class="bi bi-printer-fill"></i>
                                                <span>Imprimir Ticket</span>
                                            </button>
                                        </div>
                                    @endif

                                    <div class="ticket-productos">
                                        <div class="productos-header">
                                            <i class="bi bi-basket3"></i>
                                            Productos ({{ $ticket->pedido->detalles->count() }})
                                        </div>
                                        <div class="productos-list">
                                            @foreach($ticket->pedido->detalles->take(3) as $item)
                                                <div class="producto-item">
                                                    <span class="producto-name">{{ $item->comida->nombre ?? 'N/A' }}</span>
                                                    <span class="producto-price">${{ number_format($item->subtotal, 2) }}</span>
                                                </div>
                                            @endforeach
                                            @if($ticket->pedido->detalles->count() > 3)
                                                <div class="producto-item producto-more">
                                                    <span>+ {{ $ticket->pedido->detalles->count() - 3 }} productos más</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="ticket-card-footer">
                                    <div class="ticket-total">
                                        <span class="total-label">Total:</span>
                                        <span class="total-value">${{ number_format($ticket->total, 2) }}</span>
                                    </div>
                                </div>
                                

                                {{-- BOTÓN IMPRIMIR SOLO PARA CAJA --}}
                                                               {{-- BOTÓN IMPRIMIR SOLO PARA CAJA --}}
                                {{-- BOTÓN IMPRIMIR SOLO PARA CAJA (por ID) --}}

                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tickets Cancelados -->
            <div id="tab-cancelados" class="tab-pane-tickets">
                @php
                    $ticketsCancelados = $tickets->where('tipoTicket.nombre', 'Cancelado');
                @endphp

                @if($ticketsCancelados->isEmpty())
                    <div class="empty-state-tickets">
                        <div class="empty-icon-tickets">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h3 class="empty-title-tickets">No hay tickets cancelados</h3>
                        <p class="empty-desc-tickets">No se encontraron tickets cancelados para esta fecha</p>
                    </div>
                @else
                    <div class="tickets-grid">
                        @foreach($ticketsCancelados as $ticket)
                            <div class="ticket-card ticket-cancelado">
                                <div class="ticket-card-header">
                                    <div class="ticket-badge">
                                        <i class="bi bi-receipt"></i>
                                        TICKET #{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div class="ticket-status status-cancelado">
                                        <i class="bi bi-x-circle-fill"></i>
                                        Cancelado
                                    </div>
                                </div>

                                <div class="ticket-card-body">
                                    <div class="ticket-info-group">
                                        <div class="info-row">
                                            <i class="bi bi-person-fill"></i>
                                            <span class="info-label">Cliente:</span>
                                            <span class="info-value">{{ $ticket->pedido->cliente->nombre ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <i class="bi bi-telephone-fill"></i>
                                            <span class="info-label">Teléfono:</span>
                                            <span class="info-value">{{ $ticket->pedido->cliente->telefono ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <i class="bi bi-person-badge"></i>
                                            <span class="info-label">Empleado:</span>
                                            <span class="info-value">{{ $ticket->pedido->usuario->nombre ?? 'N/A' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <i class="bi bi-clock-fill"></i>
                                            <span class="info-label">Hora:</span>
                                            <span class="info-value">{{ \Carbon\Carbon::parse($ticket->fecha_ticket)->format('H:i A') }}</span>
                                        </div>
                                    </div>

                                    <div class="ticket-divider"></div>

                                    <div class="ticket-productos">
                                        <div class="productos-header">
                                            <i class="bi bi-basket3"></i>
                                            Productos ({{ $ticket->pedido->detalles->count() }})
                                        </div>
                                        <div class="productos-list">
                                            @foreach($ticket->pedido->detalles->take(3) as $item)
                                                <div class="producto-item">
                                                    <span class="producto-name">{{ $item->comida->nombre ?? 'N/A' }}</span>
                                                    <span class="producto-price">${{ number_format($item->subtotal, 2) }}</span>
                                                </div>
                                            @endforeach
                                            @if($ticket->pedido->detalles->count() > 3)
                                                <div class="producto-item producto-more">
                                                    <span>+ {{ $ticket->pedido->detalles->count() - 3 }} productos más</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="ticket-card-footer ticket-footer-cancelado">
                                    <div class="ticket-total">
                                        <span class="total-label">Total:</span>
                                        <span class="total-value">${{ number_format($ticket->total, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Pedidos Cancelados -->
            <div id="tab-pedidos-cancelados" class="tab-pane-tickets">
                @if($pedidosCancelados->isEmpty())
                    <div class="empty-state-tickets">
                        <div class="empty-icon-tickets">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h3 class="empty-title-tickets">No hay pedidos cancelados</h3>
                        <p class="empty-desc-tickets">No se encontraron pedidos cancelados para esta fecha</p>
                    </div>
                @else
                    <div class="tickets-grid">
                        @foreach($pedidosCancelados as $pedido)
                            <div class="pedido-cancelado-card">
                                <div class="pedido-cancelado-header">
                                    <div class="pedido-badge">
                                        <i class="bi bi-basket"></i>
                                        PEDIDO #{{ str_pad($pedido->id, 4, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div class="pedido-status">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Cancelado
                                    </div>
                                </div>

                                <div class="pedido-cancelado-body">
                                    <div class="info-row">
                                        <i class="bi bi-person-fill"></i>
                                        <span class="info-label">Cliente:</span>
                                        <span class="info-value">{{ $pedido->cliente->nombre ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-row">
                                        <i class="bi bi-person-badge"></i>
                                        <span class="info-label">Empleado:</span>
                                        <span class="info-value">{{ $pedido->usuario->nombre ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-row">
                                        <i class="bi bi-calendar"></i>
                                        <span class="info-label">Fecha:</span>
                                        <span class="info-value">{{ \Carbon\Carbon::parse($pedido->fecha_pedido)->format('d/m/Y H:i') }}</span>
                                    </div>
                                    
                                    @if($pedido->notas)
                                        <div class="pedido-notas">
                                            <i class="bi bi-chat-left-text"></i>
                                            <span>{{ $pedido->notas }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>
</div>

<style>
/* ==================== VARIABLES ==================== */
:root {
    --color-bg: #f8fafc;
    --color-card: #ffffff;
    --color-primary: #3b82f6;
    --color-success: #10b981;
    --color-warning: #f59e0b;
    --color-danger: #ef4444;
    --color-text: #1e293b;
    --color-text-secondary: #64748b;
    --color-text-muted: #94a3b8;
    --color-border: #e2e8f0;
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
}

/* ==================== CONTENEDOR ==================== */
.tickets-container {
    background: var(--color-bg);
    min-height: 100vh;
}

/* ==================== HEADER ==================== */
.tickets-header {
    background: var(--color-card);
    padding: 2rem 0;
    border-bottom: 1px solid var(--color-border);
    margin-bottom: 2rem;
}

.tickets-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.tickets-title i {
    color: var(--color-primary);
}

.tickets-subtitle {
    color: var(--color-text-secondary);
    margin: 0.5rem 0 0 0;
}

.date-badge-tickets {
    background: linear-gradient(135deg, var(--color-primary) 0%, #2563eb 100%);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    box-shadow: var(--shadow-md);
}

/* ==================== STATS GRID ==================== */
.stats-tickets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-ticket-card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.stat-ticket-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}

.stat-ticket-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-success::before { background: var(--color-success); }
.stat-danger::before { background: var(--color-danger); }
.stat-warning::before { background: var(--color-warning); }
.stat-primary::before { background: var(--color-primary); }

.stat-ticket-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-success .stat-ticket-icon {
    background: #d1fae5;
    color: var(--color-success);
}

.stat-danger .stat-ticket-icon {
    background: #fee2e2;
    color: var(--color-danger);
}

.stat-warning .stat-ticket-icon {
    background: #fef3c7;
    color: var(--color-warning);
}

.stat-primary .stat-ticket-icon {
    background: #dbeafe;
    color: var(--color-primary);
}

.stat-ticket-content {
    flex: 1;
}

.stat-ticket-label {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.stat-ticket-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-ticket-info {
    font-size: 0.8125rem;
    color: var(--color-text-muted);
}

/* ==================== TABS ==================== */
.tickets-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    background: var(--color-card);
    padding: 0.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid var(--color-border);
    flex-wrap: wrap;
}

.tab-btn {
    flex: 1;
    min-width: 200px;
    background: transparent;
    border: none;
    padding: 1rem 1.5rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.tab-btn:hover {
    background: var(--color-bg);
    color: var(--color-text);
}

.tab-btn.active {
    background: linear-gradient(135deg, var(--color-primary) 0%, #2563eb 100%);
    color: white;
}

.tab-count {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.5rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    min-width: 24px;
    text-align: center;
}

.tab-btn:not(.active) .tab-count {
    background: var(--color-border);
    color: var(--color-text);
}

/* ==================== TAB CONTENT ==================== */
.tab-content-tickets {
    position: relative;
}

.tab-pane-tickets {
    display: none;
    animation: fadeIn 0.3s ease;
}

.tab-pane-tickets.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ==================== TICKETS GRID ==================== */
.tickets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

/* ==================== TICKET CARD ==================== */
.ticket-card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all 0.3s;
}

.ticket-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}

.ticket-pagado {
    border-left: 4px solid var(--color-success);
}

.ticket-cancelado {
    border-left: 4px solid var(--color-danger);
}

.ticket-card-header {
    background: var(--color-bg);
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--color-border);
}

.ticket-badge {
    font-weight: 700;
    font-size: 0.875rem;
    color: var(--color-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ticket-status {
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.status-pagado {
    background: #d1fae5;
    color: #065f46;
}

.status-cancelado {
    background: #fee2e2;
    color: #991b1b;
}

.ticket-card-body {
    padding: 1.25rem;
}

.ticket-info-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-row {
    display: grid;
    grid-template-columns: 24px auto 1fr;
    gap: 0.5rem;
    align-items: center;
    font-size: 0.875rem;
}

.info-row i {
    color: var(--color-primary);
    font-size: 1rem;
}

.info-label {
    font-weight: 600;
    color: var(--color-text-secondary);
}

.info-value {
    color: var(--color-text);
    text-align: right;
}

.ticket-divider {
    height: 1px;
    background: var(--color-border);
    margin: 1rem 0;
}

.ticket-pago-info {
    background: var(--color-bg);
    border-radius: var(--radius-sm);
    padding: 1rem;
    margin-bottom: 1rem;
}

.pago-header {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pago-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.pago-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
    padding: 0.5rem;
    background: var(--color-card);
    border-radius: 6px;
}

.pago-label {
    color: var(--color-text);
    flex: 1;
}

.pago-value {
    font-weight: 700;
    color: var(--color-success);
}

.pago-efectivo-row {
    background: #dbeafe !important;
    border-left: 3px solid var(--color-primary);
}

.pago-recibido {
    color: var(--color-primary) !important;
}

.pago-cambio-row {
    background: #fef3c7 !important;
    border-left: 3px solid var(--color-warning);
}

.pago-cambio {
    color: var(--color-warning) !important;
    font-size: 1rem;
}

.ticket-productos {
    background: var(--color-bg);
    border-radius: var(--radius-sm);
    padding: 1rem;
}

.productos-header {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.productos-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.producto-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
    padding: 0.5rem;
    background: var(--color-card);
    border-radius: 6px;
}

.producto-name {
    color: var(--color-text);
    flex: 1;
}

.producto-price {
    font-weight: 700;
    color: var(--color-success);
}

.producto-more {
    color: var(--color-text-muted);
    justify-content: center;
    font-style: italic;
}

.ticket-card-footer {
    background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%);
    padding: 1rem 1.25rem;
}

.ticket-footer-cancelado {
    background: linear-gradient(135deg, var(--color-danger) 0%, #dc2626 100%);
}

.ticket-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    color: white;
    font-weight: 600;
    font-size: 1rem;
}

.total-value {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
}

/* ==================== PEDIDO CANCELADO CARD ==================== */
.pedido-cancelado-card {
    background: var(--color-card);
    border: 2px solid var(--color-warning);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all 0.3s;
}

.pedido-cancelado-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}

.pedido-cancelado-header {
    background: linear-gradient(135deg, var(--color-warning) 0%, #d97706 100%);
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pedido-badge {
    font-weight: 700;
    font-size: 0.875rem;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pedido-status {
    background: rgba(255, 255, 255, 0.25);
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.pedido-cancelado-body {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.pedido-notas {
    background: var(--color-bg);
    padding: 0.75rem;
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--color-warning);
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
    margin-top: 0.5rem;
}

.pedido-notas i {
    color: var(--color-warning);
    margin-top: 0.125rem;
}

/* ==================== EMPTY STATE ==================== */
.empty-state-tickets {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon-tickets {
    font-size: 5rem;
    color: var(--color-text-muted);
    margin-bottom: 1.5rem;
}

.empty-title-tickets {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.empty-desc-tickets {
    color: var(--color-text-secondary);
}
/* ==================== BOTÓN IMPRIMIR TICKET ==================== */
.ticket-imprimir-btn-container {
    padding: 0.75rem 1.25rem;
    background: var(--color-bg);
    border-top: 1px solid var(--color-border);
}

.btn-imprimir-ticket {
    width: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.875rem 1.25rem;
    border: none;
    border-radius: var(--radius-sm);
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    font-size: 0.9375rem;
    transition: all 0.3s;
}

.btn-imprimir-ticket:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-imprimir-ticket:active {
    transform: translateY(0);
}

.btn-imprimir-ticket:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-imprimir-ticket i {
    font-size: 1.125rem;
}

.btn-imprimir-ticket.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
/* ==================== RESPONSIVE ==================== */
@media (max-width: 768px) {
    .tickets-title {
        font-size: 1.5rem;
    }

    .tickets-grid {
        grid-template-columns: 1fr;
    }

    .stats-tickets-grid {
        grid-template-columns: 1fr;
    }

    .tickets-tabs {
        flex-direction: column;
    }

    .tab-btn {
        min-width: 100%;
    }
}
</style>

<script>
function cambiarTab(tab) {
    // Remover active de todos los tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.tab-pane-tickets').forEach(pane => {
        pane.classList.remove('active');
    });

    // Agregar active al tab seleccionado
    event.target.closest('.tab-btn').classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}
// ==================== IMPRESIÓN DESDE EMPLEADOS TICKETS ====================
document.querySelectorAll('.btn-imprimir-ticket').forEach(btn => {
    btn.addEventListener('click', function() {
        const ticketId = this.dataset.ticketId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Mostrar loading
        const originalHTML = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-hourglass-split"></i><span>Imprimiendo...</span>';
        
        // Enviar solicitud de impresión
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
                // Cambiar a verde temporalmente
                this.classList.add('success');
                this.innerHTML = '<i class="bi bi-check-circle-fill"></i><span>¡Impreso!</span>';
                
                // Volver al estado normal después de 2 segundos
                setTimeout(() => {
                    this.disabled = false;
                    this.classList.remove('success');
                    this.innerHTML = originalHTML;
                }, 2000);
            } else {
                alert('❌ Error: ' + data.message);
                this.disabled = false;
                this.innerHTML = originalHTML;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error al conectar con la impresora.\nVerifica que esté encendida y conectada.');
            this.disabled = false;
            this.innerHTML = originalHTML;
        });
    });
});
</script>

@endsection
