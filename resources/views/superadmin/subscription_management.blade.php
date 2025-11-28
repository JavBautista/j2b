@extends('superadmin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <i class="fa fa-credit-card"></i> Gestión de Suscripciones
            <p class="text-muted small mb-0">Administra las suscripciones de todas las tiendas</p>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Tienda</th>
                            <th width="10%">Plan</th>
                            <th width="10%">Estado</th>
                            <th width="10%">Días Restantes</th>
                            <th width="12%">Vencimiento</th>
                            <th width="10%">Dueño</th>
                            <th width="8%">Activo</th>
                            <th width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shops as $shop)
                            <tr>
                                <td>{{ $shop->id }}</td>
                                <td>
                                    <strong>{{ $shop->name }}</strong>
                                    @if($shop->is_trial)
                                        <br><small class="badge bg-info">EN TRIAL</small>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->plan)
                                        <span class="badge bg-secondary">{{ $shop->plan->name }}</span>
                                        <br><small>{{ $shop->plan->currency }} ${{ $shop->plan->price }}</small>
                                    @else
                                        <span class="text-muted">Sin plan</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->subscription_status === 'trial')
                                        <span class="badge bg-info">Trial</span>
                                    @elseif($shop->subscription_status === 'active')
                                        <span class="badge bg-success">Activo</span>
                                    @elseif($shop->subscription_status === 'grace_period')
                                        <span class="badge bg-warning">Gracia</span>
                                    @elseif($shop->subscription_status === 'expired')
                                        <span class="badge bg-danger">Vencido</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $shop->subscription_status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $days = $shop->daysRemaining();
                                    @endphp
                                    @if($days > 0)
                                        <span class="text-success">{{ $days }} días</span>
                                    @elseif($days === 0)
                                        <span class="text-warning">Vence hoy</span>
                                    @else
                                        <span class="text-danger">Vencido</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->is_trial && $shop->trial_ends_at)
                                        <small>{{ $shop->trial_ends_at->format('d/m/Y') }}</small>
                                    @elseif($shop->subscription_ends_at)
                                        <small>{{ $shop->subscription_ends_at->format('d/m/Y') }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->owner)
                                        <small>{{ $shop->owner->name }}</small>
                                    @else
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#assignOwnerModal{{ $shop->id }}">
                                            <i class="fa fa-user-plus"></i> Asignar
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    @if($shop->active)
                                        <span class="badge bg-success">Sí</span>
                                    @else
                                        <span class="badge bg-danger">No</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group-vertical d-grid gap-1" role="group">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#extendModal{{ $shop->id }}">
                                                <i class="fa fa-clock-o"></i> Extender
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#changePlanModal{{ $shop->id }}">
                                                <i class="fa fa-exchange"></i> Cambiar Plan
                                            </button>
                                        </div>
                                        <form method="POST" action="{{ route('superadmin.shops.toggle-active', $shop->id) }}" onsubmit="return confirm('¿Estás seguro? Esto también {{ $shop->active ? 'desactivará' : 'reactivará' }} TODOS los usuarios de esta tienda.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-{{ $shop->active ? 'danger' : 'warning' }} w-100">
                                                <i class="fa fa-{{ $shop->active ? 'ban' : 'check-circle' }}"></i> {{ $shop->active ? 'Desactivar' : 'Reactivar' }}
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Modal Extender Trial/Suscripción -->
                                    <div class="modal fade" id="extendModal{{ $shop->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('superadmin.shops.extend-trial', $shop->id) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Extender Suscripción: {{ $shop->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Días a extender:</label>
                                                            <input type="number" name="days" class="form-control" min="1" max="365" value="30" required>
                                                            <small class="text-muted">Máximo 365 días</small>
                                                        </div>
                                                        <div class="alert alert-info">
                                                            <strong>Estado actual:</strong> {{ ucfirst($shop->subscription_status) }}<br>
                                                            @if($shop->is_trial && $shop->trial_ends_at)
                                                                <strong>Trial vence:</strong> {{ $shop->trial_ends_at->format('d/m/Y H:i') }}
                                                            @elseif($shop->subscription_ends_at)
                                                                <strong>Suscripción vence:</strong> {{ $shop->subscription_ends_at->format('d/m/Y H:i') }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fa fa-check"></i> Extender
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Cambiar Plan -->
                                    <div class="modal fade" id="changePlanModal{{ $shop->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('superadmin.shops.change-plan', $shop->id) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Cambiar Plan: {{ $shop->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Seleccionar Plan:</label>
                                                            <select name="plan_id" class="form-control" required>
                                                                <option value="">-- Selecciona un plan --</option>
                                                                @foreach($plans as $plan)
                                                                    <option value="{{ $plan->id }}" {{ $shop->plan_id == $plan->id ? 'selected' : '' }}>
                                                                        {{ $plan->name }} - {{ $plan->currency }} ${{ $plan->price }}/mes
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Duración (meses):</label>
                                                            <select name="duration_months" class="form-control" required>
                                                                <option value="1">1 mes</option>
                                                                <option value="3">3 meses</option>
                                                                <option value="6">6 meses</option>
                                                                <option value="12">12 meses (anual)</option>
                                                            </select>
                                                        </div>
                                                        <hr>
                                                        <div class="mb-3">
                                                            <label class="form-label">
                                                                <i class="fa fa-star text-warning"></i> Precio Personalizado (opcional)
                                                            </label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">$</span>
                                                                <input type="number" step="0.01" name="custom_price" class="form-control" placeholder="Dejar vacío para usar precio del plan">
                                                                <span class="input-group-text">MXN/mes (sin IVA)</span>
                                                            </div>
                                                            <small class="text-muted">
                                                                💡 Para respetar precios antiguos. Si se deja vacío, usará el precio actual del plan.
                                                            </small>
                                                        </div>
                                                        <div class="alert alert-info">
                                                            <strong>💰 Ejemplo:</strong> Si el plan cuesta $999 pero esta tienda paga $300 (precio antiguo), ingresa 258.62 (300 ÷ 1.16 para quitar IVA).
                                                        </div>
                                                        <div class="alert alert-warning">
                                                            <strong>⚠️ Nota:</strong> Esto cambiará el plan actual y creará un registro de pago manual.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fa fa-check"></i> Cambiar Plan
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Asignar Owner -->
                                    @if(!$shop->owner)
                                        <div class="modal fade" id="assignOwnerModal{{ $shop->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('superadmin.shops.assign-owner', $shop->id) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Asignar Owner: {{ $shop->name }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Seleccionar Usuario Admin:</label>
                                                                <select name="owner_user_id" class="form-control" required>
                                                                    <option value="">-- Selecciona un usuario --</option>
                                                                    @php
                                                                        $shopUsers = \App\Models\User::where('shop_id', $shop->id)
                                                                            ->whereHas('roles', function($query) {
                                                                                $query->where('name', 'admin');
                                                                            })
                                                                            ->get();
                                                                    @endphp
                                                                    @foreach($shopUsers as $user)
                                                                        <option value="{{ $user->id }}">
                                                                            {{ $user->name }} ({{ $user->email }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <small class="text-muted">Solo usuarios con rol Admin de esta tienda</small>
                                                            </div>
                                                            <div class="alert alert-info">
                                                                <strong>ℹ️ Info:</strong> El owner es el usuario principal responsable de la tienda.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="fa fa-user-plus"></i> Asignar Owner
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    No hay tiendas registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-3">
                {{ $shops->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <!-- Resumen de estadísticas -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>En Trial</h5>
                    <h2>{{ $shops->where('subscription_status', 'trial')->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Activos</h5>
                    <h2>{{ $shops->where('subscription_status', 'active')->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Gracia</h5>
                    <h2>{{ $shops->where('subscription_status', 'grace_period')->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5>Vencidos</h5>
                    <h2>{{ $shops->where('subscription_status', 'expired')->count() }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
