@extends('layouts.app')

@section('title', 'Dashboard de Comidas')

@section('content')
<div class="dashboard-container-light">
    
    <!-- Header del Dashboard -->
    <div class="dashboard-header-light">
        <div class="container-fluid">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <h1 class="dashboard-title-light">
                        <i class="bi bi-house-door-fill"></i>
                        Bienvenido al Dashboard
                    </h1>
                    <p class="dashboard-subtitle-light">Resumen general y estadísticas de tu restaurante</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="date-display-light">
                        <i class="bi bi-calendar-event me-2"></i>
                        {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">

        <!-- KPI Cards - Diseño Minimalista -->
        <div class="row g-4 mb-4">
            
            <!-- Total Comidas -->
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card-light">
                    <div class="kpi-icon-light kpi-blue">
                        <i class="bi bi-basket3-fill"></i>
                    </div>
                    <div class="kpi-content-light">
                        <div class="kpi-label-light">Total Comidas</div>
                        <div class="kpi-value-light">{{ App\Models\Comida::count() }}</div>
                        <div class="kpi-change-light positive">
                            <i class="bi bi-arrow-up"></i> 12%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Total -->
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card-light">
                    <div class="kpi-icon-light kpi-green">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div class="kpi-content-light">
                        <div class="kpi-label-light">Stock Disponible</div>
                        <div class="kpi-value-light">{{ App\Models\Comida::sum('disponible') }}</div>
                        <div class="kpi-change-light positive">
                            <i class="bi bi-arrow-up"></i> 8%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categorías -->
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card-light">
                    <div class="kpi-icon-light kpi-purple">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </div>
                    <div class="kpi-content-light">
                        <div class="kpi-label-light">Categorías</div>
                        <div class="kpi-value-light">{{ App\Models\TipoComida::count() }}</div>
                        <div class="kpi-change-light neutral">
                            <i class="bi bi-dash"></i> Sin cambios
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Crítico -->
            <div class="col-xl-3 col-md-6">
                @php
                    $stockCritico = App\Models\Comida::where('disponible', '<', 5)->count();
                @endphp
                <div class="kpi-card-light">
                    <div class="kpi-icon-light kpi-orange">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="kpi-content-light">
                        <div class="kpi-label-light">Alertas Críticas</div>
                        <div class="kpi-value-light">{{ $stockCritico }}</div>
                        <div class="kpi-change-light negative">
                            <i class="bi bi-arrow-down"></i> Requiere atención
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Estadísticas Secundarias -->
        <div class="row g-4 mb-4">
            
            <!-- Precio Promedio -->
            <div class="col-lg-4">
                <div class="stats-card-light">
                    <div class="stats-icon-light stats-blue">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stats-content-light">
                        <div class="stats-label-light">Precio Promedio</div>
                        <div class="stats-value-light">${{ number_format(App\Models\Comida::avg('precio'), 2) }}</div>
                        <div class="stats-desc-light">Promedio de todas las comidas</div>
                    </div>
                </div>
            </div>

            <!-- Comida Más Cara -->
            <div class="col-lg-4">
                @php
                    $maxPrecio = App\Models\Comida::orderByDesc('precio')->first();
                @endphp
                <div class="stats-card-light">
                    <div class="stats-icon-light stats-green">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <div class="stats-content-light">
                        <div class="stats-label-light">Más Cara</div>
                        <div class="stats-value-light">${{ number_format($maxPrecio->precio ?? 0, 2) }}</div>
                        <div class="stats-desc-light">{{ Str::limit($maxPrecio->nombre ?? '-', 25) }}</div>
                    </div>
                </div>
            </div>

            <!-- Comida Más Barata -->
            <div class="col-lg-4">
                @php
                    $minPrecio = App\Models\Comida::orderBy('precio')->first();
                @endphp
                <div class="stats-card-light">
                    <div class="stats-icon-light stats-purple">
                        <i class="bi bi-tag-fill"></i>
                    </div>
                    <div class="stats-content-light">
                        <div class="stats-label-light">Más Económica</div>
                        <div class="stats-value-light">${{ number_format($minPrecio->precio ?? 0, 2) }}</div>
                        <div class="stats-desc-light">{{ Str::limit($minPrecio->nombre ?? '-', 25) }}</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sección Principal -->
        <div class="row g-4 mb-4">
            
            <!-- Alertas de Stock -->
            <div class="col-lg-5">
                <div class="panel-light">
                    <div class="panel-header-light">
                        <div class="panel-title-light">
                            <i class="bi bi-bell-fill text-warning"></i>
                            <span>Alertas de Stock Bajo</span>
                        </div>
                        <span class="badge bg-light text-dark">{{ App\Models\Comida::where('disponible', '<', 5)->count() }}</span>
                    </div>
                    <div class="panel-body-light">
                        @php
                            $alertas = App\Models\Comida::where('disponible', '<', 5)->get();
                        @endphp
                        
                        @if($alertas->count() > 0)
                            <div class="alert-list-light">
                                @foreach($alertas as $item)
                                    <div class="alert-item-light">
                                        <div class="alert-icon-light">
                                            @if($item->imagen)
                                                <img src="{{ asset('storage/' . $item->imagen) }}" alt="{{ $item->nombre }}">
                                            @else
                                                <div class="alert-placeholder-light">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="alert-info-light">
                                            <div class="alert-name-light">{{ $item->nombre }}</div>
                                            <div class="alert-stock-light">
                                                <i class="bi bi-box"></i> Solo {{ $item->disponible }} unidades
                                            </div>
                                        </div>
                                        <div class="alert-badge-light">
                                            <span class="badge-critical">Crítico</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state-light">
                                <i class="bi bi-check-circle-fill"></i>
                                <p>¡Todo está bien!</p>
                                <small>No hay alertas de stock bajo</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Gráfico de Stock -->
            <div class="col-lg-7">
                <div class="panel-light">
                    <div class="panel-header-light">
                        <div class="panel-title-light">
                            <i class="bi bi-bar-chart-fill text-primary"></i>
                            <span>Top 5 con Mayor Stock</span>
                        </div>
                    </div>
                    <div class="panel-body-light">
                        @php
                            $topStock = App\Models\Comida::orderByDesc('disponible')->take(5)->get();
                            $maxStock = $topStock->max('disponible');
                        @endphp
                        
                        <div class="chart-light">
                            @foreach($topStock as $index => $item)
                                @php
                                    $colors = ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444'];
                                    $color = $colors[$index % 5];
                                    $percentage = $maxStock > 0 ? ($item->disponible / $maxStock) * 100 : 0;
                                @endphp
                                <div class="chart-bar-light">
                                    <div class="chart-label-light">
                                        <span class="chart-name-light">{{ Str::limit($item->nombre, 20) }}</span>
                                        <span class="chart-value-light">{{ $item->disponible }}</span>
                                    </div>
                                    <div class="chart-progress-light">
                                        <div class="chart-fill-light" style="width: {{ $percentage }}%; background: {{ $color }};"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Distribución por Categorías -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="panel-light">
                    <div class="panel-header-light">
                        <div class="panel-title-light">
                            <i class="bi bi-pie-chart-fill text-success"></i>
                            <span>Distribución por Categorías</span>
                        </div>
                    </div>
                    <div class="panel-body-light">
                        <div class="row g-3">
                            @php
                                $categorias = App\Models\TipoComida::withCount('comidas')->get();
                                $totalComidas = App\Models\Comida::count();
                                $colores = ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899'];
                            @endphp
                            
                            @foreach($categorias as $index => $cat)
                                @php
                                    $porcentaje = $totalComidas > 0 ? ($cat->comidas_count / $totalComidas) * 100 : 0;
                                    $color = $colores[$index % count($colores)];
                                @endphp
                                <div class="col-md-3">
                                    <div class="category-card-light">
                                        <div class="category-icon-light" style="background: {{ $color }}20; color: {{ $color }};">
                                            <i class="bi bi-grid-fill"></i>
                                        </div>
                                        <div class="category-info-light">
                                            <div class="category-name-light">{{ $cat->descripcion }}</div>
                                            <div class="category-count-light">{{ $cat->comidas_count }} comidas</div>
                                        </div>
                                        <div class="category-percentage-light" style="color: {{ $color }};">
                                            {{ number_format($porcentaje, 1) }}%
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Inventario -->
        <div class="row g-4">
            <div class="col-12">
                <div class="panel-light">
                    <div class="panel-header-light">
                        <div class="panel-title-light">
                            <i class="bi bi-table text-info"></i>
                            <span>Inventario Completo</span>
                        </div>
                        <div class="panel-actions-light">
                            <button class="btn-action-light">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <button class="btn-action-light">
                                <i class="bi bi-download"></i> Exportar
                            </button>
                        </div>
                    </div>
                    <div class="panel-body-light p-0">
                        <div class="table-responsive">
                            <table class="table-light">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="form-check-input"></th>
                                        <th>Imagen</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Subcategoría</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(App\Models\Comida::with('subtipoComida.tipoComida')->take(10)->get() as $comida)
                                        <tr>
                                            <td><input type="checkbox" class="form-check-input"></td>
                                            <td>
                                                <div class="table-image-light">
                                                    @if($comida->imagen)
                                                        <img src="{{ asset('storage/' . $comida->imagen) }}" alt="{{ $comida->nombre }}">
                                                    @else
                                                        <div class="table-placeholder-light">
                                                            <i class="bi bi-image"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="table-product-light">
                                                    <span class="product-name-light">{{ $comida->nombre }}</span>
                                                    <span class="product-code-light">{{ $comida->abreviatura_op }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="table-badge-light badge-blue">
                                                    {{ $comida->subtipoComida->tipoComida->descripcion ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="table-badge-light badge-purple">
                                                    {{ $comida->subtipoComida->descripcion ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="table-price-light">${{ number_format($comida->precio, 2) }}</span>
                                            </td>
                                            <td>
                                                <span class="table-stock-light">{{ $comida->disponible }}</span>
                                            </td>
                                            <td>
                                                @if($comida->disponible > 10)
                                                    <span class="status-badge-light status-success">Disponible</span>
                                                @elseif($comida->disponible > 0)
                                                    <span class="status-badge-light status-warning">Stock Bajo</span>
                                                @else
                                                    <span class="status-badge-light status-danger">Agotado</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<style>
/* ==================== VARIABLES DE COLORES CLAROS ==================== */
:root {
    --bg-primary: #f8fafc;
    --bg-card: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-color: #e2e8f0;
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    --blue: #3b82f6;
    --green: #10b981;
    --purple: #8b5cf6;
    --orange: #f59e0b;
    --red: #ef4444;
}

/* ==================== CONTENEDOR PRINCIPAL ==================== */
.dashboard-container-light {
    background: var(--bg-primary);
    min-height: 100vh;
}

.dashboard-header-light {
    background: var(--bg-card);
    padding: 2rem 0;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 2rem;
}

.dashboard-title-light {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.dashboard-title-light i {
    color: var(--blue);
    margin-right: 0.5rem;
}

.dashboard-subtitle-light {
    color: var(--text-secondary);
    margin: 0.5rem 0 0 0;
    font-size: 1rem;
}

.date-display-light {
    background: var(--bg-primary);
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    color: var(--text-secondary);
    font-weight: 500;
    display: inline-block;
}

/* ==================== KPI CARDS ==================== */
.kpi-card-light {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    transition: all 0.3s ease;
}

.kpi-card-light:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.kpi-icon-light {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.kpi-blue { background: #dbeafe; color: var(--blue); }
.kpi-green { background: #d1fae5; color: var(--green); }
.kpi-purple { background: #ede9fe; color: var(--purple); }
.kpi-orange { background: #fed7aa; color: var(--orange); }

.kpi-content-light {
    flex: 1;
}

.kpi-label-light {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.kpi-value-light {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.kpi-change-light {
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.kpi-change-light.positive { color: var(--green); }
.kpi-change-light.negative { color: var(--orange); }
.kpi-change-light.neutral { color: var(--text-muted); }

/* ==================== STATS CARDS ==================== */
.stats-card-light {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    transition: all 0.3s ease;
}

.stats-card-light:hover {
    box-shadow: var(--shadow-md);
}

.stats-icon-light {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.stats-blue { background: #dbeafe; color: var(--blue); }
.stats-green { background: #d1fae5; color: var(--green); }
.stats-purple { background: #ede9fe; color: var(--purple); }

.stats-content-light {
    flex: 1;
}

.stats-label-light {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}

.stats-value-light {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stats-desc-light {
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* ==================== PANEL ==================== */
.panel-light {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
}

.panel-header-light {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-title-light {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.panel-body-light {
    padding: 1.5rem;
}

.panel-actions-light {
    display: flex;
    gap: 0.5rem;
}

.btn-action-light {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s;
}

.btn-action-light:hover {
    background: var(--bg-card);
    color: var(--text-primary);
    border-color: var(--text-secondary);
}

/* ==================== ALERTAS ==================== */
.alert-list-light {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.alert-item-light {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-primary);
    border-radius: 12px;
    align-items: center;
    transition: all 0.2s;
}

.alert-item-light:hover {
    background: #f1f5f9;
}

.alert-icon-light {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.alert-icon-light img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.alert-placeholder-light {
    width: 100%;
    height: 100%;
    background: var(--bg-card);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
}

.alert-info-light {
    flex: 1;
}

.alert-name-light {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.alert-stock-light {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.alert-badge-light .badge-critical {
    background: #fee2e2;
    color: var(--red);
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.empty-state-light {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.empty-state-light i {
    font-size: 3rem;
    color: var(--green);
    margin-bottom: 1rem;
}

.empty-state-light p {
    font-weight: 600;
    font-size: 1.125rem;
    margin-bottom: 0.25rem;
}

/* ==================== GRÁFICO ==================== */
.chart-light {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.chart-bar-light {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.chart-label-light {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-name-light {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.chart-value-light {
    font-weight: 700;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.chart-progress-light {
    height: 8px;
    background: var(--bg-primary);
    border-radius: 4px;
    overflow: hidden;
}

.chart-fill-light {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

/* ==================== CATEGORÍAS ==================== */
.category-card-light {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    transition: all 0.2s;
}

.category-card-light:hover {
    background: var(--bg-card);
    box-shadow: var(--shadow-sm);
}

.category-icon-light {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.category-info-light {
    flex: 1;
}

.category-name-light {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.category-count-light {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.category-percentage-light {
    font-weight: 700;
    font-size: 1.25rem;
}

/* ==================== TABLA ==================== */
.table-light {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table-light thead {
    background: var(--bg-primary);
}

.table-light th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-secondary);
    border-bottom: 1px solid var(--border-color);
}

.table-light tbody tr {
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
}

.table-light tbody tr:hover {
    background: var(--bg-primary);
}

.table-light td {
    padding: 1rem 1.5rem;
}

.table-image-light {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    overflow: hidden;
}

.table-image-light img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.table-placeholder-light {
    width: 100%;
    height: 100%;
    background: var(--bg-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
}

.table-product-light {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.product-name-light {
    font-weight: 600;
    color: var(--text-primary);
}

.product-code-light {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.table-badge-light {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-blue { background: #dbeafe; color: var(--blue); }
.badge-purple { background: #ede9fe; color: var(--purple); }

.table-price-light {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 0.9375rem;
}

.table-stock-light {
    font-weight: 600;
    color: var(--text-secondary);
}

.status-badge-light {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-success {
    background: #d1fae5;
    color: var(--green);
}

.status-warning {
    background: #fed7aa;
    color: var(--orange);
}

.status-danger {
    background: #fee2e2;
    color: var(--red);
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 768px) {
    .dashboard-title-light {
        font-size: 1.5rem;
    }

    .kpi-value-light {
        font-size: 1.5rem;
    }

    .stats-value-light {
        font-size: 1.25rem;
    }

    .panel-header-light {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .table-light {
        font-size: 0.875rem;
    }

    .table-light th,
    .table-light td {
        padding: 0.75rem;
    }
}
</style>

@endsection
