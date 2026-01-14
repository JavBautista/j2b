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

    <!-- Filtros -->
    <div class="j2b-card mb-4">
        <div class="j2b-card-body py-3">
            <form method="GET" action="{{ route('superadmin.subscription-management') }}" class="row g-2 align-items-end">
                <!-- Buscar por nombre -->
                <div class="col-md-3">
                    <label class="j2b-label mb-1"><i class="fa fa-search"></i> Buscar tienda</label>
                    <input type="text" name="buscar" class="j2b-input" placeholder="Nombre de tienda..." value="{{ request('buscar') }}">
                </div>

                <!-- Filtro por Plan -->
                <div class="col-md-2">
                    <label class="j2b-label mb-1"><i class="fa fa-cube"></i> Plan</label>
                    <select name="plan_id" class="j2b-select">
                        <option value="">Todos</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro por Estado -->
                <div class="col-md-2">
                    <label class="j2b-label mb-1"><i class="fa fa-tag"></i> Estado</label>
                    <select name="estado" class="j2b-select">
                        <option value="">Todos</option>
                        <option value="trial" {{ request('estado') == 'trial' ? 'selected' : '' }}>Trial</option>
                        <option value="active" {{ request('estado') == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="grace_period" {{ request('estado') == 'grace_period' ? 'selected' : '' }}>Gracia</option>
                        <option value="expired" {{ request('estado') == 'expired' ? 'selected' : '' }}>Vencido</option>
                        <option value="cancelled" {{ request('estado') == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>

                <!-- Filtro por Activo/Inactivo -->
                <div class="col-md-2">
                    <label class="j2b-label mb-1"><i class="fa fa-power-off"></i> Tienda</label>
                    <select name="activo" class="j2b-select">
                        <option value="">Todas</option>
                        <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activas</option>
                        <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivas</option>
                    </select>
                </div>

                <!-- Botones -->
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="j2b-btn j2b-btn-primary">
                            <i class="fa fa-filter"></i> Filtrar
                        </button>
                        <a href="{{ route('superadmin.subscription-management') }}" class="j2b-btn j2b-btn-outline">
                            <i class="fa fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
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
                            <th style="width: 80px;">Logo</th>
                            <th style="width: 120px;">Plan</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 100px;">Dias</th>
                            <th style="width: 100px;">Vence</th>
                            <th style="width: 90px;">Creacion</th>
                            <th style="width: 120px;">Admin</th>
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
                                    @if($shop->logo)
                                        <img src="{{ asset('storage/' . $shop->logo) }}" alt="{{ $shop->name }}"
                                             style="max-width: 60px; max-height: 40px; border-radius: 4px; object-fit: contain;">
                                    @else
                                        <div style="width: 60px; height: 40px; background: var(--j2b-gray-100); border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fa fa-image" style="color: var(--j2b-gray-400);"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->plan)
                                        <span class="j2b-badge j2b-badge-primary">{{ $shop->plan->name }}</span>
                                        <br>
                                        @if($shop->monthly_price)
                                            <small style="color: var(--j2b-primary); font-weight: 600;">${{ number_format($shop->monthly_price, 2) }}</small>
                                            <button type="button" class="btn btn-link p-0 ml-1" data-bs-toggle="modal" data-bs-target="#shopConfigModal{{ $shop->id }}" title="Editar config" style="font-size: 10px;">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                        @else
                                            <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-warning" data-bs-toggle="modal" data-bs-target="#shopConfigModal{{ $shop->id }}" title="Configurar precios" style="font-size: 10px; padding: 2px 6px;">
                                                <i class="fa fa-exclamation-triangle"></i> Configurar
                                            </button>
                                        @endif
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
                                    @if($shop->created_at)
                                        <small style="color: var(--j2b-gray-600);">{{ $shop->created_at->format('d/m/Y') }}</small>
                                    @else
                                        <span class="j2b-text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->owner)
                                        <small style="color: var(--j2b-dark);">{{ $shop->owner->name }}</small>
                                    @else
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" data-bs-toggle="modal" data-bs-target="#assignOwnerModal{{ $shop->id }}" title="Asignar Admin">
                                            <i class="fa fa-user-plus"></i>
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" data-bs-toggle="modal" data-bs-target="#shopInfoModal{{ $shop->id }}" title="Ver Info">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-info" data-bs-toggle="modal" data-bs-target="#statsModal{{ $shop->id }}" title="Actividad">
                                            <i class="fa fa-bar-chart"></i>
                                        </button>
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-primary" data-bs-toggle="modal" data-bs-target="#extendModal{{ $shop->id }}" title="Extender">
                                            <i class="fa fa-clock-o"></i>
                                        </button>
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-secondary" data-bs-toggle="modal" data-bs-target="#changePlanModal{{ $shop->id }}" title="Cambiar Plan">
                                            <i class="fa fa-exchange"></i>
                                        </button>
                                        <form id="toggleForm{{ $shop->id }}" method="POST" action="{{ route('superadmin.shops.toggle-active', $shop->id) }}" style="display: inline;">
                                            @csrf
                                            <button type="button" class="j2b-btn j2b-btn-sm {{ $shop->active ? 'j2b-btn-danger' : 'j2b-btn-success' }}" title="{{ $shop->active ? 'Desactivar' : 'Reactivar' }}" onclick="confirmToggle({{ $shop->id }}, {{ $shop->active ? 'true' : 'false' }}, '{{ $shop->name }}', '{{ $shop->owner ? $shop->owner->name : 'Sin owner' }}')">
                                                <i class="fa fa-{{ $shop->active ? 'ban' : 'check-circle' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
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
        @include('superadmin.partials.modal_shop_config', ['shop' => $shop, 'basicPlan' => $basicPlan])
        @include('superadmin.partials.modal_shop_info', ['shop' => $shop])
        @include('superadmin.partials.modal_shop_stats', ['shop' => $shop])
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

/* Stat cards para modal estadísticas */
.stat-card {
    background: var(--j2b-white);
    border-radius: var(--j2b-radius-md);
    padding: 1rem;
    text-align: center;
    border: 1px solid var(--j2b-gray-200);
    transition: 150ms ease;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--j2b-shadow-sm);
}
.stat-number {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
}
.stat-label {
    font-size: 12px;
    color: var(--j2b-gray-500);
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stat-card-primary { border-left: 4px solid var(--j2b-primary); }
.stat-card-primary .stat-number { color: var(--j2b-primary); }
.stat-card-success { border-left: 4px solid var(--j2b-success); }
.stat-card-success .stat-number { color: var(--j2b-success); }
.stat-card-warning { border-left: 4px solid var(--j2b-warning); }
.stat-card-warning .stat-number { color: var(--j2b-warning); }
.stat-card-info { border-left: 4px solid var(--j2b-info); }
.stat-card-info .stat-number { color: var(--j2b-info); }
.stat-card-danger { border-left: 4px solid var(--j2b-danger); }
.stat-card-danger .stat-number { color: var(--j2b-danger); }
</style>

<script>
function confirmToggle(shopId, isActive, shopName, ownerName) {
    if (isActive) {
        // Desactivar tienda
        Swal.fire({
            title: '¿Desactivar tienda?',
            html: `
                <div style="text-align: left;">
                    <p><strong>Tienda:</strong> ${shopName}</p>
                    <p class="text-danger"><i class="fa fa-exclamation-triangle"></i> Esta accion desactivara:</p>
                    <ul>
                        <li>La tienda completa</li>
                        <li><strong>TODOS</strong> los usuarios de esta tienda</li>
                    </ul>
                    <p class="text-muted">Los usuarios no podran iniciar sesion hasta que reactives la tienda.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-ban"></i> Si, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('toggleForm' + shopId).submit();
            }
        });
    } else {
        // Reactivar tienda
        Swal.fire({
            title: '¿Reactivar tienda?',
            html: `
                <div style="text-align: left;">
                    <p><strong>Tienda:</strong> ${shopName}</p>
                    <p class="text-success"><i class="fa fa-check-circle"></i> Esta accion reactivara:</p>
                    <ul>
                        <li>La tienda</li>
                        <li>Solo el usuario <strong>owner</strong>: ${ownerName}</li>
                    </ul>
                    <p class="text-muted">Los demas usuarios deberan ser reactivados manualmente si es necesario.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00f5a0',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-check-circle"></i> Si, reactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('toggleForm' + shopId).submit();
            }
        });
    }
}
</script>
@endsection
