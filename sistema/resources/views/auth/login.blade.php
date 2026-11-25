<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema Farmacia</title>
    <link href="{{ asset('css/bootstrap.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://jsdelivr.net">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="card border-0 shadow-sm" style="width: 100%; max-width: 400px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div class="rounded-circle bg-danger-subtle d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                    <i class="bi bi-capsule text-danger fs-3"></i>
                </div>
                <h4 class="fw-bold mb-1">Bienvenido</h4>
                <p class="text-secondary small mb-0">Ingresa tus credenciales para continuar</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Correo electrónico</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="ejemplo@farmacia.com">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label small fw-semibold">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-danger w-100 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                </button>
            </form>
        </div>
    </div>

</body>
</html>