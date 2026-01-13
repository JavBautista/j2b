@extends('superadmin.layouts.app')

@section('content')
<div class="container-fluid" style="padding: 1.5rem;">

    <!-- Header con título -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                <i class="fa fa-credit-card" style="color: var(--j2b-primary);"></i> Gestion de Suscripciones
            </h4>
            <p class="mb-0" style="color: var(--j2b-gray-500);">Administra las suscripciones de todas las tiendas</p>
        </div>
        <a href="{{ route('superadmin.subscription-settings') }}" class="j2b-btn j2b-btn-secondary">
            <i class="fa fa-cog"></i> Configuracion
        </a>
    </div>

    <!-- Estadísticas -->
    <div class="j2b-stat-grid mb-4">
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-info">
                <i class="fa fa-flask"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">{{ $shops->where('subscription_status', 'trial')->count() }}</div>
                <div class="j2b-stat-label">En Trial</div>
            </div>
        </div>
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-success">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">{{ $shops->where('subscription_status', 'active')->count() }}</div>
                <div class="j2b-stat-label">Activos</div>
            </div>
        </div>
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-warning">
                <i class="fa fa-clock-o"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">{{ $shops->where('subscription_status', 'grace_period')->count() }}</div>
                <div class="j2b-stat-label">En Gracia</div>
            </div>
        </div>
        <div class="j2b-stat j2b-card-hover-glow">
            <div class="j2b-stat-icon j2b-stat-icon-danger">
                <i class="fa fa-times-circle"></i>
            </div>
            <div class="j2b-stat-content">
                <div class="j2b-stat-value">{{ $shops->where('subscription_status', 'expired')->count() }}</div>
                <div class="j2b-stat-label">Vencidos</div>
            </div>
        </div>
    </div>

    <!-- Mensaje de éxito -->
    @if(session('success'))
        <div class="j2b-banner-alert j2b-banner-success mb-4">
            <i class="fa fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Card principal con tabla -->
    <div class="j2b-card">
        <div class="j2b-card-header d-flex justify-content-between align-items-center">
            <h5 class="j2b-card-title mb-0">
                <i class="fa fa-list j2b-text-primary"></i> Listado de Suscripciones
            </h5>
            <span class="j2b-badge j2b-badge-info">{{ $shops->total() }} tiendas</span>
        </div>
        <div class="j2b-card-body p-0">
            <div class="j2b-table-responsive">
                <table class="j2b-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Tienda</th>
                            <th style="width: 120px;">Plan</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 100px;">Dias</th>
                            <th style="width: 100px;">Vence</th>
                            <th style="width: 120px;">Dueno</th>
                            <th style="width: 180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shops as $shop)
                            <tr>
                                <td>
                                    <span class="j2b-badge j2b-badge-dark">{{ $shop->id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-2" style="font-size: 11px; width: 32px; height: 32px; flex-shrink: 0;">
                                            {{ strtoupper(substr($shop->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong style="color: var(--j2b-dark);">{{ $shop->name }}</strong>
                                            @if($shop->is_trial)
                                                <span class="j2b-badge j2b-badge-info ml-1" style="font-size: 9px;">TRIAL</span>
                                            @endif
                                            <br>
                                            <small style="color: var(--j2b-gray-500);">
                                                @if(!$shop->active)
                                                    <i class="fa fa-ban text-danger"></i> Desactivada
                                                @else
                                                    <i class="fa fa-check text-success"></i> Activa
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($shop->plan)
                                        <span class="j2b-badge j2b-badge-primary">{{ $shop->plan->name }}</span>
                                        <br><small style="color: var(--j2b-primary); font-weight: 600;">${{ $shop->plan->price }}</small>
                                    @else
                                        <span class="j2b-badge j2b-badge-outline">Sin plan</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->subscription_status === 'trial')
                                        <span class="j2b-badge j2b-badge-info"><i class="fa fa-flask"></i> Trial</span>
                                    @elseif($shop->subscription_status === 'active')
                                        <span class="j2b-badge j2b-badge-success"><i class="fa fa-check"></i> Activo</span>
                                    @elseif($shop->subscription_status === 'grace_period')
                                        <span class="j2b-badge j2b-badge-warning"><i class="fa fa-clock-o"></i> Gracia</span>
                                    @elseif($shop->subscription_status === 'expired')
                                        <span class="j2b-badge j2b-badge-danger"><i class="fa fa-times"></i> Vencido</span>
                                    @else
                                        <span class="j2b-badge j2b-badge-outline">{{ $shop->subscription_status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $days = $shop->daysRemaining();
                                    @endphp
                                    @if($days > 7)
                                        <span class="j2b-badge j2b-badge-success">{{ $days }} dias</span>
                                    @elseif($days > 0)
                                        <span class="j2b-badge j2b-badge-warning">{{ $days }} dias</span>
                                    @elseif($days === 0)
                                        <span class="j2b-badge j2b-badge-danger">Hoy</span>
                                    @else
                                        <span class="j2b-badge j2b-badge-danger">Vencido</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->is_trial && $shop->trial_ends_at)
                                        <small style="color: var(--j2b-gray-600);">{{ $shop->trial_ends_at->format('d/m/Y') }}</small>
                                    @elseif($shop->subscription_ends_at)
                                        <small style="color: var(--j2b-gray-600);">{{ $shop->subscription_ends_at->format('d/m/Y') }}</small>
                                    @else
                                        <span class="j2b-text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->owner)
                                        <small style="color: var(--j2b-dark);">{{ $shop->owner->name }}</small>
                                    @else
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" data-bs-toggle="modal" data-bs-target="#assignOwnerModal{{ $shop->id }}">
                                            <i class="fa fa-user-plus"></i> Asignar
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-primary" data-bs-toggle="modal" data-bs-target="#extendModal{{ $shop->id }}" title="Extender">
                                            <i class="fa fa-clock-o"></i>
                                        </button>
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-secondary" data-bs-toggle="modal" data-bs-target="#changePlanModal{{ $shop->id }}" title="Cambiar Plan">
                                            <i class="fa fa-exchange"></i>
                                        </button>
                                        <form method="POST" action="{{ route('superadmin.shops.toggle-active', $shop->id) }}" style="display: inline;" onsubmit="return confirm('¿Estas seguro? Esto tambien {{ $shop->active ? 'desactivara' : 'reactivara' }} TODOS los usuarios de esta tienda.');">
                                            @csrf
                                            <button type="submit" class="j2b-btn j2b-btn-sm {{ $shop->active ? 'j2b-btn-danger' : 'j2b-btn-outline' }}" title="{{ $shop->active ? 'Desactivar' : 'Reactivar' }}">
                                                <i class="fa fa-{{ $shop->active ? 'ban' : 'check-circle' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fa fa-inbox fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                                    <p style="color: var(--j2b-gray-500);">No hay tiendas registradas</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($shops->hasPages())
                <div class="j2b-card-body">
                    {{ $shops->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modales fuera de la tabla --}}
    @foreach($shops as $shop)
        @include('superadmin.partials.modal_extend', ['shop' => $shop])
        @include('superadmin.partials.modal_change_plan', ['shop' => $shop, 'plans' => $plans])
        @if(!$shop->owner)
            @include('superadmin.partials.modal_assign_owner', ['shop' => $shop])
        @endif
    @endforeach

</div>

<style>
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

/* Modal J2B styles */
.j2b-modal .modal-content {
    border: none;
    border-radius: var(--j2b-radius-lg);
    box-shadow: var(--j2b-shadow-lg);
}
.j2b-modal .modal-header {
    background: var(--j2b-gradient-dark);
    color: var(--j2b-white);
    border-radius: var(--j2b-radius-lg) var(--j2b-radius-lg) 0 0;
    padding: 1rem 1.5rem;
    border-bottom: none;
}
.j2b-modal .modal-header .modal-title {
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.j2b-modal .modal-header .btn-close {
    filter: invert(1);
}
.j2b-modal .modal-body {
    padding: 1.5rem;
}
.j2b-modal .modal-footer {
    padding: 1rem 1.5rem;
    background: var(--j2b-gray-100);
    border-top: 1px solid var(--j2b-gray-200);
    border-radius: 0 0 var(--j2b-radius-lg) var(--j2b-radius-lg);
}

/* Banner info para modales */
.j2b-banner-alert.j2b-banner-info {
    background: rgba(0, 217, 245, 0.1);
    border: 1px solid var(--j2b-info);
    border-radius: var(--j2b-radius-md);
    padding: 1rem;
    color: var(--j2b-dark);
}
</style>
@endsection
