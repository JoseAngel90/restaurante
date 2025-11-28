@extends('layouts.app')

@section('title', 'Registro de Clientes')

@section('content')
<div class="clientes-container">
    
    <!-- Header -->
    <div class="clientes-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="clientes-title">
                        <i class="bi bi-person-badge-fill"></i>
                        Gestión de Clientes
                    </h1>
                    <p class="clientes-subtitle">Administra y controla tu base de clientes</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="clientes-stats">
                        <div class="stat-box">
                            <div class="stat-value">{{ App\Models\Cliente::count() }}</div>
                            <div class="stat-label">Clientes Totales</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">

        <!-- Alertas -->
        @if(session('success'))
            <div class="alert-modern alert-success">
                <div class="alert-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">¡Éxito!</div>
                    <div class="alert-message">{{ session('success') }}</div>
                </div>
                <button type="button" class="alert-close" data-bs-dismiss="alert">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert-modern alert-danger">
                <div class="alert-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Error</div>
                    <div class="alert-message">{{ session('error') }}</div>
                </div>
                <button type="button" class="alert-close" data-bs-dismiss="alert">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @endif

        <div class="row g-4">

            <!-- ==================== FORMULARIO NUEVO CLIENTE ==================== -->
            <div class="col-lg-5">
                <div class="card-cliente sticky-card">
                    <div class="card-cliente-header">
                        <div class="card-header-icon">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <div>
                            <h3 class="card-cliente-title">Nuevo Cliente</h3>
                            <p class="card-cliente-desc">Registra un nuevo cliente en el sistema</p>
                        </div>
                    </div>

                    <div class="card-cliente-body">
                        <form action="{{ route('clientes.store') }}" method="POST" class="form-cliente needs-validation" novalidate>
                            @csrf

                            <div class="form-group-cliente">
                                <label class="form-label-cliente">
                                    <i class="bi bi-person"></i>
                                    Nombre Completo
                                </label>
                                <input type="text" 
                                       name="nombre" 
                                       class="form-input-cliente" 
                                       placeholder="Ej: María García López"
                                       value="{{ old('nombre') }}"
                                       required>
                                <div class="invalid-feedback-cliente">Por favor ingresa el nombre del cliente</div>
                            </div>

                            <div class="form-group-cliente">
                                <label class="form-label-cliente">
                                    <i class="bi bi-telephone"></i>
                                    Número de Teléfono
                                </label>
                                <input type="tel" 
                                       name="telefono" 
                                       class="form-input-cliente" 
                                       placeholder="Ej: 9991234567"
                                       value="{{ old('telefono') }}"
                                       pattern="[0-9]{10,20}"
                                       required>
                                <div class="invalid-feedback-cliente">Ingresa un teléfono válido (10-20 dígitos)</div>
                            </div>

                            <div class="form-actions-cliente">
                                <button type="reset" class="btn-cliente btn-secondary-cliente">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i>
                                    Limpiar
                                </button>
                                <button type="submit" class="btn-cliente btn-primary-cliente">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Guardar Cliente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Botón Importar Excel -->
                <div class="card-cliente mt-4">
                    <div class="card-cliente-body">
                        <button class="btn-import-excel" data-bs-toggle="modal" data-bs-target="#importarClientesModal">
                            <div class="btn-import-icon">
                                <i class="bi bi-file-earmark-excel-fill"></i>
                            </div>
                            <div class="btn-import-content">
                                <div class="btn-import-title">Importar desde Excel</div>
                                <div class="btn-import-desc">Carga múltiples clientes a la vez</div>
                            </div>
                            <i class="bi bi-arrow-right btn-import-arrow"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ==================== LISTA DE CLIENTES ==================== -->
            <div class="col-lg-7">
                <div class="card-cliente">
                    <div class="card-cliente-header">
                        <div class="card-header-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="card-cliente-title">Clientes Registrados</h3>
                            <p class="card-cliente-desc" id="contador-clientes">{{ App\Models\Cliente::count() }} clientes en total</p>
                        </div>
                        <div class="header-actions-cliente">
                            <div class="search-box-cliente">
                                <i class="bi bi-search"></i>
                                <input type="text" 
                                       id="buscador-clientes" 
                                       class="input-buscar-cliente" 
                                       placeholder="Buscar por nombre o teléfono...">
                            </div>
                            <button class="btn-icon-cliente" title="Filtrar">
                                <i class="bi bi-funnel"></i>
                            </button>
                            <button class="btn-icon-cliente" title="Exportar">
                                <i class="bi bi-download"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-cliente-body p-0">
                        @php 
                            $clientes = App\Models\Cliente::orderBy('id','desc')->get(); 
                        @endphp

                        @if($clientes->isEmpty())
                            <div class="empty-state-cliente">
                                <div class="empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <div class="empty-title">No hay clientes registrados</div>
                                <div class="empty-desc">Comienza agregando tu primer cliente usando el formulario</div>
                            </div>
                        @else
                            <div class="clientes-list">
                                @foreach($clientes as $cliente)
                                    <div class="cliente-item" 
                                         id="cliente-{{ $cliente->id }}"
                                         data-nombre="{{ strtolower($cliente->nombre) }}"
                                         data-telefono="{{ $cliente->telefono }}">
                                        <div class="cliente-avatar">
                                            {{ strtoupper(substr($cliente->nombre, 0, 2)) }}
                                        </div>
                                        <div class="cliente-info">
                                            <div class="cliente-name">{{ $cliente->nombre }}</div>
                                            <div class="cliente-phone">
                                                <i class="bi bi-telephone-fill me-1"></i>
                                                {{ $cliente->telefono }}
                                            </div>
                                        </div>
                                        <div class="cliente-id">
                                            #{{ str_pad($cliente->id, 4, '0', STR_PAD_LEFT) }}
                                        </div>
                                        <div class="cliente-actions">
                                            <button class="btn-action-cliente btn-edit" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditar{{ $cliente->id }}"
                                                    title="Editar">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn-action-cliente btn-delete"
                                                    onclick="eliminarCliente({{ $cliente->id }}, '{{ $cliente->nombre }}')"
                                                    title="Eliminar">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>

                                        <form id="delete-form-{{ $cliente->id }}" 
                                              action="{{ route('clientes.destroy', $cliente->id) }}" 
                                              method="POST" 
                                              style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Empty state para búsqueda sin resultados -->
                            <div class="empty-state-cliente" id="sin-resultados" style="display: none;">
                                <div class="empty-icon">
                                    <i class="bi bi-search"></i>
                                </div>
                                <div class="empty-title">No se encontraron clientes</div>
                                <div class="empty-desc" id="mensaje-busqueda">Intenta con otro término de búsqueda</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Importar Excel -->
<div class="modal fade" id="importarClientesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-cliente">
            <div class="modal-header-cliente">
                <div class="modal-icon-cliente">
                    <i class="bi bi-cloud-upload-fill"></i>
                </div>
                <div>
                    <h5 class="modal-title-cliente">Importar desde Excel</h5>
                    <p class="modal-subtitle-cliente">Carga múltiples clientes a la vez</p>
                </div>
                <button type="button" class="btn-close-cliente" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form action="{{ route('clientes.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body-cliente">
                    <div class="upload-area">
                        <div class="upload-icon">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                        </div>
                        <div class="upload-text">
                            <div class="upload-title">Selecciona un archivo Excel</div>
                            <div class="upload-desc">Formato: .xlsx o .csv con columnas: <code>nombre</code> y <code>telefono</code></div>
                        </div>
                        <input type="file" 
                               name="archivo" 
                               class="upload-input" 
                               accept=".xlsx,.csv" 
                               required
                               onchange="mostrarArchivo(this)">
                        <div class="upload-selected" id="archivo-seleccionado" style="display: none;">
                            <i class="bi bi-file-earmark-check-fill text-success me-2"></i>
                            <span id="nombre-archivo"></span>
                        </div>
                    </div>

                    <div class="alert-info-cliente">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            <strong>Importante:</strong> El archivo debe contener las columnas "nombre" y "telefono" en la primera fila.
                        </div>
                    </div>
                </div>

                <div class="modal-footer-cliente">
                    <button type="button" class="btn-cliente btn-secondary-cliente" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-cliente btn-primary-cliente">
                        <i class="bi bi-upload me-2"></i>
                        Importar Clientes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modales de Edición -->
@foreach($clientes as $cliente)
<div class="modal fade" id="modalEditar{{ $cliente->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-cliente">
            <div class="modal-header-cliente">
                <div class="modal-icon-cliente">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <h5 class="modal-title-cliente">Editar Cliente</h5>
                    <p class="modal-subtitle-cliente">Actualiza la información del cliente</p>
                </div>
                <button type="button" class="btn-close-cliente" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form action="{{ route('clientes.update', $cliente->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body-cliente">
                    <div class="form-group-cliente">
                        <label class="form-label-cliente">
                            <i class="bi bi-person"></i>
                            Nombre Completo
                        </label>
                        <input type="text" 
                               name="nombre" 
                               class="form-input-cliente" 
                               value="{{ $cliente->nombre }}"
                               required>
                    </div>

                    <div class="form-group-cliente">
                        <label class="form-label-cliente">
                            <i class="bi bi-telephone"></i>
                            Número de Teléfono
                        </label>
                        <input type="tel" 
                               name="telefono" 
                               class="form-input-cliente" 
                               value="{{ $cliente->telefono }}"
                               pattern="[0-9]{10,20}"
                               required>
                    </div>
                </div>

                <div class="modal-footer-cliente">
                    <button type="button" class="btn-cliente btn-secondary-cliente" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-cliente btn-primary-cliente">
                        <i class="bi bi-check-circle me-2"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

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
.clientes-container {
    background: var(--color-bg);
    min-height: 100vh;
    padding-bottom: 2rem;
}

/* ==================== HEADER ==================== */
.clientes-header {
    background: var(--color-card);
    padding: 2rem 0;
    border-bottom: 1px solid var(--color-border);
    margin-bottom: 2rem;
}

.clientes-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.clientes-title i {
    color: var(--color-primary);
}

.clientes-subtitle {
    color: var(--color-text-secondary);
    margin: 0.5rem 0 0 0;
}

.clientes-stats {
    display: flex;
    justify-content: flex-end;
}

.stat-box {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    padding: 1rem 1.5rem;
    border-radius: var(--radius-md);
    color: white;
    text-align: center;
    box-shadow: var(--shadow-md);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-top: 0.25rem;
}

/* ==================== ALERTAS MODERNAS ==================== */
.alert-modern {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-radius: var(--radius-md);
    margin-bottom: 1.5rem;
    border: 1px solid;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: #d1fae5;
    border-color: var(--color-success);
    color: #065f46;
}

.alert-danger {
    background: #fee2e2;
    border-color: var(--color-danger);
    color: #991b1b;
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.alert-success .alert-icon {
    background: var(--color-success);
    color: white;
}

.alert-danger .alert-icon {
    background: var(--color-danger);
    color: white;
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.alert-message {
    font-size: 0.875rem;
}

.alert-close {
    background: transparent;
    border: none;
    color: currentColor;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.alert-close:hover {
    opacity: 1;
}

/* ==================== CARD CLIENTE ==================== */
.card-cliente {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all 0.3s ease;
}

.card-cliente:hover {
    box-shadow: var(--shadow-md);
}

.sticky-card {
    position: sticky;
    top: 20px;
}

.card-cliente-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.card-header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.card-cliente-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.card-cliente-desc {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0.25rem 0 0 0;
}

.card-cliente-body {
    padding: 1.5rem;
}

.header-actions-cliente {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}

.btn-icon-cliente {
    width: 36px;
    height: 36px;
    border: 1px solid var(--color-border);
    background: var(--color-bg);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon-cliente:hover {
    background: var(--color-card);
    color: var(--color-text);
    border-color: var(--color-text-secondary);
}

/* ==================== FORMULARIO ==================== */
.form-group-cliente {
    margin-bottom: 1.25rem;
}

.form-label-cliente {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.form-label-cliente i {
    color: var(--color-primary);
}

.form-input-cliente {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    font-size: 0.9375rem;
    color: var(--color-text);
    background: var(--color-card);
    transition: all 0.2s;
}

.form-input-cliente:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.invalid-feedback-cliente {
    display: none;
    font-size: 0.875rem;
    color: var(--color-danger);
    margin-top: 0.25rem;
}

.was-validated .form-input-cliente:invalid ~ .invalid-feedback-cliente {
    display: block;
}

.form-actions-cliente {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.btn-cliente {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 1;
}

.btn-primary-cliente {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.btn-primary-cliente:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-secondary-cliente {
    background: var(--color-bg);
    color: var(--color-text-secondary);
    border: 1px solid var(--color-border);
}

.btn-secondary-cliente:hover {
    background: var(--color-card);
    color: var(--color-text);
}

/* ==================== BOTÓN IMPORTAR ==================== */
.btn-import-excel {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    border-radius: var(--radius-md);
    color: white;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-import-excel:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-import-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.btn-import-content {
    flex: 1;
    text-align: left;
}

.btn-import-title {
    font-weight: 600;
    font-size: 1rem;
}

.btn-import-desc {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-top: 0.125rem;
}

.btn-import-arrow {
    font-size: 1.25rem;
}

/* ==================== LISTA DE CLIENTES ==================== */
.clientes-list {
    max-height: 600px;
    overflow-y: auto;
}

.cliente-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--color-border);
    transition: background 0.2s;
}

.cliente-item:hover {
    background: var(--color-bg);
}

.cliente-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
}

.cliente-info {
    flex: 1;
}

.cliente-name {
    font-weight: 600;
    color: var(--color-text);
    font-size: 0.9375rem;
}

.cliente-phone {
    font-size: 0.8125rem;
    color: var(--color-text-secondary);
    margin-top: 0.25rem;
}

.cliente-id {
    font-size: 0.875rem;
    color: var(--color-text-muted);
    font-weight: 600;
}

.cliente-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action-cliente {
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

.btn-action-cliente:hover {
    transform: scale(1.1);
}

.btn-edit:hover {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

.btn-delete:hover {
    background: var(--color-danger);
    color: white;
    border-color: var(--color-danger);
}

/* ==================== EMPTY STATE ==================== */
.empty-state-cliente {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    font-size: 4rem;
    color: var(--color-text-muted);
    margin-bottom: 1rem;
}

.empty-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.empty-desc {
    color: var(--color-text-secondary);
}

/* ==================== MODAL ==================== */
.modal-cliente {
    border: none;
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.modal-header-cliente {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    background: var(--color-bg);
}

.modal-icon-cliente {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.modal-title-cliente {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.modal-subtitle-cliente {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0.25rem 0 0 0;
}

.btn-close-cliente {
    width: 32px;
    height: 32px;
    background: transparent;
    border: none;
    color: var(--color-text-secondary);
    cursor: pointer;
    margin-left: auto;
    transition: all 0.2s;
    border-radius: 6px;
}

.btn-close-cliente:hover {
    background: var(--color-border);
    color: var(--color-text);
}

.modal-body-cliente {
    padding: 1.5rem;
}

.modal-footer-cliente {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    padding: 1.5rem;
    border-top: 1px solid var(--color-border);
}

/* ==================== UPLOAD AREA ==================== */
.upload-area {
    border: 2px dashed var(--color-border);
    border-radius: var(--radius-md);
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.upload-area:hover {
    border-color: var(--color-primary);
    background: rgba(59, 130, 246, 0.05);
}

.upload-icon {
    font-size: 3rem;
    color: var(--color-success);
    margin-bottom: 1rem;
}

.upload-title {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.upload-desc {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
}

.upload-input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-selected {
    margin-top: 1rem;
    font-size: 0.875rem;
    color: var(--color-success);
    font-weight: 600;
}

.alert-info-cliente {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    background: #dbeafe;
    border: 1px solid var(--color-primary);
    border-radius: var(--radius-sm);
    color: #1e40af;
    font-size: 0.875rem;
    margin-top: 1rem;
}

.alert-info-cliente i {
    font-size: 1.25rem;
    flex-shrink: 0;
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 992px) {
    .sticky-card {
        position: relative;
        top: 0;
    }

    .clientes-header .row {
        text-align: center;
    }

    .clientes-stats {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .clientes-title {
        font-size: 1.5rem;
    }

    .form-actions-cliente {
        flex-direction: column;
    }

    .cliente-item {
        flex-wrap: wrap;
    }

    .cliente-id {
        order: -1;
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
// Validación de formularios
(() => {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Función para eliminar cliente
function eliminarCliente(id, nombre) {
    if (confirm(`¿Estás seguro de eliminar al cliente "${nombre}"?`)) {
        document.getElementById(`delete-form-${id}`).submit();
    }
}

// Mostrar nombre de archivo seleccionado
function mostrarArchivo(input) {
    const nombreArchivo = input.files[0]?.name;
    if (nombreArchivo) {
        document.getElementById('nombre-archivo').textContent = nombreArchivo;
        document.getElementById('archivo-seleccionado').style.display = 'flex';
    }
}

// Filtrar clientes por búsqueda
document.getElementById('buscador-clientes').addEventListener('input', function() {
    const valorBusqueda = this.value.toLowerCase();
    const clientes = document.querySelectorAll('.cliente-item');
    let hayResultados = false;

    clientes.forEach(cliente => {
        const nombre = cliente.getAttribute('data-nombre');
        const telefono = cliente.getAttribute('data-telefono');

        if (nombre.includes(valorBusqueda) || telefono.includes(valorBusqueda)) {
            cliente.style.display = 'flex';
            hayResultados = true;
        } else {
            cliente.style.display = 'none';
        }
    });

    document.getElementById('sin-resultados').style.display = hayResultados ? 'none' : 'flex';
    document.getElementById('contador-clientes').textContent = `${Array.from(clientes).filter(cliente => cliente.style.display !== 'none').length} clientes en total`;
});
</script>

@endsection
