<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Mi Aplicación')</title>

    <!-- CSS global -->
    <link href="{{ asset('layout/css/app.css') }}" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">


        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">


    <style>
        /* ==================== BOTÓN FLOTANTE DE RECARGA ==================== */
        .btn-reload-float {
            position: fixed;
            top: 30px;
            right: 30px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            z-index: 9999;
            opacity: 0.85;
        }

        .btn-reload-float:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            transform: scale(1.08);
            opacity: 1;
        }

        .btn-reload-float:active {
            transform: scale(0.95);
        }

        .btn-reload-float i {
            transition: transform 0.4s ease;
        }

        .btn-reload-float:hover i {
            transform: rotate(180deg);
        }

        .btn-reload-float.reloading i {
            animation: spin 0.6s linear infinite;
        }

        /* Punto indicador sutil */
        .btn-reload-float .status-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 10px;
            height: 10px;
            background: #ef4444;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            animation: pulseGlow 2s ease-in-out infinite;
        }

        /* Animación de pulso suave */
        @keyframes pulseGlow {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.6;
                transform: scale(1.1);
            }
        }

        /* Animación de giro */
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Tooltip discreto pero informativo */
        .btn-reload-float::before {
            content: 'Actualizar datos';
            position: absolute;
            right: 70px;
            background: rgba(31, 41, 55, 0.95);
            color: white;
            padding: 0.5rem 0.875rem;
            border-radius: 8px;
            font-size: 0.813rem;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .btn-reload-float:hover::before {
            opacity: 1;
            right: 75px;
        }

        /* Indicador de tiempo transcurrido */
        .time-indicator {
            position: fixed;
            top: 95px;
            right: 30px;
            background: rgba(255, 255, 255, 0.95);
            color: #6b7280;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .time-indicator.show {
            opacity: 1;
        }

        .time-indicator.outdated {
            color: #ef4444;
            border-color: #fecaca;
            animation: gentlePulse 2s ease-in-out infinite;
        }

        @keyframes gentlePulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
        }

        /* Notificación sutil */
        .update-notification {
            position: fixed;
            top: 95px;
            right: 30px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.625rem 1rem;
            border-radius: 12px;
            font-size: 0.813rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            z-index: 9998;
            animation: slideInSoft 0.4s ease-out, fadeOutSoft 0.4s ease-out 3.5s forwards;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .update-notification i {
            font-size: 1rem;
        }

        @keyframes slideInSoft {
            from {
                transform: translateX(150px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOutSoft {
            to {
                opacity: 0;
                transform: translateY(-8px);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .btn-reload-float {
                top: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 1.35rem;
            }

            .btn-reload-float::before {
                display: none;
            }

            .time-indicator,
            .update-notification {
                top: 80px;
                right: 20px;
                font-size: 0.7rem;
                padding: 0.35rem 0.65rem;
            }
        }

        /* Variante de color según página */
        .btn-reload-float.variant-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-reload-float.variant-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-reload-float.variant-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-reload-float.variant-warning:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
    </style>
</head>
<body>
    @auth
        @include('layouts.sidebar')
    @endauth

    <!-- Header / Navbar -->
    <header id="header" class="bg-primary text-white p-3 mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="h4 m-0">@yield('header', 'Sistema de Gestión')</h1>

            @auth
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-circle me-2 fs-5"></i>
                    <span class="fw-semibold">{{ Auth::user()->nombre }}</span>
                </div>
            @endauth
        </div>
    </header>

    <!-- Contenido principal -->
    <main id="main-content" class="@auth sidebar-active @endauth">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer id="footer" class="bg-light text-center text-muted py-3 mt-auto">
        &copy; {{ date('Y') }} Mi Aplicación
    </footer>

    {{-- BOTÓN FLOTANTE DE RECARGA (Solo para usuarios autenticados) --}}
    @auth
        <a href="javascript:void(0);" class="btn-reload-float" id="btnReload" title="Actualizar página" onclick="location.reload(); return false;">
            <span class="status-dot"></span>
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <div class="time-indicator" id="timeIndicator">Actualizado hace <span id="timeElapsed">0s</span></div>
    @endauth

    <!-- Scripts globales -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('layout/js/app.js') }}"></script>
    @stack('scripts')

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script>
        // ==================== AJUSTE DE LAYOUT (SIDEBAR) ====================
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const header = document.getElementById('header');
        const footer = document.getElementById('footer');

        function adjustLayout() {
            if(sidebar && sidebar.classList.contains('active')) {
                mainContent.style.marginLeft = '250px';
                header.style.marginLeft = '250px';
                footer.style.marginLeft = '250px';
            } else {
                mainContent.style.marginLeft = '0';
                header.style.marginLeft = '0';
                footer.style.marginLeft = '0';
            }
        }

        // Ajustar al abrir/cerrar sidebar
        if(sidebar) {
            const toggleBtn = document.getElementById('sidebar-toggle');
            const closeBtn = document.getElementById('sidebar-close');

            toggleBtn?.addEventListener('click', () => {
                sidebar.classList.add('active');
                adjustLayout();
                toggleBtn.classList.add('hide');
            });

            closeBtn?.addEventListener('click', () => {
                sidebar.classList.remove('active');
                adjustLayout();
                toggleBtn.classList.remove('hide');
            });
        }

        // Ajuste inicial al cargar
        adjustLayout();

        // ==================== BOTÓN DE RECARGA ====================
        const isAuthenticated = {{ Auth::check() ? 'true' : 'false' }};

        if(isAuthenticated) {
            window.addEventListener('load', function() {
                const btnReload = document.getElementById('btnReload');
                const timeIndicator = document.getElementById('timeIndicator');
                const timeElapsed = document.getElementById('timeElapsed');
                let seconds = 0;
                let timerInterval;
                
                if(btnReload) {
                    // Agregar animación al hacer click
                    btnReload.addEventListener('click', function(e) {
                        e.preventDefault();
                        this.classList.add('reloading');
                        window.location.reload();
                    });

                    // Cambiar color según la ruta
                    const currentPath = window.location.pathname;
                    
                    if(currentPath.includes('/admin')) {
                        btnReload.classList.add('variant-success');
                    } else if(currentPath.includes('/pedidos')) {
                        btnReload.classList.add('variant-warning');
                    }

                    // Mostrar indicador de tiempo al pasar el cursor
                    btnReload.addEventListener('mouseenter', function() {
                        if(timeIndicator) {
                            timeIndicator.classList.add('show');
                        }
                    });

                    btnReload.addEventListener('mouseleave', function() {
                        if(timeIndicator) {
                            timeIndicator.classList.remove('show');
                        }
                    });

                    // Contador de tiempo transcurrido
                    if(timeIndicator && timeElapsed) {
                        timerInterval = setInterval(function() {
                            seconds++;
                            
                            if(seconds < 60) {
                                timeElapsed.textContent = seconds + 's';
                            } else if(seconds < 3600) {
                                const minutes = Math.floor(seconds / 60);
                                timeElapsed.textContent = minutes + 'm';
                            } else {
                                const hours = Math.floor(seconds / 3600);
                                timeElapsed.textContent = hours + 'h';
                            }

                            // Marcar como desactualizado después de 2 minutos
                            if(seconds >= 120) {
                                timeIndicator.classList.add('outdated');
                            }
                        }, 1000);
                    }

                    // Mostrar notificación sutil cada 3 minutos
                    setInterval(function() {
                        showUpdateNotification();
                    }, 180000); // 3 minutos

                    // Primera notificación después de 2 minutos
                    setTimeout(function() {
                        showUpdateNotification();
                    }, 120000);
                }
            });
        }

        // Función para mostrar notificación sutil
        function showUpdateNotification() {
            const notification = document.createElement('div');
            notification.className = 'update-notification';
            notification.innerHTML = '<i class="bi bi-info-circle"></i> Datos desactualizados';
            document.body.appendChild(notification);

            // Remover después de 4 segundos
            setTimeout(() => {
                notification.remove();
            }, 4000);
        }

        // Atajo de teclado F5
        document.addEventListener('keydown', function(e) {
            if(isAuthenticated) {
                const btnReload = document.getElementById('btnReload');
                if (btnReload && (e.key === 'F5' || (e.ctrlKey && e.key === 'r'))) {
                    btnReload.classList.add('reloading');
                }
            }
        });
    </script>
</body>
</html>
