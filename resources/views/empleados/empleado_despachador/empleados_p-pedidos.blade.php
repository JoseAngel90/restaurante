@extends('layouts.app')

@section('title', 'Pedidos Pendientes')

@section('content')
@php
use Carbon\Carbon;
use App\Models\DisponibilidadComidaDia;

$hoy = Carbon::today()->format('Y-m-d');

$comidasHoy = DisponibilidadComidaDia::with('comida.tipoComida')
    ->where('fecha', $hoy)
    ->whereHas('comida', fn($q) => $q->where('disponible', '>', 0))
    ->get();

$categorias = $comidasHoy->groupBy(fn($disp) => strtoupper($disp->comida->tipoComida->descripcion ?? 'SIN CATEGORÍA'));

$pedidosPendientes = App\Models\Pedido::whereHas('tipoPedido', fn($q) => $q->whereIn('nombre', ['Pendiente','Walking','A domicilio']))
    ->whereDoesntHave('tickets')
    ->with('cliente','tipoPedido','detalles.comida.tipoComida')
    ->orderBy('id', 'asc')
    ->get();

$pedidosEnCaja = App\Models\Pedido::whereHas('tickets', function($q) {
        $q->whereHas('tipoTicket', fn($query) => $query->where('nombre', 'Pendiente'));
    })
    ->where('id_usuario', auth()->id())
    ->with('cliente', 'tickets.tipoTicket', 'detalles.comida.tipoComida', 'tipoPedido')
    ->orderBy('fecha_entrega', 'desc')
    ->get();
@endphp

<div class="despachador-container">
    {{-- CONTADOR FLOTANTE AUTO-REFRESH --}}
    @if(auth()->check() && auth()->user()->id_rol == 6)
    <div class="auto-refresh-indicator-floating" id="autoRefreshIndicator">
        <i class="bi bi-arrow-clockwise"></i>
        <span id="contadorRefresh">60s</span>
    </div>
    @endif

    {{-- HEADER --}}
    <div class="main-header">
        <div class="header-left">
            <div class="header-icon">
                <i class="bi bi-clipboard-check"></i>
            </div>
            <div>
                <h1>Panel de Despacho</h1>
                <p>Gestiona pedidos y ventas directas</p>
            </div>
        </div>
        <button class="btn-nueva-venta" data-bs-toggle="modal" data-bs-target="#ventaDirectaModal">
            <i class="bi bi-plus-circle"></i>
            Nueva Venta
        </button>
    </div>

    {{-- ESTADÍSTICAS --}}
    <div class="stats-grid">
        <div class="stat-card stat-pendientes">
            <div class="stat-icon">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Pedidos Pendientes</span>
                <span class="stat-value">{{ $pedidosPendientes->count() }}</span>
            </div>
        </div>
        <div class="stat-card stat-caja">
            <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">En Caja</span>
                <span class="stat-value">{{ $pedidosEnCaja->count() }}</span>
            </div>
        </div>
        <div class="stat-card stat-disponibles">
            <div class="stat-icon">
                <i class="bi bi-basket"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Comidas Disponibles</span>
                <span class="stat-value">{{ $comidasHoy->sum(fn($d) => $d->comida->disponible) }}</span>
            </div>
        </div>
    </div>

    {{-- COMIDAS DISPONIBLES --}}
    <div class="section-card comidas-section">
        <div class="section-header">
            <h2>
                <i class="bi bi-basket3"></i>
                Menú Disponible
            </h2>
            <button class="btn-toggle" onclick="toggleSection('comidas')">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="section-content" id="comidas-content">
            <div class="comidas-grid">
                @foreach($categorias as $cat => $comidas)
                    <div class="categoria-card">
                        <div class="categoria-header">{{ $cat }}</div>
                        <div class="categoria-items">
                            @foreach($comidas as $disp)
                                <div class="comida-chip" title="{{ $disp->comida->nombre }}">
                                    <span class="comida-abrev">{{ $disp->comida->abreviatura_op }}</span>
                                    <span class="comida-stock">{{ $disp->comida->disponible }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- BUSCADOR Y CONTADOR --}}
    <div class="search-section">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" 
                   id="buscadorNombre" 
                   placeholder="Buscar cliente por nombre..."
                   autocomplete="off">
            <button id="limpiarBusqueda" class="btn-clear">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="pedidos-counter">
            <span id="contadorPedidos">{{ $pedidosPendientes->count() }} pedidos</span>
        </div>
    </div>

    {{-- TABLA DE PEDIDOS PENDIENTES --}}
    <div class="section-card pedidos-section">
        <div class="section-header">
            <h2>
                <i class="bi bi-bag"></i>
                Pedidos por Entregar
            </h2>
        </div>

        @if($pedidosPendientes->count() > 0)
            <div class="table-wrapper">
                <table class="table-modern" id="tablaPedidos">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Cliente</th>
                            <th class="text-center">Teléfono</th>
                            <th class="text-center">Estado</th>
                            <th>Detalles</th>
                            <th>Notas</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedidosPendientes as $pedido)
                            @php
                                $paquetes = [];
                                $currentPaquete = 0;
                                foreach($pedido->detalles as $item) {
                                    $tipo = strtoupper($item->comida->tipoComida->descripcion ?? 'SIN TIPO');
                                    if($tipo === 'PLATO FUERTE') $currentPaquete++;
                                    $paquetes[$currentPaquete][] = $item;
                                }
                            @endphp
                            <tr class="pedido-row" data-cliente-nombre="{{ strtolower($pedido->cliente->nombre ?? '') }}">
                                <td class="text-center">
                                    <span class="pedido-numero">#{{ $pedido->id }}</span>
                                </td>
                                <td>
                                    <div class="cliente-info-cell">
                                        <div class="cliente-avatar">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                        <div>
                                            <strong class="cliente-nombre">{{ $pedido->cliente->nombre ?? 'N/A' }}</strong>
                                            <small class="cliente-fecha">{{ Carbon::parse($pedido->fecha_pedido)->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge-telefono">{{ $pedido->cliente->telefono ?? 'N/A' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge-estado estado-{{ strtolower($pedido->tipoPedido->nombre ?? 'pendiente') }}">
                                        {{ $pedido->tipoPedido->nombre ?? 'Pendiente' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="detalles-cell">
                                        @foreach($paquetes as $numPaquete => $items)
                                            <div class="paquete-box">
                                                <span class="paquete-label">P{{ $numPaquete }}</span>
                                                <div class="items-list">
                                                    @foreach($items as $item)
                                                        <span class="item-badge" title="{{ $item->comida->nombre ?? 'N/A' }}">
                                                            {{ $item->comida->abreviatura_op ?? '?' }}
                                                            @if($item->cantidad > 1)
                                                                <sup class="item-qty">×{{ $item->cantidad }}</sup>
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <div class="notas-cell">
                                        @if($pedido->notas)
                                            <span class="nota-text" title="{{ $pedido->notas }}">
                                                {{ Str::limit($pedido->notas, 40) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="actions-cell">
                                        <a href="{{ route('pedido.entregar', $pedido->id) }}" 
                                           class="btn-action btn-entregar"
                                           onclick="return confirm('¿Confirmar entrega del pedido #{{ $pedido->id }}?')">
                                            <i class="bi bi-check-circle"></i>
                                            Entregar
                                        </a>
                                        <button class="btn-action btn-cancelar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#cancelarPedidoModal{{ $pedido->id }}">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- MODAL CANCELAR --}}
                            <div class="modal fade" id="cancelarPedidoModal{{ $pedido->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                Cancelar Pedido #{{ $pedido->id }}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-center py-4">
                                            <i class="bi bi-exclamation-octagon text-danger" style="font-size: 3rem;"></i>
                                            <h5 class="mt-3">¿Estás seguro?</h5>
                                            <p class="text-muted">Esta acción no se puede deshacer.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                                            <form action="{{ route('pedido.cancelar', $pedido->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-trash"></i> Sí, cancelar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="sinResultados" class="empty-state" style="display: none;">
                <i class="bi bi-search"></i>
                <h4>No se encontraron resultados</h4>
                <p>Intenta con otro nombre de cliente</p>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>No hay pedidos pendientes</h4>
                <p>Todos los pedidos han sido procesados</p>
            </div>
        @endif
    </div>

    {{-- PEDIDOS EN CAJA --}}
    <div class="section-card caja-section">
        <div class="section-header">
            <h2>
                <i class="bi bi-cash-coin"></i>
                Mis Pedidos en Caja
            </h2>
        </div>

        @if($pedidosEnCaja->count() > 0)
            <div class="table-wrapper">
                <table class="table-modern table-caja">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Ticket</th>
                            <th class="text-center">Tipo</th>
                            <th>Cliente</th>
                            <th class="text-center">Teléfono</th>
                            <th>Detalles</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Hora</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedidosEnCaja as $pedido)
                            @php
                                $ticket = $pedido->tickets->first();
                                $paquetes = [];
                                $currentPaquete = 0;
                                foreach($pedido->detalles as $item) {
                                    $tipo = strtoupper($item->comida->tipoComida->descripcion ?? 'SIN TIPO');
                                    if($tipo === 'PLATO FUERTE') $currentPaquete++;
                                    $paquetes[$currentPaquete][] = $item;
                                }
                                $esVentaDirecta = str_contains($pedido->notas ?? '', 'Venta directa') || 
                                                ($pedido->tipoPedido && $pedido->tipoPedido->nombre === 'Walking' && 
                                                 $pedido->cliente->telefono === 'walking');
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <span class="pedido-numero">#{{ $pedido->id }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge-ticket">#{{ $ticket->id ?? 'N/A' }}</span>
                                </td>
                                <td class="text-center">
                                    @if($esVentaDirecta)
                                        <span class="badge-tipo tipo-venta">
                                            <i class="bi bi-lightning"></i>
                                            Venta
                                        </span>
                                    @else
                                        <span class="badge-tipo tipo-pedido">
                                            <i class="bi bi-box"></i>
                                            {{ $pedido->tipoPedido->nombre ?? 'Pedido' }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="cliente-info-cell">
                                        <div class="cliente-avatar small">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <div>
                                            <strong>{{ $pedido->cliente->nombre ?? 'N/A' }}</strong>
                                            <small>{{ Carbon::parse($pedido->fecha_pedido)->format('d/m H:i') }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge-telefono">{{ $pedido->cliente->telefono ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div class="detalles-cell">
                                        @foreach($paquetes as $numPaquete => $items)
                                            <div class="paquete-box">
                                                <span class="paquete-label">P{{ $numPaquete }}</span>
                                                <div class="items-list">
                                                    @foreach($items as $item)
                                                        <span class="item-badge">
                                                            {{ $item->comida->abreviatura_op ?? '?' }}
                                                            @if($item->cantidad > 1)
                                                                <sup>×{{ $item->cantidad }}</sup>
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="total-value">${{ number_format($ticket->total ?? 0, 2) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="hora-value">{{ Carbon::parse($pedido->fecha_entrega)->format('H:i') }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge-estado estado-caja">
                                        <i class="bi bi-clock"></i>
                                        En Caja
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-cash-coin"></i>
                <h4>No tienes pedidos en caja</h4>
                <p>Los pedidos que entregues aparecerán aquí</p>
            </div>
        @endif
    </div>
</div>

{{-- MODAL VENTA DIRECTA (mantener el mismo) --}}
<div class="modal fade" id="ventaDirectaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('venta.directa') }}" method="POST" id="formVentaDirecta">
                @csrf
                
                <div class="modal-header bg-gradient-success text-white border-0">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="bi bi-cash-coin me-2 fs-3"></i>
                        <div>
                            <div>Nueva Venta Directa</div>
                            <small class="opacity-75">Venta inmediata de comidas</small>
                        </div>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    {{-- Información del cliente --}}
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="bi bi-person me-2"></i>Datos del Cliente
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre del Cliente</label>
                                <input type="text" name="cliente_nombre" class="form-control" 
                                       placeholder="Nombre del cliente" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Teléfono</label>
                                <input type="text" name="cliente_telefono" class="form-control" 
                                       placeholder="Número de teléfono" value="walking" readonly>
                            </div>
                        </div>
                    </div>

                    {{-- Buscador de comidas --}}
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="bi bi-search me-2"></i>Búsqueda Rápida por Abreviatura
                        </h6>
                        <div class="row g-3 align-items-end position-relative">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Buscar Comida</label>
                                <input type="text" id="buscarComidaVenta" class="form-control" 
                                       placeholder="Escribe la abreviatura (ej: PLL, ARZ, etc.)">
                                <div id="resultadosComidaVenta" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Cantidad</label>
                                <input type="number" id="cantidadBusqueda" class="form-control" 
                                       min="1" value="1" placeholder="1">
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-primary w-100" id="btnAgregarComida">
                                    <i class="bi bi-plus-circle me-1"></i>Agregar
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Selección de comidas --}}
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="bi bi-basket me-2"></i>Seleccionar Comidas por Categoría
                        </h6>
                        
                        <div class="row g-3">
                            @foreach($categorias as $categoria => $comidas)
                                <div class="col-md-6 col-lg-4">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white fw-bold text-center">
                                            {{ $categoria }}
                                        </div>
                                        <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                            @foreach($comidas as $disp)
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded bg-light comida-item" 
                                                     data-id="{{ $disp->comida->id }}"
                                                     data-abrev="{{ $disp->comida->abreviatura_op }}"
                                                     data-nombre="{{ $disp->comida->nombre }}"
                                                     data-disponible="{{ $disp->comida->disponible }}"
                                                     data-precio="{{ $disp->comida->precio ?? 0 }}">
                                                    <div class="flex-grow-1">
                                                        <small class="fw-bold text-primary">{{ $disp->comida->abreviatura_op }}</small>
                                                        <br>
                                                        <small class="text-muted">{{ Str::limit($disp->comida->nombre, 20) }}</small>
                                                        <br>
                                                        <small class="badge bg-success">Disp: {{ $disp->comida->disponible }}</small>
                                                    </div>
                                                    <div class="text-center">
                                                        <input type="number" 
                                                               name="comidas[{{ $disp->comida->id }}]" 
                                                               class="form-control form-control-sm text-center cantidad-venta" 
                                                               style="width: 60px;"
                                                               min="0" 
                                                               max="{{ $disp->comida->disponible }}" 
                                                               value="0"
                                                               data-disponible="{{ $disp->comida->disponible }}"
                                                               data-nombre="{{ $disp->comida->nombre }}"
                                                               data-precio="{{ $disp->comida->precio ?? 0 }}">
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Resumen de la venta --}}
                    <div class="mb-3">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="bi bi-receipt me-2"></i>Resumen de Venta
                        </h6>
                        <div class="bg-light rounded p-3">
                            <div id="resumenVenta">
                                <p class="text-muted text-center">Selecciona comidas para ver el resumen</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <strong class="fs-5">Total:</strong>
                                <strong class="fs-4 text-success" id="totalVenta">$0.00</strong>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success btn-lg px-4" id="btnProcesarVenta" disabled>
                        <i class="bi bi-check2-circle me-2"></i>Procesar Venta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================= ESTILOS ================= --}}
<style>
:root {
    --primary: #3b82f6;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #06b6d4;
    --dark: #1f2937;
    --light: #f9fafb;
    --border: #e5e7eb;
    --shadow: 0 1px 3px rgba(0,0,0,0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f3f4f6;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ==================== CONTAINER ==================== */
.despachador-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 2rem;
}

/* ==================== HEADER ==================== */
.main-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    background: white;
    padding: 1.5rem 2rem;
    border-radius: 16px;
    box-shadow: var(--shadow);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary) 0%, #2563eb 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.75rem;
}

.main-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0;
}

.main-header p {
    color: #6b7280;
    margin: 0;
}

.btn-nueva-venta {
    background: var(--success);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: var(--shadow-lg);
}

.btn-nueva-venta:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}

/* ==================== AUTO REFRESH INDICATOR FLOATING ==================== */
.auto-refresh-indicator-floating {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 50px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 700;
    font-size: 1.125rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    z-index: 9999;
    animation: pulse 2s infinite;
    cursor: default;
    transition: all 0.3s ease;
}

.auto-refresh-indicator-floating:hover {
    transform: scale(1.05);
    box-shadow: 0 15px 40px rgba(0,0,0,0.4);
}

.auto-refresh-indicator-floating i {
    font-size: 1.5rem;
    animation: rotate 3s linear infinite;
}

.auto-refresh-indicator-floating.warning {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    animation: shake 0.5s infinite, pulse 2s infinite;
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.5);
}

.auto-refresh-indicator-floating span {
    font-size: 1.25rem;
    min-width: 45px;
    text-align: center;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-5px);
    }
    75% {
        transform: translateX(5px);
    }
}

/* ==================== STATS ==================== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow);
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.stat-pendientes .stat-icon {
    background: #fef3c7;
    color: var(--warning);
}

.stat-caja .stat-icon {
    background: #d1fae5;
    color: var(--success);
}

.stat-disponibles .stat-icon {
    background: #dbeafe;
    color: var(--primary);
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark);
}

/* ==================== SECTION CARD ==================== */
.section-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--light);
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.btn-toggle {
    background: var(--light);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-toggle:hover {
    background: var(--border);
}

/* ==================== COMIDAS GRID ==================== */
.comidas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.categoria-card {
    background: var(--light);
    border-radius: 12px;
    overflow: hidden;
}

.categoria-header {
    background: var(--primary);
    color: white;
    padding: 0.75rem;
    font-weight: 600;
    text-align: center;
    font-size: 0.875rem;
}

.categoria-items {
    padding: 0.75rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.comida-chip {
    background: white;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 0.375rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
}

.comida-abrev {
    font-weight: 700;
    color: var(--primary);
}

.comida-stock {
   background: #212529;   /* morado ejemplo */
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 999px;
    font-weight: 600;
}

/* ==================== SEARCH ==================== */
.search-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    gap: 1rem;
}

.search-box {
    flex: 1;
    max-width: 500px;
    display: flex;
    align-items: center;
    background: white;
    border-radius: 12px;
    padding: 0.75rem 1.25rem;
    gap: 0.75rem;
    box-shadow: var(--shadow);
}

.search-box i {
    color: #6b7280;
    font-size: 1.25rem;
}

.search-box input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 1rem;
}

.btn-clear {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn-clear:hover {
    background: var(--light);
    color: var(--danger);
}

.pedidos-counter {
    background: white;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    color: var(--dark);
    box-shadow: var(--shadow);
}

/* ==================== TABLE MODERN ==================== */
.table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
}

.table-modern {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.875rem;
}

.table-modern thead {
    background: linear-gradient(135deg, var(--primary) 0%, #2563eb 100%);
    color: white;
}

.table-modern thead th {
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    border: none;
}

.table-modern thead th:first-child {
    border-top-left-radius: 12px;
}

.table-modern thead th:last-child {
    border-top-right-radius: 12px;
}

.table-modern tbody tr {
    background: white;
    transition: all 0.2s;
}

.table-modern tbody tr:hover {
    background: #f0f9ff;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.table-modern tbody tr.highlight {
    background: #fef3c7 !important;
    box-shadow: inset 4px 0 0 var(--warning);
}

.table-modern tbody td {
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.table-modern tbody tr:last-child td:first-child {
    border-bottom-left-radius: 12px;
}

.table-modern tbody tr:last-child td:last-child {
    border-bottom-right-radius: 12px;
}

/* Table Caja */
.table-caja thead {
    background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
}

/* ==================== TABLE CELLS ==================== */
.pedido-numero {
    background: var(--primary);
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.875rem;
    display: inline-block;
}

.cliente-info-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.cliente-avatar {
    width: 40px;
    height: 40px;
    background: var(--light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--primary);
    flex-shrink: 0;
}

.cliente-avatar.small {
    width: 32px;
    height: 32px;
    font-size: 1.25rem;
}

.cliente-nombre {
    display: block;
    font-weight: 600;
    color: var(--dark);
    font-size: 0.875rem;
}

.cliente-fecha {
    display: block;
    color: #6b7280;
    font-size: 0.75rem;
}

.badge-telefono {
    background: #dbeafe;
    color: #1e40af;
    padding: 0.375rem 0.75rem;
    border-radius: 999px;
    font-weight: 600;
    font-size: 0.75rem;
    display: inline-block;
}

.badge-estado {
    padding: 0.375rem 0.875rem;
    border-radius: 999px;
    font-weight: 600;
    font-size: 0.75rem;
    display: inline-block;
}

.estado-pendiente {
    background: #fef3c7;
    color: #92400e;
}

.estado-walking {
    background: #dbeafe;
    color: #1e40af;
}

.estado-a.domicilio {
    background: #fce7f3;
    color: #9f1239;
}

.estado-caja {
    background: #fef3c7;
    color: #92400e;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    justify-content: center;
}

.badge-ticket {
    background: var(--success);
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.75rem;
    display: inline-block;
}

.badge-tipo {
    padding: 0.375rem 0.75rem;
    border-radius: 999px;
    font-weight: 600;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.tipo-venta {
    background: #cffafe;
    color: #0e7490;
}

.tipo-pedido {
    background: #e0e7ff;
    color: #3730a3;
}

/* ==================== DETALLES CELL ==================== */
.detalles-cell {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-width: 300px;
}

.paquete-box {
    background: var(--light);
    border-radius: 8px;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.paquete-label {
    background: var(--primary);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.7rem;
    flex-shrink: 0;
}

.items-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}

.item-badge {
    background: white;
    border: 1px solid var(--border);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--dark);
}

.item-qty {
    background: var(--danger);
    color: white;
    padding: 0.125rem 0.375rem;
    border-radius: 999px;
    margin-left: 0.25rem;
}

/* ==================== NOTAS CELL ==================== */
.notas-cell {
    max-width: 200px;
}

.nota-text {
    color: #6b7280;
    font-size: 0.8125rem;
    display: block;
}

/* ==================== ACTIONS CELL ==================== */
.actions-cell {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-action {
    border: none;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    text-decoration: none;
}

.btn-entregar {
    background: var(--success);
    color: white;
}

.btn-entregar:hover {
    background: #059669;
    transform: translateY(-2px);
    color: white;
}

.btn-cancelar {
    background: var(--danger);
    color: white;
    padding: 0.625rem;
}

.btn-cancelar:hover {
    background: #dc2626;
    transform: translateY(-2px);
}

/* ==================== TOTAL & HORA ==================== */
.total-value {
    font-weight: 700;
    color: var(--success);
    font-size: 1rem;
}

.hora-value {
    color: #6b7280;
    font-size: 0.875rem;
}

/* ==================== EMPTY STATE ==================== */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h4 {
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #9ca3af;
    margin: 0;
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 768px) {
    .despachador-container {
        padding: 1rem;
    }

    .main-header {
        flex-direction: column;
        align-items: stretch;
    }

    .auto-refresh-indicator-floating {
        bottom: 15px;
        right: 15px;
        padding: 0.75rem 1.25rem;
        font-size: 1rem;
    }

    .auto-refresh-indicator-floating i {
        font-size: 1.25rem;
    }

    .auto-refresh-indicator-floating span {
        font-size: 1rem;
        min-width: 40px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .search-section {
        flex-direction: column;
    }

    .search-box {
        max-width: none;
    }

    .table-wrapper {
        font-size: 0.75rem;
    }

    .cliente-info-cell {
        flex-direction: column;
        align-items: flex-start;
    }

    .actions-cell {
        flex-direction: column;
    }
}

/* ==================== ANIMATIONS ==================== */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.table-modern tbody tr {
    animation: fadeIn 0.3s ease-out;
}

/* Toggle Section */
window.toggleSection = function(section) {
    const content = document.getElementById(`${section}-content`);
    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(0deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(-90deg)';
    }
};
</style>

@if(auth()->check() && auth()->user()->id_rol == 6)
<!-- MODAL DE ADVERTENCIA AUTO REFRESH -->
<div class="modal fade" id="modalAutoRefresh" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Atención
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-clock-history text-warning" style="font-size:3rem;"></i>
                <h5 class="mt-3">La página se actualizará pronto</h5>
                <p class="text-muted">
                    Si tienes algo que guardar o revisar, hazlo ahora.
                </p>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ==================== BUSCADOR DE PEDIDOS POR NOMBRE ====================
    const buscadorInput = document.getElementById('buscadorNombre');
    const limpiarBtn = document.getElementById('limpiarBusqueda');
    const sinResultados = document.getElementById('sinResultados');
    const contadorPedidos = document.getElementById('contadorPedidos');
    const tablaPedidos = document.getElementById('tablaPedidos');
    const pedidoRows = document.querySelectorAll('.pedido-row');
    const totalPedidos = pedidoRows.length;

    // Mostrar contador inicial
    contadorPedidos.textContent = `(${totalPedidos} pedidos)`;

    // Función de búsqueda por nombre
    function buscarPorNombre() {
        const termino = buscadorInput.value.toLowerCase().trim();
        let pedidosVisibles = 0;

        pedidoRows.forEach(row => {
            const clienteNombre = row.dataset.clienteNombre;
            const coincide = !termino || clienteNombre.includes(termino);

            if (coincide) {
                row.style.display = '';
                pedidosVisibles++;
                
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
        contadorPedidos.textContent = termino 
            ? `(${pedidosVisibles} de ${totalPedidos} pedidos)` 
            : `(${totalPedidos} pedidos)`;

        // Mostrar/ocultar mensaje "Sin resultados"
        if (pedidosVisibles === 0) {
            tablaPedidos.style.display = 'none';
            sinResultados.style.display = 'block';
        } else {
            tablaPedidos.style.display = 'table';
            sinResultados.style.display = 'none';
        }
    }

    // Evento de búsqueda en tiempo real
    if (buscadorInput) {
        buscadorInput.addEventListener('input', buscarPorNombre);
    }

    // Limpiar búsqueda
    if (limpiarBtn) {
        limpiarBtn.addEventListener('click', function() {
            buscadorInput.value = '';
            buscarPorNombre();
            buscadorInput.focus();
        });
    }

    // Enter para buscar
    if (buscadorInput) {
        buscadorInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarPorNombre();
            }
        });
    }

    // ==================== FUNCIONALIDAD EXISTENTE DEL MODAL ====================
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Variables para el modal de venta
    const cantidadInputs = document.querySelectorAll('.cantidad-venta');
    const resumenDiv = document.getElementById('resumenVenta');
    const totalSpan = document.getElementById('totalVenta');
    const btnProcesar = document.getElementById('btnProcesarVenta');
    
    // Variables para el buscador
    const buscarInput = document.getElementById('buscarComidaVenta');
    const resultadosDiv = document.getElementById('resultadosComidaVenta');
    const cantidadBusqueda = document.getElementById('cantidadBusqueda');
    const btnAgregar = document.getElementById('btnAgregarComida');
    const comidasItems = document.querySelectorAll('.comida-item');

    // Crear array de comidas para búsqueda
    const comidasDisponibles = Array.from(comidasItems).map(item => ({
        id: item.dataset.id,
        abrev: item.dataset.abrev.toLowerCase(),
        nombre: item.dataset.nombre,
        disponible: parseInt(item.dataset.disponible),
        precio: parseFloat(item.dataset.precio),
        elemento: item
    }));

    // Funcionalidad del buscador
    buscarInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        resultadosDiv.innerHTML = '';
        
        if (!query) return;

        const filtradas = comidasDisponibles.filter(comida =>
            comida.abrev.includes(query) ||
            comida.nombre.toLowerCase().includes(query)
        );

        filtradas.forEach(comida => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            
            item.innerHTML = `
                <div>
                    <strong class="text-primary">${comida.abrev.toUpperCase()}</strong> - ${comida.nombre}
                    <br>
                    <small class="text-muted">Disponible: ${comida.disponible} | Precio: $${comida.precio.toFixed(2)}</small>
                </div>
                <i class="bi bi-plus-circle text-success"></i>
            `;

            item.addEventListener('click', () => {
                buscarInput.value = comida.abrev.toUpperCase();
                resultadosDiv.innerHTML = '';
                
                // Resaltar la comida en las categorías
                highlightComida(comida.elemento);
            });

            resultadosDiv.appendChild(item);
        });
    });

    // Función para resaltar comida encontrada
    function highlightComida(elemento) {
        // Remover highlights previos
        comidasItems.forEach(item => item.classList.remove('highlight'));
        
        // Agregar highlight al elemento encontrado
        elemento.classList.add('highlight');
        
        // Scroll hacia el elemento
        elemento.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        // Remover highlight después de 3 segundos
        setTimeout(() => {
            elemento.classList.remove('highlight');
        }, 3000);
    }

    // Agregar comida por búsqueda
    btnAgregar.addEventListener('click', function() {
        const abrev = buscarInput.value.trim().toLowerCase();
        const cantidad = parseInt(cantidadBusqueda.value) || 1;

        if (!abrev) {
            alert('Ingrese una abreviatura para buscar');
            buscarInput.focus();
            return;
        }

        const comida = comidasDisponibles.find(c => c.abrev === abrev);
        
        if (!comida) {
            alert('No se encontró ninguna comida con esa abreviatura');
            buscarInput.focus();
            return;
        }

        if (cantidad > comida.disponible) {
            alert(`Solo hay ${comida.disponible} unidades disponibles`);
            cantidadBusqueda.value = comida.disponible;
            return;
        }

        // Buscar el input correspondiente y actualizar su valor
        const inputComida = document.querySelector(`input[name="comidas[${comida.id}]"]`);
        if (inputComida) {
            const valorActual = parseInt(inputComida.value) || 0;
            const nuevoValor = valorActual + cantidad;
            
            if (nuevoValor > comida.disponible) {
                alert(`No se puede agregar esa cantidad. Máximo disponible: ${comida.disponible}`);
                return;
            }
            
            inputComida.value = nuevoValor;
            highlightComida(comida.elemento);
            actualizarResumen();
        }

        // Limpiar buscador
        buscarInput.value = '';
        cantidadBusqueda.value = 1;
        resultadosDiv.innerHTML = '';
    });

    // Permitir agregar con Enter
    buscarInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnAgregar.click();
        }
    });

    cantidadBusqueda.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnAgregar.click();
        }
    });

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!buscarInput.contains(e.target) && !resultadosDiv.contains(e.target)) {
            resultadosDiv.innerHTML = '';
        }
    });

    function actualizarResumen() {
        let items = [];
        let total = 0;
        let hasItems = false;

        cantidadInputs.forEach(input => {
            const cantidad = parseInt(input.value) || 0;
            if (cantidad > 0) {
                hasItems = true;
                const nombre = input.dataset.nombre;
                const precio = parseFloat(input.dataset.precio) || 0;
                const subtotal = cantidad * precio;
                total += subtotal;

                items.push({
                    nombre: nombre,
                    cantidad: cantidad,
                    precio: precio,
                    subtotal: subtotal
                });

                input.classList.add('selected');
            } else {
                input.classList.remove('selected');
            }
        });

        // Actualizar resumen
        if (hasItems) {
            let html = '';
            items.forEach(item => {
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>${item.nombre}</strong><br>
                            <small class="text-muted">${item.cantidad} x $${item.precio.toFixed(2)}</small>
                        </div>
                        <span class="fw-bold">$${item.subtotal.toFixed(2)}</span>
                    </div>
                `;
            });
            resumenDiv.innerHTML = html;
            btnProcesar.disabled = false;
        } else {
            resumenDiv.innerHTML = '<p class="text-muted text-center">Selecciona comidas para ver el resumen</p>';
            btnProcesar.disabled = true;
        }

        totalSpan.textContent = `$${total.toFixed(2)}`;
    }

    // Escuchar cambios en las cantidades
    cantidadInputs.forEach(input => {
        input.addEventListener('input', function() {
            const cantidad = parseInt(this.value) || 0;
            const disponible = parseInt(this.dataset.disponible);

            // Validar que no exceda el stock
            if (cantidad > disponible) {
                this.value = disponible;
                alert(`Solo hay ${disponible} unidades disponibles`);
            }

            // Validar que no sea negativo
            if (cantidad < 0) {
                this.value = 0;
            }

            actualizarResumen();
        });
    });

    // Validar formulario antes de enviar
    document.getElementById('formVentaDirecta').addEventListener('submit', function(e) {
        const hasItems = Array.from(cantidadInputs).some(input => parseInt(input.value) > 0);
        
        if (!hasItems) {
            e.preventDefault();
            alert('Debe seleccionar al menos una comida para procesar la venta');
            return false;
        }

        return confirm('¿Confirmar la venta directa?');
    });

    // Reiniciar modal al cerrarlo
    document.getElementById('ventaDirectaModal').addEventListener('hidden.bs.modal', function() {
        cantidadInputs.forEach(input => {
            input.value = 0;
            input.classList.remove('selected');
        });
        comidasItems.forEach(item => item.classList.remove('highlight'));
        actualizarResumen();
        buscarInput.value = '';
        cantidadBusqueda.value = 1;
        resultadosDiv.innerHTML = '';
        this.querySelector('form').reset();
    });

    @if(auth()->check() && auth()->user()->id_rol == 6)

    // ================= AUTO REFRESH DESPACHADOR =================
    let tiempoTotal = 60;        // 1 minuto
    let tiempoAdvertencia = 20;  // aviso 20s antes
    let tiempoRestante = tiempoTotal;

    const modalRefresh = new bootstrap.Modal(
        document.getElementById('modalAutoRefresh')
    );
    const contadorElement = document.getElementById('contadorRefresh');
    const indicadorElement = document.getElementById('autoRefreshIndicator');

    // Actualizar contador cada segundo
    const intervalo = setInterval(() => {
        tiempoRestante--;
        
        if (tiempoRestante > 0) {
            contadorElement.textContent = `${tiempoRestante}s`;
            
            // Cambiar a advertencia cuando quedan 20s o menos
            if (tiempoRestante <= tiempoAdvertencia) {
                indicadorElement.classList.add('warning');
            }
        } else {
            clearInterval(intervalo);
        }
    }, 1000);

    // Mostrar advertencia
    setTimeout(() => {
        modalRefresh.show();
    }, (tiempoTotal - tiempoAdvertencia) * 1000);

    // Recargar página
    setTimeout(() => {
        location.reload();
    }, tiempoTotal * 1000);

    console.log("⏳ Auto refresh activo para despachador - Tiempo total: " + tiempoTotal + "s");

@endif

});
</script>
@endpush
