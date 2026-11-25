<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema Farmacia</title>
    <link href="https://jsdelivr.net" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow-sm border-0" style="width: 100%; max-width: 400px;">
        <div class="card-body p-4">
            <h4 class="text-center fw-bold mb-1">Bienvenido</h4>
            <p class="text-center text-muted small mb-4">Ingresa tus credenciales para continuar</p>

            {{-- Mensaje de éxito (ej. al cerrar sesión) --}}
            @if (session('ok'))
                <div class="alert alert-success py-2 small">
                    {{ session('ok') }}
                </div>
            @endif

            {{-- Mensajes de error de validación o credenciales --}}
            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.attempt') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        placeholder="usuario@farmacia.com"
                        required
                        autofocus
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label small">Mantener sesión iniciada</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    Ingresar
                </button>
            </form>
        </div>
    </div>

</body>
</html>
