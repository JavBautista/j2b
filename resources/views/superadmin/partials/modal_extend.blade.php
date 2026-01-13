{{-- Modal Extender Suscripcion --}}
<div class="modal fade j2b-modal" id="extendModal{{ $shop->id }}" tabindex="-1" aria-labelledby="extendModalLabel{{ $shop->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.shops.extend-trial', $shop->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="extendModalLabel{{ $shop->id }}">
                        <i class="fa fa-clock-o"></i> Extender Suscripcion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    {{-- Info de la tienda --}}
                    <div class="j2b-banner-alert j2b-banner-info mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary">
                                {{ strtoupper(substr($shop->name, 0, 1)) }}
                            </div>
                            <div>
                                <strong>{{ $shop->name }}</strong>
                                <br>
                                <small>
                                    @if($shop->is_trial)
                                        <span class="j2b-badge j2b-badge-info">Trial</span>
                                        Vence: {{ $shop->trial_ends_at ? $shop->trial_ends_at->format('d/m/Y') : 'No definido' }}
                                    @else
                                        <span class="j2b-badge j2b-badge-primary">{{ $shop->plan->name ?? 'Sin plan' }}</span>
                                        Vence: {{ $shop->subscription_ends_at ? $shop->subscription_ends_at->format('d/m/Y') : 'No definido' }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Input de dias --}}
                    <div class="mb-3">
                        <label for="days{{ $shop->id }}" class="j2b-label">
                            <i class="fa fa-calendar-plus-o j2b-text-primary"></i> Dias a extender
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <input
                                type="number"
                                class="j2b-input"
                                id="days{{ $shop->id }}"
                                name="days"
                                value="30"
                                min="1"
                                max="365"
                                required
                                style="max-width: 120px;"
                            >
                            <span class="j2b-badge j2b-badge-outline">dias</span>
                        </div>
                        <small class="j2b-text-muted">Minimo 1 dia, maximo 365 dias</small>
                    </div>

                    {{-- Quick buttons --}}
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" onclick="document.getElementById('days{{ $shop->id }}').value = 7">
                            +7 dias
                        </button>
                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" onclick="document.getElementById('days{{ $shop->id }}').value = 15">
                            +15 dias
                        </button>
                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" onclick="document.getElementById('days{{ $shop->id }}').value = 30">
                            +30 dias
                        </button>
                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" onclick="document.getElementById('days{{ $shop->id }}').value = 90">
                            +90 dias
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="j2b-btn j2b-btn-primary">
                        <i class="fa fa-clock-o"></i> Extender
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
