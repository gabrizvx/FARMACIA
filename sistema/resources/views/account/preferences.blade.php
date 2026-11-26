@extends('template.administrador')

@section('title', 'Preferencias')
@section('name_section', 'Preferencias')
@section('description', 'Personaliza la apariencia de tu cuenta')
@section('item_section', 'Cuenta')
@section('route_section', route('preferences.edit'))
@section('section', 'Preferencias')
@section('module', 'Cuenta')

@section('content')
@include('partials.alerts')

<div class="row justify-content-center">
    <div class="col-12 col-xxl-9">
        <form method="POST" action="{{ route('preferences.update') }}">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <p class="fw-semibold mb-1">Revisa las preferencias seleccionadas:</p>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="card bg-white border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom px-4 py-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-palette me-2 text-primary" aria-hidden="true"></i>Apariencia</h2>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="theme" class="form-label">Tema</label>
                            <select id="theme" name="theme" class="form-select @error('theme') is-invalid @enderror" required>
                                <option value="light" @selected(old('theme', $preferences->theme) === 'light')>Claro</option>
                                <option value="dark" @selected(old('theme', $preferences->theme) === 'dark')>Oscuro</option>
                                <option value="system" @selected(old('theme', $preferences->theme) === 'system')>Automático según el dispositivo</option>
                            </select>
                            @error('theme')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="accentColor" class="form-label">Color principal</label>
                            <select id="accentColor" name="accent_color" class="form-select @error('accent_color') is-invalid @enderror" required>
                                <option value="brand" @selected(old('accent_color', $preferences->accent_color) === 'brand')>Rojo FARM SJB</option>
                                <option value="blue" @selected(old('accent_color', $preferences->accent_color) === 'blue')>Azul profesional</option>
                                <option value="teal" @selected(old('accent_color', $preferences->accent_color) === 'teal')>Verde azulado</option>
                                <option value="violet" @selected(old('accent_color', $preferences->accent_color) === 'violet')>Violeta</option>
                            </select>
                            @error('accent_color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="card bg-white border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom px-4 py-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-sliders me-2 text-primary" aria-hidden="true"></i>Visualización</h2>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="density" class="form-label">Tamaño de los elementos</label>
                            <select id="density" name="density" class="form-select @error('density') is-invalid @enderror" required>
                                <option value="comfortable" @selected(old('density', $preferences->density) === 'comfortable')>Cómodo</option>
                                <option value="compact" @selected(old('density', $preferences->density) === 'compact')>Compacto</option>
                            </select>
                            @error('density')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="sidebarCollapsed" class="form-label">Menú lateral al ingresar</label>
                            <select id="sidebarCollapsed" name="sidebar_collapsed" class="form-select @error('sidebar_collapsed') is-invalid @enderror" required>
                                <option value="0" @selected(! (bool) old('sidebar_collapsed', $preferences->sidebar_collapsed))>Expandido</option>
                                <option value="1" @selected((bool) old('sidebar_collapsed', $preferences->sidebar_collapsed))>Contraído</option>
                            </select>
                            @error('sidebar_collapsed')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            <div class="alert alert-info small" role="status">
                Las preferencias de notificaciones no están disponibles porque el sistema aún no genera notificaciones reales.
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Guardar preferencias
                </button>
                <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>

        <form method="POST" action="{{ route('preferences.reset') }}" class="mt-2" data-confirm-preferences-reset>
            @csrf
            <button type="submit" class="btn btn-link text-danger px-0">
                Restaurar valores predeterminados
            </button>
        </form>
    </div>
</div>
@endsection
