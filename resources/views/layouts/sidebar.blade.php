<!-- Sidebar -->
<aside id="sidebar">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="m-0">Mi Sistema</h4>
        <button id="sidebar-close" class="btn btn-sm btn-light sidebar-close-btn">✖</button>
    </div>

    <style>
    /* Estilo inicial del botón */
    .sidebar-close-btn {
        transition: transform 0.3s ease, background-color 0.3s ease, color 0.3s ease;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Hover: girar y cambiar color */
    .sidebar-close-btn:hover {
        transform: rotate(90deg) scale(1.2);
        background-color: #dc3545;
        color: #fff;
        cursor: pointer;
    }

    /* Efecto al hacer clic */
    .sidebar-close-btn:active {
        transform: rotate(90deg) scale(0.9);
        transition: transform 0.1s ease;
    }
    </style>

    <ul class="nav flex-column">
        @if(Auth::user()->id_rol == 1)
            <!-- Sidebar Admin -->
           <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="{{ url('/administrador') }}" class="nav-link">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ url('/admin_usuarios') }}" class="nav-link">
                        <i class="bi bi-people-fill me-2"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_registro_clientes') }}" class="nav-link">
                        <i class="bi bi-person-add me-2"></i> Registrar clientes
                    </a>
                </li> 
                <li class="nav-item mb-2">
                    <a href="{{ url('/admin_comidas') }}" class="nav-link">
                        <i class="bi bi-basket-fill me-2"></i> Comidas/Productos
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.menu') }}" class="nav-link">
                        <i class="bi bi-basket-fill me-2"></i> Creación/Visualizar Menú
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_h-pedido') }}" class="nav-link">
                        <i class="bi bi-pencil-square me-2"></i> Hacer pedido
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="{{ url('/admin_pedidos') }}" class="nav-link">
                        <i class="bi bi-card-checklist me-2"></i> Pedidos
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ url('/admin_tickets') }}" class="nav-link">
                        <i class="bi bi-ticket-detailed-fill me-2"></i> Tickets
                    </a>
                </li>
            </ul>

        @elseif(Auth::user()->id_rol == 2)
            <!-- Sidebar Empleado -->
            <ul class="nav flex-column">
                <!-- <li class="nav-item mb-2">
                    <a href="{{ url('/empleados') }}" class="nav-link">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li> -->
                <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_registro_clientes') }}" class="nav-link">
                        <i class="bi bi-person-add me-2"></i> Registrar clientes
                    </a>
                </li> 
                <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_h-pedido') }}" class="nav-link">
                        <i class="bi bi-pencil-square me-2"></i> Hacer pedido
                    </a>
                </li>
                <!-- <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_p-pedidos') }}" class="nav-link">
                        <i class="bi bi-card-checklist me-2"></i> Pedidos pendientes
                    </a>
                </li> -->
                <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_c-disponible') }}" class="nav-link">
                        <i class="bi bi-basket-fill me-2"></i> Comidas/Productos Disponibles
                    </a>
                </li> 
                <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_tickets') }}" class="nav-link">
                        <i class="bi bi-ticket-detailed-fill me-2"></i> Tickets
                    </a>
                </li> 
            </ul>


            @elseif(Auth::user()->id_rol == 5)
            <!-- Sidebar Empleado -->
            <ul class="nav flex-column">

                <li class="nav-item mb-2">
                    <a href="{{ url('/admin_pedidos') }}" class="nav-link">
                        <i class="bi bi-card-checklist me-2"></i> Pedidos
                    </a>
                </li>
               
               
                <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_c-disponible') }}" class="nav-link">
                        <i class="bi bi-basket-fill me-2"></i> Comidas/Productos Disponibles
                    </a>
                </li> 
                 <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_tickets') }}" class="nav-link">
                        <i class="bi bi-ticket-detailed-fill me-2"></i> Tickets
                    </a>
                </li> 

                
                
                
            </ul>


            @elseif(Auth::user()->id_rol == 6)

            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_p-pedidos') }}" class="nav-link">
                        <i class="bi bi-card-checklist me-2"></i> Pedidos pendientes
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ url('/empleados_tickets') }}" class="nav-link">
                        <i class="bi bi-ticket-detailed-fill me-2"></i> Tickets
                    </a>
                </li>
            </ul>


        @endif

        <li class="nav-item mt-4">
            <form action="{{ url('/logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('¿Seguro que quieres cerrar sesión?')">
                    Cerrar Sesión
                </button>
            </form>
        </li>
    </ul>
</aside>

<!-- Botón hamburguesa -->
<button id="sidebar-toggle" class="btn btn-primary">☰ Menú</button>

<script>
 const toggleBtn = document.getElementById('sidebar-toggle');
const closeBtn = document.getElementById('sidebar-close');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.add('active');
    mainContent.classList.add('sidebar-active');
    toggleBtn.classList.add('hide'); // Oculta el botón
});

closeBtn.addEventListener('click', () => {
    sidebar.classList.remove('active');
    mainContent.classList.remove('sidebar-active');
    toggleBtn.classList.remove('hide'); // Muestra el botón otra vez
});

</script>
