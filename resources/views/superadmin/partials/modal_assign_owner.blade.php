{{-- Modal Asignar Admin Principal --}}
@php
    // Precargar usuarios admin full de esta tienda
    $adminUsers = \App\Models\User::where('shop_id', $shop->id)
        ->where('limited', 0)
        ->whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })
        ->get(['id', 'name', 'email']);
@endphp
<div class="modal fade j2b-modal" id="assignOwnerModal{{ $shop->id }}" tabindex="-1" aria-labelledby="assignOwnerModalLabel{{ $shop->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.shops.assign-owner', $shop->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="assignOwnerModalLabel{{ $shop->id }}">
                        <i class="fa fa-user-plus"></i> Asignar Admin Principal
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
                                    Esta tienda no tiene un admin principal asignado
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
                            @if($adminUsers->isEmpty())
                                <option value="">No hay usuarios admin disponibles</option>
                            @else
                                <option value="">Seleccionar usuario...</option>
                                @foreach($adminUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            @endif
                        </select>
                        <small class="j2b-text-muted">Solo usuarios Admin full (no limitados) de esta tienda</small>
                    </div>

                    {{-- Info adicional --}}
                    <div class="j2b-card" style="background: rgba(0, 245, 160, 0.05); border: 1px dashed var(--j2b-primary);">
                        <div class="j2b-card-body py-3">
                            <small style="color: var(--j2b-gray-600);">
                                <i class="fa fa-info-circle j2b-text-primary"></i>
                                El <strong>Admin Principal</strong> es el usuario responsable de la cuenta:
                                <ul class="mb-0 mt-2">
                                    <li>Recibe notificaciones de vencimiento</li>
                                    <li>Emails de recordatorio de pago</li>
                                    <li>Se reactiva automaticamente al reactivar la tienda</li>
                                </ul>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="j2b-btn j2b-btn-primary" {{ $adminUsers->isEmpty() ? 'disabled' : '' }}>
                        <i class="fa fa-user-plus"></i> Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
