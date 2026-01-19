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
        
        // Obtener todas las comidas seleccionadas de 3 semanas
        $lunesDate = Carbon::now()->startOfWeek();
        $menuSemanas = [];
        
        // Generar menú para 3 semanas
        for ($semana = 0; $semana < 3; $semana++) {
            $menuSemanas[$semana] = [];
            $lunesSemana = $lunesDate->copy()->addWeeks($semana);
            
            foreach($diasSemana as $index => $dia) {
                $fecha = $lunesSemana->copy()->addDays($index)->format('Y-m-d');
                $menuSemanas[$semana][$fecha] = \App\Models\DisponibilidadComidaDia::where('fecha', $fecha)
                    ->with('comida.tipoComida')
                    ->get()
                    ->groupBy('comida.tipoComida.descripcion');
            }
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
                <div class="card-body p-3">
                    <!-- Buscador de Comidas -->
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-primary text-white border-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" id="buscadorBancoComidas" class="form-control" placeholder="Buscar comidas..." onkeyup="filtrarBancoComidas()">
                        </div>
                    </div>
                </div>
                <div class="card-body p-2" style="max-height: calc(100vh - 300px); overflow-y: auto;" id="contenedorBancoComidas">
                    @foreach($tiposComida as $tipo)
                        <div class="categoria-seccion mb-3 tipo-comida-banco" data-tipo-nombre="{{ strtolower($tipo->descripcion) }}">
                            <div class="categoria-header">
                                <i class="bi bi-chevron-right categoria-toggle"></i>
                                <span class="fw-bold">{{ $tipo->descripcion }}</span>
                                <span class="badge bg-success">{{ $tipo->comidas->count() }}</span>
                            </div>
                            <div class="categoria-contenido">
                                @foreach($tipo->comidas as $comida)
                                    <div class="comida-chip draggable comida-banco-item" 
                                         draggable="true"
                                         data-comida-id="{{ $comida->id }}"
                                         data-comida-nombre="{{ strtolower($comida->nombre) }}"
                                         data-tipo="{{ $tipo->descripcion }}"
                                         data-disponible="{{ $comida->disponible }}">
                                        <input type="checkbox" class="checkbox-comida" style="cursor: pointer; margin-right: 4px;">
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
            @for($semana = 0; $semana < 3; $semana++)
                @php
                    $lunesSemana = $lunesDate->copy()->addWeeks($semana);
                    $fechaInicio = $lunesSemana->format('d/m/Y');
                    $fechaFin = $lunesSemana->copy()->addDays(4)->format('d/m/Y');
                    $esSemanActual = $semana === 0;
                @endphp
                
                <div class="mb-5">
                    <div class="semana-titulo">
                        <h3 class="mb-0">
                            <i class="bi bi-calendar-week me-2"></i>
                            Semana
                            <small class="text-muted">({{ $fechaInicio }} - {{ $fechaFin }})</small>
                            @if($esSemanActual)
                                <span class="badge bg-warning text-dark ms-2">Semana Actual</span>
                            @endif
                        </h3>
                    </div>
                    
                    <div class="semana-container">
                        @foreach($diasSemana as $index => $dia)
                            @php
                                $fecha = $lunesSemana->copy()->addDays($index)->format('Y-m-d');
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
                                            $comidasTipo = $menuSemanas[$semana][$fecha][$tipo->descripcion] ?? collect();
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
            @endfor
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

    .comida-chip.seleccionada {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border-color: #059669;
    }

    .comida-chip.seleccionada .comida-nombre,
    .comida-chip.seleccionada .comida-stock {
        color: white;
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
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        overflow-x: auto;
    }

    .semana-titulo {
        padding: 20px 0 16px 0;
        border-bottom: 3px solid #10b981;
        margin-bottom: 16px;
    }

    .semana-titulo h3 {
        color: #1f2937;
        font-size: 1.5rem;
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
        min-width: 240px;
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
            grid-template-columns: repeat(5, 1fr);
            min-width: min-content;
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
// Función de filtrado - Accesible globalmente para onkeyup
function filtrarBancoComidas() {
    const input = document.getElementById('buscadorBancoComidas');
    if (!input) {
        console.error('Buscador no encontrado');
        return;
    }
    
    const busca = input.value.toLowerCase().trim();
    const grupos = document.querySelectorAll('.tipo-comida-banco');

    grupos.forEach(grupo => {
        const itemsGrupo = grupo.querySelectorAll('.comida-banco-item');
        let grupoVisible = false;

        itemsGrupo.forEach(item => {
            const nombre = item.dataset.comidaNombre || '';

            if (busca === '' || nombre.includes(busca)) {
                item.style.display = '';
                grupoVisible = true;
            } else {
                item.style.display = 'none';
            }
        });

        grupo.style.display = grupoVisible ? '' : 'none';
    });
}

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

    // Selección múltiple de comidas
    document.querySelectorAll('.checkbox-comida').forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            e.stopPropagation();
            const comidaChip = this.closest('.comida-chip');
            if (this.checked) {
                comidaChip.classList.add('seleccionada');
            } else {
                comidaChip.classList.remove('seleccionada');
            }
        });
    });

    // Drag and Drop para comidas disponibles
    draggables.forEach(draggable => {
        draggable.addEventListener('dragstart', function(e) {
            // Obtener todas las comidas seleccionadas
            const comidasSeleccionadas = document.querySelectorAll('.comida-chip.seleccionada');
            
            if (comidasSeleccionadas.length === 0) {
                // Si no hay ninguna seleccionada, arrastrar solo esta
                this.classList.add('dragging');
                const comidasData = [{
                    id: this.dataset.comidaId,
                    nombre: this.dataset.comidaNombre,
                    tipo: this.dataset.tipo,
                    disponible: this.dataset.disponible
                }];
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('comidas-json', JSON.stringify(comidasData));
            } else {
                // Si hay seleccionadas, arrastrar todas
                comidasSeleccionadas.forEach(chip => chip.classList.add('dragging'));
                const comidasData = Array.from(comidasSeleccionadas).map(chip => ({
                    id: chip.dataset.comidaId,
                    nombre: chip.dataset.comidaNombre,
                    tipo: chip.dataset.tipo,
                    disponible: chip.dataset.disponible
                }));
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('comidas-json', JSON.stringify(comidasData));
            }
        });

        draggable.addEventListener('dragend', function() {
            document.querySelectorAll('.comida-chip.dragging').forEach(chip => {
                chip.classList.remove('dragging');
            });
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

            const comidasJson = e.dataTransfer.getData('comidas-json');
            const comidas = JSON.parse(comidasJson);
            
            const fecha = this.dataset.fecha;
            const tipoCelda = this.dataset.tipo;

            let comidasAgregadas = 0;
            let comidasRechazadas = 0;

            comidas.forEach(comida => {
                // Verificar que sea del mismo tipo
                if (comida.tipo !== tipoCelda) {
                    comidasRechazadas++;
                    return;
                }

                // Verificar si ya existe
                const yaExiste = this.querySelector(`[data-comida-id="${comida.id}"]`);
                if (yaExiste) {
                    comidasRechazadas++;
                    return;
                }

                // Ocultar zona vacía
                const zonaVacia = this.querySelector('.zona-vacia');
                if (zonaVacia) zonaVacia.style.display = 'none';

                // Agregar la comida
                const comidaDiv = document.createElement('div');
                comidaDiv.className = 'comida-seleccionada';
                comidaDiv.dataset.comidaId = comida.id;
                comidaDiv.dataset.fecha = fecha;
                comidaDiv.innerHTML = `
                    <button type="button" class="btn-remove-small" title="Quitar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div>
                            <div class="comida-nombre-small">${comida.nombre}</div>
                            <small class="text-muted">Stock: ${comida.disponible}</small>
                        </div>
                    </div>
                `;

                // Agregar evento para quitar
                comidaDiv.querySelector('.btn-remove-small').addEventListener('click', function() {
                    registrarCambio(fecha, comida.id, 'quitar');
                    comidaDiv.remove();
                    
                    // Mostrar zona vacía si no hay comidas
                    if (zone.querySelectorAll('.comida-seleccionada').length === 0) {
                        const zonaVacia = zone.querySelector('.zona-vacia');
                        if (zonaVacia) zonaVacia.style.display = 'flex';
                    }
                    
                    mostrarNotificacion('Comida eliminada', 'success');
                });

                this.appendChild(comidaDiv);
                
                // Registrar cambio
                registrarCambio(fecha, comida.id, 'agregar');
                comidasAgregadas++;
            });

            // Desseleccionar después de arrastrar
            document.querySelectorAll('.checkbox-comida:checked').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.comida-chip').classList.remove('seleccionada');
            });

            // Mostrar notificación
            if (comidasAgregadas > 0) {
                mostrarNotificacion(`${comidasAgregadas} comida(s) agregada(s) correctamente`, 'success');
            }
            if (comidasRechazadas > 0) {
                mostrarNotificacion(`${comidasRechazadas} comida(s) no pudo(eron) agregarse (tipo incorrecto o duplicado)`, 'warning');
            }
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
