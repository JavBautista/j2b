{{-- Modal Asignar Owner --}}
<div class="modal fade j2b-modal" id="assignOwnerModal{{ $shop->id }}" tabindex="-1" aria-labelledby="assignOwnerModalLabel{{ $shop->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.shops.assign-owner', $shop->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="assignOwnerModalLabel{{ $shop->id }}">
                        <i class="fa fa-user-plus"></i> Asignar Dueno
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    {{-- Info de la tienda --}}
                    <div class="j2b-banner-alert j2b-banner-info mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary">
                                {{ strtoupper(substr($shop->name, 0, 1)) }}
                            </div>
                            <div>
                                <strong>{{ $shop->name }}</strong>
                                <br>
                                <small class="j2b-text-muted">
                                    Esta tienda no tiene un dueno asignado
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Select de usuarios --}}
                    <div class="mb-3">
                        <label for="owner_user_id{{ $shop->id }}" class="j2b-label">
                            <i class="fa fa-user j2b-text-primary"></i> Seleccionar Usuario Admin
                        </label>
                        <select class="j2b-select" id="owner_user_id{{ $shop->id }}" name="owner_user_id" required>
                            <option value="">Cargando usuarios...</option>
                        </select>
                        <small class="j2b-text-muted">Solo se muestran usuarios con rol Admin de esta tienda</small>
                    </div>

                    {{-- Info adicional --}}
                    <div class="j2b-card" style="background: rgba(0, 245, 160, 0.05); border: 1px dashed var(--j2b-primary);">
                        <div class="j2b-card-body py-3">
                            <small style="color: var(--j2b-gray-600);">
                                <i class="fa fa-info-circle j2b-text-primary"></i>
                                El <strong>Dueno</strong> es el usuario principal de la tienda que recibira:
                                <ul class="mb-0 mt-2">
                                    <li>Notificaciones de vencimiento</li>
                                    <li>Emails de recordatorio de pago</li>
                                    <li>Acceso a la facturacion</li>
                                </ul>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="j2b-btn j2b-btn-primary" id="assignOwnerBtn{{ $shop->id }}" disabled>
                        <i class="fa fa-user-plus"></i> Asignar Dueno
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Cargar usuarios cuando se abre el modal
document.getElementById('assignOwnerModal{{ $shop->id }}').addEventListener('shown.bs.modal', function () {
    const select = document.getElementById('owner_user_id{{ $shop->id }}');
    const btn = document.getElementById('assignOwnerBtn{{ $shop->id }}');

    select.innerHTML = '<option value="">Cargando usuarios...</option>';
    btn.disabled = true;

    fetch('{{ route("superadmin.shops.users", $shop->id) }}')
        .then(response => response.json())
        .then(users => {
            if (users.length === 0) {
                select.innerHTML = '<option value="">No hay usuarios admin disponibles</option>';
            } else {
                select.innerHTML = '<option value="">Seleccionar usuario...</option>';
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name + ' (' + user.email + ')';
                    select.appendChild(option);
                });
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error cargando usuarios:', error);
            select.innerHTML = '<option value="">Error al cargar usuarios</option>';
        });
});

// Habilitar/deshabilitar boton segun seleccion
document.getElementById('owner_user_id{{ $shop->id }}').addEventListener('change', function() {
    document.getElementById('assignOwnerBtn{{ $shop->id }}').disabled = !this.value;
});
</script>
