<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="profileModalLabel">
                    <i class="bi bi-person-circle text-danger me-2"></i>Mi Perfil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center fw-bold fs-4 mb-2" style="width: 64px; height: 64px;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <h5 class="fw-bold mb-0">{{ auth()->user()->name }}</h5>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3 mt-1">
                        {{ auth()->user()->role->name ?? 'Usuario' }}
                    </span>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-secondary fw-semibold">Correo electrónico:</span>
                        <span>{{ auth()->user()->email }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-secondary fw-semibold">DNI:</span>
                        <span>{{ auth()->user()->dni ?? 'No registrado' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-secondary fw-semibold">Teléfono:</span>
                        <span>{{ auth()->user()->phone ?? 'No registrado' }}</span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-sm btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>