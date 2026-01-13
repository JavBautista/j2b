{{-- Modal Cambiar Plan --}}
<div class="modal fade j2b-modal" id="changePlanModal{{ $shop->id }}" tabindex="-1" aria-labelledby="changePlanModalLabel{{ $shop->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.shops.change-plan', $shop->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="changePlanModalLabel{{ $shop->id }}">
                        <i class="fa fa-exchange"></i> Cambiar Plan
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
                                    Plan actual:
                                    @if($shop->plan)
                                        <span class="j2b-badge j2b-badge-primary">{{ $shop->plan->name }}</span>
                                        <span class="j2b-text-primary">${{ $shop->plan->price }}/mes</span>
                                    @else
                                        <span class="j2b-badge j2b-badge-outline">Sin plan</span>
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Columna izquierda: Seleccion de plan --}}
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="plan_id{{ $shop->id }}" class="j2b-label">
                                    <i class="fa fa-cube j2b-text-primary"></i> Nuevo Plan
                                </label>
                                <select class="j2b-select" id="plan_id{{ $shop->id }}" name="plan_id" required onchange="updatePlanPrice{{ $shop->id }}(this)">
                                    <option value="">Seleccionar plan...</option>
                                    @foreach($plans as $plan)
                                        <option
                                            value="{{ $plan->id }}"
                                            data-price="{{ $plan->price }}"
                                            data-currency="{{ $plan->currency }}"
                                            {{ $shop->plan_id == $plan->id ? 'selected' : '' }}
                                        >
                                            {{ $plan->name }} - ${{ $plan->price }}/mes
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="duration_months{{ $shop->id }}" class="j2b-label">
                                    <i class="fa fa-calendar j2b-text-primary"></i> Duracion
                                </label>
                                <select class="j2b-select" id="duration_months{{ $shop->id }}" name="duration_months" required onchange="calculateTotal{{ $shop->id }}()">
                                    <option value="1">1 mes</option>
                                    <option value="3">3 meses</option>
                                    <option value="6">6 meses</option>
                                    <option value="12" selected>12 meses (1 anio)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="custom_price{{ $shop->id }}" class="j2b-label">
                                    <i class="fa fa-dollar j2b-text-warning"></i> Precio Personalizado (opcional)
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="j2b-badge j2b-badge-dark">$</span>
                                    <input
                                        type="number"
                                        class="j2b-input"
                                        id="custom_price{{ $shop->id }}"
                                        name="custom_price"
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        onchange="calculateTotal{{ $shop->id }}()"
                                        onkeyup="calculateTotal{{ $shop->id }}()"
                                    >
                                    <span class="j2b-badge j2b-badge-outline">/mes</span>
                                </div>
                                <small class="j2b-text-muted">Dejar vacio para usar precio del plan</small>
                            </div>
                        </div>

                        {{-- Columna derecha: Resumen --}}
                        <div class="col-md-6">
                            <div class="j2b-card" style="background: var(--j2b-gray-100);">
                                <div class="j2b-card-header" style="background: var(--j2b-gradient-dark); color: white;">
                                    <h6 class="mb-0"><i class="fa fa-calculator"></i> Resumen</h6>
                                </div>
                                <div class="j2b-card-body">
                                    <table class="w-100" style="font-size: 0.9em;">
                                        <tr>
                                            <td class="py-1">Plan:</td>
                                            <td class="py-1 text-right"><strong id="summary_plan{{ $shop->id }}">-</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="py-1">Precio/mes:</td>
                                            <td class="py-1 text-right"><span id="summary_price{{ $shop->id }}">$0.00</span></td>
                                        </tr>
                                        <tr>
                                            <td class="py-1">Duracion:</td>
                                            <td class="py-1 text-right"><span id="summary_duration{{ $shop->id }}">12 meses</span></td>
                                        </tr>
                                        <tr style="border-top: 1px solid var(--j2b-gray-300);">
                                            <td class="py-2"><strong>Subtotal:</strong></td>
                                            <td class="py-2 text-right"><strong id="summary_subtotal{{ $shop->id }}">$0.00</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="py-1">IVA (16%):</td>
                                            <td class="py-1 text-right"><span id="summary_iva{{ $shop->id }}">$0.00</span></td>
                                        </tr>
                                        <tr style="border-top: 2px solid var(--j2b-primary); background: rgba(0,245,160,0.1);">
                                            <td class="py-2"><strong style="color: var(--j2b-primary);">TOTAL:</strong></td>
                                            <td class="py-2 text-right"><strong id="summary_total{{ $shop->id }}" style="color: var(--j2b-primary); font-size: 1.2em;">$0.00</strong></td>
                                        </tr>
                                    </table>
                                    <div class="mt-3">
                                        <small class="j2b-text-muted">
                                            <i class="fa fa-info-circle"></i> Vencera el: <strong id="summary_expires{{ $shop->id }}">-</strong>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="j2b-btn j2b-btn-primary">
                        <i class="fa fa-check"></i> Cambiar Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updatePlanPrice{{ $shop->id }}(select) {
    const option = select.options[select.selectedIndex];
    document.getElementById('summary_plan{{ $shop->id }}').textContent = option.text.split(' - ')[0] || '-';
    calculateTotal{{ $shop->id }}();
}

function calculateTotal{{ $shop->id }}() {
    const planSelect = document.getElementById('plan_id{{ $shop->id }}');
    const durationSelect = document.getElementById('duration_months{{ $shop->id }}');
    const customPriceInput = document.getElementById('custom_price{{ $shop->id }}');

    const option = planSelect.options[planSelect.selectedIndex];
    const planPrice = parseFloat(option.dataset.price) || 0;
    const customPrice = parseFloat(customPriceInput.value) || 0;
    const duration = parseInt(durationSelect.value) || 1;

    const pricePerMonth = customPrice > 0 ? customPrice : planPrice;
    const subtotal = pricePerMonth * duration;
    const iva = subtotal * 0.16;
    const total = subtotal + iva;

    document.getElementById('summary_price{{ $shop->id }}').textContent = '$' + pricePerMonth.toFixed(2);
    document.getElementById('summary_duration{{ $shop->id }}').textContent = duration + (duration === 1 ? ' mes' : ' meses');
    document.getElementById('summary_subtotal{{ $shop->id }}').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('summary_iva{{ $shop->id }}').textContent = '$' + iva.toFixed(2);
    document.getElementById('summary_total{{ $shop->id }}').textContent = '$' + total.toFixed(2);

    // Calcular fecha de vencimiento
    const today = new Date();
    today.setMonth(today.getMonth() + duration);
    const expiresDate = today.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
    document.getElementById('summary_expires{{ $shop->id }}').textContent = expiresDate;
}

// Inicializar cuando se abre el modal
document.getElementById('changePlanModal{{ $shop->id }}').addEventListener('shown.bs.modal', function () {
    calculateTotal{{ $shop->id }}();
});
</script>
