{{-- Modal Configuración de Tienda --}}
<div class="modal fade j2b-modal" id="shopConfigModal{{ $shop->id }}" tabindex="-1" aria-labelledby="shopConfigModalLabel{{ $shop->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.shops.update-config', $shop->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="shopConfigModalLabel{{ $shop->id }}">
                        <i class="fa fa-cog"></i> Configuracion de Tienda
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
                                <small>
                                    Plan:
                                    @if($shop->plan)
                                        <span class="j2b-badge j2b-badge-primary">{{ $shop->plan->name }}</span>
                                    @else
                                        <span class="j2b-badge j2b-badge-outline">Sin plan</span>
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Precio mensual --}}
                    <div class="mb-3">
                        <label for="monthly_price{{ $shop->id }}" class="j2b-label">
                            <i class="fa fa-dollar j2b-text-primary"></i> Precio Mensual (con IVA)
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="j2b-badge j2b-badge-dark">$</span>
                            <input
                                type="number"
                                class="j2b-input"
                                id="monthly_price{{ $shop->id }}"
                                name="monthly_price"
                                value="{{ $shop->monthly_price ?? ($basicPlan->price ?? 999) }}"
                                step="0.01"
                                min="0"
                                required
                            >
                            <span class="j2b-badge j2b-badge-outline">/mes</span>
                        </div>
                        <small class="j2b-text-muted">Precio que se le cobra a esta tienda</small>
                    </div>

                    {{-- Días de trial --}}
                    <div class="mb-3">
                        <label for="trial_days{{ $shop->id }}" class="j2b-label">
                            <i class="fa fa-flask j2b-text-info"></i> Dias de Prueba Gratuita
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <input
                                type="number"
                                class="j2b-input"
                                id="trial_days{{ $shop->id }}"
                                name="trial_days"
                                value="{{ $shop->trial_days ?? 30 }}"
                                min="0"
                                max="365"
                                required
                            >
                            <span class="j2b-badge j2b-badge-outline">dias</span>
                        </div>
                        <small class="j2b-text-muted">Dias de trial asignados a esta tienda</small>
                    </div>

                    {{-- Días de gracia --}}
                    <div class="mb-3">
                        <label for="grace_period_days{{ $shop->id }}" class="j2b-label">
                            <i class="fa fa-clock-o j2b-text-warning"></i> Dias de Gracia
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <input
                                type="number"
                                class="j2b-input"
                                id="grace_period_days{{ $shop->id }}"
                                name="grace_period_days"
                                value="{{ $shop->grace_period_days ?? 7 }}"
                                min="0"
                                max="30"
                                required
                            >
                            <span class="j2b-badge j2b-badge-outline">dias</span>
                        </div>
                        <small class="j2b-text-muted">Dias adicionales antes de bloquear la tienda</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="j2b-btn j2b-btn-primary">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
