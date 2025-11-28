@extends('layouts.app')

@section('title', 'Tickets Completados')

@section('content')
@php
    use Illuminate\Support\Facades\Request;
    use Carbon\Carbon;
    use App\Models\Ticket;
    use App\Models\Pedido;

    // Fecha seleccionada o actual
    $fechaSeleccionada = Request::get('fecha', Carbon::now()->format('Y-m-d'));

    // Tickets (Pagado o Cancelado) - ORDENADOS DEL MÁS RECIENTE AL ANTIGUO
    $tickets = Ticket::with('pedido.cliente', 'pedido.usuario', 'tipoPago', 'tipoTicket', 'pedido.detalles.comida')
        ->whereHas('tipoTicket', fn($q) => $q->whereIn('nombre', ['Pagado', 'Cancelado']))
        ->whereDate('fecha_ticket', $fechaSeleccionada)
        ->orderBy('fecha_ticket', 'desc') // ✅ MÁS RECIENTE PRIMERO
        ->get();

    // Pedidos tipo "Cancelado" - ORDENADOS DEL MÁS RECIENTE AL ANTIGUO
    $pedidosCancelados = Pedido::with('cliente','usuario','tipoPedido','detalles.comida')
        ->whereHas('tipoPedido', fn($q) => $q->where('nombre', 'Cancelado'))
        ->whereDate('fecha_pedido', $fechaSeleccionada)
        ->orderBy('fecha_pedido', 'desc') // ✅ MÁS RECIENTE PRIMERO
        ->get();

    // Estadísticas
    $ticketsPagados = $tickets->where('tipoTicket.nombre', 'Pagado');
    $ticketsCancelados = $tickets->where('tipoTicket.nombre', 'Cancelado');
    $totalIngresos = $ticketsPagados->sum('total');
@endphp

<div class="admin-tickets-container">
    
    <!-- Header -->
    <div class="admin-tickets-header">
        <div class="container-fluid">
            <div class="header-content-admin">
                <div>
                    <h1 class="admin-tickets-title">
                        <i class="bi bi-receipt-cutoff"></i>
                        Gestión de Tickets
                    </h1>
                    <p class="admin-tickets-subtitle">Administración completa de tickets y pedidos</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">

        <!-- Filtro de Fecha -->
        <div class="filtro-fecha-card">
            <form method="GET" class="filtro-form">
                <div class="filtro-icon">
                    <i class="bi bi-calendar-range"></i>
                </div>
                <div class="filtro-content">
                    <label for="fecha" class="filtro-label">Seleccionar Fecha:</label>
                    <input type="date" 
                           id="fecha" 
                           name="fecha" 
                           class="filtro-input"
                           value="{{ $fechaSeleccionada }}"
                           max="{{ Carbon::now()->format('Y-m-d') }}">
                </div>
                <button type="submit" class="btn-filtro">
                    <i class="bi bi-search"></i>
                    Consultar
                </button>
            </form>
        </div>

        <!-- Stats Dashboard -->
        <div class="stats-dashboard-grid">
            <div class="stat-dashboard-card stat-success">
                <div class="stat-dashboard-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-dashboard-content">
                    <div class="stat-dashboard-label">Tickets Pagados</div>
                    <div class="stat-dashboard-value">{{ $ticketsPagados->count() }}</div>
                    <div class="stat-dashboard-footer">
                        <i class="bi bi-cash-stack"></i>
                        <span>${{ number_format($totalIngresos, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="stat-dashboard-card stat-danger">
                <div class="stat-dashboard-icon">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-dashboard-content">
                    <div class="stat-dashboard-label">Tickets Cancelados</div>
                    <div class="stat-dashboard-value">{{ $ticketsCancelados->count() }}</div>
                    <div class="stat-dashboard-footer">
                        <i class="bi bi-receipt"></i>
                        <span>Anulados</span>
                    </div>
                </div>
            </div>

            <div class="stat-dashboard-card stat-warning">
                <div class="stat-dashboard-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="stat-dashboard-content">
                    <div class="stat-dashboard-label">Pedidos Cancelados</div>
                    <div class="stat-dashboard-value">{{ $pedidosCancelados->count() }}</div>
                    <div class="stat-dashboard-footer">
                        <i class="bi bi-basket"></i>
                        <span>Sin procesar</span>
                    </div>
                </div>
            </div>

            <div class="stat-dashboard-card stat-primary">
                <div class="stat-dashboard-icon">
                    <i class="bi bi-calendar-day"></i>
                </div>
                <div class="stat-dashboard-content">
                    <div class="stat-dashboard-label">Fecha Consultada</div>
                    <div class="stat-dashboard-date">{{ Carbon::parse($fechaSeleccionada)->locale('es')->isoFormat('D MMM') }}</div>
                    <div class="stat-dashboard-footer">
                        <i class="bi bi-clock"></i>
                        <span>{{ Carbon::parse($fechaSeleccionada)->locale('es')->isoFormat('YYYY') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="admin-tabs-nav">
            <button class="admin-tab-btn active" onclick="cambiarTabAdmin('pagados')">
                <i class="bi bi-check-circle-fill"></i>
                <span>Tickets Pagados</span>
                <span class="admin-tab-badge">{{ $ticketsPagados->count() }}</span>
            </button>
            <button class="admin-tab-btn" onclick="cambiarTabAdmin('cancelados')">
                <i class="bi bi-x-circle-fill"></i>
                <span>Tickets Cancelados</span>
                <span class="admin-tab-badge">{{ $ticketsCancelados->count() }}</span>
            </button>
            <button class="admin-tab-btn" onclick="cambiarTabAdmin('pedidos-cancelados')">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Pedidos Cancelados</span>
                <span class="admin-tab-badge">{{ $pedidosCancelados->count() }}</span>
            </button>
        </div>

        <!-- Tab Content -->
        <div class="admin-tabs-content">

            <!-- Tickets Pagados -->
            <div id="admin-tab-pagados" class="admin-tab-pane active">
                @if($ticketsPagados->isEmpty())
                    <div class="empty-state-admin">
                        <div class="empty-icon-admin">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h3 class="empty-title-admin">No hay tickets pagados</h3>
                        <p class="empty-desc-admin">No se encontraron tickets pagados para el {{ Carbon::parse($fechaSeleccionada)->format('d/m/Y') }}</p>
                    </div>
                @else
                    <div class="tickets-admin-grid">
                        @foreach($ticketsPagados as $ticket)
                            <div class="ticket-admin-card ticket-pagado-card">
                                <!-- Header -->
                                <div class="ticket-admin-header">
                                    <div class="ticket-admin-number">
                                        <i class="bi bi-receipt"></i>
                                        <span>TICKET #{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    <div class="ticket-admin-status status-pagado">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Pagado
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="ticket-admin-body">
                                    <div class="ticket-admin-info-grid">
                                        <div class="info-item-admin">
                                            <i class="bi bi-person-fill"></i>
                                            <div>
                                                <small>Cliente</small>
                                                <strong>{{ $ticket->pedido->cliente->nombre ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                        <div class="info-item-admin">
                                            <i class="bi bi-telephone-fill"></i>
                                            <div>
                                                <small>Teléfono</small>
                                                <strong>{{ $ticket->pedido->cliente->telefono ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                        <div class="info-item-admin">
                                            <i class="bi bi-person-badge"></i>
                                            <div>
                                                <small>Empleado</small>
                                                <strong>{{ $ticket->pedido->usuario->nombre ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                        <div class="info-item-admin">
                                            <i class="bi bi-clock-fill"></i>
                                            <div>
                                                <small>Hora</small>
                                                <strong>{{ Carbon::parse($ticket->fecha_ticket)->format('h:i A') }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ticket-admin-divider"></div>

                                    {{-- INFORMACIÓN DE PAGO --}}
                                    <div class="ticket-admin-pago-info">
                                        <div class="pago-header-admin">
                                            <i class="bi bi-credit-card-fill"></i>
                                            <span>Información de Pago</span>
                                        </div>
                                        <div class="pago-details-admin">
                                            <div class="pago-row-admin">
                                                <span class="pago-label-admin">
                                                    <i class="bi bi-wallet2"></i>
                                                    Método:
                                                </span>
                                                <span class="pago-value-admin pago-metodo-admin">
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
                                                    $montoRecibido = floatval($ticket->monto_recibido ?? 0);
                                                    $total = floatval($ticket->total);
                                                    $cambio = floatval($ticket->cambio ?? 0);
                                                @endphp
                                                
                                                <div class="pago-row-admin pago-efectivo-row-admin">
                                                    <span class="pago-label-admin">
                                                        <i class="bi bi-cash"></i>
                                                        Recibido:
                                                    </span>
                                                    <span class="pago-value-admin pago-recibido-admin">
                                                        ${{ number_format($montoRecibido > 0 ? $montoRecibido : $total, 2) }}
                                                    </span>
                                                </div>
                                                
                                                <div class="pago-row-admin pago-cambio-row-admin">
                                                    <span class="pago-label-admin">
                                                        <i class="bi bi-arrow-return-left"></i>
                                                        Cambio:
                                                    </span>
                                                    <span class="pago-value-admin pago-cambio-admin">
                                                        ${{ number_format($cambio, 2) }}
                                                    </span>
                                                </div>
                                                
                                                @if($cambio == 0 && $montoRecibido == $total)
                                                    <div class="pago-row-admin pago-exacto-row-admin">
                                                        <span class="pago-label-admin">
                                                            <i class="bi bi-check-circle"></i>
                                                            Nota:
                                                        </span>
                                                        <span class="pago-value-admin pago-exacto-admin">Pago Exacto</span>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <div class="ticket-admin-divider"></div>

                                    <!-- BOTÓN DE IMPRIMIR -->
<div class="ticket-admin-imprimir-btn">
    <button type="button" 
            class="btn-imprimir-admin" 
            data-ticket-id="{{ $ticket->id }}"
            title="Imprimir ticket en térmica">
        <i class="bi bi-printer-fill"></i>
        <span>Imprimir Ticket</span>
    </button>
</div>


                                    <!-- Productos -->
                                    <div class="ticket-admin-productos">
                                        <div class="productos-header-admin">
                                            <i class="bi bi-basket3-fill"></i>
                                            <span>Productos ({{ $ticket->pedido->detalles->count() }})</span>
                                        </div>
                                        <div class="productos-table-admin">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th>Precio</th>
                                                        <th>Cant.</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($ticket->pedido->detalles as $item)
                                                        <tr>
                                                            <td>{{ $item->comida->nombre ?? 'N/A' }}</td>
                                                            <td>${{ number_format($item->precio_unitario, 2) }}</td>
                                                            <td>{{ $item->cantidad }}</td>
                                                            <td class="text-success fw-bold">${{ number_format($item->subtotal, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="ticket-admin-footer footer-pagado">
                                    <span class="footer-label">TOTAL</span>
                                    <span class="footer-total">${{ number_format($ticket->total, 2) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tickets Cancelados -->
            <div id="admin-tab-cancelados" class="admin-tab-pane">
                @if($ticketsCancelados->isEmpty())
                    <div class="empty-state-admin">
                        <div class="empty-icon-admin">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h3 class="empty-title-admin">No hay tickets cancelados</h3>
                        <p class="empty-desc-admin">No se encontraron tickets cancelados para el {{ Carbon::parse($fechaSeleccionada)->format('d/m/Y') }}</p>
                    </div>
                @else
                    <div class="tickets-admin-grid">
                        @foreach($ticketsCancelados as $ticket)
                            <div class="ticket-admin-card ticket-cancelado-card">
                                <!-- Header -->
                                <div class="ticket-admin-header">
                                    <div class="ticket-admin-number">
                                        <i class="bi bi-receipt"></i>
                                        <span>TICKET #{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    <div class="ticket-admin-status status-cancelado">
                                        <i class="bi bi-x-circle-fill"></i>
                                        Cancelado
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="ticket-admin-body">
                                    <div class="ticket-admin-info-grid">
                                        <div class="info-item-admin">
                                            <i class="bi bi-person-fill"></i>
                                            <div>
                                                <small>Cliente</small>
                                                <strong>{{ $ticket->pedido->cliente->nombre ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                        <div class="info-item-admin">
                                            <i class="bi bi-telephone-fill"></i>
                                            <div>
                                                <small>Teléfono</small>
                                                <strong>{{ $ticket->pedido->cliente->telefono ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                        <div class="info-item-admin">
                                            <i class="bi bi-person-badge"></i>
                                            <div>
                                                <small>Empleado</small>
                                                <strong>{{ $ticket->pedido->usuario->nombre ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                        <div class="info-item-admin">
                                            <i class="bi bi-clock-fill"></i>
                                            <div>
                                                <small>Hora</small>
                                                <strong>{{ Carbon::parse($ticket->fecha_ticket)->format('h:i A') }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ticket-admin-divider"></div>

                                    <!-- Productos -->
                                    <div class="ticket-admin-productos">
                                        <div class="productos-header-admin">
                                            <i class="bi bi-basket3-fill"></i>
                                            <span>Productos ({{ $ticket->pedido->detalles->count() }})</span>
                                        </div>
                                        <div class="productos-table-admin">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th>Precio</th>
                                                        <th>Cant.</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($ticket->pedido->detalles as $item)
                                                        <tr>
                                                            <td>{{ $item->comida->nombre ?? 'N/A' }}</td>
                                                            <td>${{ number_format($item->precio_unitario, 2) }}</td>
                                                            <td>{{ $item->cantidad }}</td>
                                                            <td class="text-danger fw-bold">${{ number_format($item->subtotal, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="ticket-admin-footer footer-cancelado">
                                    <span class="footer-label">TOTAL</span>
                                    <span class="footer-total">${{ number_format($ticket->total, 2) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Pedidos Cancelados -->
            <div id="admin-tab-pedidos-cancelados" class="admin-tab-pane">
                @if($pedidosCancelados->isEmpty())
                    <div class="empty-state-admin">
                        <div class="empty-icon-admin">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h3 class="empty-title-admin">No hay pedidos cancelados</h3>
                        <p class="empty-desc-admin">No se encontraron pedidos cancelados para el {{ Carbon::parse($fechaSeleccionada)->format('d/m/Y') }}</p>
                    </div>
                @else
                    <div class="tickets-admin-grid">
                        @foreach($pedidosCancelados as $pedido)
                            <div class="pedido-cancelado-admin-card">
                                <!-- Header -->
                                <div class="pedido-cancelado-header">
                                    <div class="pedido-cancelado-number">
                                        <i class="bi bi-basket"></i>
                                        <span>PEDIDO #{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    <div class="pedido-cancelado-status">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Cancelado
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="pedido-cancelado-body">
                                    <div class="pedido-cancelado-info-grid">
                                        <div class="info-item-admin">
                                            <i class="bi bi-person-fill"></i>
                                            <div>
                                                <small>Cliente</small>
                                                <strong>{{ $pedido->cliente->nombre ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                        <div class="info-item-admin">
                                            <i class="bi bi-person-badge"></i>
                                            <div>
                                                <small>Empleado</small>
                                                <strong>{{ $pedido->usuario->nombre ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                        <div class="info-item-admin">
                                            <i class="bi bi-calendar"></i>
                                            <div>
                                                <small>Fecha</small>
                                                <strong>{{ Carbon::parse($pedido->fecha_pedido)->format('d/m/Y H:i') }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    @if($pedido->notas)
                                        <div class="pedido-notas-admin">
                                            <i class="bi bi-chat-left-text"></i>
                                            <span>{{ $pedido->notas }}</span>
                                        </div>
                                    @endif

                                    <div class="ticket-admin-divider"></div>

                                    <!-- Productos -->
                                    <div class="ticket-admin-productos">
                                        <div class="productos-header-admin">
                                            <i class="bi bi-basket3-fill"></i>
                                            <span>Productos ({{ $pedido->detalles->count() }})</span>
                                        </div>
                                        <div class="productos-table-admin">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th>Precio</th>
                                                        <th>Cant.</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($pedido->detalles as $item)
                                                        <tr>
                                                            <td>{{ $item->comida->nombre ?? 'N/A' }}</td>
                                                            <td>${{ number_format($item->precio_unitario, 2) }}</td>
                                                            <td>{{ $item->cantidad }}</td>
                                                            <td class="text-warning fw-bold">${{ number_format($item->subtotal, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
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
.admin-tickets-container {
    background: var(--color-bg);
    min-height: 100vh;
}

/* ==================== HEADER ==================== */
.admin-tickets-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2.5rem 0;
    margin-bottom: 2rem;
}

.header-content-admin {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.admin-tickets-title {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.admin-tickets-subtitle {
    color: rgba(255, 255, 255, 0.9);
    margin: 0.5rem 0 0 0;
    font-size: 1.125rem;
}

/* ==================== FILTRO ==================== */
.filtro-fecha-card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-md);
}

.filtro-form {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.filtro-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--color-primary) 0%, #2563eb 100%);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.filtro-content {
    flex: 1;
    min-width: 250px;
}

.filtro-label {
    display: block;
    font-weight: 600;
    color: var(--color-text-secondary);
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.filtro-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--color-border);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    color: var(--color-text);
    transition: all 0.2s;
}

.filtro-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-filtro {
    background: linear-gradient(135deg, var(--color-primary) 0%, #2563eb 100%);
    color: white;
    padding: 0.875rem 2rem;
    border: none;
    border-radius: var(--radius-md);
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.btn-filtro:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
}

/* ==================== STATS DASHBOARD ==================== */
.stats-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-dashboard-card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.stat-dashboard-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}

.stat-success::before { background: var(--color-success); }
.stat-danger::before { background: var(--color-danger); }
.stat-warning::before { background: var(--color-warning); }
.stat-primary::before { background: var(--color-primary); }

.stat-dashboard-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-dashboard-icon {
    width: 64px;
    height: 64px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
}

.stat-success .stat-dashboard-icon {
    background: #d1fae5;
    color: var(--color-success);
}

.stat-danger .stat-dashboard-icon {
    background: #fee2e2;
    color: var(--color-danger);
}

.stat-warning .stat-dashboard-icon {
    background: #fef3c7;
    color: var(--color-warning);
}

.stat-primary .stat-dashboard-icon {
    background: #dbeafe;
    color: var(--color-primary);
}

.stat-dashboard-content {
    flex: 1;
}

.stat-dashboard-label {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.stat-dashboard-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-dashboard-date {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-dashboard-footer {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: var(--color-text-muted);
    font-weight: 600;
}

/* ==================== TABS ==================== */
.admin-tabs-nav {
    display: flex;
    gap: 0.5rem;
    background: var(--color-card);
    padding: 0.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid var(--color-border);
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.admin-tab-btn {
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

.admin-tab-btn:hover {
    background: var(--color-bg);
    color: var(--color-text);
}

.admin-tab-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.admin-tab-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.5rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    min-width: 24px;
}

.admin-tab-btn:not(.active) .admin-tab-badge {
    background: var(--color-border);
    color: var(--color-text);
}

/* ==================== TAB CONTENT ==================== */
.admin-tabs-content {
    position: relative;
}

.admin-tab-pane {
    display: none;
    animation: fadeInAdmin 0.3s ease;
}

.admin-tab-pane.active {
    display: block;
}

@keyframes fadeInAdmin {
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
.tickets-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

/* ==================== TICKET CARD ==================== */
.ticket-admin-card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all 0.3s;
}

.ticket-admin-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
}

.ticket-pagado-card {
    border-left: 4px solid var(--color-success);
}

.ticket-cancelado-card {
    border-left: 4px solid var(--color-danger);
}

.ticket-admin-header {
    background: var(--color-bg);
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--color-border);
}

.ticket-admin-number {
    font-weight: 700;
    font-size: 0.9375rem;
    color: var(--color-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ticket-admin-status {
    padding: 0.375rem 0.875rem;
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

.ticket-admin-body {
    padding: 1.25rem;
}

.ticket-admin-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.info-item-admin {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.info-item-admin i {
    color: var(--color-primary);
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.info-item-admin small {
    display: block;
    font-size: 0.75rem;
    color: var(--color-text-secondary);
    margin-bottom: 0.125rem;
}

.info-item-admin strong {
    display: block;
    font-size: 0.875rem;
    color: var(--color-text);
}

.ticket-admin-divider {
    height: 1px;
    background: var(--color-border);
    margin: 1.25rem 0;
}

.ticket-admin-productos {
    background: var(--color-bg);
    border-radius: var(--radius-sm);
    padding: 1rem;
}

.productos-header-admin {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.productos-table-admin {
    overflow-x: auto;
}

.productos-table-admin table {
    width: 100%;
    font-size: 0.8125rem;
    border-collapse: collapse;
}

.productos-table-admin thead {
    background: var(--color-card);
}

.productos-table-admin th {
    padding: 0.5rem;
    text-align: left;
    font-weight: 600;
    color: var(--color-text-secondary);
    border-bottom: 2px solid var(--color-border);
}

.productos-table-admin td {
    padding: 0.5rem;
    color: var(--color-text);
    border-bottom: 1px solid var(--color-border);
}

.productos-table-admin tbody tr:last-child td {
    border-bottom: none;
}

.ticket-admin-footer {
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.footer-pagado {
    background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%);
}

.footer-cancelado {
    background: linear-gradient(135deg, var(--color-danger) 0%, #dc2626 100%);
}

.footer-label {
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
}

.footer-total {
    color: white;
    font-weight: 700;
    font-size: 1.75rem;
}

/* ==================== PEDIDO CANCELADO ==================== */
.pedido-cancelado-admin-card {
    background: var(--color-card);
    border: 2px solid var(--color-warning);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all 0.3s;
}

.pedido-cancelado-admin-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
}

.pedido-cancelado-header {
    background: linear-gradient(135deg, var(--color-warning) 0%, #d97706 100%);
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pedido-cancelado-number {
    font-weight: 700;
    font-size: 0.9375rem;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pedido-cancelado-status {
    background: rgba(255, 255, 255, 0.25);
    padding: 0.375rem 0.875rem;
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
}

.pedido-cancelado-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.pedido-notas-admin {
    background: #fef3c7;
    padding: 1rem;
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--color-warning);
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.pedido-notas-admin i {
    color: var(--color-warning);
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.pedido-notas-admin span {
    flex: 1;
    color: var(--color-text);
    font-size: 0.875rem;
}

/* ==================== INFORMACIÓN DE PAGO (ADMIN) ==================== */
.ticket-admin-pago-info {
    background: var(--color-bg);
    border-radius: var(--radius-sm);
    padding: 1rem;
    margin-bottom: 1.25rem;
}

.pago-header-admin {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9375rem;
}

.pago-header-admin i {
    color: var(--color-primary);
    font-size: 1.125rem;
}

.pago-details-admin {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.pago-row-admin {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0.75rem;
    background: var(--color-card);
    border-radius: var(--radius-sm);
    border: 1px solid var(--color-border);
    transition: all 0.2s;
}

.pago-row-admin:hover {
    border-color: var(--color-primary);
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.1);
}

.pago-label-admin {
    font-size: 0.8125rem;
    color: var(--color-text-secondary);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.pago-label-admin i {
    font-size: 1rem;
    color: var(--color-primary);
}

.pago-value-admin {
    font-size: 0.875rem;
    color: var(--color-text);
    font-weight: 600;
}

.pago-metodo-admin {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.pago-metodo-admin i {
    color: var(--color-success);
}

.pago-efectivo-row-admin {
    background: #dbeafe !important;
    border-color: var(--color-primary) !important;
    border-left: 3px solid var(--color-primary);
}

.pago-efectivo-row-admin:hover {
    background: #bfdbfe !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
}

.pago-efectivo-row-admin .pago-label-admin {
    color: #1e40af;
}

.pago-recibido-admin {
    color: var(--color-primary) !important;
    font-size: 0.9375rem !important;
}

.pago-cambio-row-admin {
    background: #fef3c7 !important;
    border-color: var(--color-warning) !important;
    border-left: 3px solid var(--color-warning);
}

.pago-cambio-row-admin:hover {
    background: #fde68a !important;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
}

.pago-cambio-row-admin .pago-label-admin {
    color: #92400e;
}

.pago-cambio-admin {
    color: var(--color-warning) !important;
    font-size: 1rem !important;
}

.pago-exacto-row-admin {
    background: #d1fae5 !important;
    border-color: var(--color-success) !important;
    border-left: 3px solid var(--color-success);
}

.pago-exacto-row-admin:hover {
    background: #a7f3d0 !important;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
}

.pago-exacto-row-admin .pago-label-admin {
    color: #065f46;
}

.pago-exacto-row-admin .pago-label-admin i {
    color: var(--color-success);
}

.pago-exacto-admin {
    color: var(--color-success) !important;
    font-weight: 700;
}
/* ==================== BOTÓN IMPRIMIR ADMIN ==================== */
.ticket-admin-imprimir-btn {
    padding: 0.75rem 0;
}

.btn-imprimir-admin {
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

.btn-imprimir-admin:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-imprimir-admin:active {
    transform: translateY(0);
}

.btn-imprimir-admin:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-imprimir-admin i {
    font-size: 1.125rem;
}
/* Responsive para información de pago */
@media (max-width: 768px) {
    .pago-row-admin {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .pago-value-admin {
        font-size: 1rem;
        align-self: flex-end;
    }
}
</style>

<script>
function cambiarTabAdmin(tab) {
    // Remover active de todos
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.admin-tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });

    // Agregar active al seleccionado
    event.target.closest('.admin-tab-btn').classList.add('active');
    document.getElementById('admin-tab-' + tab).classList.add('active');
}

// ==================== IMPRESIÓN DESDE ADMIN TICKETS ====================
document.querySelectorAll('.btn-imprimir-admin').forEach(btn => {
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
                this.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                this.innerHTML = '<i class="bi bi-check-circle-fill"></i><span>¡Impreso!</span>';
                
                // Volver al estado normal después de 2 segundos
                setTimeout(() => {
                    this.disabled = false;
                    this.style.background = '';
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
