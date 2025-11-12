@extends('superadmin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <i class="fa fa-cog"></i> Configuración de Suscripciones
            <p class="text-muted small mb-0">Configura los parámetros globales del sistema de suscripciones</p>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('superadmin.subscription-settings.update') }}">
                @csrf

                <div class="row">
                    @foreach($settings as $setting)
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <label for="{{ $setting->key }}" class="form-label fw-bold">
                                        {{ $setting->label }}
                                    </label>

                                    @if($setting->description)
                                        <p class="text-muted small">{{ $setting->description }}</p>
                                    @endif

                                    @if($setting->type === 'boolean')
                                        <select name="settings[{{ $setting->key }}]" class="form-control" id="{{ $setting->key }}">
                                            <option value="true" {{ $setting->value === 'true' ? 'selected' : '' }}>Sí</option>
                                            <option value="false" {{ $setting->value === 'false' ? 'selected' : '' }}>No</option>
                                        </select>
                                    @elseif($setting->type === 'integer')
                                        <input
                                            type="number"
                                            name="settings[{{ $setting->key }}]"
                                            class="form-control"
                                            id="{{ $setting->key }}"
                                            value="{{ $setting->value }}"
                                            step="1"
                                            min="0"
                                            max="365"
                                        >
                                    @elseif($setting->type === 'decimal')
                                        <input
                                            type="number"
                                            name="settings[{{ $setting->key }}]"
                                            class="form-control"
                                            id="{{ $setting->key }}"
                                            value="{{ $setting->value }}"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                        >
                                    @elseif($setting->key === 'default_currency')
                                        <select name="settings[{{ $setting->key }}]" class="form-control" id="{{ $setting->key }}">
                                            <option value="MXN" {{ $setting->value === 'MXN' ? 'selected' : '' }}>MXN - Peso Mexicano</option>
                                            <option value="USD" {{ $setting->value === 'USD' ? 'selected' : '' }}>USD - Dólar Estadounidense</option>
                                        </select>
                                    @else
                                        <input
                                            type="text"
                                            name="settings[{{ $setting->key }}]"
                                            class="form-control"
                                            id="{{ $setting->key }}"
                                            value="{{ $setting->value }}"
                                        >
                                    @endif

                                    @if($setting->key === 'trial_days')
                                        <small class="text-info">
                                            <i class="fa fa-info-circle"></i> Actualmente: {{ $setting->value }} días de prueba gratuita
                                        </small>
                                    @endif

                                    @if($setting->key === 'grace_period_days')
                                        <small class="text-warning">
                                            <i class="fa fa-clock-o"></i> Días adicionales antes de bloquear la tienda
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-save"></i> Guardar Configuración
                    </button>
                    <a href="{{ route('superadmin.index') }}" class="btn btn-secondary btn-lg">
                        <i class="fa fa-arrow-left"></i> Volver
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h5><i class="fa fa-info-circle"></i> Información Importante</h5>
                    <ul class="mb-0">
                        <li><strong>Trial Days:</strong> Número de días que una tienda nueva tendrá acceso completo gratuito</li>
                        <li><strong>Grace Period Days:</strong> Días adicionales después del vencimiento antes de bloquear la tienda</li>
                        <li><strong>Emails de Recordatorio:</strong> Se envían automáticamente según la configuración</li>
                        <li><strong>IVA Percentage:</strong> Porcentaje aplicado a todos los planes (16% en México)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
