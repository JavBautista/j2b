@extends('admin.layouts.app')

@section('page-header')
    <x-admin.page-header
        title="Moneda e Impuesto"
        parent-label="Configuraciones"
        :parent-route="route('admin.configurations')"
    />
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-coins me-2"></i>Configuracion de Moneda e Impuesto</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.configurations.currency.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Moneda -->
                        <div class="mb-4">
                            <label for="currency" class="form-label fw-bold">
                                <i class="fas fa-dollar-sign text-primary me-1"></i>Moneda *
                            </label>
                            <select class="form-select @error('currency') is-invalid @enderror"
                                    id="currency" name="currency">
                                <option value="MXN" {{ old('currency', $shop->currency) == 'MXN' ? 'selected' : '' }}>MXN - Peso Mexicano</option>
                                <option value="USD" {{ old('currency', $shop->currency) == 'USD' ? 'selected' : '' }}>USD - Dolar Estadounidense</option>
                            </select>
                            @error('currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                La moneda se usara en notas de venta, ordenes de compra, reportes y PDFs.
                            </div>
                        </div>

                        <!-- Nombre del Impuesto -->
                        <div class="mb-4">
                            <label for="tax_name" class="form-label fw-bold">
                                <i class="fas fa-percent text-success me-1"></i>Nombre del Impuesto
                            </label>
                            <input type="text"
                                   class="form-control @error('tax_name') is-invalid @enderror"
                                   id="tax_name"
                                   name="tax_name"
                                   value="{{ old('tax_name', $shop->tax_name) }}"
                                   placeholder="Ej. IVA, Tax, IGV o dejar vacio si no aplica"
                                   maxlength="20">
                            @error('tax_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-lightbulb me-1"></i>
                                Dejalo vacio si tu negocio no cobra impuestos.
                            </div>
                        </div>

                        <!-- Tasa del Impuesto -->
                        <div class="mb-4">
                            <label for="tax_rate" class="form-label fw-bold">
                                <i class="fas fa-calculator text-warning me-1"></i>Tasa del Impuesto (%) *
                            </label>
                            <div class="input-group" style="max-width: 200px;">
                                <input type="number"
                                       class="form-control @error('tax_rate') is-invalid @enderror"
                                       id="tax_rate"
                                       name="tax_rate"
                                       value="{{ old('tax_rate', $shop->tax_rate) }}"
                                       min="0"
                                       max="99.99"
                                       step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                            @error('tax_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Coloca 0 si no aplica impuesto. Ejemplos: Mexico 16%, Colombia 19%.
                            </div>
                        </div>

                        <!-- Presets -->
                        <div class="alert alert-light border mb-4">
                            <h6 class="mb-2"><i class="fas fa-magic me-1 text-primary"></i>Configuraciones rapidas</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary preset-btn"
                                        data-currency="MXN" data-tax-name="IVA" data-tax-rate="16">
                                    <i class="me-1">&#127474;&#127485;</i>Mexico (IVA 16%)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                        data-currency="USD" data-tax-name="" data-tax-rate="0">
                                    <i class="me-1">&#127482;&#127480;</i>USA (Sin impuesto)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning preset-btn"
                                        data-currency="USD" data-tax-name="IVA" data-tax-rate="19">
                                    <i class="me-1">&#127464;&#127476;</i>Colombia (IVA 19%)
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.configurations') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="card-body text-center py-3">
                    <small class="text-muted">
                        <i class="fas fa-question-circle me-1"></i>
                        Esta configuracion afecta como se muestran los montos en notas, ordenes de compra, PDFs y reportes.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-label.fw-bold {
    color: #495057;
    font-size: 0.9rem;
}
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.preset-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('currency').value = this.dataset.currency;
            document.getElementById('tax_name').value = this.dataset.taxName;
            document.getElementById('tax_rate').value = this.dataset.taxRate;
        });
    });
});
</script>
@endsection
