@extends('template.administrador')

@section('title', 'Mi perfil')
@section('name_section', 'Mi perfil')
@section('description', 'Consulta y actualiza tu información personal')
@section('item_section', 'Cuenta')
@section('route_section', route('profile.edit'))
@section('section', 'Mi perfil')
@section('module', 'Cuenta')

@section('content')
@include('partials.alerts')

<div class="row g-3">
    <div class="col-12 col-xl-4">
        <section class="card bg-white border-0 shadow-sm h-100">
            <div class="card-body text-center p-4">
                @if ($user->profile_photo_path)
                    <img
                        src="{{ asset('storage/' . $user->profile_photo_path) }}"
                        alt="Fotografía de {{ $user->name }}"
                        class="account-profile-photo rounded-circle mb-3"
                    >
                @else
                    <div class="account-profile-placeholder rounded-circle mx-auto mb-3" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                    </div>
                @endif
                <h2 class="h5 fw-bold mb-1">{{ $user->name }}</h2>
                <p class="text-secondary small mb-3">{{ $user->email }}</p>
                <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                    {{ $user->role?->name ?? 'Sin rol asignado' }}
                </span>
            </div>
            <div class="card-footer bg-white border-top px-4 py-3">
                <dl class="row small mb-0">
                    <dt class="col-6 text-secondary">Identificador de acceso</dt>
                    <dd class="col-6 text-end text-break">{{ $user->email }}</dd>
                    <dt class="col-6 text-secondary">Estado de sesión</dt>
                    <dd class="col-6 text-end"><span class="badge text-bg-success">Autenticada</span></dd>
                    <dt class="col-6 text-secondary">Fecha de registro</dt>
                    <dd class="col-6 text-end mb-0">{{ $user->created_at?->format('d/m/Y') ?? 'No disponible' }}</dd>
                </dl>
                <p class="small text-secondary mt-3 mb-0">
                    El sistema no registra un nombre de usuario independiente ni el último inicio de sesión.
                </p>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-8">
        <section class="card bg-white border-0 shadow-sm">
            <div class="card-header bg-white border-bottom px-4 py-3">
                <h2 class="h6 fw-bold mb-0">Información personal</h2>
            </div>
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <p class="fw-semibold mb-1">Revisa la información ingresada:</p>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="profileName" class="form-label">Nombres y apellidos</label>
                            <input
                                type="text"
                                id="profileName"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                maxlength="255"
                                autocomplete="name"
                                required
                                class="form-control @error('name') is-invalid @enderror"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="profileEmail" class="form-label">Correo electrónico</label>
                            <input
                                type="email"
                                id="profileEmail"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                maxlength="255"
                                autocomplete="email"
                                required
                                class="form-control @error('email') is-invalid @enderror"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="profilePhone" class="form-label">Teléfono <span class="text-secondary">(opcional)</span></label>
                            <input
                                type="tel"
                                id="profilePhone"
                                name="phone"
                                value="{{ old('phone', $user->phone) }}"
                                maxlength="15"
                                autocomplete="tel"
                                class="form-control @error('phone') is-invalid @enderror"
                            >
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="profilePhoto" class="form-label">Fotografía de perfil</label>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                                <div class="account-photo-preview">
                                    <img
                                        src="{{ $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : '' }}"
                                        data-profile-photo-preview
                                        data-original-src="{{ $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : '' }}"
                                        alt="Vista previa de la fotografía de perfil"
                                        class="{{ $user->profile_photo_path ? '' : 'd-none' }}"
                                    >
                                    <div
                                        data-profile-photo-placeholder
                                        class="account-profile-placeholder rounded-circle {{ $user->profile_photo_path ? 'd-none' : '' }}"
                                        aria-hidden="true"
                                    >
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <input
                                        type="file"
                                        id="profilePhoto"
                                        name="profile_photo"
                                        accept="image/jpeg,image/png,image/webp"
                                        data-profile-photo-input
                                        class="form-control @error('profile_photo') is-invalid @enderror"
                                    >
                                    <div class="form-text">JPG, PNG o WebP; tamaño máximo 2 MB.</div>
                                    @error('profile_photo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="small text-danger mt-1 d-none" data-profile-photo-error role="alert"></div>
                                    @if ($user->profile_photo_path)
                                        <div class="form-check mt-2">
                                            <input
                                                type="checkbox"
                                                id="removeProfilePhoto"
                                                name="remove_photo"
                                                value="1"
                                                data-remove-profile-photo
                                                class="form-check-input"
                                            >
                                            <label for="removeProfilePhoto" class="form-check-label small">Eliminar fotografía actual</label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Guardar cambios
                        </button>
                        <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
