@extends('superadmin.layouts.app')

@section('content')
<div class="container-fluid" style="padding: 1.5rem;">

    <!-- Header con título -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                <i class="fa fa-cog" style="color: var(--j2b-primary);"></i> Configuracion de Suscripciones
            </h4>
            <p class="mb-0" style="color: var(--j2b-gray-500);">Configura los parametros globales del sistema de suscripciones</p>
        </div>
        <a href="{{ route('superadmin.index') }}" class="j2b-btn j2b-btn-secondary">
            <i class="fa fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Mensaje de éxito -->
    @if(session('success'))
        <div class="j2b-banner-alert j2b-banner-success mb-4">
            <i class="fa fa-check-circle"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="margin-left: auto;"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('superadmin.subscription-settings.update') }}">
        @csrf

        <!-- Card principal -->
        <div class="j2b-card mb-4">
            <div class="j2b-card-header">
                <h5 class="j2b-card-title mb-0">
                    <i class="fa fa-sliders j2b-text-primary"></i> Parametros del Sistema
                </h5>
            </div>
            <div class="j2b-card-body">
                <div class="row">
                    @foreach($settings as $setting)
                        <div class="col-md-6 mb-4">
                            <div class="j2b-card j2b-card-hover-glow" style="height: 100%;">
                                <div class="j2b-card-body">
                                    <!-- Icono según el tipo de setting -->
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-3" style="flex-shrink: 0;">
                                            @if($setting->key === 'trial_days')
                                                <i class="fa fa-calendar"></i>
                                            @elseif($setting->key === 'grace_period_days')
                                                <i class="fa fa-clock-o"></i>
                                            @elseif($setting->key === 'iva_percentage')
                                                <i class="fa fa-percent"></i>
                                            @elseif($setting->key === 'default_currency')
                                                <i class="fa fa-money"></i>
                                            @elseif(str_contains($setting->key, 'email'))
                                                <i class="fa fa-envelope"></i>
                                            @else
                                                <i class="fa fa-cog"></i>
                                            @endif
                                        </div>
                                        <div style="flex: 1;">
                                            <label for="{{ $setting->key }}" class="j2b-label mb-1" style="font-size: 1em; font-weight: 600;">
                                                {{ $setting->label }}
                                            </label>
                                            @if($setting->description)
                                                <p class="mb-0" style="color: var(--j2b-gray-500); font-size: 0.85em;">{{ $setting->description }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Input según el tipo -->
                                    @if($setting->type === 'boolean')
                                        <select name="settings[{{ $setting->key }}]" class="j2b-select" id="{{ $setting->key }}">
                                            <option value="true" {{ $setting->value === 'true' ? 'selected' : '' }}>Si</option>
                                            <option value="false" {{ $setting->value === 'false' ? 'selected' : '' }}>No</option>
                                        </select>
                                    @elseif($setting->type === 'integer')
                                        <div class="d-flex align-items-center gap-2">
                                            <input
                                                type="number"
                                                name="settings[{{ $setting->key }}]"
                                                class="j2b-input"
                                                id="{{ $setting->key }}"
                                                value="{{ $setting->value }}"
                                                step="1"
                                                min="0"
                                                max="365"
                                                style="max-width: 120px;"
                                            >
                                            @if($setting->key === 'trial_days' || $setting->key === 'grace_period_days')
                                                <span class="j2b-badge j2b-badge-outline">dias</span>
                                            @endif
                                        </div>
                                    @elseif($setting->type === 'decimal')
                                        <div class="d-flex align-items-center gap-2">
                                            <input
                                                type="number"
                                                name="settings[{{ $setting->key }}]"
                                                class="j2b-input"
                                                id="{{ $setting->key }}"
                                                value="{{ $setting->value }}"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                style="max-width: 120px;"
                                            >
                                            @if(str_contains($setting->key, 'percentage'))
                                                <span class="j2b-badge j2b-badge-outline">%</span>
                                            @endif
                                        </div>
                                    @elseif($setting->key === 'default_currency')
                                        <select name="settings[{{ $setting->key }}]" class="j2b-select" id="{{ $setting->key }}">
                                            <option value="MXN" {{ $setting->value === 'MXN' ? 'selected' : '' }}>MXN - Peso Mexicano</option>
                                            <option value="USD" {{ $setting->value === 'USD' ? 'selected' : '' }}>USD - Dolar Estadounidense</option>
                                        </select>
                                    @else
                                        <input
                                            type="text"
                                            name="settings[{{ $setting->key }}]"
                                            class="j2b-input"
                                            id="{{ $setting->key }}"
                                            value="{{ $setting->value }}"
                                        >
                                    @endif

                                    <!-- Info adicional -->
                                    @if($setting->key === 'trial_days')
                                        <div class="mt-2">
                                            <small style="color: var(--j2b-info);">
                                                <i class="fa fa-info-circle"></i> Actualmente: <strong>{{ $setting->value }} dias</strong> de prueba gratuita
                                            </small>
                                        </div>
                                    @endif

                                    @if($setting->key === 'grace_period_days')
                                        <div class="mt-2">
                                            <small style="color: var(--j2b-warning);">
                                                <i class="fa fa-exclamation-triangle"></i> Dias adicionales antes de bloquear la tienda
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="j2b-btn j2b-btn-primary j2b-btn-lg">
                <i class="fa fa-save"></i> Guardar Configuracion
            </button>
            <a href="{{ route('superadmin.index') }}" class="j2b-btn j2b-btn-secondary j2b-btn-lg">
                <i class="fa fa-times"></i> Cancelar
            </a>
        </div>
    </form>

    <!-- Card de información -->
    <div class="j2b-card j2b-card-accent-left" style="border-left-color: var(--j2b-info) !important;">
        <div class="j2b-card-header" style="background: rgba(0, 217, 245, 0.1);">
            <h5 class="j2b-card-title mb-0">
                <i class="fa fa-info-circle" style="color: var(--j2b-info);"></i> Informacion Importante
            </h5>
        </div>
        <div class="j2b-card-body">
            <div class="row">
                <div class="col-md-6">
                    <ul class="mb-0" style="color: var(--j2b-gray-600); line-height: 2;">
                        <li><strong style="color: var(--j2b-dark);">Trial Days:</strong> Numero de dias que una tienda nueva tendra acceso completo gratuito</li>
                        <li><strong style="color: var(--j2b-dark);">Grace Period Days:</strong> Dias adicionales despues del vencimiento antes de bloquear la tienda</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="mb-0" style="color: var(--j2b-gray-600); line-height: 2;">
                        <li><strong style="color: var(--j2b-dark);">Emails de Recordatorio:</strong> Se envian automaticamente segun la configuracion</li>
                        <li><strong style="color: var(--j2b-dark);">IVA Percentage:</strong> Porcentaje aplicado a todos los planes (16% en Mexico)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
/* Estilos para el alert success */
.j2b-banner-alert.j2b-banner-success {
    background: rgba(0, 245, 160, 0.1);
    border: 1px solid var(--j2b-primary);
    border-radius: var(--j2b-radius-md);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--j2b-dark);
}
.j2b-banner-alert.j2b-banner-success i {
    color: var(--j2b-primary);
    font-size: 1.2em;
}
</style>
@endsection
