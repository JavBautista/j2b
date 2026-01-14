{{-- Modal Estadisticas/Actividad de Tienda --}}
@php
    // Usuarios por rol (query directa porque Shop no tiene relación users)
    $users = \App\Models\User::where('shop_id', $shop->id)->with('roles')->get();
    $admins_full = $users->filter(fn($u) => strtolower($u->roles->first()?->name ?? '') === 'admin' && !$u->limited)->count();
    $admins_limitados = $users->filter(fn($u) => strtolower($u->roles->first()?->name ?? '') === 'admin' && $u->limited)->count();
    $colaboradores = $users->filter(fn($u) => strtolower($u->roles->first()?->name ?? '') === 'colaborador')->count();
    $clientes_user = $users->filter(fn($u) => strtolower($u->roles->first()?->name ?? '') === 'cliente')->count();
    $users_activos = $users->where('active', 1)->count();
    $users_inactivos = $users->where('active', 0)->count();

    // Clientes registrados
    $clientes_total = $shop->clients()->count();
    $clientes_activos = $shop->clients()->where('active', 1)->count();

    // Ventas
    $ventas_total = $shop->receipts()->count();
    $ventas_30dias = $shop->receipts()->where('created_at', '>=', now()->subDays(30))->count();
    $ultima_venta = $shop->receipts()->orderBy('created_at', 'desc')->first();

    // Tareas
    $tareas_total = $shop->tasks()->count();
    $tareas_pendientes = $shop->tasks()->where('status', 'PENDIENTE')->count();
    $tareas_30dias = $shop->tasks()->where('created_at', '>=', now()->subDays(30))->count();

    // Nivel de actividad
    $score = 0;
    if ($clientes_total > 0) $score++;
    if ($ventas_total > 0) $score++;
    if ($ventas_30dias > 0) $score += 2;
    if ($tareas_total > 0) $score++;

    $nivel = 'sin_actividad';
    if ($score >= 4) $nivel = 'alta';
    elseif ($score >= 2) $nivel = 'media';
    elseif ($score >= 1) $nivel = 'baja';

    $nivelClases = [
        'alta' => 'j2b-badge-success',
        'media' => 'j2b-badge-warning',
        'baja' => 'j2b-badge-danger',
        'sin_actividad' => 'j2b-badge-dark'
    ];
    $nivelTextos = [
        'alta' => 'Alta',
        'media' => 'Media',
        'baja' => 'Baja',
        'sin_actividad' => 'Sin Actividad'
    ];
@endphp

<div class="modal fade j2b-modal" id="statsModal{{ $shop->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-bar-chart" style="color: var(--j2b-primary);"></i> Actividad de {{ $shop->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                {{-- Header con nivel --}}
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <small style="color: var(--j2b-gray-500);">ID: {{ $shop->id }}</small>
                    </div>
                    <span class="j2b-badge {{ $nivelClases[$nivel] }}" style="font-size: 14px; padding: 8px 16px;">
                        <i class="fa fa-signal"></i> Actividad {{ $nivelTextos[$nivel] }}
                    </span>
                </div>

                {{-- Usuarios --}}
                <div class="j2b-form-section mb-4">
                    <h6 class="j2b-form-section-title"><i class="fa fa-users"></i> Usuarios ({{ $users->count() }})</h6>
                    <div class="row">
                        <div class="col-md-3 col-6 mb-2">
                            <div class="stat-card stat-card-primary">
                                <div class="stat-number">{{ $admins_full }}</div>
                                <div class="stat-label">Admin Full</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="stat-card stat-card-warning">
                                <div class="stat-number">{{ $admins_limitados }}</div>
                                <div class="stat-label">Admin Limitado</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="stat-card stat-card-info">
                                <div class="stat-number">{{ $colaboradores }}</div>
                                <div class="stat-label">Colaboradores</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="stat-card stat-card-success">
                                <div class="stat-number">{{ $clientes_user }}</div>
                                <div class="stat-label">Clientes (user)</div>
                            </div>
                        </div>
                    </div>
                    <small style="color: var(--j2b-gray-500);">
                        <i class="fa fa-check-circle text-success"></i> {{ $users_activos }} activos
                        &nbsp;|&nbsp;
                        <i class="fa fa-times-circle text-danger"></i> {{ $users_inactivos }} inactivos
                    </small>
                </div>

                {{-- Clientes --}}
                <div class="j2b-form-section mb-4">
                    <h6 class="j2b-form-section-title"><i class="fa fa-address-book"></i> Clientes Registrados</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stat-card stat-card-primary">
                                <div class="stat-number">{{ $clientes_total }}</div>
                                <div class="stat-label">Total Clientes</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card stat-card-success">
                                <div class="stat-number">{{ $clientes_activos }}</div>
                                <div class="stat-label">Clientes Activos</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ventas --}}
                <div class="j2b-form-section mb-4">
                    <h6 class="j2b-form-section-title"><i class="fa fa-shopping-cart"></i> Notas de Venta</h6>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="stat-card stat-card-primary">
                                <div class="stat-number">{{ $ventas_total }}</div>
                                <div class="stat-label">Total Ventas</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="stat-card stat-card-success">
                                <div class="stat-number">{{ $ventas_30dias }}</div>
                                <div class="stat-label">Ultimos 30 dias</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="stat-card stat-card-info">
                                <div class="stat-number" style="font-size: 14px;">{{ $ultima_venta ? $ultima_venta->created_at->format('d/m/Y H:i') : 'Nunca' }}</div>
                                <div class="stat-label">Ultima Venta</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tareas --}}
                <div class="j2b-form-section">
                    <h6 class="j2b-form-section-title"><i class="fa fa-tasks"></i> Tareas</h6>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="stat-card stat-card-primary">
                                <div class="stat-number">{{ $tareas_total }}</div>
                                <div class="stat-label">Total Tareas</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="stat-card stat-card-warning">
                                <div class="stat-number">{{ $tareas_pendientes }}</div>
                                <div class="stat-label">Pendientes</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="stat-card stat-card-success">
                                <div class="stat-number">{{ $tareas_30dias }}</div>
                                <div class="stat-label">Ultimos 30 dias</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="j2b-btn j2b-btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
