@extends('layouts.app')

@section('title', 'Gestión de Comidas')

@section('content')
<div class="container-fluid py-4">

    <!-- Encabezado -->
    <div class="text-center mb-5">
        <h1 class="fw-bold text-primary mb-2">
            <i class="bi bi-basket3-fill"></i> Gestión de Comidas
        </h1>
        <p class="text-muted">Administra categorías, subcategorías y comidas de forma sencilla</p>
    </div>

    <!-- Tabs de Navegación -->
    <ul class="nav nav-pills nav-fill mb-4 shadow-sm" id="menuTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="comidas-tab" data-bs-toggle="tab" data-bs-target="#comidas" type="button">
                <i class="bi bi-basket-fill me-2"></i>Comidas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="categorias-tab" data-bs-toggle="tab" data-bs-target="#categorias" type="button">
                <i class="bi bi-list-ul me-2"></i>Categorías
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="subcategorias-tab" data-bs-toggle="tab" data-bs-target="#subcategorias" type="button">
                <i class="bi bi-diagram-3-fill me-2"></i>Subcategorías
            </button>
        </li>
    </ul>

    <!-- Contenido de los Tabs -->
    <div class="tab-content" id="menuTabsContent">

        <!-- ==================== TAB: COMIDAS ==================== -->
        <div class="tab-pane fade show active" id="comidas" role="tabpanel">
            <div class="row g-4">
                
                <!-- Formulario Agregar Comida -->
                <div class="col-lg-4">
                    <div class="card shadow border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-gradient-success text-white">
                            <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nueva Comida</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ url('/comidas') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nombre</label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Ensalada César" required>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">Abreviatura</label>
                                        <input type="text" name="abreviatura_op" class="form-control" placeholder="ENS" maxlength="10" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold">Precio</label>
                                        <input type="number" name="precio" class="form-control" placeholder="120.50" step="0.01" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Stock Disponible</label>
                                    <input type="number" name="disponible" class="form-control" value="0" min="0" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Subcategoría</label>
                                    <select name="id_subtipo_comida" class="form-select" required>
                                        <option value="">-- Seleccionar --</option>
                                        @foreach(App\Models\TipoComida::with('subtipos')->get() as $tipo)
                                            @if($tipo->subtipos->count() > 0)
                                                <optgroup label="🍽 {{ $tipo->descripcion }}">
                                                    @foreach($tipo->subtipos as $sub)
                                                        <option value="{{ $sub->id }}">{{ $sub->descripcion }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Imagen</label>
                                    <input type="file" name="imagen" class="form-control" accept="image/*" onchange="previewNewImage(this)">
                                    <div id="preview-new-container" class="mt-2 text-center d-none">
                                        <img id="preview-new" class="rounded shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-check-circle me-2"></i>Agregar Comida
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lista de Comidas -->
                <div class="col-lg-8">
                    @php
                        $comidas = App\Models\Comida::with('subtipoComida.tipoComida')->paginate(10);
                    @endphp

                    <div class="row g-3">
                        @foreach($comidas as $comida)
                            <div class="col-md-6">
                                <div class="card comida-card h-100 shadow-sm border-0">
                                    <div class="card-body">
                                        <div class="d-flex gap-3">
                                            <!-- Imagen -->
                                            <div class="comida-imagen-container">
                                                @if($comida->imagen)
                                                    <img src="{{ asset('storage/' . $comida->imagen) }}" alt="{{ $comida->nombre }}" class="comida-imagen">
                                                @else
                                                    <div class="comida-imagen-placeholder">
                                                        <i class="bi bi-image fs-1"></i>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Información -->
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="mb-1 fw-bold">{{ $comida->nombre }}</h6>
                                                        <span class="badge bg-primary">{{ $comida->abreviatura_op }}</span>
                                                    </div>
                                                    <span class="precio-badge">${{ number_format($comida->precio, 2) }}</span>
                                                </div>

                                                <div class="info-pills mb-2">
                                                    <span class="info-pill">
                                                        <i class="bi bi-tag-fill me-1"></i>
                                                        {{ $comida->subtipoComida->tipoComida->descripcion ?? 'Sin categoría' }}
                                                    </span>
                                                    <span class="info-pill">
                                                        <i class="bi bi-diagram-3-fill me-1"></i>
                                                        {{ $comida->subtipoComida->descripcion ?? 'Sin subcategoría' }}
                                                    </span>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="stock-badge {{ $comida->disponible > 10 ? 'stock-ok' : ($comida->disponible > 0 ? 'stock-bajo' : 'stock-agotado') }}">
                                                        <i class="bi bi-box-seam me-1"></i>
                                                        Stock: {{ $comida->disponible }}
                                                    </span>

                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editComidaModal{{ $comida->id }}" title="Editar">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" onclick="eliminarComida({{ $comida->id }})" title="Eliminar">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Editar Comida -->
                            <div class="modal fade" id="editComidaModal{{ $comida->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form action="{{ url('/comidas/'.$comida->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title">Editar Comida</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Nombre</label>
                                                    <input type="text" name="nombre" class="form-control" value="{{ $comida->nombre }}" required>
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold">Abreviatura</label>
                                                        <input type="text" name="abreviatura_op" class="form-control" value="{{ $comida->abreviatura_op }}" maxlength="10" required>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold">Precio</label>
                                                        <input type="number" name="precio" class="form-control" value="{{ $comida->precio }}" step="0.01" required>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Stock</label>
                                                    <input type="number" name="disponible" class="form-control" value="{{ $comida->disponible }}" min="0" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Subcategoría</label>
                                                    <select name="id_subtipo_comida" class="form-select" required>
                                                        @foreach(App\Models\SubtipoComida::with('tipoComida')->get() as $subtipo)
                                                            <option value="{{ $subtipo->id }}" {{ $comida->id_subtipo_comida == $subtipo->id ? 'selected' : '' }}>
                                                                {{ $subtipo->tipoComida->descripcion ?? 'Sin tipo' }} → {{ $subtipo->descripcion }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Imagen</label>
                                                    <input type="file" name="imagen" class="form-control" accept="image/*" onchange="previewEditImage{{ $comida->id }}(this)">
                                                    <div class="mt-2 text-center">
                                                        @if($comida->imagen)
                                                            <img id="preview-edit-{{ $comida->id }}" src="{{ asset('storage/' . $comida->imagen) }}" class="rounded shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                                                        @else
                                                            <img id="preview-edit-{{ $comida->id }}" class="rounded shadow-sm d-none" style="width: 120px; height: 120px; object-fit: cover;">
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-success">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <form id="eliminar-comida-{{ $comida->id }}" action="{{ url('/comidas/'.$comida->id) }}" method="POST" style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>

                            <script>
                            function previewEditImage{{ $comida->id }}(input) {
                                const preview = document.getElementById('preview-edit-{{ $comida->id }}');
                                if (input.files && input.files[0]) {
                                    const reader = new FileReader();
                                    reader.onload = e => {
                                        preview.src = e.target.result;
                                        preview.classList.remove('d-none');
                                    };
                                    reader.readAsDataURL(input.files[0]);
                                }
                            }
                            </script>
                        @endforeach
                    </div>

                    <!-- Paginación -->
                    <div class="mt-4">
                        {{ $comidas->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB: CATEGORÍAS ==================== -->
        <div class="tab-pane fade" id="categorias" role="tabpanel">
            <div class="row g-4">
                
                <!-- Formulario Agregar Categoría -->
                <div class="col-lg-4">
                    <div class="card shadow border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nueva Categoría</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ url('/tipo_comida') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Descripción</label>
                                    <input type="text" name="descripcion" class="form-control" placeholder="Ej: Ensaladas, Sopas" maxlength="100" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle me-2"></i>Agregar Categoría
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lista de Categorías -->
                <div class="col-lg-8">
                    <div class="row g-3">
                        @foreach(App\Models\TipoComida::with('subtipos')->get() as $tipo)
                            <div class="col-md-6">
                                <div class="card categoria-card shadow-sm border-0 h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="mb-1 fw-bold">{{ $tipo->descripcion }}</h5>
                                                <span class="badge bg-info">{{ $tipo->subtipos->count() }} subcategorías</span>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTipoModal{{ $tipo->id }}" title="Editar">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#verSubtiposModal{{ $tipo->id }}" title="Ver subcategorías">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="eliminarTipo({{ $tipo->id }})" title="Eliminar">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        </div>

                                        @if($tipo->subtipos->count() > 0)
                                            <div class="subcategorias-preview">
                                                @foreach($tipo->subtipos->take(3) as $sub)
                                                    <span class="badge bg-light text-dark">{{ $sub->descripcion }}</span>
                                                @endforeach
                                                @if($tipo->subtipos->count() > 3)
                                                    <span class="badge bg-secondary">+{{ $tipo->subtipos->count() - 3 }} más</span>
                                                @endif
                                            </div>
                                        @else
                                            <p class="text-muted small mb-0">Sin subcategorías</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Editar Categoría -->
                            <div class="modal fade" id="editTipoModal{{ $tipo->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form action="{{ url('/tipo_comida/'.$tipo->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Editar Categoría</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Descripción</label>
                                                    <input type="text" name="descripcion" class="form-control" value="{{ $tipo->descripcion }}" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Ver Subcategorías -->
                            <div class="modal fade" id="verSubtiposModal{{ $tipo->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title">Subcategorías de {{ $tipo->descripcion }}</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            @if($tipo->subtipos->count() > 0)
                                                <div class="list-group">
                                                    @foreach($tipo->subtipos as $sub)
                                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                                            <span>{{ $sub->descripcion }}</span>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarSubtipo({{ $sub->id }})">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                            <form id="eliminar-subtipo-{{ $sub->id }}" action="{{ url('/subtipo_comida/'.$sub->id) }}" method="POST" style="display:none;">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-muted text-center">No hay subcategorías registradas.</p>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form id="eliminar-tipo-{{ $tipo->id }}" action="{{ url('/tipo_comida/'.$tipo->id) }}" method="POST" style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB: SUBCATEGORÍAS ==================== -->
        <div class="tab-pane fade" id="subcategorias" role="tabpanel">
            <div class="row g-4">
                
                <!-- Formulario Agregar Subcategoría -->
                <div class="col-lg-4">
                    <div class="card shadow border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-gradient-info text-white">
                            <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nueva Subcategoría</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ url('/subtipo_comida') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Categoría Principal</label>
                                    <select name="id_tipo_comida" class="form-select" required>
                                        <option value="">-- Seleccionar --</option>
                                        @foreach(App\Models\TipoComida::all() as $tipo)
                                            <option value="{{ $tipo->id }}">{{ $tipo->descripcion }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nombre de Subcategoría</label>
                                    <input type="text" name="descripcion" class="form-control" placeholder="Ej: Res, Pollo, Pastas" maxlength="100" required>
                                </div>
                                <button type="submit" class="btn btn-info w-100 text-white">
                                    <i class="bi bi-check-circle me-2"></i>Agregar Subcategoría
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lista de Subcategorías Agrupadas -->
                <div class="col-lg-8">
                    @foreach(App\Models\TipoComida::with('subtipos')->get() as $tipo)
                        @if($tipo->subtipos->count() > 0)
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">
                                    <i class="bi bi-tag-fill me-2"></i>{{ $tipo->descripcion }}
                                </h5>
                                <div class="row g-3">
                                    @foreach($tipo->subtipos as $sub)
                                        <div class="col-md-6">
                                            <div class="card subcategoria-card shadow-sm border-0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-0 fw-bold">{{ $sub->descripcion }}</h6>
                                                            <small class="text-muted">{{ $tipo->descripcion }}</small>
                                                        </div>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarSubtipo({{ $sub->id }})">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    /* Tabs personalizados */
    .nav-pills .nav-link {
        border-radius: 12px;
        font-weight: 600;
        padding: 12px 20px;
        transition: all 0.3s;
        background: white;
        color: #6c757d;
        border: 2px solid transparent;
    }

    .nav-pills .nav-link:hover {
        background: #f8f9fa;
        transform: translateY(-2px);
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
    }

    /* Gradientes personalizados */
    .bg-gradient-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    }

    /* Cards de comida */
    .comida-card {
        transition: all 0.3s;
        border-radius: 12px;
        overflow: hidden;
    }

    .comida-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
    }

    .comida-imagen-container {
        width: 80px;
        height: 80px;
        flex-shrink: 0;
    }

    .comida-imagen {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }

    .comida-imagen-placeholder {
        width: 100%;
        height: 100%;
        background: #f3f4f6;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
    }

    .precio-badge {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .info-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .info-pill {
        background: #f3f4f6;
        color: #4b5563;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .stock-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .stock-ok {
        background: #d1fae5;
        color: #065f46;
    }

    .stock-bajo {
        background: #fef3c7;
        color: #92400e;
    }

    .stock-agotado {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Cards de categoría */
    .categoria-card,
    .subcategoria-card {
        transition: all 0.3s;
        border-radius: 12px;
    }

    .categoria-card:hover,
    .subcategoria-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.12) !important;
    }

    .subcategorias-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
    }

    /* Sticky sidebar */
    @media (min-width: 992px) {
        .sticky-top {
            position: sticky;
            top: 20px;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .comida-imagen-container {
            width: 60px;
            height: 60px;
        }

        .nav-pills .nav-link {
            font-size: 0.85rem;
            padding: 8px 12px;
        }
    }
</style>

@push('scripts')
<script>
function eliminarTipo(id) {
    if(confirm('¿Eliminar esta categoría? Se eliminarán todas sus subcategorías y comidas asociadas.')) {
        document.getElementById('eliminar-tipo-' + id).submit();
    }
}

function eliminarComida(id) {
    if(confirm('¿Eliminar esta comida?')) {
        document.getElementById('eliminar-comida-' + id).submit();
    }
}

function eliminarSubtipo(id) {
    if(confirm('¿Eliminar esta subcategoría? Las comidas asociadas también se eliminarán.')) {
        document.getElementById('eliminar-subtipo-' + id).submit();
    }
}

function previewNewImage(input) {
    const container = document.getElementById('preview-new-container');
    const preview = document.getElementById('preview-new');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            container.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush

@endsection
