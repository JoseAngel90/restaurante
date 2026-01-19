@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')

<div class="usuarios-container">
    
    <!-- Header -->
    <div class="usuarios-header">
        <div>
            <h1 class="usuarios-title">
                <i class="bi bi-people-fill"></i>
                Gestión de Usuarios
            </h1>
            <p class="usuarios-subtitle">Administra y controla el acceso de usuarios al sistema</p>
        </div>
        <div class="usuarios-stats">
            <div class="stat-item">
                <i class="bi bi-person-check-fill"></i>
                <div>
                    <span class="stat-value">{{ App\Models\Usuario::where('activo', true)->count() }}</span>
                    <span class="stat-label">Activos</span>
                </div>
            </div>
            <div class="stat-item">
                <i class="bi bi-person-x-fill"></i>
                <div>
                    <span class="stat-value">{{ App\Models\Usuario::where('activo', false)->count() }}</span>
                    <span class="stat-label">Inactivos</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row g-4">

            <!-- ==================== FORMULARIO NUEVO USUARIO ==================== -->
            <div class="col-lg-4">
                <div class="card-modern sticky-card">
                    <div class="card-modern-header">
                        <div class="card-icon">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <div>
                            <h3 class="card-modern-title">Nuevo Usuario</h3>
                            <p class="card-modern-desc">Registra un nuevo usuario en el sistema</p>
                        </div>
                    </div>

                    <div class="card-modern-body">
                        <form action="{{ url('/registro') }}" method="POST">
                            @csrf

                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    <i class="bi bi-person"></i>
                                    Nombre Completo
                                </label>
                                <input type="text" 
                                       name="nombre" 
                                       class="form-input-modern" 
                                       placeholder="Ej: Juan Pérez" 
                                       required
                                       value="{{ old('nombre') }}">
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    <i class="bi bi-envelope"></i>
                                    Correo Electrónico
                                </label>
                                <input type="email" 
                                       name="email" 
                                       class="form-input-modern" 
                                       placeholder="usuario@ejemplo.com" 
                                       required
                                       pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                                       value="{{ old('email') }}">
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    <i class="bi bi-key"></i>
                                    Contraseña
                                </label>
                                <div class="input-group-modern">
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           class="form-input-modern" 
                                           placeholder="••••••••" 
                                           required>
                                    <button type="button" 
                                            class="btn-input-addon" 
                                            onclick="generarContrasena()"
                                            title="Generar contraseña">
                                        <i class="bi bi-shuffle"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    <i class="bi bi-shield-check"></i>
                                    Rol del Usuario
                                </label>
                                <select name="id_rol" class="form-select-modern" required>
                                    <option value="">Selecciona un rol</option>
                                    @foreach(App\Models\Rol::all() as $rol)
                                        <option value="{{ $rol->id }}" {{ old('id_rol') == $rol->id ? 'selected' : '' }}>
                                            {{ $rol->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn-modern btn-primary-modern">
                                <i class="bi bi-check-circle me-2"></i>
                                Registrar Usuario
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ==================== LISTA DE USUARIOS ==================== -->
            <div class="col-lg-8">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <div class="card-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="card-modern-title">Usuarios Registrados</h3>
                            <p class="card-modern-desc">{{ App\Models\Usuario::count() }} usuarios en total</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn-icon-modern" title="Filtrar">
                                <i class="bi bi-funnel"></i>
                            </button>
                            <button class="btn-icon-modern" title="Buscar">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-modern-body p-0">
                        <div class="table-container">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>
                                            <div class="th-content">
                                                <input type="checkbox" class="checkbox-modern">
                                            </div>
                                        </th>
                                        <th>
                                            <div class="th-content">
                                                Usuario
                                                <i class="bi bi-chevron-expand"></i>
                                            </div>
                                        </th>
                                        <th>
                                            <div class="th-content">
                                                Rol
                                                <i class="bi bi-chevron-expand"></i>
                                            </div>
                                        </th>
                                        <th>
                                            <div class="th-content">
                                                Estado
                                                <i class="bi bi-chevron-expand"></i>
                                            </div>
                                        </th>
                                        <th>
                                            <div class="th-content">
                                                Acciones
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(App\Models\Usuario::with('rol')->get() as $usuario)
                                        <tr id="usuario-{{ $usuario->id }}" class="table-row-modern">
                                            <td>
                                                <input type="checkbox" class="checkbox-modern">
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        {{ strtoupper(substr($usuario->nombre, 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <div class="user-name">{{ $usuario->nombre }}</div>
                                                        <div class="user-email">{{ $usuario->email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge-modern badge-role">
                                                    <i class="bi bi-shield-check me-1"></i>
                                                    {{ $usuario->rol->nombre ?? 'Sin rol' }}
                                                </span>
                                            </td>
                                            <td class="estado">
                                                @if($usuario->activo)
                                                    <span class="badge-modern badge-success">
                                                        <i class="bi bi-check-circle-fill"></i>
                                                        Activo
                                                    </span>
                                                @else
                                                    <span class="badge-modern badge-danger">
                                                        <i class="bi bi-x-circle-fill"></i>
                                                        Inactivo
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-action toggle-btn {{ $usuario->activo ? 'active' : '' }}"
                                                            onclick="toggleUsuario({{ $usuario->id }})"
                                                            title="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}">
                                                        <i class="bi {{ $usuario->activo ? 'bi-toggle2-on' : 'bi-toggle2-off' }}"></i>
                                                    </button>
                                                    <button class="btn-action btn-edit"
                                                            onclick="abrirModalEditar({{ $usuario->id }}, '{{ $usuario->nombre }}', '{{ $usuario->email }}', '{{ $usuario->rol->id ?? '' }}')"
                                                            title="Editar">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button class="btn-action btn-delete"
                                                            onclick="eliminarUsuario({{ $usuario->id }})"
                                                            title="Eliminar">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </div>
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

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-modern">
            <div class="modal-header-modern">
                <div class="modal-icon">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <h5 class="modal-title-modern">Editar Usuario</h5>
                    <p class="modal-subtitle-modern">Actualiza la información del usuario</p>
                </div>
                <button type="button" class="btn-close-modern" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-body-modern">
                <form id="formEditarUsuario">
                    @csrf
                    <input type="hidden" id="editId" name="id">

                    <div class="form-group-modern">
                        <label class="form-label-modern">
                            <i class="bi bi-person"></i>
                            Nombre Completo
                        </label>
                        <input type="text" id="editNombre" name="nombre" class="form-input-modern" required>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">
                            <i class="bi bi-envelope"></i>
                            Correo Electrónico
                        </label>
                        <input type="email" id="editEmail" name="email" class="form-input-modern" required>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">
                            <i class="bi bi-shield-check"></i>
                            Rol del Usuario
                        </label>
                        <select id="editRol" name="id_rol" class="form-select-modern" required>
                            <option value="">Selecciona un rol</option>
                            @foreach(App\Models\Rol::all() as $rol)
                                <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="modal-footer-modern">
                        <button type="button" class="btn-modern btn-secondary-modern" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-modern btn-primary-modern">
                            <i class="bi bi-check-circle me-2"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
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
    --color-danger: #ef4444;
    --color-warning: #f59e0b;
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

/* ==================== CONTENEDOR PRINCIPAL ==================== */
.usuarios-container {
    background: var(--color-bg);
    min-height: 100vh;
    padding-bottom: 2rem;
}

/* ==================== HEADER ==================== */
.usuarios-header {
    background: var(--color-card);
    padding: 2rem;
    border-bottom: 1px solid var(--color-border);
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.usuarios-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.usuarios-title i {
    color: var(--color-primary);
}

.usuarios-subtitle {
    color: var(--color-text-secondary);
    margin: 0.5rem 0 0 0;
    font-size: 0.9375rem;
}

.usuarios-stats {
    display: flex;
    gap: 1.5rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    background: var(--color-bg);
    border-radius: var(--radius-md);
}

.stat-item i {
    font-size: 1.5rem;
    color: var(--color-primary);
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: var(--color-text-secondary);
    margin-top: 0.25rem;
}

/* ==================== CARD MODERNA ==================== */
.card-modern {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all 0.3s ease;
}

.card-modern:hover {
    box-shadow: var(--shadow-md);
}

.sticky-card {
    position: sticky;
    top: 20px;
}

.card-modern-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.card-icon {
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

.card-modern-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.card-modern-desc {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0.25rem 0 0 0;
}

.card-modern-body {
    padding: 1.5rem;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}

.btn-icon-modern {
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

.btn-icon-modern:hover {
    background: var(--color-card);
    color: var(--color-text);
    border-color: var(--color-text-secondary);
}

/* ==================== FORMULARIOS MODERNOS ==================== */
.form-group-modern {
    margin-bottom: 1.25rem;
}

.form-label-modern {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.form-label-modern i {
    color: var(--color-primary);
}

.form-input-modern,
.form-select-modern {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    font-size: 0.9375rem;
    color: var(--color-text);
    background: var(--color-card);
    transition: all 0.2s;
}

.form-input-modern:focus,
.form-select-modern:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.input-group-modern {
    display: flex;
    gap: 0.5rem;
}

.btn-input-addon {
    padding: 0.75rem 1rem;
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all 0.2s;
}

.btn-input-addon:hover {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

/* ==================== BOTONES MODERNOS ==================== */
.btn-modern {
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
    gap: 0.5rem;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    width: 100%;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-secondary-modern {
    background: var(--color-bg);
    color: var(--color-text-secondary);
    border: 1px solid var(--color-border);
}

.btn-secondary-modern:hover {
    background: var(--color-card);
    color: var(--color-text);
}

/* ==================== TABLA MODERNA ==================== */
.table-container {
    overflow-x: auto;
}

.table-modern {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table-modern thead {
    background: var(--color-bg);
}

.table-modern th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--color-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--color-border);
}

.th-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.th-content i {
    font-size: 0.75rem;
    color: var(--color-text-muted);
}

.table-row-modern {
    border-bottom: 1px solid var(--color-border);
    transition: background 0.2s;
}

.table-row-modern:hover {
    background: var(--color-bg);
}

.table-modern td {
    padding: 1rem 1.5rem;
}

/* ==================== USUARIO INFO ==================== */
.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.user-name {
    font-weight: 600;
    color: var(--color-text);
    font-size: 0.9375rem;
}

.user-email {
    font-size: 0.8125rem;
    color: var(--color-text-secondary);
    margin-top: 0.125rem;
}

/* ==================== BADGES MODERNOS ==================== */
.badge-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
}

.badge-role {
    background: #ede9fe;
    color: #7c3aed;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

/* ==================== BOTONES DE ACCIÓN ==================== */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
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
    font-size: 1rem;
}

.btn-action:hover {
    transform: scale(1.1);
}

.btn-action.toggle-btn:hover {
    background: var(--color-success);
    color: white;
    border-color: var(--color-success);
}

.btn-action.toggle-btn.active {
    background: #d1fae5;
    color: var(--color-success);
    border-color: var(--color-success);
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

/* ==================== CHECKBOX MODERNO ==================== */
.checkbox-modern {
    width: 18px;
    height: 18px;
    border: 2px solid var(--color-border);
    border-radius: 4px;
    cursor: pointer;
    accent-color: var(--color-primary);
}

/* ==================== MODAL MODERNO ==================== */
.modal-modern {
    border: none;
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.modal-header-modern {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    background: var(--color-bg);
}

.modal-icon {
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

.modal-title-modern {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.modal-subtitle-modern {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0.25rem 0 0 0;
}

.btn-close-modern {
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

.btn-close-modern:hover {
    background: var(--color-border);
    color: var(--color-text);
}

.modal-body-modern {
    padding: 1.5rem;
}

.modal-footer-modern {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 992px) {
    .usuarios-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .usuarios-stats {
        width: 100%;
        justify-content: space-between;
    }

    .sticky-card {
        position: relative;
        top: 0;
    }
}

@media (max-width: 768px) {
    .usuarios-title {
        font-size: 1.5rem;
    }

    .card-modern-header {
        flex-direction: column;
    }

    .header-actions {
        margin-left: 0;
    }

    .table-modern th,
    .table-modern td {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }

    .user-info {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
function toggleUsuario(id) {
    fetch(`/usuarios/toggle/${id}`, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '{{ csrf_token() }}', 
            'Accept': 'application/json' 
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`usuario-${id}`);
            const estadoCell = row.querySelector('.estado');
            const btn = row.querySelector('.toggle-btn');
            const icon = btn.querySelector('i');
            
            if (data.activo) {
                estadoCell.innerHTML = `
                    <span class="badge-modern badge-success">
                        <i class="bi bi-check-circle-fill"></i>
                        Activo
                    </span>
                `;
                btn.classList.add('active');
                icon.className = 'bi bi-toggle2-on';
            } else {
                estadoCell.innerHTML = `
                    <span class="badge-modern badge-danger">
                        <i class="bi bi-x-circle-fill"></i>
                        Inactivo
                    </span>
                `;
                btn.classList.remove('active');
                icon.className = 'bi bi-toggle2-off';
            }
        } else {
            alert('Error al actualizar el estado.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión.');
    });
}

function eliminarUsuario(id) {
    if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) return;
    
    fetch(`/usuarios/eliminar/${id}`, {
        method: 'DELETE',
        headers: { 
            'X-CSRF-TOKEN': '{{ csrf_token() }}', 
            'Accept': 'application/json' 
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`usuario-${id}`);
            row.style.transition = 'opacity 0.4s, transform 0.4s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            setTimeout(() => row.remove(), 400);
        } else {
            alert('No se pudo eliminar el usuario.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión.');
    });
}

function abrirModalEditar(id, nombre, email, rol) {
    document.getElementById('editId').value = id;
    document.getElementById('editNombre').value = nombre;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRol').value = rol;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
    modal.show();
}

document.getElementById('formEditarUsuario').addEventListener('submit', e => {
    e.preventDefault();
    
    const id = document.getElementById('editId').value;
    const data = {
        nombre: document.getElementById('editNombre').value,
        email: document.getElementById('editEmail').value,
        id_rol: document.getElementById('editRol').value,
        _token: '{{ csrf_token() }}'
    };
    
    fetch(`/usuarios/editar/${id}`, {
        method: 'PUT',
        headers: { 
            'Content-Type': 'application/json', 
            'Accept': 'application/json' 
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`usuario-${id}`);
            
            // Actualizar nombre
            row.querySelector('.user-name').textContent = data.usuario.nombre;
            // Actualizar email
            row.querySelector('.user-email').textContent = data.usuario.email;
            // Actualizar rol
            row.querySelector('.badge-role').innerHTML = `
                <i class="bi bi-shield-check me-1"></i>
                ${data.usuario.rol}
            `;
            
            bootstrap.Modal.getInstance(document.getElementById('modalEditarUsuario')).hide();
        } else {
            alert('Error al guardar los cambios.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión.');
    });
});

function generarContrasena(longitud = 12) {
    const caracteres = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+";
    let contrasena = "";
    
    for (let i = 0; i < longitud; i++) {
        contrasena += caracteres.charAt(Math.floor(Math.random() * caracteres.length));
    }
    
    const passwordInput = document.getElementById("password");
    passwordInput.value = contrasena;
    passwordInput.type = "text";
    
    // Volver a ocultar después de 3 segundos
    setTimeout(() => passwordInput.type = "password", 3000);
}
</script>

@endsection
