@extends('layouts.app')

@section('title', 'Comidas Disponibles Hoy')

@section('content')

@php
    use Carbon\Carbon;
    $hoy = Carbon::today()->format('Y-m-d');

    // Agrupamos por tipo de comida
    $tiposComida = \App\Models\TipoComida::with(['comidas' => function($q) use ($hoy) {
        $q->whereHas('disponibilidades', function($q2) use ($hoy) {
            $q2->where('fecha', $hoy);
        });
    }])->get();
@endphp

<div class="disponible-container">
    
    <!-- Header -->
    <div class="disponible-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1 class="disponible-title">
                        <i class="bi bi-calendar-check-fill"></i>
                        Comidas Disponibles Hoy
                    </h1>
                    <p class="disponible-subtitle">Consulta el menú del día actual</p>
                </div>
                <div class="date-badge-disponible">
                    <i class="bi bi-calendar3 me-2"></i>
                    {{ Carbon::parse($hoy)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-5">

        @if($tiposComida->pluck('comidas')->flatten()->count() > 0)
            
            <!-- Stats rápidas -->
            <div class="stats-disponible mb-4">
                <div class="stat-item-disponible">
                    <div class="stat-icon-disponible">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </div>
                    <div>
                        <div class="stat-value-disponible">{{ $tiposComida->filter(fn($t) => $t->comidas->count() > 0)->count() }}</div>
                        <div class="stat-label-disponible">Categorías</div>
                    </div>
                </div>
                <div class="stat-item-disponible">
                    <div class="stat-icon-disponible">
                        <i class="bi bi-egg-fried"></i>
                    </div>
                    <div>
                        <div class="stat-value-disponible">{{ $tiposComida->pluck('comidas')->flatten()->count() }}</div>
                        <div class="stat-label-disponible">Platillos</div>
                    </div>
                </div>
                <div class="stat-item-disponible">
                    <div class="stat-icon-disponible">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div>
                        <div class="stat-value-disponible">{{ $tiposComida->pluck('comidas')->flatten()->sum('disponible') }}</div>
                        <div class="stat-label-disponible">Stock Total</div>
                    </div>
                </div>
            </div>

            <!-- Grid de categorías -->
            <div class="categorias-grid">
                @foreach($tiposComida as $tipo)
                    @php
                        $comidasDisponibles = $tipo->comidas;
                        $modalId = 'modal_tipo_' . $tipo->id;
                    @endphp

                    @if($comidasDisponibles->count() > 0)
                        <div class="categoria-card">
                            <div class="categoria-card-header">
                                <div class="categoria-icon">
                                    @switch($tipo->descripcion)
                                        @case('Plato Fuerte')
                                        @case('PLATO FUERTE')
                                            <i class="bi bi-egg-fried"></i>
                                            @break
                                        @case('Sopa')
                                        @case('SOPA')
                                            <i class="bi bi-cup-hot-fill"></i>
                                            @break
                                        @case('Agua')
                                        @case('AGUA')
                                            <i class="bi bi-droplet-fill"></i>
                                            @break
                                        @case('Postre')
                                        @case('POSTRE')
                                            <i class="bi bi-cake2-fill"></i>
                                            @break
                                        @default
                                            <i class="bi bi-basket3-fill"></i>
                                    @endswitch
                                </div>
                                <h3 class="categoria-title">{{ $tipo->descripcion }}</h3>
                                <div class="categoria-badge">
                                    {{ $comidasDisponibles->count() }} platillos
                                </div>
                            </div>

                            <div class="categoria-card-body">
                                <div class="categoria-stats">
                                    <div class="categoria-stat">
                                        <i class="bi bi-box"></i>
                                        <span>{{ $comidasDisponibles->sum('disponible') }} disponibles</span>
                                    </div>
                                    <div class="categoria-stat">
                                        <i class="bi bi-cash"></i>
                                        <span>${{ number_format($comidasDisponibles->min('precio'), 2) }} - ${{ number_format($comidasDisponibles->max('precio'), 2) }}</span>
                                    </div>
                                </div>

                                <button type="button" 
                                        class="btn-ver-categoria" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#{{ $modalId }}">
                                    <span>Ver Platillos</span>
                                    <i class="bi bi-arrow-right-circle-fill"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Modal -->
                        <div class="modal fade" id="{{ $modalId }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-scrollable modal-xl modal-dialog-centered">
                                <div class="modal-content modal-disponible">
                                    <div class="modal-header-disponible">
                                        <div class="modal-icon-disponible">
                                            @switch($tipo->descripcion)
                                                @case('Plato Fuerte')
                                                @case('PLATO FUERTE')
                                                    <i class="bi bi-egg-fried"></i>
                                                    @break
                                                @case('Sopa')
                                                @case('SOPA')
                                                    <i class="bi bi-cup-hot-fill"></i>
                                                    @break
                                                @case('Agua')
                                                @case('AGUA')
                                                    <i class="bi bi-droplet-fill"></i>
                                                    @break
                                                @case('Postre')
                                                @case('POSTRE')
                                                    <i class="bi bi-cake2-fill"></i>
                                                    @break
                                                @default
                                                    <i class="bi bi-basket3-fill"></i>
                                            @endswitch
                                        </div>
                                        <div>
                                            <h5 class="modal-title-disponible">{{ $tipo->descripcion }}</h5>
                                            <p class="modal-subtitle-disponible">{{ $comidasDisponibles->count() }} platillos disponibles</p>
                                        </div>
                                        <button type="button" class="btn-close-disponible" data-bs-dismiss="modal">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>

                                    <div class="modal-body-disponible">
                                        <div class="platillos-grid">
                                            @foreach($comidasDisponibles as $comida)
                                                <div class="platillo-card">
                                                    <div class="platillo-header">
                                                        <div class="platillo-badge-abbr">
                                                            {{ $comida->abreviatura_op ?? substr($comida->nombre, 0, 2) }}
                                                        </div>
                                                        <div class="platillo-stock">
                                                            <i class="bi bi-box-fill"></i>
                                                            {{ $comida->disponible }}
                                                        </div>
                                                    </div>
                                                
                                                    <div class="platillo-body">
                                                        <h6 class="platillo-nombre">{{ $comida->nombre }}</h6>
                                                        
                                                        <div class="platillo-info">
                                                            <div class="info-item">
                                                                <i class="bi bi-cash-coin"></i>
                                                                <span>${{ number_format($comida->precio, 2) }}</span>
                                                            </div>
                                                            @if($comida->disponible > 0)
                                                                <div class="info-badge badge-disponible">
                                                                    <i class="bi bi-check-circle-fill"></i>
                                                                    Disponible
                                                                </div>
                                                            @else
                                                                <div class="info-badge badge-agotado">
                                                                    <i class="bi bi-x-circle-fill"></i>
                                                                    Agotado
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="modal-footer-disponible">
                                        <div class="modal-summary">
                                            <div class="summary-item-modal">
                                                <i class="bi bi-basket3-fill"></i>
                                                <strong>{{ $comidasDisponibles->count() }}</strong> platillos
                                            </div>
                                            <div class="summary-item-modal">
                                                <i class="bi bi-box-seam"></i>
                                                <strong>{{ $comidasDisponibles->sum('disponible') }}</strong> unidades
                                            </div>
                                        </div>
                                        <button type="button" class="btn-cerrar-modal" data-bs-dismiss="modal">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Cerrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

        @else
            <!-- Empty State -->
            <div class="empty-state-disponible">
                <div class="empty-icon-disponible">
                    <i class="bi bi-inbox"></i>
                </div>
                <h3 class="empty-title-disponible">No hay comidas disponibles hoy</h3>
                <p class="empty-desc-disponible">
                    No se han encontrado comidas disponibles para el día <strong>{{ Carbon::parse($hoy)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</strong>
                </p>
                <a href="{{ route('empleados') }}" class="btn-empty-action">
                    <i class="bi bi-house-door me-2"></i>
                    Volver al Dashboard
                </a>
            </div>
        @endif

    </div>

</div>

<style>
/* ==================== VARIABLES ==================== */
:root {
    --color-bg: #f8fafc;
    --color-card: #ffffff;
    --color-primary: #3b82f6;
    --color-success: #10b981;
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
.disponible-container {
    background: var(--color-bg);
    min-height: 100vh;
}

/* ==================== HEADER ==================== */
.disponible-header {
    background: var(--color-card);
    padding: 2rem 0;
    border-bottom: 1px solid var(--color-border);
    margin-bottom: 2rem;
}

.disponible-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.disponible-title i {
    color: var(--color-success);
}

.disponible-subtitle {
    color: var(--color-text-secondary);
    margin: 0.5rem 0 0 0;
}

.date-badge-disponible {
    background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    box-shadow: var(--shadow-md);
}

/* ==================== STATS ==================== */
.stats-disponible {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.stat-item-disponible {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    transition: all 0.3s;
}

.stat-item-disponible:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-icon-disponible {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.stat-value-disponible {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1;
}

.stat-label-disponible {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin-top: 0.25rem;
}

/* ==================== CATEGORÍAS GRID ==================== */
.categorias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.categoria-card {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all 0.3s;
}

.categoria-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
}

.categoria-card-header {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    padding: 2rem 1.5rem;
    text-align: center;
    position: relative;
}

.categoria-icon {
    width: 64px;
    height: 64px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 1rem;
}

.categoria-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.75rem 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.categoria-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.25);
    padding: 0.375rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
}

.categoria-card-body {
    padding: 1.5rem;
}

.categoria-stats {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.categoria-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-text-secondary);
    font-size: 0.9375rem;
}

.categoria-stat i {
    color: var(--color-success);
    font-size: 1.125rem;
}

.btn-ver-categoria {
    width: 100%;
    background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%);
    color: white;
    border: none;
    padding: 1rem 1.5rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-ver-categoria:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
}

.btn-ver-categoria i {
    font-size: 1.5rem;
}

/* ==================== MODAL ==================== */
.modal-disponible {
    border: none;
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.modal-header-disponible {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    background: var(--color-bg);
}

.modal-icon-disponible {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.modal-title-disponible {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
}

.modal-subtitle-disponible {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0.25rem 0 0 0;
}

.btn-close-disponible {
    width: 36px;
    height: 36px;
    background: transparent;
    border: none;
    color: var(--color-text-secondary);
    cursor: pointer;
    margin-left: auto;
    transition: all 0.2s;
    border-radius: 6px;
}

.btn-close-disponible:hover {
    background: var(--color-border);
    color: var(--color-text);
}

.modal-body-disponible {
    padding: 1.5rem;
    max-height: 60vh;
    overflow-y: auto;
}

/* ==================== PLATILLOS GRID ==================== */
.platillos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
}

.platillo-card {
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
    transition: all 0.2s;
}

.platillo-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--color-success);
}

.platillo-header {
    background: var(--color-card);
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--color-border);
}

.platillo-badge-abbr {
    background: linear-gradient(135deg, var(--color-primary) 0%, #2563eb 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.875rem;
}

.platillo-stock {
    background: #d1fae5;
    color: #065f46;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.platillo-body {
    padding: 1rem;
}

.platillo-nombre {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.75rem;
}

.platillo-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--color-success);
}

.info-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.badge-disponible {
    background: #d1fae5;
    color: #065f46;
}

.badge-agotado {
    background: #fee2e2;
    color: #991b1b;
}

/* ==================== MODAL FOOTER ==================== */
.modal-footer-disponible {
    padding: 1.5rem;
    border-top: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.modal-summary {
    display: flex;
    gap: 1.5rem;
}

.summary-item-modal {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-text-secondary);
}

.summary-item-modal i {
    color: var(--color-success);
    font-size: 1.125rem;
}

.btn-cerrar-modal {
    background: var(--color-bg);
    color: var(--color-text-secondary);
    border: 1px solid var(--color-border);
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-sm);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
}

.btn-cerrar-modal:hover {
    background: var(--color-card);
    color: var(--color-text);
    border-color: var(--color-text-secondary);
}

/* ==================== EMPTY STATE ==================== */
.empty-state-disponible {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon-disponible {
    font-size: 6rem;
    color: var(--color-text-muted);
    margin-bottom: 1.5rem;
}

.empty-title-disponible {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.75rem;
}

.empty-desc-disponible {
    font-size: 1.125rem;
    color: var(--color-text-secondary);
    margin-bottom: 2rem;
}

.btn-empty-action {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, var(--color-primary) 0%, #2563eb 100%);
    color: white;
    padding: 1rem 2rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-empty-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
    color: white;
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 768px) {
    .disponible-title {
        font-size: 1.5rem;
    }

    .categorias-grid {
        grid-template-columns: 1fr;
    }

    .platillos-grid {
        grid-template-columns: 1fr;
    }

    .stats-disponible {
        grid-template-columns: 1fr;
    }

    .modal-summary {
        flex-direction: column;
        width: 100%;
    }

    .modal-footer-disponible {
        flex-direction: column;
    }
}

/* ==================== SCROLLBAR ==================== */
.modal-body-disponible::-webkit-scrollbar {
    width: 8px;
}

.modal-body-disponible::-webkit-scrollbar-track {
    background: var(--color-bg);
    border-radius: 10px;
}

.modal-body-disponible::-webkit-scrollbar-thumb {
    background: var(--color-border);
    border-radius: 10px;
}

.modal-body-disponible::-webkit-scrollbar-thumb:hover {
    background: var(--color-text-muted);
}
</style>

@endsection
