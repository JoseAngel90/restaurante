@extends('layouts.app')

@section('title', 'Hacer Pedido')

@section('content')

@php
    use App\Models\DisponibilidadComidaDia;
    use Carbon\Carbon;

    $hoy = Carbon::today()->format('Y-m-d');

    $comidasHoy = DisponibilidadComidaDia::with('comida.tipoComida')
        ->where('fecha', $hoy)
        ->whereHas('comida', fn($q) => $q->where('disponible', '>', 0))
        ->get();

    $categorias = $comidasHoy->groupBy(fn($disp) => strtoupper($disp->comida->tipoComida->descripcion ?? 'SIN CATEGORÍA'));
@endphp

<div class="pedido-container">
    
    <!-- Header -->
    <div class="pedido-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="pedido-title">
                        <i class="bi bi-basket3-fill"></i>
                        Nuevo Pedido
                    </h1>
                    <p class="pedido-subtitle">Crea un pedido de forma rápida y sencilla</p>
                </div>
                <div class="date-badge-pedido">
                    <i class="bi bi-calendar-event me-2"></i>
                    {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">

        <!-- Menú Disponible Hoy -->
        <div class="menu-disponible-section mb-4">
            <div class="section-header">
                <h5 class="section-title">
                    <i class="bi bi-menu-button-wide-fill"></i>
                    Menú Disponible Hoy
                </h5>
                <button class="btn-toggle-menu" onclick="toggleMenu()">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>

            <div class="menu-grid" id="menuGrid">
                @foreach($categorias as $cat => $comidas)
                    <div class="menu-card">
                        <div class="menu-card-header">
                            <i class="bi bi-tag-fill"></i>
                            {{ $cat }}
                        </div>
                        <div class="menu-card-body">
                            @foreach($comidas as $disp)
                                <div class="menu-item">
                                    <div class="menu-item-badge">{{ $disp->comida->abreviatura_op }}</div>
                                    <div class="menu-item-name">{{ $disp->comida->nombre }}</div>
                                    <div class="menu-item-stock">
                                        <i class="bi bi-box"></i>
                                        {{ $disp->comida->disponible }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Formulario Principal -->
        <form id="formPedido" action="{{ url('/pedido') }}" method="POST">
            @csrf

            @php
                use App\Models\TipoComida;

                $categoriasSelect = TipoComida::orderBy('id')->pluck('descripcion')->map(fn($d) => strtoupper($d));

                $comidasDisponiblesHoy = \App\Models\DisponibilidadComidaDia::where('fecha', $hoy)
                    ->with('comida.tipoComida')
                    ->get();

                $comidasJson = $comidasDisponiblesHoy->map(function($disp){
                    return [
                        'id' => $disp->comida->id,
                        'abreviatura' => strtolower($disp->comida->abreviatura_op ?? ''),
                        'categoria' => strtoupper($disp->comida->tipoComida->descripcion ?? ''),
                        'nombre' => $disp->comida->nombre,
                        'disponible' => $disp->comida->disponible,
                    ];
                });
            @endphp

            <div class="row g-4">

                <!-- Columna Izquierda: Paquetes -->
                <div class="col-lg-8">
                    <div class="card-pedido">
                        <div class="card-pedido-header">
                            <div class="header-content">
                                <div class="header-icon">
                                    <i class="bi bi-box-seam-fill"></i>
                                </div>
                                <div>
                                    <h5 class="header-title">Paquetes del Pedido</h5>
                                    <p class="header-desc">Agrega y personaliza los paquetes</p>
                                </div>
                            </div>
                            <button type="button" class="btn-add-package" id="btnAgregarPaquete">
                                <i class="bi bi-plus-lg"></i>
                                Agregar Paquete
                            </button>
                        </div>

                        <div class="card-pedido-body">
                            <div id="contenedorPaquetes" class="paquetes-container">
                                <!-- Plantilla de paquete -->
                                <div class="paquete-item d-none" id="cardNuevoPaquete">
                                    <div class="paquete-header-item">
                                        <div class="paquete-number">
                                            <span class="paquete-label">PAQUETE</span>
                                            <span class="paquete-num">X</span>
                                        </div>
                                        <button type="button" class="btn-remove-package btn-eliminar-paquete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>

                                    <div class="paquete-content">
                                        @foreach($categoriasSelect as $index => $cat)
                                            <div class="categoria-box">
                                                <div class="categoria-header">
                                                    <i class="bi bi-tag"></i>
                                                    {{ $cat }}
                                                </div>
                                                <div class="categoria-body">
                                                    <input type="text" 
                                                           class="categoria-input abreviatura-input" 
                                                           data-categoria="{{ $cat }}" 
                                                           placeholder="Escribe abreviatura...">
                                                    <div class="categoria-info mini-tabla"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Cliente y Acciones -->
                <div class="col-lg-4">
                    <div class="card-pedido sticky-sidebar">
                        <div class="card-pedido-header">
                            <div class="header-content">
                                <div class="header-icon">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <h5 class="header-title">Datos del Cliente</h5>
                                    <p class="header-desc">Información del pedido</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-pedido-body">
                            @php
                                use App\Models\Cliente;
                                $clientes = Cliente::all();
                                $clientesJson = $clientes->map(fn($c) => [
                                    'nombre' => $c->nombre,
                                    'telefono' => $c->telefono
                                ]);
                            @endphp

                            <div class="form-group-pedido">
                                <label class="form-label-pedido">
                                    <i class="bi bi-person-badge"></i>
                                    Nombre del Cliente
                                </label>
                                <input type="text" 
                                       name="cliente_nombre" 
                                       id="cliente_nombre" 
                                       class="form-input-pedido" 
                                       placeholder="Escribe el nombre..." 
                                       autocomplete="off" 
                                       required 
                                       list="clientesNombre">
                                <datalist id="clientesNombre">
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->nombre }}"></option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div class="form-group-pedido">
                                <label class="form-label-pedido">
                                    <i class="bi bi-telephone"></i>
                                    Teléfono
                                </label>
                                <input type="text" 
                                       name="cliente_telefono" 
                                       id="cliente_telefono" 
                                       class="form-input-pedido" 
                                       placeholder="Número de contacto..." 
                                       autocomplete="off" 
                                       required>
                                <!-- <small class="form-hint">* Para clientes de paso escribe "Walking"</small> -->
                            </div>

                            <div class="form-group-pedido">
                                <label class="form-label-pedido">
                                    <i class="bi bi-truck"></i>
                                    Estado del Pedido
                                </label>
                                <select name="estado_pedido" class="form-select-pedido" required>
                                    <option value="Pendiente" selected>🕐 Pendiente</option>
                                    <!-- <option value="Walking">🚶 Walking</option> -->
                                    <option value="A domicilio">🏠 A domicilio</option>
                                </select>
                            </div>

                            <div class="form-group-pedido">
                                <label class="form-label-pedido">
                                    <i class="bi bi-chat-left-text"></i>
                                    Notas Adicionales
                                </label>
                                <textarea name="notas" 
                                          class="form-textarea-pedido" 
                                          rows="3" 
                                          placeholder="Comentarios opcionales..."></textarea>
                            </div>

                            <div class="divider-pedido"></div>

                            <button type="submit" id="btnRegistrarPedido" class="btn-submit-pedido">
                                <i class="bi bi-check-circle-fill"></i>
                                Registrar Pedido
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>

</div>

<!-- Modales -->
<div class="modal fade" id="modalClienteNoRegistrado" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-pedido">
            <div class="modal-header-pedido modal-danger">
                <div class="modal-icon-pedido">
                    <i class="bi bi-person-x-fill"></i>
                </div>
                <div>
                    <h5 class="modal-title-pedido">Cliente no encontrado</h5>
                    <p class="modal-subtitle-pedido">El cliente no está registrado en el sistema</p>
                </div>
            </div>
            <div class="modal-body-pedido">
                <p class="text-center">Por favor, registra primero al cliente en el módulo correspondiente.</p>
            </div>
            <div class="modal-footer-pedido">
                <a href="{{ route('empleados_registro_clientes') }}" class="btn-modal-pedido btn-danger-modal">
                    <i class="bi bi-arrow-right-circle me-2"></i>
                    Ir a Registro de Clientes
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNoComidaSeleccionada" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-pedido">
            <div class="modal-header-pedido modal-warning">
                <div class="modal-icon-pedido">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <h5 class="modal-title-pedido">Pedido Vacío</h5>
                    <p class="modal-subtitle-pedido">No hay productos seleccionados</p>
                </div>
            </div>
            <div class="modal-body-pedido">
                <p class="text-center">Debes seleccionar al menos una comida con cantidad mayor a 0.</p>
            </div>
            <div class="modal-footer-pedido">
                <button type="button" class="btn-modal-pedido btn-warning-modal" data-bs-dismiss="modal">
                    <i class="bi bi-check-circle me-2"></i>
                    Entendido
                </button>
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
.pedido-container {
    background: var(--color-bg);
    min-height: 100vh;
    padding-bottom: 2rem;
}

/* ==================== HEADER ==================== */
.pedido-header {
    background: var(--color-card);
    padding: 2rem 0;
    border-bottom: 1px solid var(--color-border);
    margin-bottom: 2rem;
}

.pedido-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.pedido-title i {
    color: var(--color-primary);
}

.pedido-subtitle {
    color: var(--color-text-secondary);
    margin: 0.5rem 0 0 0;
}

.date-badge-pedido {
    background: var(--color-bg);
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius-md);
    color: var(--color-text-secondary);
    font-weight: 500;
}

/* ==================== MENÚ DISPONIBLE ==================== */
.menu-disponible-section {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.section-header {
    padding: 1rem 1.5rem;
    background: var(--color-bg);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--color-border);
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-toggle-menu {
    width: 32px;
    height: 32px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all 0.2s;
}

.btn-toggle-menu:hover {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

.btn-toggle-menu i {
    transition: transform 0.3s;
}

.btn-toggle-menu.active i {
    transform: rotate(180deg);
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
    max-height: 400px;
    overflow-y: auto;
}

.menu-card {
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.menu-card-header {
    background: linear-gradient(135deg, var(--color-primary) 0%, #2563eb 100%);
    color: white;
    padding: 0.75rem 1rem;
    font-weight: 600;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.menu-card-body {
    padding: 0.75rem;
    max-height: 200px;
    overflow-y: auto;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    background: var(--color-card);
    border-radius: 6px;
    margin-bottom: 0.5rem;
    transition: all 0.2s;
}

.menu-item:hover {
    box-shadow: var(--shadow-sm);
}

.menu-item-badge {
    background: var(--color-primary);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 700;
    font-size: 0.75rem;
    min-width: 50px;
    text-align: center;
}

.menu-item-name {
    flex: 1;
    font-size: 0.875rem;
    color: var(--color-text);
}

.menu-item-stock {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--color-success);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* ==================== CARD PEDIDO ==================== */
.card-pedido {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.sticky-sidebar {
    position: sticky;
    top: 20px;
}

.card-pedido-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-content {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--color-primary) 0%, #2563eb 100%);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.header-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.header-desc {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0.25rem 0 0 0;
}

.card-pedido-body {
    padding: 1.5rem;
}

/* ==================== BOTONES ==================== */
.btn-add-package {
    background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--radius-sm);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-add-package:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-submit-pedido {
    width: 100%;
    background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%);
    color: white;
    padding: 1rem 1.5rem;
    border: none;
    border-radius: var(--radius-md);
    font-weight: 700;
    font-size: 1.125rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.btn-submit-pedido:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

/* ==================== PAQUETES ==================== */
.paquetes-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.paquete-item {
    background: var(--color-bg);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    transition: all 0.3s;
}

.paquete-item:hover {
    border-color: var(--color-primary);
    box-shadow: var(--shadow-md);
}

.paquete-header-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.paquete-number {
    background: linear-gradient(135deg, var(--color-primary) 0%, #2563eb 100%);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 700;
}

.paquete-label {
    font-size: 0.75rem;
    opacity: 0.9;
}

.paquete-num {
    font-size: 1.5rem;
}

.btn-remove-package {
    width: 40px;
    height: 40px;
    background: var(--color-danger);
    color: white;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-remove-package:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* ==================== CATEGORÍAS ==================== */
.paquete-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.categoria-box {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.categoria-header {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    padding: 0.75rem;
    font-weight: 600;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.categoria-body {
    padding: 1rem;
}

.categoria-input {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--color-border);
    border-radius: var(--radius-sm);
    font-size: 0.9375rem;
    text-align: center;
    font-weight: 600;
    color: var(--color-text);
    transition: all 0.2s;
}

.categoria-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.categoria-input:disabled {
    background: #fee2e2;
    cursor: not-allowed;
}

.categoria-info {
    margin-top: 0.75rem;
}

.mini-tabla-content {
    background: var(--color-bg);
    border-radius: var(--radius-sm);
    padding: 0.75rem;
}

.mini-tabla-content small {
    font-size: 0.8125rem;
    color: var(--color-text);
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.mini-tabla-content .d-flex {
    gap: 0.5rem;
}

.mini-tabla-content .badge {
    font-size: 0.8125rem;
    padding: 0.375rem 0.75rem;
    font-weight: 600;
}

.mini-tabla-content input[type="number"] {
    flex: 1;
    padding: 0.5rem;
    border: 2px solid var(--color-border);
    border-radius: 6px;
    text-align: center;
    font-weight: 700;
    color: var(--color-text);
}

.mini-tabla-content input[type="number"]:focus {
    border-color: var(--color-primary);
    outline: none;
}

.plato-fuerte-input {
    background: #fef3c7 !important;
    border-color: var(--color-warning) !important;
}

.text-warning {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: var(--color-warning);
    font-weight: 600;
}

/* ==================== FORMULARIO ==================== */
.form-group-pedido {
    margin-bottom: 1.25rem;
}

.form-label-pedido {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.form-label-pedido i {
    color: var(--color-primary);
}

.form-input-pedido,
.form-select-pedido,
.form-textarea-pedido {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    font-size: 0.9375rem;
    color: var(--color-text);
    background: var(--color-card);
    transition: all 0.2s;
}

.form-input-pedido:focus,
.form-select-pedido:focus,
.form-textarea-pedido:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-hint {
    display: block;
    font-size: 0.8125rem;
    color: var(--color-text-muted);
    margin-top: 0.25rem;
}

.divider-pedido {
    height: 1px;
    background: var(--color-border);
    margin: 1.5rem 0;
}

/* ==================== MODAL ==================== */
.modal-pedido {
    border: none;
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.modal-header-pedido {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.modal-danger {
    background: #fee2e2;
}

.modal-warning {
    background: #fef3c7;
}

.modal-icon-pedido {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.modal-danger .modal-icon-pedido {
    background: var(--color-danger);
    color: white;
}

.modal-warning .modal-icon-pedido {
    background: var(--color-warning);
    color: white;
}

.modal-title-pedido {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.modal-subtitle-pedido {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0.25rem 0 0 0;
}

.modal-body-pedido {
    padding: 1.5rem;
}

.modal-footer-pedido {
    padding: 1.5rem;
    border-top: 1px solid var(--color-border);
    display: flex;
    justify-content: center;
}

.btn-modal-pedido {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--radius-sm);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    color: white;
    text-decoration: none;
}

.btn-danger-modal {
    background: var(--color-danger);
}

.btn-danger-modal:hover {
    background: #dc2626;
    color: white;
}

.btn-warning-modal {
    background: var(--color-warning);
}

.btn-warning-modal:hover {
    background: #d97706;
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 992px) {
    .sticky-sidebar {
        position: relative;
        top: 0;
    }

    .paquete-content {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
}

@media (max-width: 768px) {
    .pedido-title {
        font-size: 1.5rem;
    }

    .menu-grid {
        grid-template-columns: 1fr;
    }

    .paquete-content {
        grid-template-columns: 1fr;
    }

    .card-pedido-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* ==================== SCROLLBAR ==================== */
.menu-grid::-webkit-scrollbar,
.menu-card-body::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.menu-grid::-webkit-scrollbar-track,
.menu-card-body::-webkit-scrollbar-track {
    background: var(--color-bg);
    border-radius: 10px;
}

.menu-grid::-webkit-scrollbar-thumb,
.menu-card-body::-webkit-scrollbar-thumb {
    background: var(--color-border);
    border-radius: 10px;
}

.menu-grid::-webkit-scrollbar-thumb:hover,
.menu-card-body::-webkit-scrollbar-thumb:hover {
    background: var(--color-text-muted);
}
</style>

@endsection

@push('scripts')
<script>
// Toggle menú
function toggleMenu() {
    const menu = document.getElementById('menuGrid');
    const btn = document.querySelector('.btn-toggle-menu');
    
    if (menu.style.display === 'none') {
        menu.style.display = 'grid';
        btn.classList.add('active');
    } else {
        menu.style.display = 'none';
        btn.classList.remove('active');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const btnAgregar = document.getElementById('btnAgregarPaquete');
    const contenedor = document.getElementById('contenedorPaquetes');
    const cardTemplate = document.getElementById('cardNuevoPaquete');
    let paqueteCount = 0;

    const comidasOriginal = @json($comidasJson);

    let disponibilidad = {};
    comidasOriginal.forEach(c => disponibilidad[c.id] = c.disponible);

    function actualizarMiniTabla(input, numeroPaquete) {
        const cat = input.dataset.categoria;
        const val = input.value.trim().toLowerCase();
        const contenedorMini = input.nextElementSibling;
        contenedorMini.innerHTML = '';

        const disponiblesEnCategoria = comidasOriginal.filter(c => c.categoria === cat && disponibilidad[c.id] > 0);
        if (disponiblesEnCategoria.length === 0) {
            input.disabled = true;
            contenedorMini.innerHTML = `<small class="text-danger">❌ Sin stock disponible</small>`;
            return;
        } else {
            input.disabled = false;
        }

        if (!val) return;

        const comida = comidasOriginal.find(c => (c.abreviatura === val || c.nombre.toLowerCase() === val) && c.categoria === cat);

        if (comida) {
            const esPlatoFuerte = cat === 'PLATO FUERTE';
            
            contenedorMini.innerHTML = `
                <div class="mini-tabla-content">
                    <small title="${comida.nombre}">${comida.nombre}</small>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info disponibles" data-comida-id="${comida.id}">
                            ${disponibilidad[comida.id]}
                        </span>
                        <input type="number"
                            name="detalle[${comida.id}][cantidad][${numeroPaquete}]"
                            class="cantidad-input ${esPlatoFuerte ? 'plato-fuerte-input' : ''}"
                            data-paquete="${numeroPaquete}"
                            min="0"
                            max="${esPlatoFuerte ? 1 : disponibilidad[comida.id]}"
                            value="1"
                            data-comida-id="${comida.id}"
                            ${esPlatoFuerte ? 'readonly title="Solo 1 plato fuerte"' : ''}>
                    </div>
                    ${esPlatoFuerte ? '<small class="text-warning">⚠️ Máximo: 1</small>' : ''}
                </div>
            `;
        }

        inicializarCantidadInputs();
    }

    function inicializarInputs(card, numeroPaquete) {
        card.querySelectorAll('.abreviatura-input').forEach(input => {
            input.value = '';
            input.nextElementSibling.innerHTML = '';

            const cat = input.dataset.categoria;
            const disponiblesEnCategoria = comidasOriginal.filter(c => c.categoria === cat && disponibilidad[c.id] > 0);
            if (disponiblesEnCategoria.length === 0) {
                input.disabled = true;
                input.nextElementSibling.innerHTML = `<small class="text-danger">❌ Sin stock</small>`;
            } else {
                input.disabled = false;
            }

            input.addEventListener('input', () => actualizarMiniTabla(input, numeroPaquete));
        });
    }

    function inicializarCantidadInputs() {
        document.querySelectorAll('.cantidad-input').forEach(input => {
            if (input.classList.contains('plato-fuerte-input')) {
                return;
            }
            
            input.removeEventListener('input', input._listener);
            const listener = function() {
                const comidaId = this.dataset.comidaId;
                const numeroPaquete = this.dataset.paquete;
                if (!comidaId) return;

                if (parseInt(this.value) < 0) this.value = 0;

                // Solo contar en este paquete
                const totalUsadoEnPaquete = Array.from(document.querySelectorAll(`.cantidad-input[data-comida-id="${comidaId}"][data-paquete="${numeroPaquete}"]:not(.plato-fuerte-input)`))
                    .reduce((sum, i) => sum + (parseInt(i.value) || 0), 0);

                // Total en otros paquetes
                const totalEnOtrosPaquetes = Array.from(document.querySelectorAll(`.cantidad-input[data-comida-id="${comidaId}"]:not([data-paquete="${numeroPaquete}"])`))
                    .reduce((sum, i) => sum + (parseInt(i.value) || 0), 0);

                const totalOriginal = comidasOriginal.find(c => c.id == comidaId).disponible;
                disponibilidad[comidaId] = totalOriginal - totalEnOtrosPaquetes - totalUsadoEnPaquete;

                document.querySelectorAll(`.cantidad-input[data-comida-id="${comidaId}"][data-paquete="${numeroPaquete}"]:not(.plato-fuerte-input)`).forEach(i => {
                    i.max = disponibilidad[comidaId] + parseInt(i.value || 0);
                    if (parseInt(i.value) > i.max) i.value = i.max;
                });
                document.querySelectorAll(`.disponibles[data-comida-id="${comidaId}"]`).forEach(span => {
                    span.innerText = `${disponibilidad[comidaId]}`;
                });
            };
            input.addEventListener('input', listener);
            input._listener = listener;
        });
    }

    function crearNuevoPaquete() {
        paqueteCount++;
        const card = cardTemplate.cloneNode(true);
        card.id = '';
        card.classList.remove('d-none');
        card.querySelector('.paquete-num').innerText = `${paqueteCount}`;

        card.querySelector('.btn-eliminar-paquete').addEventListener('click', function() {
            card.querySelectorAll('.cantidad-input').forEach(input => {
                const comidaId = input.dataset.comidaId;
                if (!comidaId) return;
                
                if (input.classList.contains('plato-fuerte-input')) {
                    disponibilidad[comidaId] += 1;
                } else {
                    disponibilidad[comidaId] += parseInt(input.value || 0);
                }
            });
            card.remove();
            actualizarTodasDisponibilidades();
        });

        inicializarInputs(card, paqueteCount);
        contenedor.appendChild(card);
    }

    btnAgregar.addEventListener('click', crearNuevoPaquete);

    // Crear 3 paquetes automáticamente
    for (let i = 0; i < 3; i++) {
        crearNuevoPaquete();
    }

    function actualizarTodasDisponibilidades() {
        document.querySelectorAll('.cantidad-input:not(.plato-fuerte-input)').forEach(input => {
            const comidaId = input.dataset.comidaId;
            const numeroPaquete = input.dataset.paquete;
            
            const totalUsadoEnPaquete = Array.from(document.querySelectorAll(`.cantidad-input[data-comida-id="${comidaId}"][data-paquete="${numeroPaquete}"]:not(.plato-fuerte-input)`))
                .reduce((sum, i) => sum + (parseInt(i.value) || 0), 0);
                
            const totalEnOtrosPaquetes = Array.from(document.querySelectorAll(`.cantidad-input[data-comida-id="${comidaId}"]:not([data-paquete="${numeroPaquete}"])`))
                .reduce((sum, i) => sum + (parseInt(i.value) || 0), 0);
                
            const totalOriginal = comidasOriginal.find(c => c.id == comidaId).disponible;
            disponibilidad[comidaId] = totalOriginal - totalEnOtrosPaquetes - totalUsadoEnPaquete;

            input.max = disponibilidad[comidaId] + parseInt(input.value || 0);
            if (parseInt(input.value) < 0) input.value = 0;
        });

        document.querySelectorAll('.disponibles').forEach(span => {
            const comidaId = span.dataset.comidaId;
            span.innerText = `${disponibilidad[comidaId]}`;
        });
    }

    inicializarInputs(cardTemplate, 0);

    const form = document.getElementById('formPedido');
    form.addEventListener('submit', function(e) {
        const cantidades = form.querySelectorAll('input[type="number"]');
        let total = 0;
        cantidades.forEach(i => total += parseInt(i.value) || 0);
        if (total === 0) {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('modalNoComidaSeleccionada'));
            modal.show();
        }
    });

    const clientes = @json($clientesJson);
    const inputNombre = document.getElementById('cliente_nombre');
    const inputTelefono = document.getElementById('cliente_telefono');

    inputNombre.addEventListener('input', function() {
        const texto = this.value.trim().toLowerCase();

        if (!texto) {
            inputTelefono.value = '';
            return;
        }

        const cliente = clientes.find(c => c.nombre.toLowerCase().startsWith(texto));

        if (cliente) {
            inputTelefono.value = cliente.telefono;
        } else {
            inputTelefono.value = '';
        }
    });
});
</script>
@endpush