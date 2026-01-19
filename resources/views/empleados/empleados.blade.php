@extends('layouts.app')

@section('title', 'Dashboard Empleado')

@section('content')
<div class="empleado-dashboard">
    
    <!-- Header -->
    <div class="dashboard-header-emp">
        <div class="container">
            <div class="welcome-section">
                <div class="welcome-avatar">
                    {{ strtoupper(substr(auth()->user()->nombre, 0, 2)) }}
                </div>
                <div>
                    <h1 class="welcome-title">¡Hola, {{ auth()->user()->nombre }}!</h1>
                    <p class="welcome-subtitle">Aquí está el resumen de tu actividad</p>
                </div>
            </div>
            <div class="date-badge">
                <i class="bi bi-calendar-event me-2"></i>
                {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
            </div>
        </div>
    </div>

    <div class="container py-5">

        <!-- Resumen rápido -->
        <div class="stats-grid">

            <!-- Pedidos pendientes -->
            <div class="stat-card stat-warning">
                <div class="stat-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Pedidos Pendientes</div>
                    <div class="stat-value">
                        {{ auth()->user()->pedidos()->whereHas('tipoPedido', fn($q) => $q->where('nombre','Pendiente'))->count() }}
                    </div>
                    <div class="stat-trend">
                        <i class="bi bi-arrow-up"></i>
                        <span>En proceso</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <div class="mini-chart chart-warning"></div>
                </div>
            </div>

            <!-- Pedidos entregados -->
            <div class="stat-card stat-success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Pedidos Entregados</div>
                    <div class="stat-value">
                        {{ auth()->user()->pedidos()->whereHas('tipoPedido', fn($q) => $q->where('nombre','Entregado'))->count() }}
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="bi bi-arrow-up"></i>
                        <span>Completados</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <div class="mini-chart chart-success"></div>
                </div>
            </div>

            <!-- Tickets Pagados -->
            <div class="stat-card stat-primary">
                <div class="stat-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Tickets Pagados</div>
                    <div class="stat-value">
                        {{ auth()->user()->pedidos()->with('tickets')->get()->pluck('tickets')->flatten()->where('tipoTicket.nombre','Pagado')->count() }}
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="bi bi-arrow-up"></i>
                        <span>Cobrados</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <div class="mini-chart chart-primary"></div>
                </div>
            </div>

            <!-- Tickets Cancelados -->
            <div class="stat-card stat-danger">
                <div class="stat-icon">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Tickets Cancelados</div>
                    <div class="stat-value">
                        {{ auth()->user()->pedidos()->with('tickets')->get()->pluck('tickets')->flatten()->where('tipoTicket.nombre','Cancelado')->count() }}
                    </div>
                    <div class="stat-trend trend-down">
                        <i class="bi bi-arrow-down"></i>
                        <span>Anulados</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <div class="mini-chart chart-danger"></div>
                </div>
            </div>

        </div>

        <!-- Gráfico de Progreso -->
        <div class="row g-4 mt-2">
            <div class="col-lg-8">
                <div class="progress-card">
                    <div class="progress-header">
                        <h5 class="progress-title">
                            <i class="bi bi-graph-up me-2"></i>
                            Progreso de Pedidos
                        </h5>
                    </div>
                    <div class="progress-body">
                        @php
                            $pendientes = auth()->user()->pedidos()->whereHas('tipoPedido', fn($q) => $q->where('nombre','Pendiente'))->count();
                            $entregados = auth()->user()->pedidos()->whereHas('tipoPedido', fn($q) => $q->where('nombre','Entregado'))->count();
                            $total = $pendientes + $entregados;
                            $porcentaje = $total > 0 ? ($entregados / $total) * 100 : 0;
                        @endphp
                        
                        <div class="progress-info">
                            <div class="progress-item">
                                <span class="progress-dot dot-warning"></span>
                                <span class="progress-label">Pendientes: <strong>{{ $pendientes }}</strong></span>
                            </div>
                            <div class="progress-item">
                                <span class="progress-dot dot-success"></span>
                                <span class="progress-label">Entregados: <strong>{{ $entregados }}</strong></span>
                            </div>
                            <div class="progress-percentage">{{ number_format($porcentaje, 1) }}%</div>
                        </div>

                        <div class="progress-bar-container">
                            <div class="progress-bar-custom">
                                <div class="progress-fill" style="width: {{ $porcentaje }}%"></div>
                            </div>
                        </div>

                        <div class="progress-stats">
                            <div class="progress-stat">
                                <div class="stat-number">{{ $total }}</div>
                                <div class="stat-text">Total</div>
                            </div>
                            <div class="progress-stat">
                                <div class="stat-number">{{ $entregados }}</div>
                                <div class="stat-text">Completados</div>
                            </div>
                            <div class="progress-stat">
                                <div class="stat-number">{{ $pendientes }}</div>
                                <div class="stat-text">Restantes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="summary-card">
                    <div class="summary-header">
                        <h5 class="summary-title">
                            <i class="bi bi-bar-chart-fill me-2"></i>
                            Resumen General
                        </h5>
                    </div>
                    <div class="summary-body">
                        @php
                            $ticketsPagados = auth()->user()->pedidos()->with('tickets')->get()->pluck('tickets')->flatten()->where('tipoTicket.nombre','Pagado')->count();
                            $ticketsCancelados = auth()->user()->pedidos()->with('tickets')->get()->pluck('tickets')->flatten()->where('tipoTicket.nombre','Cancelado')->count();
                            $totalTickets = $ticketsPagados + $ticketsCancelados;
                        @endphp

                        <div class="summary-item">
                            <div class="summary-icon summary-icon-primary">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-label">Total Tickets</div>
                                <div class="summary-value">{{ $totalTickets }}</div>
                            </div>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-item">
                            <div class="summary-icon summary-icon-success">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-label">Tasa de Éxito</div>
                                <div class="summary-value">
                                    {{ $total > 0 ? number_format(($entregados / $total) * 100, 1) : 0 }}%
                                </div>
                            </div>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-item">
                            <div class="summary-icon summary-icon-warning">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div class="summary-info">
                                <div class="summary-label">Eficiencia</div>
                                <div class="summary-value">
                                    {{ $totalTickets > 0 ? number_format(($ticketsPagados / $totalTickets) * 100, 1) : 0 }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
.empleado-dashboard {
    background: var(--color-bg);
    min-height: 100vh;
}

/* ==================== HEADER ==================== */
.dashboard-header-emp {
    background: var(--color-card);
    padding: 2rem 0;
    border-bottom: 1px solid var(--color-border);
    margin-bottom: 2rem;
}

.dashboard-header-emp .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.welcome-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.welcome-avatar {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    box-shadow: var(--shadow-md);
}

.welcome-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
}

.welcome-subtitle {
    color: var(--color-text-secondary);
    margin: 0.25rem 0 0 0;
}

.date-badge {
    background: var(--color-bg);
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius-md);
    color: var(--color-text-secondary);
    font-weight: 500;
}

/* ==================== STATS GRID ==================== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}

.stat-warning::before { background: var(--color-warning); }
.stat-success::before { background: var(--color-success); }
.stat-primary::before { background: var(--color-primary); }
.stat-danger::before { background: var(--color-danger); }

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-warning .stat-icon {
    background: #fef3c7;
    color: var(--color-warning);
}

.stat-success .stat-icon {
    background: #d1fae5;
    color: var(--color-success);
}

.stat-primary .stat-icon {
    background: #dbeafe;
    color: var(--color-primary);
}

.stat-danger .stat-icon {
    background: #fee2e2;
    color: var(--color-danger);
}

.stat-content {
    flex: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-trend {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
}

.stat-warning .stat-trend {
    background: #fef3c7;
    color: #92400e;
}

.stat-success .stat-trend {
    background: #d1fae5;
    color: #065f46;
}

.stat-primary .stat-trend {
    background: #dbeafe;
    color: #1e40af;
}

.stat-danger .stat-trend {
    background: #fee2e2;
    color: #991b1b;
}

.stat-chart {
    position: absolute;
    bottom: 0;
    right: 0;
    opacity: 0.1;
}

.mini-chart {
    width: 100px;
    height: 60px;
    background-size: contain;
    background-repeat: no-repeat;
}

/* ==================== PROGRESS CARD ==================== */
.progress-card,
.summary-card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.progress-header,
.summary-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
}

.progress-title,
.summary-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
    display: flex;
    align-items: center;
}

.progress-body,
.summary-body {
    padding: 1.5rem;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.progress-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9375rem;
    color: var(--color-text-secondary);
}

.progress-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.dot-warning { background: var(--color-warning); }
.dot-success { background: var(--color-success); }

.progress-percentage {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-success);
}

.progress-bar-container {
    margin-bottom: 1.5rem;
}

.progress-bar-custom {
    height: 12px;
    background: var(--color-border);
    border-radius: 6px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-success) 0%, #059669 100%);
    transition: width 1s ease;
}

.progress-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.progress-stat {
    text-align: center;
    padding: 1rem;
    background: var(--color-bg);
    border-radius: var(--radius-md);
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-text {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
}

/* ==================== SUMMARY ==================== */
.summary-item {
    display: flex;
    gap: 1rem;
    align-items: center;
    padding: 1rem 0;
}

.summary-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.summary-icon-primary {
    background: #dbeafe;
    color: var(--color-primary);
}

.summary-icon-success {
    background: #d1fae5;
    color: var(--color-success);
}

.summary-icon-warning {
    background: #fef3c7;
    color: var(--color-warning);
}

.summary-info {
    flex: 1;
}

.summary-label {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin-bottom: 0.25rem;
}

.summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
}

.summary-divider {
    height: 1px;
    background: var(--color-border);
    margin: 0.5rem 0;
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 768px) {
    .dashboard-header-emp .container {
        flex-direction: column;
        align-items: flex-start;
    }

    .welcome-title {
        font-size: 1.5rem;
    }

    .stat-value {
        font-size: 1.75rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .progress-stats {
        grid-template-columns: 1fr;
    }
}
</style>

@endsection
