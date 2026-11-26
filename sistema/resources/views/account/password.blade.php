@extends('template.administrador')

@section('title', 'Cambiar contraseña')
@section('name_section', 'Cambiar contraseña')
@section('description', 'Protege tu cuenta con una contraseña segura')
@section('item_section', 'Cuenta')
@section('route_section', route('password.edit'))
@section('section', 'Cambiar contraseña')
@section('module', 'Cuenta')

@section('content')
@include('partials.alerts')

<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <section class="card bg-white border-0 shadow-sm">
            <div class="card-header bg-white border-bottom px-4 py-3">
                <h2 class="h6 fw-bold mb-0">Actualizar contraseña</h2>
            </div>
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <p class="fw-semibold mb-1">No se pudo actualizar la contraseña:</p>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" autocomplete="off">
                    @csrf
                    @method('PUT')

                    @foreach ([
                        'current_password' => ['Contraseña actual', 'currentPassword', 'current-password'],
                        'password' => ['Nueva contraseña', 'newPassword', 'new-password'],
                        'password_confirmation' => ['Confirmar nueva contraseña', 'confirmPassword', 'new-password'],
                    ] as $field => [$label, $id, $autocomplete])
                        <div class="mb-3">
                            <label for="{{ $id }}" class="form-label">{{ $label }}</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    id="{{ $id }}"
                                    name="{{ $field }}"
                                    autocomplete="{{ $autocomplete }}"
                                    @if ($field === 'password') minlength="8" data-new-password @elseif ($field === 'password_confirmation') data-confirm-password @endif
                                    required
                                    class="form-control @error($field) is-invalid @enderror"
                                >
                                <button
                                    type="button"
                                    class="input-group-text bg-white"
                                    data-password-toggle
                                    data-target="#{{ $id }}"
                                    aria-label="Mostrar u ocultar {{ mb_strtolower($label) }}"
                                >
                                    <i class="bi bi-eye text-secondary" aria-hidden="true"></i>
                                </button>
                                @error($field)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endforeach

                    <ul class="list-unstyled small text-secondary mb-4" aria-live="polite">
                        <li data-password-requirement-length><i class="bi bi-circle me-1" aria-hidden="true"></i>Mínimo 8 caracteres</li>
                        <li data-password-requirement-match><i class="bi bi-circle me-1" aria-hidden="true"></i>Las contraseñas nuevas deben coincidir</li>
                    </ul>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Actualizar contraseña
                        </button>
                        <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
