<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body, html {
            height: 100%;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
        }

        /* Carrusel fullscreen */
        #bgCarousel {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            z-index: -1;
                        background-color: rgba(0,0,0,0.5);

        }

        #bgCarousel .carousel-item {
            height: 100vh;
            background-size: cover;
            background-position: center;
        }

        .overlay {
            background-color: rgba(0,0,0,0.6);
            height: 100%;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            padding: 30px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .login-card img.logo {
            width: 120px;
            margin-bottom: 20px;
        }

        .login-card h3 {
            margin-bottom: 25px;
            font-weight: 600;
        }

        .login-card .btn-success {
            background-color: #28a745;
            border: none;
        }

        .login-card .btn-success:hover {
            background-color: #218838;
        }

        .alert {
            text-align: left;
        }
        /* Animación Fade In */
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Aplica fade-in al login card */
        .login-card {
            animation: fadeIn 0.5s ease forwards;
        }

        /* Fade en carrusel de fondo */
        #bgCarousel .carousel-item {
            opacity: 0;
            transition: opacity .5s ease-in-out;
            filter: brightness(0.4);
        }

        #bgCarousel .carousel-item.active {
            opacity: 1;
            filter: brightness(0.6);
        }
      

    </style>
</head>
<body>

    <!-- Carrusel de fondo -->
    <div id="bgCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
        <div class="carousel-inner">
            <div class="carousel-item active" style="background-image: url('{{ asset('assets/img/prueba.jpg') }}')"></div>
            <div class="carousel-item" style="background-image: url('{{ asset('assets/img/prueba2.jpg') }}')"></div>
        </div>
    </div>

    <!-- Overlay -->
    <div class="overlay"></div>

    <!-- Contenedor login -->
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <img src="{{ asset('assets/img/logo.svg') }}" alt="Logo" class="logo">

            <h3>Iniciar Sesión</h3>

            <!-- Mensajes -->
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

            <!-- Formulario Login -->
            <form action="{{ url('/login') }}" method="POST">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Ingresar</button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
