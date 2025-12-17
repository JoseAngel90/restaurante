@extends('layouts.app')

@section('title', 'Menú Semanal')

@section('content')
<div class="container-fluid py-4">

    <!-- Encabezado -->
    <div class="text-center mb-4">
        <h1 class="fw-bold text-success mb-2">
            <i class="bi bi-calendar-week"></i> Menú Semanal
        </h1>
        <p class="text-muted">Selecciona y arrastra las comidas para cada día de la semana</p>
        
        <div class="d-flex justify-content-center gap-3 mt-3">
            <a href="{{ route('menu_semanal.exportar_pdf') }}" class="btn btn-danger btn-lg">
                <i class="bi bi-filetype-pdf me-2"></i> Exportar PDF
            </a>
            <button type="button" class="btn btn-success btn-lg" id="btnGuardarTodo">
                <i class="bi bi-save me-2"></i> Guardar Todo
            </button>
        </div>
    </div>

    @php
        use Carbon\Carbon;
        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        $tiposComida = \App\Models\TipoComida::with('comidas')->orderBy('id')->get();
        
        // Obtener todas las comidas seleccionadas de la semana
        $lunesDate = Carbon::now()->startOfWeek();
        $menuSemana = [];
        
        foreach($diasSemana as $index => $dia) {
            $fecha = $lunesDate->copy()->addDays($index)->format('Y-m-d');
            $menuSemana[$fecha] = \App\Models\DisponibilidadComidaDia::where('fecha', $fecha)
                ->with('comida.tipoComida')
                ->get()
                ->groupBy('comida.tipoComida.descripcion');
        }
    @endphp

    <div class="row g-4">
        <!-- Panel Lateral: Comidas Disponibles -->
        <div class="col-lg-3">
            <div class="card shadow-lg border-0 sticky-sidebar">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-basket3-fill me-2"></i>
                        Banco de Comidas
                    </h5>
                </div>
                <div class="card-body p-2" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                    @foreach($tiposComida as $tipo)
                        <div class="categoria-seccion mb-3">
                            <div class="categoria-header">
                                <i class="bi bi-chevron-right categoria-toggle"></i>
                                <span class="fw-bold">{{ $tipo->descripcion }}</span>
                                <span class="badge bg-success">{{ $tipo->comidas->count() }}</span>
                            </div>
                            <div class="categoria-contenido">
                                @foreach($tipo->comidas as $comida)
                                    <div class="comida-chip draggable" 
                                         draggable="true"
                                         data-comida-id="{{ $comida->id }}"
                                         data-comida-nombre="{{ $comida->nombre }}"
                                         data-tipo="{{ $tipo->descripcion }}"
                                         data-disponible="{{ $comida->disponible }}">
                                        <i class="bi bi-grip-vertical me-1"></i>
                                        <div class="comida-info">
                                            <span class="comida-nombre">{{ $comida->nombre }}</span>
                                            <small class="comida-stock">Stock: {{ $comida->disponible }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Panel Principal: Calendario Semanal -->
        <div class="col-lg-9">
            <div class="semana-container">
                @foreach($diasSemana as $index => $dia)
                    @php
                        $fecha = $lunesDate->copy()->addDays($index)->format('Y-m-d');
                        $fechaFormateada = Carbon::parse($fecha)->format('d/m');
                        $esHoy = Carbon::parse($fecha)->isToday();
                    @endphp
                    
                    <div class="dia-card {{ $esHoy ? 'dia-hoy' : '' }}">
                        <div class="dia-header">
                            <div class="dia-info">
                                <h4 class="dia-nombre">{{ $dia }}</h4>
                                <span class="dia-fecha">{{ $fechaFormateada }}</span>
                            </div>
                            @if($esHoy)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-star-fill me-1"></i>Hoy
                                </span>
                            @endif
                        </div>
                        
                        <div class="dia-contenido">
                            @foreach($tiposComida as $tipo)
                                @php
                                    $comidasTipo = $menuSemana[$fecha][$tipo->descripcion] ?? collect();
                                @endphp
                                
                                <div class="categoria-slot">
                                    <div class="slot-label">
                                        <i class="bi bi-egg-fried text-success me-1"></i>
                                        <small class="fw-semibold">{{ $tipo->descripcion }}</small>
                                    </div>
                                    
                                    <div class="drop-zone" 
                                         data-fecha="{{ $fecha }}"
                                         data-tipo="{{ $tipo->descripcion }}">
                                        @if($comidasTipo->isEmpty())
                                            <div class="zona-vacia">
                                                <i class="bi bi-plus-circle"></i>
                                                <small>Arrastra aquí</small>
                                            </div>
                                        @endif
                                        
                                        @foreach($comidasTipo as $item)
                                            <div class="comida-seleccionada"
                                                 data-comida-id="{{ $item->comida->id }}"
                                                 data-fecha="{{ $fecha }}">
                                                <button type="button" class="btn-remove-small" title="Quitar">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-check-circle-fill text-success"></i>
                                                    <div>
                                                        <div class="comida-nombre-small">{{ $item->comida->nombre }}</div>
                                                        <small class="text-muted">Stock: {{ $item->comida->disponible }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Leyenda flotante -->
    <div class="leyenda-flotante">
        <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#leyendaCollapse">
            <i class="bi bi-info-circle me-1"></i> Ayuda
        </button>
        <div class="collapse mt-2" id="leyendaCollapse">
            <div class="card card-body shadow-sm">
                <div class="d-flex flex-column gap-2">
                    <div><i class="bi bi-grip-vertical text-primary me-2"></i><small>Arrastra las comidas</small></div>
                    <div><i class="bi bi-x-lg text-danger me-2"></i><small>Clic para quitar</small></div>
                    <div><i class="bi bi-star-fill text-warning me-2"></i><small>Día actual</small></div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    /* Variables de colores */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-color: #10b981;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --gray-light: #f3f4f6;
        --gray-medium: #d1d5db;
    }

    /* Panel lateral sticky */
    .sticky-sidebar {
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
        display: flex;
        flex-direction: column;
    }

    .sticky-sidebar .card-body {
        flex: 1;
        overflow-y: auto;
    }

    .bg-gradient-primary {
        background: var(--primary-gradient);
    }

    /* Categorías colapsables */
    .categoria-seccion {
        background: white;
        border-radius: 8px;
        padding: 8px;
        margin-bottom: 8px;
        border: 1px solid var(--gray-medium);
    }

    .categoria-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        cursor: pointer;
        border-radius: 4px;
        transition: background 0.2s;
        font-size: 0.875rem;
    }

    .categoria-header:hover {
        background: var(--gray-light);
    }

    .categoria-toggle {
        transition: transform 0.3s;
        flex-shrink: 0;
    }

    .categoria-seccion.abierta .categoria-toggle {
        transform: rotate(90deg);
    }

    .categoria-contenido {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }

    .categoria-seccion.abierta .categoria-contenido {
        max-height: none;
        padding-top: 8px;
    }

    /* Comidas como chips */
    .comida-chip {
        display: flex;
        align-items: center;
        gap: 4px;
        background: white;
        border: 2px solid var(--gray-medium);
        border-radius: 20px;
        padding: 6px 12px;
        margin: 4px 0;
        cursor: move;
        transition: all 0.2s;
        font-size: 0.8rem;
    }

    .comida-chip:hover {
        transform: translateX(4px);
        border-color: var(--success-color);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
    }

    .comida-chip.dragging {
        opacity: 0.5;
        transform: rotate(3deg) scale(0.95);
    }

    .comida-info {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 0;
    }

    .comida-nombre {
        font-size: 0.75rem;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .comida-stock {
        font-size: 0.65rem;
        color: #6b7280;
    }

    /* Contenedor de la semana */
    .semana-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
    }

    /* Tarjeta de día */
    .dia-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: all 0.3s;
        max-height: calc(100vh - 200px);
        display: flex;
        flex-direction: column;
    }

    .dia-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }

    .dia-card.dia-hoy {
        border: 3px solid var(--warning-color);
        box-shadow: 0 0 20px rgba(245, 158, 11, 0.3);
    }

    .dia-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .dia-hoy .dia-header {
        background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%);
    }

    .dia-nombre {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
    }

    .dia-fecha {
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .dia-contenido {
        padding: 12px;
        overflow-y: auto;
        flex: 1;
    }

    /* Slots de categorías */
    .categoria-slot {
        margin-bottom: 12px;
        background: var(--gray-light);
        border-radius: 8px;
        padding: 8px;
    }

    .slot-label {
        display: flex;
        align-items: center;
        margin-bottom: 6px;
        padding: 4px 8px;
        background: white;
        border-radius: 4px;
        font-size: 0.75rem;
    }

    /* Drop zone */
    .drop-zone {
        min-height: 60px;
        border: 2px dashed transparent;
        border-radius: 6px;
        padding: 6px;
        transition: all 0.3s;
    }

    .drop-zone.drag-over {
        border-color: var(--success-color);
        background: rgba(16, 185, 129, 0.1);
    }

    .zona-vacia {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 16px;
        color: #9ca3af;
        text-align: center;
    }

    .zona-vacia i {
        font-size: 1.5rem;
        margin-bottom: 4px;
    }

    /* Comida seleccionada */
    .comida-seleccionada {
        position: relative;
        background: white;
        border: 1px solid var(--success-color);
        border-radius: 6px;
        padding: 8px;
        margin-bottom: 6px;
        animation: slideIn 0.3s ease;
        font-size: 0.8rem;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .comida-nombre-small {
        font-size: 0.8rem;
        font-weight: 600;
        color: #1f2937;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .btn-remove-small {
        position: absolute;
        top: -6px;
        right: -6px;
        background: white;
        border: 2px solid var(--danger-color);
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--danger-color);
        font-size: 0.7rem;
        transition: all 0.2s;
        padding: 0;
        z-index: 10;
    }

    .btn-remove-small:hover {
        transform: scale(1.15);
        background: var(--danger-color);
        color: white;
    }

    /* Leyenda flotante */
    .leyenda-flotante {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }

    /* Scrollbar personalizado */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--gray-light);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--success-color);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #059669;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .semana-container {
            grid-template-columns: 1fr;
        }
        
        .sticky-sidebar {
            position: relative;
            margin-bottom: 20px;
            max-height: none;
        }

        .sticky-sidebar .card-body {
            max-height: 400px;
        }
    }

    @media (max-width: 768px) {
        .dia-nombre {
            font-size: 1rem;
        }
        
        .comida-chip {
            font-size: 0.75rem;
            padding: 4px 8px;
        }

        .categoria-header {
            font-size: 0.8rem;
        }

        .dia-card {
            max-height: calc(100vh - 150px);
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const draggables = document.querySelectorAll('.draggable');
    const dropZones = document.querySelectorAll('.drop-zone');
    
    // Datos para guardar
    let cambios = {};

    // Categorías colapsables
    document.querySelectorAll('.categoria-header').forEach(header => {
        header.addEventListener('click', function() {
            const seccion = this.closest('.categoria-seccion');
            seccion.classList.toggle('abierta');
        });
    });

    // Abrir todas las categorías por defecto
    document.querySelectorAll('.categoria-seccion').forEach(sec => {
        sec.classList.add('abierta');
    });

    // Drag and Drop para comidas disponibles
    draggables.forEach(draggable => {
        draggable.addEventListener('dragstart', function(e) {
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('comida-id', this.dataset.comidaId);
            e.dataTransfer.setData('comida-nombre', this.dataset.comidaNombre);
            e.dataTransfer.setData('tipo', this.dataset.tipo);
            e.dataTransfer.setData('disponible', this.dataset.disponible);
        });

        draggable.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
    });

    // Drop zones
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            this.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });

        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            const comidaId = e.dataTransfer.getData('comida-id');
            const comidaNombre = e.dataTransfer.getData('comida-nombre');
            const tipo = e.dataTransfer.getData('tipo');
            const disponible = e.dataTransfer.getData('disponible');
            
            const fecha = this.dataset.fecha;
            const tipoCelda = this.dataset.tipo;

            // Verificar que sea del mismo tipo
            if (tipo !== tipoCelda) {
                mostrarNotificacion(`Esta comida es de tipo "${tipo}" y no puede ir en "${tipoCelda}"`, 'danger');
                return;
            }

            // Verificar si ya existe
            const yaExiste = this.querySelector(`[data-comida-id="${comidaId}"]`);
            if (yaExiste) {
                mostrarNotificacion('Esta comida ya está seleccionada para este día', 'warning');
                return;
            }

            // Ocultar zona vacía
            const zonaVacia = this.querySelector('.zona-vacia');
            if (zonaVacia) zonaVacia.style.display = 'none';

            // Agregar la comida
            const comidaDiv = document.createElement('div');
            comidaDiv.className = 'comida-seleccionada';
            comidaDiv.dataset.comidaId = comidaId;
            comidaDiv.dataset.fecha = fecha;
            comidaDiv.innerHTML = `
                <button type="button" class="btn-remove-small" title="Quitar">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <div>
                        <div class="comida-nombre-small">${comidaNombre}</div>
                        <small class="text-muted">Stock: ${disponible}</small>
                    </div>
                </div>
            `;

            // Agregar evento para quitar
            comidaDiv.querySelector('.btn-remove-small').addEventListener('click', function() {
                registrarCambio(fecha, comidaId, 'quitar');
                comidaDiv.remove();
                
                // Mostrar zona vacía si no hay comidas
                if (zone.querySelectorAll('.comida-seleccionada').length === 0) {
                    if (zonaVacia) zonaVacia.style.display = 'flex';
                }
                
                mostrarNotificacion('Comida eliminada', 'success');
            });

            this.appendChild(comidaDiv);
            
            // Registrar cambio
            registrarCambio(fecha, comidaId, 'agregar');
            mostrarNotificacion('Comida agregada correctamente', 'success');
        });
    });

    // Eventos para quitar comidas existentes
    document.querySelectorAll('.btn-remove-small').forEach(btn => {
        btn.addEventListener('click', function() {
            const comidaDiv = this.closest('.comida-seleccionada');
            const comidaId = comidaDiv.dataset.comidaId;
            const fecha = comidaDiv.dataset.fecha;
            
            registrarCambio(fecha, comidaId, 'quitar');
            comidaDiv.remove();
            
            // Mostrar zona vacía si no hay comidas
            const zone = this.closest('.drop-zone');
            if (zone.querySelectorAll('.comida-seleccionada').length === 0) {
                const zonaVacia = zone.querySelector('.zona-vacia');
                if (zonaVacia) zonaVacia.style.display = 'flex';
            }
            
            mostrarNotificacion('Comida eliminada', 'success');
        });
    });

    // Registrar cambios
    function registrarCambio(fecha, comidaId, accion) {
        if (!cambios[fecha]) {
            cambios[fecha] = { agregar: [], quitar: [] };
        }

        if (accion === 'agregar') {
            const indexQuitar = cambios[fecha].quitar.indexOf(comidaId);
            if (indexQuitar > -1) {
                cambios[fecha].quitar.splice(indexQuitar, 1);
            } else {
                cambios[fecha].agregar.push(comidaId);
            }
        } else if (accion === 'quitar') {
            const indexAgregar = cambios[fecha].agregar.indexOf(comidaId);
            if (indexAgregar > -1) {
                cambios[fecha].agregar.splice(indexAgregar, 1);
            } else {
                cambios[fecha].quitar.push(comidaId);
            }
        }

        console.log('Cambios registrados:', cambios);
    }

    // Mostrar notificaciones
    function mostrarNotificacion(mensaje, tipo) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => alertDiv.remove(), 3000);
    }

    // Guardar todos los cambios
    document.getElementById('btnGuardarTodo').addEventListener('click', function() {
        if (Object.keys(cambios).length === 0) {
            mostrarNotificacion('No hay cambios para guardar', 'info');
            return;
        }

        if (!confirm('¿Guardar todos los cambios del menú semanal?')) {
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Guardando...';

        // Enviar cambios al servidor
        fetch('{{ route("menu_semanal.guardar_bulk") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ cambios: cambios })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('✅ Menú guardado correctamente', 'success');
                cambios = {};
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarNotificacion('❌ Error al guardar: ' + data.message, 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save me-2"></i> Guardar Todo';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('❌ Error de conexión', 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-2"></i> Guardar Todo';
        });
    });
});
</script>

@endsection
