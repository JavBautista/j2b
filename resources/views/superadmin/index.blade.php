@extends('superadmin.layouts.app')

@section('content')
<div class="container-fluid" style="padding: 1.5rem;">

    <!-- Welcome Banner -->
    <div class="j2b-banner j2b-banner-gradient mb-4">
        <div class="j2b-banner-content">
            <h2 class="j2b-banner-title">Hola, {{ auth()->user()->name }}</h2>
            <p class="j2b-banner-subtitle">Panel de Super Administrador J2Biznes</p>
        </div>
        <div class="j2b-banner-icon">
            <i class="fa fa-dashboard"></i>
        </div>
    </div>

    <!-- Alertas de suscripciones por vencer -->
    @if($expiringSubscriptions->count() > 0)
    <div class="j2b-banner-alert j2b-banner-warning mb-4">
        <i class="fa fa-exclamation-triangle"></i>
        <span><strong>{{ $expiringSubscriptions->count() }}</strong> suscripciones por vencer en los proximos 7 dias</span>
        <a href="{{ route('superadmin.subscription-management') }}" class="j2b-btn j2b-btn-sm j2b-btn-dark ml-auto">
            Ver detalles
        </a>
    </div>
    @endif

    <!-- Stats Grid -->
    <div class="j2b-stat-grid mb-4">
        <!-- Total Tiendas -->
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-primary">
                <i class="fa fa-shopping-cart"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">{{ number_format($totalShops) }}</div>
                <div class="j2b-stat-label">Tiendas Totales</div>
                <div class="j2b-stat-trend j2b-stat-trend-up">
                    <i class="fa fa-check-circle"></i> {{ $activeShops }} activas
                </div>
            </div>
        </div>

        <!-- Usuarios -->
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-info">
                <i class="fa fa-users"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">{{ number_format($totalUsers) }}</div>
                <div class="j2b-stat-label">Usuarios Totales</div>
                <div class="j2b-stat-trend j2b-stat-trend-up">
                    <i class="fa fa-check-circle"></i> {{ $activeUsers }} activos
                </div>
            </div>
        </div>

        <!-- Suscripciones Activas -->
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-success">
                <i class="fa fa-credit-card"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">{{ number_format($activeSubscriptions) }}</div>
                <div class="j2b-stat-label">Suscripciones Activas</div>
                <div class="j2b-stat-trend">
                    <i class="fa fa-clock-o"></i> {{ $trialShops }} en trial
                </div>
            </div>
        </div>

        <!-- Ingresos del Mes -->
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-warning">
                <i class="fa fa-dollar"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">${{ number_format($monthlyRevenue, 0) }}</div>
                <div class="j2b-stat-label">Ingresos del Mes</div>
                <div class="j2b-stat-trend">
                    <i class="fa fa-calendar"></i> {{ now()->format('F Y') }}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="j2b-card" style="height: 100%;">
                <div class="j2b-card-header">
                    <h5 class="j2b-card-title">
                        <i class="fa fa-bolt j2b-text-primary"></i> Accesos Rapidos
                    </h5>
                </div>
                <div class="j2b-card-body">
                    <a href="{{ route('superadmin.shops') }}" class="quick-link d-flex align-items-center p-3 mb-2 rounded">
                        <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-3">
                            <i class="fa fa-shopping-cart"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Tiendas</strong>
                            <small class="d-block j2b-text-muted">Gestionar tiendas</small>
                        </div>
                        <i class="fa fa-chevron-right j2b-text-muted"></i>
                    </a>

                    <a href="{{ route('superadmin.users') }}" class="quick-link d-flex align-items-center p-3 mb-2 rounded">
                        <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-info mr-3">
                            <i class="fa fa-users"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Usuarios</strong>
                            <small class="d-block j2b-text-muted">Administrar usuarios</small>
                        </div>
                        <i class="fa fa-chevron-right j2b-text-muted"></i>
                    </a>

                    <a href="{{ route('superadmin.plans') }}" class="quick-link d-flex align-items-center p-3 mb-2 rounded">
                        <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-success mr-3">
                            <i class="fa fa-list-alt"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Planes</strong>
                            <small class="d-block j2b-text-muted">Configurar planes</small>
                        </div>
                        <i class="fa fa-chevron-right j2b-text-muted"></i>
                    </a>

                    <a href="{{ route('superadmin.subscription-management') }}" class="quick-link d-flex align-items-center p-3 mb-2 rounded">
                        <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-warning mr-3">
                            <i class="fa fa-credit-card"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Suscripciones</strong>
                            <small class="d-block j2b-text-muted">Gestionar pagos</small>
                        </div>
                        <i class="fa fa-chevron-right j2b-text-muted"></i>
                    </a>

                    <a href="{{ route('superadmin.pre-registers') }}" class="quick-link d-flex align-items-center p-3 mb-2 rounded">
                        <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-danger mr-3">
                            <i class="fa fa-user-plus"></i>
                        </div>
                        <div style="flex: 1;">
                            <strong>Pre-Registros</strong>
                            <small class="d-block j2b-text-muted">Solicitudes nuevas</small>
                        </div>
                        <i class="fa fa-chevron-right j2b-text-muted"></i>
                    </a>

                </div>
            </div>
        </div>

        <!-- Tiendas Recientes -->
        <div class="col-lg-8 mb-4">
            <div class="j2b-card" style="height: 100%;">
                <div class="j2b-card-header d-flex justify-content-between align-items-center">
                    <h5 class="j2b-card-title mb-0">
                        <i class="fa fa-clock-o j2b-text-primary"></i> Tiendas Recientes
                    </h5>
                    <a href="{{ route('superadmin.shops') }}" class="j2b-btn j2b-btn-sm j2b-btn-outline">
                        Ver todas
                    </a>
                </div>
                <div class="j2b-card-body p-0">
                    <div class="j2b-table-responsive">
                        <table class="j2b-table">
                            <thead>
                                <tr>
                                    <th>Tienda</th>
                                    <th>Plan</th>
                                    <th>Estado</th>
                                    <th>Creada</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentShops as $shop)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-2" style="font-size: 12px; width: 28px; height: 28px;">
                                                {{ strtoupper(substr($shop->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong>{{ $shop->name }}</strong>
                                                @if($shop->owner)
                                                <small class="d-block j2b-text-muted">{{ $shop->owner->email }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($shop->plan)
                                            <span class="j2b-badge j2b-badge-primary">{{ $shop->plan->name }}</span>
                                        @else
                                            <span class="j2b-badge j2b-badge-outline">Sin plan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($shop->is_trial)
                                            <span class="j2b-badge j2b-badge-warning">Trial</span>
                                        @elseif($shop->active && $shop->subscription_status == 'active')
                                            <span class="j2b-badge j2b-badge-success">Activa</span>
                                        @elseif(!$shop->active)
                                            <span class="j2b-badge j2b-badge-danger">Inactiva</span>
                                        @else
                                            <span class="j2b-badge j2b-badge-outline">{{ $shop->subscription_status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="j2b-text-muted">{{ $shop->created_at->diffForHumans() }}</small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 j2b-text-muted">
                                        <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                        <p class="mb-0">No hay tiendas registradas</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suscripciones por vencer (si hay) -->
    @if($expiringSubscriptions->count() > 0)
    <div class="j2b-card j2b-card-accent-left mb-4" style="border-left-color: var(--j2b-warning) !important;">
        <div class="j2b-card-header" style="background: rgba(255, 193, 7, 0.1);">
            <h5 class="j2b-card-title mb-0">
                <i class="fa fa-exclamation-triangle" style="color: var(--j2b-warning);"></i>
                Suscripciones por Vencer (7 dias)
            </h5>
        </div>
        <div class="j2b-card-body p-0">
            <div class="j2b-table-responsive">
                <table class="j2b-table">
                    <thead>
                        <tr>
                            <th>Tienda</th>
                            <th>Tipo</th>
                            <th>Vence</th>
                            <th>Dias Restantes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expiringSubscriptions as $shop)
                        <tr>
                            <td>
                                <strong>{{ $shop->name }}</strong>
                                @if($shop->owner)
                                <small class="d-block j2b-text-muted">{{ $shop->owner->email }}</small>
                                @endif
                            </td>
                            <td>
                                @if($shop->is_trial)
                                    <span class="j2b-badge j2b-badge-warning">Trial</span>
                                @else
                                    <span class="j2b-badge j2b-badge-info">{{ $shop->plan->name ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td>
                                @if($shop->is_trial && $shop->trial_ends_at)
                                    {{ $shop->trial_ends_at->format('d/m/Y') }}
                                @elseif($shop->subscription_ends_at)
                                    {{ $shop->subscription_ends_at->format('d/m/Y') }}
                                @endif
                            </td>
                            <td>
                                @php
                                    $endDate = $shop->is_trial ? $shop->trial_ends_at : $shop->subscription_ends_at;
                                    $daysLeft = $endDate ? now()->diffInDays($endDate, false) : 0;
                                @endphp
                                @if($daysLeft <= 2)
                                    <span class="j2b-badge j2b-badge-danger">{{ $daysLeft }} dias</span>
                                @else
                                    <span class="j2b-badge j2b-badge-warning">{{ $daysLeft }} dias</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('superadmin.subscription-management') }}" class="j2b-btn j2b-btn-sm j2b-btn-outline">
                                    <i class="fa fa-cog"></i> Gestionar
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

<style>
/* Estilos adicionales para quick links */
.quick-link {
    background: var(--j2b-gray-100);
    text-decoration: none;
    transition: all 0.2s ease;
    color: inherit;
}
.quick-link:hover {
    background: var(--j2b-gray-200);
    transform: translateX(4px);
    text-decoration: none;
    color: inherit;
}
.quick-link strong {
    color: var(--j2b-dark);
}
</style>
@endsection
