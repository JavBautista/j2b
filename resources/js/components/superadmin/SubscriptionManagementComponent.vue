<template>
  <div>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header con titulo -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-credit-card" style="color: var(--j2b-primary);"></i> Gestion de Suscripciones
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Administra las suscripciones de todas las tiendas</p>
            </div>
            <a href="/superadmin/subscription-settings" class="j2b-btn j2b-btn-secondary">
                <i class="fa fa-cog"></i> Configuracion
            </a>
        </div>

        <!-- Estadisticas -->
        <div class="j2b-stat-grid mb-4">
            <div class="j2b-stat j2b-card-hover-glow">
                <div class="j2b-stat-icon j2b-stat-icon-info">
                    <i class="fa fa-flask"></i>
                </div>
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">{{ numStatus.trial }}</div>
                    <div class="j2b-stat-label">En Trial</div>
                </div>
            </div>
            <div class="j2b-stat j2b-card-hover-glow">
                <div class="j2b-stat-icon j2b-stat-icon-success">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">{{ numStatus.active }}</div>
                    <div class="j2b-stat-label">Activos</div>
                </div>
            </div>
            <div class="j2b-stat j2b-card-hover-glow">
                <div class="j2b-stat-icon j2b-stat-icon-warning">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">{{ numStatus.grace_period }}</div>
                    <div class="j2b-stat-label">En Gracia</div>
                </div>
            </div>
            <div class="j2b-stat j2b-card-hover-glow">
                <div class="j2b-stat-icon j2b-stat-icon-danger">
                    <i class="fa fa-times-circle"></i>
                </div>
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">{{ numStatus.expired }}</div>
                    <div class="j2b-stat-label">Vencidos</div>
                </div>
            </div>
        </div>

        <!-- Card principal -->
        <div class="j2b-card">
            <!-- Filtros -->
            <div class="j2b-card-header">
                <div class="row align-items-end g-2">
                    <div class="col-md-3">
                        <label class="j2b-label mb-1"><i class="fa fa-search"></i> Buscar tienda</label>
                        <input type="text" v-model="buscar" class="j2b-input" placeholder="Nombre de tienda..." @keyup.enter="loadShops(1)">
                    </div>
                    <div class="col-md-2">
                        <label class="j2b-label mb-1"><i class="fa fa-cube"></i> Plan</label>
                        <select v-model="plan_id" class="j2b-select">
                            <option value="">Todos</option>
                            <option v-for="plan in arrayPlans" :key="plan.id" :value="plan.id">
                                {{ plan.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="j2b-label mb-1"><i class="fa fa-tag"></i> Estado</label>
                        <select v-model="estado" class="j2b-select">
                            <option value="">Todos</option>
                            <option value="trial">Trial</option>
                            <option value="active">Activo</option>
                            <option value="grace_period">Gracia</option>
                            <option value="expired">Vencido</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="j2b-label mb-1"><i class="fa fa-power-off"></i> Tienda</label>
                        <select v-model="activo" class="j2b-select">
                            <option value="">Todas</option>
                            <option value="1">Activas</option>
                            <option value="0">Inactivas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="button" @click="loadShops(1)" class="j2b-btn j2b-btn-primary">
                                <i class="fa fa-filter"></i> Filtrar
                            </button>
                            <button type="button" @click="limpiarFiltros()" class="j2b-btn j2b-btn-outline">
                                <i class="fa fa-times"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="j2b-card-body p-0">
                <div class="j2b-table-responsive">
                    <table class="j2b-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="sortable-header" @click="sortTable('id')">
                                    ID <i class="fa" :class="getSortIcon('id')"></i>
                                </th>
                                <th class="sortable-header" @click="sortTable('name')">
                                    Tienda <i class="fa" :class="getSortIcon('name')"></i>
                                </th>
                                <th style="width: 120px;" class="sortable-header" @click="sortTable('plan_name')">
                                    Plan <i class="fa" :class="getSortIcon('plan_name')"></i>
                                </th>
                                <th style="width: 100px;" class="sortable-header" @click="sortTable('subscription_status')">
                                    Estado <i class="fa" :class="getSortIcon('subscription_status')"></i>
                                </th>
                                <th style="width: 100px;" class="sortable-header" @click="sortTable('days_remaining')">
                                    Dias <i class="fa" :class="getSortIcon('days_remaining')"></i>
                                </th>
                                <th style="width: 90px;" class="sortable-header" @click="sortTable('created_at')">
                                    Creacion <i class="fa" :class="getSortIcon('created_at')"></i>
                                </th>
                                <th style="width: 100px;" class="sortable-header" @click="sortTable('owner_name')">
                                    Admin <i class="fa" :class="getSortIcon('owner_name')"></i>
                                </th>
                                <th style="width: 120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="shop in sortedShops" :key="shop.id">
                                <td>
                                    <span class="j2b-badge j2b-badge-dark">{{ shop.id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <!-- Logo o inicial -->
                                        <img v-if="shop.logo" :src="'/storage/' + shop.logo" :alt="shop.name" class="shop-logo-thumb mr-2">
                                        <div v-else class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary mr-2" style="font-size: 11px; width: 36px; height: 36px; flex-shrink: 0;">
                                            {{ shop.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <strong style="color: var(--j2b-dark);">{{ shop.name }}</strong>
                                            <span v-if="shop.is_exempt" class="j2b-badge j2b-badge-dark ml-1" style="font-size: 9px;">EXENTA</span>
                                            <span v-else-if="shop.is_trial && shop.subscription_status === 'trial'" class="j2b-badge j2b-badge-info ml-1" style="font-size: 9px;">TRIAL</span>
                                            <br>
                                            <small style="color: var(--j2b-gray-500);">
                                                <i v-if="!shop.active" class="fa fa-ban text-danger"></i>
                                                <i v-else class="fa fa-check text-success"></i>
                                                {{ shop.active ? 'Activa' : 'Desactivada' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <template v-if="shop.plan">
                                        <span class="j2b-badge j2b-badge-primary">{{ shop.plan.name }}</span>
                                        <!-- Ocultar ciclo y precio si es exenta -->
                                        <template v-if="!shop.is_exempt">
                                            <span v-if="shop.billing_cycle === 'yearly'" class="j2b-badge j2b-badge-info ml-1" style="font-size: 9px;">ANUAL</span>
                                            <span v-else-if="shop.billing_cycle === 'monthly'" class="j2b-badge j2b-badge-outline ml-1" style="font-size: 9px;">MENSUAL</span>
                                            <br>
                                            <small v-if="shop.monthly_price" style="color: var(--j2b-primary); font-weight: 600;">
                                                ${{ formatNumber(shop.billing_cycle === 'yearly' ? shop.yearly_price : shop.monthly_price) }}/{{ shop.billing_cycle === 'yearly' ? 'año' : 'mes' }}
                                                <button type="button" class="btn btn-link p-0 ml-1" @click="abrirModal('config', shop)" title="Editar config" style="font-size: 10px;">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            </small>
                                            <button v-else type="button" class="j2b-btn j2b-btn-sm j2b-btn-warning" @click="abrirModal('config', shop)" title="Configurar precios" style="font-size: 10px; padding: 2px 6px;">
                                                <i class="fa fa-exclamation-triangle"></i> Configurar
                                            </button>
                                        </template>
                                    </template>
                                    <span v-else class="j2b-badge j2b-badge-outline">Sin plan</span>
                                </td>
                                <td>
                                    <span v-if="shop.is_exempt" class="j2b-badge j2b-badge-dark"><i class="fa fa-shield"></i> Exenta</span>
                                    <span v-else-if="shop.subscription_status === 'trial'" class="j2b-badge j2b-badge-info"><i class="fa fa-flask"></i> Trial</span>
                                    <span v-else-if="shop.subscription_status === 'active'" class="j2b-badge j2b-badge-success"><i class="fa fa-check"></i> Activo</span>
                                    <span v-else-if="shop.subscription_status === 'grace_period'" class="j2b-badge j2b-badge-warning"><i class="fa fa-clock-o"></i> Gracia</span>
                                    <span v-else-if="shop.subscription_status === 'expired'" class="j2b-badge j2b-badge-danger"><i class="fa fa-times"></i> Vencido</span>
                                    <span v-else class="j2b-badge j2b-badge-outline">{{ shop.subscription_status }}</span>
                                </td>
                                <td>
                                    <!-- Si está cancelled o expired sin fechas, mostrar - -->
                                    <span v-if="shop.subscription_status === 'cancelled'" class="j2b-text-muted">-</span>
                                    <span v-else-if="getDaysRemaining(shop) === -1 && shop.subscription_status === 'expired'" class="j2b-badge j2b-badge-danger">Vencido</span>
                                    <span v-else-if="getDaysRemaining(shop) === -1" class="j2b-text-muted">-</span>
                                    <span v-else-if="getDaysRemaining(shop) > 7" class="j2b-badge j2b-badge-success">{{ getDaysRemaining(shop) }} dias</span>
                                    <span v-else-if="getDaysRemaining(shop) > 0" class="j2b-badge j2b-badge-warning">{{ getDaysRemaining(shop) }} dias</span>
                                    <span v-else-if="getDaysRemaining(shop) === 0" class="j2b-badge j2b-badge-danger">Hoy</span>
                                    <span v-else class="j2b-badge j2b-badge-danger">Vencido</span>
                                </td>
                                <td>
                                    <small v-if="shop.created_at" style="color: var(--j2b-gray-600);">{{ formatDate(shop.created_at) }}</small>
                                    <span v-else class="j2b-text-muted">-</span>
                                </td>
                                <td>
                                    <small v-if="shop.owner" style="color: var(--j2b-dark);">{{ shop.owner.name }}</small>
                                    <button v-else type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="abrirModal('asignar_owner', shop)" title="Asignar Admin">
                                        <i class="fa fa-user-plus"></i>
                                    </button>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 align-items-center">
                                        <!-- Acciones principales (consulta) -->
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="abrirModal('ver_info', shop)" title="Ver Info">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-info" @click="abrirModal('stats', shop)" title="Actividad">
                                            <i class="fa fa-bar-chart"></i>
                                        </button>
                                        <!-- Dropdown acciones secundarias -->
                                        <div class="action-dropdown">
                                            <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-secondary" @click.stop="toggleDropdown(shop.id)" title="Más acciones">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </button>
                                            <div class="action-dropdown-menu" :class="{ 'show': openDropdown === shop.id }">
                                                <!-- Solo mostrar acciones de pago si NO es exenta -->
                                                <template v-if="!shop.is_exempt">
                                                    <button type="button" class="action-dropdown-item text-success" @click="abrirModal('registrar_pago', shop); closeDropdown()">
                                                        <i class="fa fa-money text-success"></i> Registrar Pago
                                                    </button>
                                                    <button type="button" class="action-dropdown-item" @click="abrirModal('historial_pagos', shop); closeDropdown()">
                                                        <i class="fa fa-history text-info"></i> Historial Pagos
                                                    </button>
                                                    <div class="action-dropdown-divider"></div>
                                                    <button type="button" class="action-dropdown-item" @click="abrirModal('extender', shop); closeDropdown()">
                                                        <i class="fa fa-clock-o text-primary"></i> Extender
                                                    </button>
                                                </template>
                                                <button type="button" class="action-dropdown-item" @click="abrirModal('cambiar_plan', shop); closeDropdown()">
                                                    <i class="fa fa-exchange text-warning"></i> Cambiar Plan
                                                </button>
                                                <button type="button" class="action-dropdown-item" @click="abrirModal('config', shop); closeDropdown()">
                                                    <i class="fa fa-cog text-secondary"></i> Configurar
                                                </button>
                                                <div class="action-dropdown-divider"></div>
                                                <button type="button" class="action-dropdown-item" :class="shop.is_exempt ? 'text-warning' : 'text-dark'" @click="toggleExempt(shop); closeDropdown()">
                                                    <i :class="shop.is_exempt ? 'fa fa-unlock' : 'fa fa-shield'"></i>
                                                    {{ shop.is_exempt ? 'Quitar exencion' : 'Marcar exenta' }}
                                                </button>
                                                <button type="button" class="action-dropdown-item" :class="shop.active ? 'text-danger' : 'text-success'" @click="toggleActivo(shop); closeDropdown()">
                                                    <i :class="shop.active ? 'fa fa-ban' : 'fa fa-check-circle'"></i>
                                                    {{ shop.active ? 'Desactivar' : 'Reactivar' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="sortedShops.length === 0">
                                <td colspan="8" class="text-center py-5">
                                    <i class="fa fa-inbox fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                                    <p style="color: var(--j2b-gray-500);">No hay tiendas registradas</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginacion -->
                <div v-if="pagination.last_page > 1" class="d-flex justify-content-between align-items-center p-3" style="border-top: 1px solid var(--j2b-gray-200);">
                    <small style="color: var(--j2b-gray-500);">
                        Mostrando {{ pagination.from || 0 }} - {{ pagination.to || 0 }} de {{ pagination.total || 0 }}
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page - 1)">
                                    <i class="fa fa-chevron-left"></i>
                                </a>
                            </li>
                            <li class="page-item" v-for="page in pagesNumber" :key="page" :class="{ active: page === pagination.current_page }">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(page)">{{ page }}</a>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page + 1)">
                                    <i class="fa fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Principal (multiples acciones) -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modal}" role="dialog">
        <div class="modal-dialog" :class="modalSize" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i :class="modalIcon" style="color: var(--j2b-primary);"></i>
                        {{ tituloModal }}
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body">

                    <!-- Modal Extender Suscripcion (tipoAccion = 1) -->
                    <div v-if="tipoAccion === 1">
                        <div class="j2b-banner-alert j2b-banner-info mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary">
                                    {{ selectedShop?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <strong>{{ selectedShop?.name }}</strong>
                                    <br>
                                    <small>
                                        <span v-if="selectedShop?.is_trial" class="j2b-badge j2b-badge-info">Trial</span>
                                        <span v-else class="j2b-badge j2b-badge-primary">{{ selectedShop?.plan?.name || 'Sin plan' }}</span>
                                        Vence: {{ selectedShop?.is_trial ? formatDate(selectedShop?.trial_ends_at) : formatDate(selectedShop?.subscription_ends_at) }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="j2b-label">
                                <i class="fa fa-calendar-plus-o j2b-text-primary"></i> Dias a extender
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" class="j2b-input" v-model.number="extendDays" min="1" max="365" required style="max-width: 120px;">
                                <span class="j2b-badge j2b-badge-outline">dias</span>
                            </div>
                            <small class="j2b-text-muted">Minimo 1 dia, maximo 365 dias</small>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="extendDays = 7">+7 dias</button>
                            <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="extendDays = 15">+15 dias</button>
                            <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="extendDays = 30">+30 dias</button>
                            <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="extendDays = 90">+90 dias</button>
                        </div>
                    </div>

                    <!-- Modal Cambiar Plan (tipoAccion = 2) -->
                    <div v-if="tipoAccion === 2">
                        <div class="j2b-banner-alert j2b-banner-info mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary">
                                    {{ selectedShop?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <strong>{{ selectedShop?.name }}</strong>
                                    <br>
                                    <small>
                                        Plan actual:
                                        <span v-if="selectedShop?.plan" class="j2b-badge j2b-badge-primary">{{ selectedShop.plan.name }}</span>
                                        <span v-else class="j2b-badge j2b-badge-outline">Sin plan</span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Cambiar Plan -->
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-cube j2b-text-primary"></i> Plan</label>
                                    <select class="j2b-select" v-model="newPlanId">
                                        <option v-for="plan in arrayPlans" :key="plan.id" :value="plan.id">
                                            {{ plan.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Ciclo de Facturacion -->
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-refresh j2b-text-info"></i> Ciclo de Facturacion</label>
                                    <div class="d-flex gap-2">
                                        <label class="j2b-radio-card flex-fill" :class="{ active: newBillingCycle === 'monthly' }">
                                            <input type="radio" v-model="newBillingCycle" value="monthly" class="d-none">
                                            <div class="text-center py-2">
                                                <strong>Mensual</strong>
                                            </div>
                                        </label>
                                        <label class="j2b-radio-card flex-fill" :class="{ active: newBillingCycle === 'yearly' }">
                                            <input type="radio" v-model="newBillingCycle" value="yearly" class="d-none">
                                            <div class="text-center py-2">
                                                <strong>Anual</strong>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Precios personalizados -->
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-dollar j2b-text-success"></i> Precio de esta Tienda</label>
                                    <div class="d-flex gap-3">
                                        <div class="flex-fill">
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" v-model.number="customMonthlyPrice" step="0.01" min="0" placeholder="0.00">
                                                <span class="input-group-text">/mes</span>
                                            </div>
                                        </div>
                                        <div class="flex-fill">
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" v-model.number="customYearlyPrice" step="0.01" min="0" placeholder="0.00">
                                                <span class="input-group-text">/año</span>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="j2b-text-muted">
                                        Precio del plan: ${{ formatNumber(getPlanPrice('monthly')) }}/mes - ${{ formatNumber(getPlanPrice('yearly')) }}/año
                                    </small>
                                </div>

                                <!-- Dia de Corte -->
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-calendar j2b-text-warning"></i> Dia de Corte (del mes)</label>
                                    <select class="j2b-select" v-model.number="newCutoff">
                                        <option value="">Sin definir</option>
                                        <option v-for="dia in 31" :key="dia" :value="dia">
                                            Dia {{ dia }}
                                        </option>
                                    </select>
                                    <small class="j2b-text-muted">Dia del mes en que se cobra/vence la suscripcion</small>
                                </div>

                                <!-- Recalcular fecha -->
                                <div class="mb-3">
                                    <label class="d-flex align-items-center gap-2 p-2 rounded"
                                           :style="recalcularFecha ? 'background: rgba(0,245,160,0.1); border: 1px solid var(--j2b-primary);' : 'background: var(--j2b-gray-100);'"
                                           style="cursor: pointer;">
                                        <input type="checkbox" v-model="recalcularFecha" style="width: 18px; height: 18px;">
                                        <div>
                                            <strong><i class="fa fa-refresh"></i> Recalcular fecha de vencimiento</strong>
                                            <br>
                                            <small class="j2b-text-muted">Establece el proximo vencimiento desde hoy</small>
                                        </div>
                                    </label>
                                    <div v-if="recalcularFecha && newCutoff" class="mt-2 p-2 rounded" style="background: rgba(0,245,160,0.1);">
                                        <small>
                                            <i class="fa fa-arrow-right j2b-text-primary"></i>
                                            Nueva fecha: <strong>{{ calcularNuevaFechaVencimiento() }}</strong>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Info actual -->
                                <div class="j2b-card mb-3" style="background: var(--j2b-gray-100);">
                                    <div class="j2b-card-body" style="font-size: 0.85em;">
                                        <div class="mb-2">
                                            <i class="fa fa-info-circle j2b-text-info"></i> <strong>Estado actual:</strong>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span>Ciclo:</span>
                                            <span class="j2b-badge" :class="selectedShop?.billing_cycle === 'yearly' ? 'j2b-badge-info' : 'j2b-badge-outline'">
                                                {{ selectedShop?.billing_cycle === 'yearly' ? 'ANUAL' : 'MENSUAL' }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span>Dia de corte actual:</span>
                                            <strong>{{ selectedShop?.cutoff ? 'Dia ' + selectedShop.cutoff : 'Sin definir' }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span>Proximo vencimiento:</span>
                                            <strong :class="recalcularFecha ? 'text-decoration-line-through text-muted' : ''">
                                                {{ selectedShop?.subscription_ends_at ? formatDate(selectedShop.subscription_ends_at) : 'Sin fecha' }}
                                            </strong>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span>Dias restantes:</span>
                                            <span :class="getDaysRemaining(selectedShop) > 7 ? 'text-success' : getDaysRemaining(selectedShop) > 0 ? 'text-warning' : 'text-danger'">
                                                <strong>{{ getDaysRemaining(selectedShop) >= 0 ? getDaysRemaining(selectedShop) + ' dias' : 'Vencido' }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nota explicativa -->
                                <div class="j2b-banner-alert j2b-banner-info">
                                    <i class="fa fa-lightbulb-o"></i>
                                    <strong>Nota:</strong> Este modal es para ajustar el plan y corregir configuracion. Para registrar un pago usa el boton "Registrar Pago".
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Config Tienda (tipoAccion = 3) -->
                    <div v-if="tipoAccion === 3">
                        <div class="j2b-banner-alert j2b-banner-info mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary">
                                    {{ selectedShop?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <strong>{{ selectedShop?.name }}</strong>
                                    <br>
                                    <small>
                                        Plan:
                                        <span v-if="selectedShop?.plan" class="j2b-badge j2b-badge-primary">{{ selectedShop.plan.name }}</span>
                                        <span v-else class="j2b-badge j2b-badge-outline">Sin plan</span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-dollar j2b-text-primary"></i> Precio Mensual</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="j2b-badge j2b-badge-dark">$</span>
                                        <input type="number" class="j2b-input" v-model.number="monthlyPrice" step="0.01" min="0" required>
                                        <span class="j2b-badge j2b-badge-outline">/mes</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-calendar j2b-text-warning"></i> Precio Anual</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="j2b-badge j2b-badge-dark">$</span>
                                        <input type="number" class="j2b-input" v-model.number="yearlyPrice" step="0.01" min="0" placeholder="Opcional">
                                        <span class="j2b-badge j2b-badge-outline">/año</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="j2b-text-muted mb-3 d-block">Precios personalizados para esta tienda. Si el anual esta vacio se calcula como mensual x 12.</small>

                        <div class="mb-3">
                            <label class="j2b-label"><i class="fa fa-flask j2b-text-info"></i> Dias de Prueba Gratuita</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" class="j2b-input" v-model.number="trialDays" min="0" max="365" required>
                                <span class="j2b-badge j2b-badge-outline">dias</span>
                            </div>
                            <small class="j2b-text-muted">Dias de trial asignados a esta tienda</small>
                        </div>

                        <div class="mb-3">
                            <label class="j2b-label"><i class="fa fa-clock-o j2b-text-warning"></i> Dias de Gracia</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" class="j2b-input" v-model.number="gracePeriodDays" min="0" max="30" required>
                                <span class="j2b-badge j2b-badge-outline">dias</span>
                            </div>
                            <small class="j2b-text-muted">Dias adicionales antes de bloquear la tienda</small>
                        </div>
                    </div>

                    <!-- Modal Ver Info (tipoAccion = 4) con Tabs -->
                    <div v-if="tipoAccion === 4">
                        <!-- Tabs Header -->
                        <div class="info-tabs-header mb-3">
                            <button type="button" class="info-tab-btn" :class="{ active: infoTab === 'basica' }" @click="infoTab = 'basica'">
                                <i class="fa fa-info-circle"></i> Info Basica
                            </button>
                            <button type="button" class="info-tab-btn" :class="{ active: infoTab === 'direccion' }" @click="infoTab = 'direccion'">
                                <i class="fa fa-map-marker"></i> Direccion
                            </button>
                            <button type="button" class="info-tab-btn" :class="{ active: infoTab === 'banco' }" @click="infoTab = 'banco'">
                                <i class="fa fa-bank"></i> Banco
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div class="info-tab-content">
                            <!-- Tab: Info Basica -->
                            <div v-show="infoTab === 'basica'" class="j2b-form-section" style="margin-bottom: 0;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Nombre de la Tienda</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.name" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Propietario</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.owner_name" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="j2b-form-group">
                                    <label class="j2b-label">Descripcion</label>
                                    <textarea class="j2b-input" rows="2" :value="selectedShop?.description" readonly></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Slogan</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.slogan" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Web</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.web" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Direccion y Contacto -->
                            <div v-show="infoTab === 'direccion'" class="j2b-form-section" style="margin-bottom: 0;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Calle</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.address" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Num. Ext.</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.number_out" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Num. Int.</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.number_int" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Colonia</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.district" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">CP</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.zip_code" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Ciudad</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.city" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Estado</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.state" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Telefono</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.phone" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">WhatsApp</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.whatsapp" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Email</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.email" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Datos Bancarios -->
                            <div v-show="infoTab === 'banco'" class="j2b-form-section" style="margin-bottom: 0;">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Banco</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.bank_name" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Cuenta Principal</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.bank_number" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="j2b-form-group">
                                            <label class="j2b-label">Cuenta Secundaria</label>
                                            <input type="text" class="j2b-input" :value="selectedShop?.bank_number_secondary" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Stats (tipoAccion = 5) con Tabs -->
                    <div v-if="tipoAccion === 5">
                        <div v-if="statsLoading" class="text-center py-5">
                            <i class="fa fa-spinner fa-spin fa-3x" style="color: var(--j2b-primary);"></i>
                            <p class="mt-3">Cargando estadisticas...</p>
                        </div>
                        <div v-else-if="statsData">
                            <!-- Header con ID y badge actividad -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small style="color: var(--j2b-gray-500);">ID: {{ selectedShop?.id }}</small>
                                <span class="j2b-badge" :class="getNivelActividadClass(statsData.nivel_actividad)" style="font-size: 13px; padding: 6px 12px;">
                                    <i class="fa fa-signal"></i> {{ getNivelActividadTexto(statsData.nivel_actividad) }}
                                </span>
                            </div>

                            <!-- Tabs Header -->
                            <div class="info-tabs-header mb-3">
                                <button type="button" class="info-tab-btn" :class="{ active: statsTab === 'usuarios' }" @click="statsTab = 'usuarios'">
                                    <i class="fa fa-users"></i> Usuarios
                                </button>
                                <button type="button" class="info-tab-btn" :class="{ active: statsTab === 'clientes' }" @click="statsTab = 'clientes'">
                                    <i class="fa fa-address-book"></i> Clientes
                                </button>
                                <button type="button" class="info-tab-btn" :class="{ active: statsTab === 'ventas' }" @click="statsTab = 'ventas'">
                                    <i class="fa fa-shopping-cart"></i> Ventas
                                </button>
                                <button type="button" class="info-tab-btn" :class="{ active: statsTab === 'tareas' }" @click="statsTab = 'tareas'">
                                    <i class="fa fa-tasks"></i> Tareas
                                </button>
                            </div>

                            <!-- Tab Content -->
                            <div class="info-tab-content">
                                <!-- Tab: Usuarios -->
                                <div v-show="statsTab === 'usuarios'" class="j2b-form-section" style="margin-bottom: 0;">
                                    <div class="row">
                                        <div class="col-md-3 col-6 mb-2">
                                            <div class="stat-card stat-card-primary">
                                                <div class="stat-number">{{ statsData.usuarios.admins_full }}</div>
                                                <div class="stat-label">Admin Full</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mb-2">
                                            <div class="stat-card stat-card-warning">
                                                <div class="stat-number">{{ statsData.usuarios.admins_limitados }}</div>
                                                <div class="stat-label">Admin Limitado</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mb-2">
                                            <div class="stat-card stat-card-info">
                                                <div class="stat-number">{{ statsData.usuarios.colaboradores }}</div>
                                                <div class="stat-label">Colaboradores</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mb-2">
                                            <div class="stat-card stat-card-success">
                                                <div class="stat-number">{{ statsData.usuarios.clientes }}</div>
                                                <div class="stat-label">Clientes</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <span class="j2b-badge j2b-badge-success mr-2">
                                            <i class="fa fa-check-circle"></i> {{ statsData.usuarios.activos }} activos
                                        </span>
                                        <span class="j2b-badge j2b-badge-danger">
                                            <i class="fa fa-times-circle"></i> {{ statsData.usuarios.inactivos }} inactivos
                                        </span>
                                        <div class="mt-2">
                                            <small style="color: var(--j2b-gray-500);">Total: {{ statsData.usuarios.total }} usuarios</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab: Clientes -->
                                <div v-show="statsTab === 'clientes'" class="j2b-form-section" style="margin-bottom: 0;">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <div class="stat-card stat-card-primary">
                                                <div class="stat-number">{{ statsData.clientes.total }}</div>
                                                <div class="stat-label">Total Clientes</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="stat-card stat-card-success">
                                                <div class="stat-number">{{ statsData.clientes.activos }}</div>
                                                <div class="stat-label">Clientes Activos</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab: Ventas -->
                                <div v-show="statsTab === 'ventas'" class="j2b-form-section" style="margin-bottom: 0;">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <div class="stat-card stat-card-primary">
                                                <div class="stat-number">{{ statsData.ventas.total }}</div>
                                                <div class="stat-label">Total Ventas</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="stat-card stat-card-success">
                                                <div class="stat-number">{{ statsData.ventas.ultimos_30_dias }}</div>
                                                <div class="stat-label">Ultimos 30 dias</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="stat-card stat-card-info">
                                                <div class="stat-number" style="font-size: 14px;">{{ statsData.ventas.ultima_venta || 'Nunca' }}</div>
                                                <div class="stat-label">Ultima Venta</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab: Tareas -->
                                <div v-show="statsTab === 'tareas'" class="j2b-form-section" style="margin-bottom: 0;">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <div class="stat-card stat-card-primary">
                                                <div class="stat-number">{{ statsData.tareas.total }}</div>
                                                <div class="stat-label">Total Tareas</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="stat-card stat-card-warning">
                                                <div class="stat-number">{{ statsData.tareas.pendientes }}</div>
                                                <div class="stat-label">Pendientes</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="stat-card stat-card-success">
                                                <div class="stat-number">{{ statsData.tareas.ultimos_30_dias }}</div>
                                                <div class="stat-label">Ultimos 30 dias</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Asignar Owner (tipoAccion = 6) -->
                    <div v-if="tipoAccion === 6">
                        <div class="j2b-banner-alert j2b-banner-info mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary">
                                    {{ selectedShop?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <strong>{{ selectedShop?.name }}</strong>
                                    <br>
                                    <small class="j2b-text-muted">Esta tienda no tiene un admin principal asignado</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="j2b-label"><i class="fa fa-user j2b-text-primary"></i> Seleccionar Usuario Admin</label>
                            <select class="j2b-select" v-model="ownerUserId" required>
                                <option v-if="shopUsers.length === 0" value="">No hay usuarios admin disponibles</option>
                                <option v-else value="">Seleccionar usuario...</option>
                                <option v-for="user in shopUsers" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                            <small class="j2b-text-muted">Solo usuarios Admin full (no limitados) de esta tienda</small>
                        </div>

                        <div class="j2b-card" style="background: rgba(0, 245, 160, 0.05); border: 1px dashed var(--j2b-primary);">
                            <div class="j2b-card-body py-3">
                                <small style="color: var(--j2b-gray-600);">
                                    <i class="fa fa-info-circle j2b-text-primary"></i>
                                    El <strong>Admin Principal</strong> es el usuario responsable de la cuenta:
                                    <ul class="mb-0 mt-2">
                                        <li>Recibe notificaciones de vencimiento</li>
                                        <li>Emails de recordatorio de pago</li>
                                        <li>Se reactiva automaticamente al reactivar la tienda</li>
                                    </ul>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Registrar Pago (tipoAccion = 7) -->
                    <div v-if="tipoAccion === 7">
                        <div class="j2b-banner-alert j2b-banner-info mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary">
                                    {{ selectedShop?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <strong>{{ selectedShop?.name }}</strong>
                                    <br>
                                    <small>
                                        Plan: <span class="j2b-badge j2b-badge-primary">{{ selectedShop?.plan?.name || 'Sin plan' }}</span>
                                        <span v-if="selectedShop?.billing_cycle === 'yearly'" class="j2b-badge j2b-badge-warning ml-1">Anual</span>
                                        <span v-else class="j2b-badge j2b-badge-info ml-1">Mensual</span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Fila principal: Monto, Fecha, Metodo -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-dollar j2b-text-success"></i> Monto del Pago</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="j2b-badge j2b-badge-dark">$</span>
                                        <input type="number" class="j2b-input" v-model.number="paymentAmount" step="0.01" min="0" required>
                                    </div>
                                    <small class="j2b-text-muted">
                                        Sugerido: ${{ getMontoSugerido() }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-calendar-check-o j2b-text-success"></i> Fecha del Pago</label>
                                    <input type="date" class="j2b-input" v-model="paymentDate" required>
                                    <small class="j2b-text-muted">Cuando el cliente pago</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-credit-card j2b-text-primary"></i> Metodo de Pago</label>
                                    <select class="j2b-select" v-model="paymentMethod" required>
                                        <option value="transfer">Transferencia</option>
                                        <option value="cash">Efectivo</option>
                                        <option value="card">Tarjeta</option>
                                        <option value="other">Otro</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Fila secundaria: IVA, Referencia -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                                        <input type="checkbox" v-model="paymentIncludeIva" style="width: 18px; height: 18px;">
                                        <span class="j2b-label mb-0"><i class="fa fa-percent j2b-text-info"></i> Incluye IVA (16%)</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-hashtag j2b-text-secondary"></i> Referencia</label>
                                    <input type="text" class="j2b-input" v-model="paymentReference" placeholder="Folio transferencia">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="j2b-label"><i class="fa fa-sticky-note j2b-text-warning"></i> Notas</label>
                                    <input type="text" class="j2b-input" v-model="paymentNotes" placeholder="Observaciones...">
                                </div>
                            </div>
                        </div>

                        <div class="j2b-card" style="background: rgba(0, 245, 160, 0.1); border: 2px solid var(--j2b-primary);">
                            <div class="j2b-card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong style="color: var(--j2b-dark);">Resumen del Pago</strong>
                                        <br>
                                        <small class="j2b-text-muted">
                                            {{ selectedShop?.billing_cycle === 'yearly' ? 'Pago anual (12 meses)' : 'Pago mensual (30 dias)' }}
                                            <span v-if="paymentIncludeIva"> - IVA incluido</span>
                                        </small>
                                    </div>
                                    <div class="text-right">
                                        <span style="font-size: 1.5em; font-weight: 700; color: var(--j2b-primary);">
                                            ${{ formatNumber(paymentAmount || 0) }}
                                        </span>
                                        <br>
                                        <small class="j2b-text-muted">Vence: {{ calcularFechaVencimiento() }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Historial Pagos (tipoAccion = 8) -->
                    <div v-if="tipoAccion === 8">
                        <div v-if="historialLoading" class="text-center py-5">
                            <i class="fa fa-spinner fa-spin fa-3x" style="color: var(--j2b-primary);"></i>
                            <p class="mt-3">Cargando historial de pagos...</p>
                        </div>
                        <div v-else>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="j2b-badge j2b-badge-primary">{{ historialData?.total_payments || 0 }} pagos</span>
                                </div>
                                <div>
                                    <strong>Total pagado: </strong>
                                    <span style="color: var(--j2b-success); font-weight: 700;">${{ formatNumber(historialData?.total_paid || 0) }}</span>
                                </div>
                            </div>

                            <div v-if="historialData?.payments?.length > 0" class="j2b-table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="j2b-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Periodo</th>
                                            <th>Monto</th>
                                            <th>Metodo</th>
                                            <th>Registrado por</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="pago in historialData.payments" :key="pago.id">
                                            <td>
                                                <small>{{ pago.date }}</small>
                                            </td>
                                            <td>
                                                <span class="j2b-badge" :class="pago.billing_period === 'yearly' ? 'j2b-badge-warning' : 'j2b-badge-info'">
                                                    {{ pago.billing_period === 'yearly' ? 'Anual' : 'Mensual' }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong style="color: var(--j2b-success);">${{ formatNumber(pago.total_amount) }}</strong>
                                            </td>
                                            <td>
                                                <small>{{ getPaymentMethodLabel(pago.payment_method) }}</small>
                                            </td>
                                            <td>
                                                <small class="j2b-text-muted">{{ pago.registered_by }}</small>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-4">
                                <i class="fa fa-inbox fa-2x mb-2" style="color: var(--j2b-gray-300);"></i>
                                <p class="j2b-text-muted">No hay pagos registrados</p>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">
                        <i class="fa fa-times"></i> {{ [4, 5, 8].includes(tipoAccion) ? 'Cerrar' : 'Cancelar' }}
                    </button>
                    <button v-if="tipoAccion === 1" type="button" class="j2b-btn j2b-btn-primary" @click="extenderSuscripcion()" :disabled="loading">
                        <i class="fa fa-clock-o"></i> Extender
                    </button>
                    <button v-if="tipoAccion === 2" type="button" class="j2b-btn j2b-btn-primary" @click="cambiarPlan()" :disabled="loading">
                        <i class="fa fa-save"></i> Guardar Cambios
                    </button>
                    <button v-if="tipoAccion === 3" type="button" class="j2b-btn j2b-btn-primary" @click="actualizarConfig()" :disabled="loading">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                    <button v-if="tipoAccion === 6" type="button" class="j2b-btn j2b-btn-primary" @click="asignarOwner()" :disabled="loading || !ownerUserId || shopUsers.length === 0">
                        <i class="fa fa-user-plus"></i> Asignar
                    </button>
                    <button v-if="tipoAccion === 7" type="button" class="j2b-btn j2b-btn-success" @click="registrarPago()" :disabled="loading || !paymentAmount">
                        <i class="fa fa-money"></i> Registrar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>

<script>
export default {
    name: 'SubscriptionManagementComponent',
    data() {
        return {
            // Lista de tiendas
            arrayShops: [],

            // Paginacion
            pagination: {
                total: 0,
                current_page: 1,
                per_page: 20,
                last_page: 1,
                from: 0,
                to: 0
            },
            offset: 3,

            // Filtros
            buscar: '',
            plan_id: '',
            estado: '',
            activo: '1', // Por defecto solo tiendas activas

            // Contadores
            numStatus: {
                trial: 0,
                active: 0,
                grace_period: 0,
                expired: 0
            },

            // Planes disponibles
            arrayPlans: [],

            // Modal control
            modal: 0,
            tipoAccion: 0,
            tituloModal: '',
            loading: false,

            // Shop seleccionado
            selectedShop: null,

            // Modal Extender (tipoAccion = 1)
            extendDays: 30,

            // Modal Cambiar Plan (tipoAccion = 2)
            newPlanId: '',
            newBillingCycle: 'monthly',
            newCutoff: '',
            recalcularFecha: false,
            customMonthlyPrice: 0,
            customYearlyPrice: 0,

            // Modal Config (tipoAccion = 3)
            monthlyPrice: 0,
            yearlyPrice: null,
            trialDays: 30,
            gracePeriodDays: 7,

            // Modal Stats (tipoAccion = 5)
            statsLoading: false,
            statsData: null,
            statsTab: 'usuarios',

            // Modal Asignar Owner (tipoAccion = 6)
            ownerUserId: '',
            shopUsers: [],

            // Modal Ver Info (tipoAccion = 4) - Tabs
            infoTab: 'basica',

            // Modal Registrar Pago (tipoAccion = 7)
            paymentAmount: 0,
            paymentIncludeIva: false,
            paymentMethod: 'transfer',
            paymentReference: '',
            paymentNotes: '',
            paymentDate: '',

            // Modal Historial Pagos (tipoAccion = 8)
            historialLoading: false,
            historialData: null,

            // Ordenamiento de tabla
            sortBy: 'id',
            sortDesc: true,

            // Dropdown acciones
            openDropdown: null
        }
    },
    computed: {
        pagesNumber() {
            if (!this.pagination.to) return [];

            let from = this.pagination.current_page - this.offset;
            if (from < 1) from = 1;

            let to = from + (this.offset * 2);
            if (to >= this.pagination.last_page) to = this.pagination.last_page;

            let pages = [];
            while (from <= to) {
                pages.push(from);
                from++;
            }
            return pages;
        },
        modalSize() {
            if (this.tipoAccion === 2) return 'modal-lg';
            if (this.tipoAccion === 4 || this.tipoAccion === 5) return 'modal-lg';
            if (this.tipoAccion === 7 || this.tipoAccion === 8) return 'modal-lg'; // Registrar Pago / Historial
            return '';
        },
        modalIcon() {
            const icons = {
                1: 'fa fa-clock-o',
                2: 'fa fa-exchange',
                3: 'fa fa-cog',
                4: 'fa fa-shopping-cart',
                5: 'fa fa-bar-chart',
                6: 'fa fa-user-plus'
            };
            return icons[this.tipoAccion] || 'fa fa-credit-card';
        },
        resumenCambio() {
            const plan = this.arrayPlans.find(p => p.id == this.newPlanId);
            const planName = plan?.name || '-';
            const price = this.customPrice > 0 ? this.customPrice : (plan?.price || 0);
            const subtotal = price * this.durationMonths;
            const iva = this.includeIva ? subtotal * 0.16 : 0;
            const total = subtotal + iva;

            // Calcular fecha vencimiento
            const today = new Date();
            today.setMonth(today.getMonth() + this.durationMonths);
            const expiresDate = today.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });

            return { planName, price, subtotal, iva, total, expiresDate };
        },
        sortedShops() {
            if (!this.sortBy) return this.arrayShops;

            return [...this.arrayShops].sort((a, b) => {
                let valA = a[this.sortBy];
                let valB = b[this.sortBy];

                // Manejar valores nested (plan.name, owner.name)
                if (this.sortBy === 'plan_name') {
                    valA = a.plan?.name || '';
                    valB = b.plan?.name || '';
                } else if (this.sortBy === 'owner_name') {
                    valA = a.owner?.name || '';
                    valB = b.owner?.name || '';
                } else if (this.sortBy === 'days_remaining') {
                    valA = this.getDaysRemaining(a);
                    valB = this.getDaysRemaining(b);
                }

                // Manejar nulls
                if (valA === null || valA === undefined) valA = '';
                if (valB === null || valB === undefined) valB = '';

                // Comparar strings
                if (typeof valA === 'string') {
                    valA = valA.toLowerCase();
                    valB = valB.toLowerCase();
                }

                let result = 0;
                if (valA < valB) result = -1;
                if (valA > valB) result = 1;

                return this.sortDesc ? -result : result;
            });
        }
    },
    methods: {
        // === CARGA DE DATOS ===
        loadShops(page = 1) {
            const params = new URLSearchParams({
                page: page,
                buscar: this.buscar,
                plan_id: this.plan_id,
                estado: this.estado,
                activo: this.activo
            });

            axios.get('/superadmin/subscription-management/get?' + params.toString())
                .then(response => {
                    this.arrayShops = response.data.shops;
                    this.pagination = response.data.pagination;
                })
                .catch(error => {
                    console.log(error);
                    Swal.fire('Error', 'No se pudieron cargar las tiendas', 'error');
                });
        },

        loadNumStatus() {
            axios.get('/superadmin/subscription-management/num-status')
                .then(response => {
                    this.numStatus = response.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },

        loadPlans() {
            axios.get('/superadmin/subscription-management/plans')
                .then(response => {
                    this.arrayPlans = response.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },

        // === PAGINACION ===
        cambiarPagina(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.loadShops(page);
        },

        // === FILTROS ===
        limpiarFiltros() {
            this.buscar = '';
            this.plan_id = '';
            this.estado = '';
            this.activo = '1'; // Mantener filtro de activas por defecto
            this.loadShops(1);
        },

        // === MODALES ===
        abrirModal(accion, shop = null) {
            this.selectedShop = shop;
            this.modal = 1;

            switch(accion) {
                case 'extender':
                    this.tipoAccion = 1;
                    this.tituloModal = 'Extender Suscripcion';
                    this.extendDays = 30;
                    break;

                case 'cambiar_plan':
                    this.tipoAccion = 2;
                    this.tituloModal = 'Ajustar Plan';
                    // Si no tiene plan, usar el primero (Basic)
                    this.newPlanId = shop?.plan_id || (this.arrayPlans.length > 0 ? this.arrayPlans[0].id : '');
                    this.newBillingCycle = shop?.billing_cycle || 'monthly';
                    this.newCutoff = shop?.cutoff || '';
                    this.recalcularFecha = false;
                    // Cargar precios de la tienda (o del plan si no tiene)
                    const planData = this.arrayPlans.find(p => p.id == this.newPlanId);
                    this.customMonthlyPrice = (shop?.monthly_price > 0) ? shop.monthly_price : (planData?.price || 0);
                    this.customYearlyPrice = (shop?.yearly_price > 0) ? shop.yearly_price : (planData?.yearly_price || 0);
                    break;

                case 'config':
                    this.tipoAccion = 3;
                    this.tituloModal = 'Configuracion de Tienda';
                    this.monthlyPrice = shop?.monthly_price || 0;
                    this.yearlyPrice = shop?.yearly_price || null;
                    this.trialDays = shop?.trial_days || 30;
                    this.gracePeriodDays = shop?.grace_period_days || 7;
                    break;

                case 'ver_info':
                    this.tipoAccion = 4;
                    this.tituloModal = 'Ver Datos';
                    this.infoTab = 'basica'; // Reset a primera pestaña
                    break;

                case 'stats':
                    this.tipoAccion = 5;
                    this.tituloModal = 'Actividad de ' + shop?.name;
                    this.statsLoading = true;
                    this.statsData = null;
                    this.statsTab = 'usuarios';
                    this.loadStats(shop.id);
                    break;

                case 'asignar_owner':
                    this.tipoAccion = 6;
                    this.tituloModal = 'Asignar Admin Principal';
                    this.ownerUserId = '';
                    this.shopUsers = [];
                    this.loadShopUsers(shop.id);
                    break;

                case 'registrar_pago':
                    this.tipoAccion = 7;
                    this.tituloModal = 'Registrar Pago';
                    // Monto sugerido: precio de tienda, o del plan si no tiene
                    if (shop?.billing_cycle === 'yearly') {
                        this.paymentAmount = shop?.yearly_price || shop?.plan?.yearly_price || (shop?.monthly_price * 12) || (shop?.plan?.price * 12) || 0;
                    } else {
                        this.paymentAmount = shop?.monthly_price || shop?.plan?.price || 0;
                    }
                    this.paymentIncludeIva = false;
                    this.paymentMethod = 'transfer';
                    this.paymentReference = '';
                    this.paymentNotes = '';
                    // Fecha de hoy por default (formato YYYY-MM-DD)
                    this.paymentDate = new Date().toISOString().split('T')[0];
                    break;

                case 'historial_pagos':
                    this.tipoAccion = 8;
                    this.tituloModal = 'Historial de Pagos - ' + shop?.name;
                    this.historialLoading = true;
                    this.historialData = null;
                    this.loadPaymentHistory(shop.id);
                    break;
            }
        },

        cerrarModal() {
            this.modal = 0;
            this.selectedShop = null;
            this.statsData = null;
        },

        // === CARGAR DATOS AUXILIARES ===
        loadStats(shopId) {
            axios.get('/superadmin/subscription-management/' + shopId + '/stats')
                .then(response => {
                    this.statsLoading = false;
                    this.statsData = response.data;
                })
                .catch(error => {
                    this.statsLoading = false;
                    console.log(error);
                    Swal.fire('Error', 'No se pudieron cargar las estadisticas', 'error');
                    this.cerrarModal();
                });
        },

        loadShopUsers(shopId) {
            axios.get('/superadmin/shops/' + shopId + '/users')
                .then(response => {
                    this.shopUsers = response.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },

        // === ACCIONES ===
        extenderSuscripcion() {
            if (this.extendDays < 1 || this.extendDays > 365) {
                Swal.fire('Error', 'Los dias deben estar entre 1 y 365', 'error');
                return;
            }

            this.loading = true;
            axios.put('/superadmin/subscription-management/' + this.selectedShop.id + '/extend', {
                days: this.extendDays
            })
            .then(response => {
                this.loading = false;
                this.cerrarModal();
                this.loadShops(this.pagination.current_page);
                this.loadNumStatus();
                Swal.fire('Exito', response.data.message, 'success');
            })
            .catch(error => {
                this.loading = false;
                console.log(error);
                Swal.fire('Error', 'No se pudo extender la suscripcion', 'error');
            });
        },

        cambiarPlan() {
            if (!this.newPlanId) {
                Swal.fire('Error', 'Selecciona un plan', 'error');
                return;
            }

            this.loading = true;
            axios.put('/superadmin/subscription-management/' + this.selectedShop.id + '/change-plan', {
                plan_id: this.newPlanId,
                billing_cycle: this.newBillingCycle,
                cutoff: this.newCutoff || null,
                recalcular_fecha: this.recalcularFecha,
                monthly_price: this.customMonthlyPrice,
                yearly_price: this.customYearlyPrice
            })
            .then(response => {
                this.loading = false;
                this.cerrarModal();
                this.loadShops(this.pagination.current_page);
                this.loadNumStatus();
                Swal.fire('Exito', response.data.message, 'success');
            })
            .catch(error => {
                this.loading = false;
                console.log(error);
                Swal.fire('Error', error.response?.data?.message || 'No se pudo actualizar', 'error');
            });
        },

        actualizarConfig() {
            this.loading = true;
            axios.put('/superadmin/subscription-management/' + this.selectedShop.id + '/update-config', {
                monthly_price: this.monthlyPrice,
                yearly_price: this.yearlyPrice,
                trial_days: this.trialDays,
                grace_period_days: this.gracePeriodDays
            })
            .then(response => {
                this.loading = false;
                this.cerrarModal();
                this.loadShops(this.pagination.current_page);
                Swal.fire('Exito', response.data.message, 'success');
            })
            .catch(error => {
                this.loading = false;
                console.log(error);
                Swal.fire('Error', 'No se pudo actualizar la configuracion', 'error');
            });
        },

        asignarOwner() {
            if (!this.ownerUserId) {
                Swal.fire('Error', 'Selecciona un usuario', 'error');
                return;
            }

            this.loading = true;
            axios.put('/superadmin/subscription-management/' + this.selectedShop.id + '/assign-owner', {
                owner_user_id: this.ownerUserId
            })
            .then(response => {
                this.loading = false;
                this.cerrarModal();
                this.loadShops(this.pagination.current_page);
                Swal.fire('Exito', response.data.message, 'success');
            })
            .catch(error => {
                this.loading = false;
                console.log(error);
                Swal.fire('Error', 'No se pudo asignar el admin', 'error');
            });
        },

        // === PAGOS ===
        registrarPago() {
            if (!this.paymentAmount || this.paymentAmount <= 0) {
                Swal.fire('Error', 'El monto debe ser mayor a 0', 'error');
                return;
            }

            this.loading = true;
            axios.post('/superadmin/subscription-management/' + this.selectedShop.id + '/register-payment', {
                billing_cycle: this.selectedShop?.billing_cycle || 'monthly',
                amount: this.paymentAmount,
                include_iva: this.paymentIncludeIva,
                payment_method: this.paymentMethod,
                reference: this.paymentReference,
                notes: this.paymentNotes,
                payment_date: this.paymentDate
            })
            .then(response => {
                this.loading = false;
                this.cerrarModal();
                this.loadShops(this.pagination.current_page);
                this.loadNumStatus();
                Swal.fire('Pago Registrado', response.data.message, 'success');
            })
            .catch(error => {
                this.loading = false;
                console.log(error);
                const msg = error.response?.data?.message || 'No se pudo registrar el pago';
                Swal.fire('Error', msg, 'error');
            });
        },

        loadPaymentHistory(shopId) {
            axios.get('/superadmin/subscription-management/' + shopId + '/payment-history')
            .then(response => {
                this.historialLoading = false;
                this.historialData = response.data;
            })
            .catch(error => {
                this.historialLoading = false;
                console.log(error);
                Swal.fire('Error', 'No se pudo cargar el historial', 'error');
            });
        },

        calcularFechaVencimiento() {
            const hoy = new Date();
            if (this.selectedShop?.billing_cycle === 'yearly') {
                hoy.setFullYear(hoy.getFullYear() + 1);
            } else {
                hoy.setDate(hoy.getDate() + 30);
            }
            return hoy.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        getMontoSugerido() {
            const shop = this.selectedShop;
            if (!shop) return '0.00';

            // Obtener precio de la tienda, o del plan si no tiene configurado
            let precio = 0;
            if (shop.billing_cycle === 'yearly') {
                precio = shop.yearly_price || shop.plan?.yearly_price || (shop.monthly_price * 12) || (shop.plan?.price * 12) || 0;
            } else {
                precio = shop.monthly_price || shop.plan?.price || 0;
            }
            return this.formatNumber(precio);
        },

        calcularNuevaFechaVencimiento() {
            if (!this.newCutoff) return 'Selecciona un dia de corte';

            const hoy = new Date();
            let fecha = new Date();

            if (this.newBillingCycle === 'yearly') {
                // Proximo año, mismo mes, dia de corte
                fecha.setFullYear(hoy.getFullYear() + 1);
            } else {
                // Proximo mes, dia de corte
                fecha.setMonth(hoy.getMonth() + 1);
            }

            // Establecer el dia de corte
            fecha.setDate(this.newCutoff);

            // Si el dia no existe en ese mes (ej: 31 en febrero), ajustar
            if (fecha.getDate() != this.newCutoff) {
                fecha = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0); // Ultimo dia del mes
            }

            return fecha.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        getPaymentMethodLabel(method) {
            const labels = {
                'transfer': 'Transferencia',
                'cash': 'Efectivo',
                'card': 'Tarjeta',
                'other': 'Otro'
            };
            return labels[method] || method;
        },

        toggleExempt(shop) {
            const accion = shop.is_exempt ? 'quitar exencion' : 'marcar como exenta';
            const mensaje = shop.is_exempt
                ? '<p class="text-warning"><i class="fa fa-unlock"></i> Quitar exencion:</p><ul><li>La tienda volvera al flujo normal de suscripciones</li><li>Se le aplicaran vencimientos y bloqueos</li></ul>'
                : '<p class="text-dark"><i class="fa fa-shield"></i> Marcar como exenta:</p><ul><li>La tienda <strong>NO pagara</strong> suscripcion</li><li>El cron la ignorara completamente</li><li>Nunca se bloqueara por vencimiento</li></ul><p class="text-muted">Usar solo para tiendas internas, de pruebas o del dueño.</p>';

            Swal.fire({
                title: '¿' + accion.charAt(0).toUpperCase() + accion.slice(1) + '?',
                html: '<p><strong>Tienda:</strong> ' + shop.name + '</p>' + mensaje,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: shop.is_exempt ? '#ffc107' : '#343a40',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-' + (shop.is_exempt ? 'unlock' : 'shield') + '"></i> Si, ' + accion,
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.put('/superadmin/subscription-management/' + shop.id + '/toggle-exempt')
                        .then(response => {
                            this.loadShops(this.pagination.current_page);
                            this.loadNumStatus();
                            Swal.fire('Exito', response.data.message, 'success');
                        })
                        .catch(error => {
                            console.log(error);
                            Swal.fire('Error', 'No se pudo actualizar la tienda', 'error');
                        });
                }
            });
        },

        toggleActivo(shop) {
            const accion = shop.active ? 'desactivar' : 'reactivar';
            const mensaje = shop.active
                ? '<p class="text-danger"><i class="fa fa-exclamation-triangle"></i> Esta accion desactivara:</p><ul><li>La tienda completa</li><li><strong>TODOS</strong> los usuarios de esta tienda</li></ul><p class="text-muted">Los usuarios no podran iniciar sesion hasta que reactives la tienda.</p>'
                : '<p class="text-success"><i class="fa fa-check-circle"></i> Esta accion reactivara:</p><ul><li>La tienda</li><li>Solo el usuario <strong>owner</strong></li></ul><p class="text-muted">Los demas usuarios deberan ser reactivados manualmente.</p>';

            Swal.fire({
                title: '¿' + accion.charAt(0).toUpperCase() + accion.slice(1) + ' tienda?',
                html: '<p><strong>Tienda:</strong> ' + shop.name + '</p>' + mensaje,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: shop.active ? '#dc3545' : '#00f5a0',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-' + (shop.active ? 'ban' : 'check-circle') + '"></i> Si, ' + accion,
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.put('/superadmin/subscription-management/' + shop.id + '/toggle-active')
                        .then(response => {
                            this.loadShops(this.pagination.current_page);
                            this.loadNumStatus();
                            Swal.fire('Exito', response.data.message, 'success');
                        })
                        .catch(error => {
                            console.log(error);
                            Swal.fire('Error', 'No se pudo actualizar la tienda', 'error');
                        });
                }
            });
        },

        // === HELPERS ===
        getPlanPrice(cycle) {
            const plan = this.arrayPlans.find(p => p.id == this.newPlanId);
            if (!plan) return 0;
            if (cycle === 'yearly') {
                return plan.yearly_price || (plan.price * 12);
            }
            return plan.price || 0;
        },

        formatNumber(num) {
            return parseFloat(num || 0).toFixed(2);
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        getDaysRemaining(shop) {
            if (!shop) return -1;
            let endDate = null;
            if (shop.is_trial && shop.trial_ends_at) {
                endDate = new Date(shop.trial_ends_at);
            } else if (shop.subscription_ends_at) {
                endDate = new Date(shop.subscription_ends_at);
            }

            if (!endDate) return -1;

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            endDate.setHours(0, 0, 0, 0);

            const diff = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
            return diff;
        },

        getNivelActividadClass(nivel) {
            const clases = {
                'alta': 'j2b-badge-success',
                'media': 'j2b-badge-warning',
                'baja': 'j2b-badge-danger',
                'sin_actividad': 'j2b-badge-dark'
            };
            return clases[nivel] || 'j2b-badge-outline';
        },

        getNivelActividadTexto(nivel) {
            const textos = {
                'alta': 'Alta',
                'media': 'Media',
                'baja': 'Baja',
                'sin_actividad': 'Sin Actividad'
            };
            return textos[nivel] || nivel;
        },

        // === ORDENAMIENTO ===
        sortTable(column) {
            if (this.sortBy === column) {
                this.sortDesc = !this.sortDesc;
            } else {
                this.sortBy = column;
                this.sortDesc = false;
            }
        },

        getSortIcon(column) {
            if (this.sortBy !== column) return 'fa-sort';
            return this.sortDesc ? 'fa-sort-desc' : 'fa-sort-asc';
        },

        // === DROPDOWN ACCIONES ===
        toggleDropdown(shopId) {
            this.openDropdown = this.openDropdown === shopId ? null : shopId;
        },

        closeDropdown() {
            this.openDropdown = null;
        },

        handleClickOutside(event) {
            if (!event.target.closest('.action-dropdown')) {
                this.closeDropdown();
            }
        }
    },
    mounted() {
        this.loadShops(1);
        this.loadNumStatus();
        this.loadPlans();
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    }
}
</script>

<style>
/* Logo thumbnail en columna Tienda */
.shop-logo-thumb {
    width: 36px;
    height: 36px;
    border-radius: var(--j2b-radius-md);
    object-fit: contain;
    background: var(--j2b-white);
    border: 1px solid var(--j2b-gray-200);
    flex-shrink: 0;
}

/* Modal styles */
.mostrar {
    display: block !important;
    opacity: 1 !important;
    position: fixed !important;
    background-color: rgba(26, 26, 46, 0.8) !important;
    overflow-y: auto;
    z-index: 1050;
}

.j2b-modal-content {
    border: none;
    border-radius: var(--j2b-radius-lg);
    box-shadow: var(--j2b-shadow-lg);
}

.j2b-modal-header {
    background: var(--j2b-gradient-dark);
    color: var(--j2b-white);
    border-radius: var(--j2b-radius-lg) var(--j2b-radius-lg) 0 0;
    padding: 1rem 1.5rem;
    border-bottom: none;
}

.j2b-modal-header .modal-title {
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.j2b-modal-close {
    background: rgba(255,255,255,0.1);
    border: none;
    color: var(--j2b-white);
    width: 32px;
    height: 32px;
    border-radius: var(--j2b-radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--j2b-transition-fast);
}

.j2b-modal-close:hover {
    background: rgba(255,255,255,0.2);
}

.j2b-modal-body {
    padding: 1.5rem;
}

.j2b-modal-footer {
    padding: 1rem 1.5rem;
    background: var(--j2b-gray-100);
    border-top: 1px solid var(--j2b-gray-200);
    border-radius: 0 0 var(--j2b-radius-lg) var(--j2b-radius-lg);
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

/* Form sections */
.j2b-form-section {
    background: var(--j2b-gray-100);
    border-radius: var(--j2b-radius-md);
    padding: 1rem;
    margin-bottom: 1rem;
}

.j2b-form-section-title {
    color: var(--j2b-dark);
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--j2b-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.j2b-form-section-title i {
    color: var(--j2b-primary);
}

/* Pagination styles override */
.pagination .page-link {
    border-color: var(--j2b-gray-300);
    color: var(--j2b-dark);
}

.pagination .page-item.active .page-link {
    background: var(--j2b-gradient-primary);
    border-color: var(--j2b-primary);
    color: var(--j2b-dark);
}

.pagination .page-link:hover {
    background: var(--j2b-gray-200);
    border-color: var(--j2b-primary);
}

/* Info Tabs */
.info-tabs-header {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid var(--j2b-gray-200);
    padding-bottom: 0;
}

.info-tab-btn {
    padding: 0.75rem 1.25rem;
    border: none;
    background: transparent;
    color: var(--j2b-gray-500);
    font-weight: 500;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-tab-btn:hover {
    color: var(--j2b-primary);
    background: rgba(0, 245, 160, 0.05);
}

.info-tab-btn.active {
    color: var(--j2b-primary);
    border-bottom-color: var(--j2b-primary);
    background: rgba(0, 245, 160, 0.1);
}

.info-tab-btn i {
    font-size: 14px;
}

.info-tab-content {
    padding-top: 1rem;
}

/* Gap utility */
.gap-1 { gap: 0.25rem; }
.gap-2 { gap: 0.5rem; }

/* Stat cards */
.stat-card {
    background: var(--j2b-white);
    border-radius: var(--j2b-radius-md);
    padding: 1rem;
    text-align: center;
    border: 1px solid var(--j2b-gray-200);
    transition: var(--j2b-transition-fast);
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

/* Banner alert */
.j2b-banner-alert.j2b-banner-info {
    background: rgba(0, 217, 245, 0.1);
    border: 1px solid var(--j2b-info);
    border-radius: var(--j2b-radius-md);
    padding: 1rem;
    color: var(--j2b-dark);
}

/* Sortable headers */
.sortable-header {
    cursor: pointer;
    user-select: none;
    transition: background 0.2s ease;
    white-space: nowrap;
}

.sortable-header:hover {
    background: rgba(0, 245, 160, 0.1);
}

.sortable-header i {
    margin-left: 4px;
    font-size: 11px;
    color: var(--j2b-gray-400);
    transition: color 0.2s ease;
}

.sortable-header:hover i,
.sortable-header i.fa-sort-asc,
.sortable-header i.fa-sort-desc {
    color: var(--j2b-primary);
}

/* Action Dropdown */
.action-dropdown {
    position: relative;
    display: inline-block;
}

.action-dropdown-menu {
    position: absolute;
    right: 0;
    top: 100%;
    min-width: 160px;
    background: var(--j2b-white);
    border-radius: var(--j2b-radius-md);
    box-shadow: var(--j2b-shadow-lg);
    border: 1px solid var(--j2b-gray-200);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
}

.action-dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(4px);
}

.action-dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 14px;
    border: none;
    background: transparent;
    text-align: left;
    font-size: 13px;
    color: var(--j2b-dark);
    cursor: pointer;
    transition: background 0.15s ease;
}

.action-dropdown-item:hover {
    background: var(--j2b-gray-100);
}

.action-dropdown-item:first-child {
    border-radius: var(--j2b-radius-md) var(--j2b-radius-md) 0 0;
}

.action-dropdown-item:last-child {
    border-radius: 0 0 var(--j2b-radius-md) var(--j2b-radius-md);
}

.action-dropdown-item i {
    width: 16px;
    text-align: center;
}

.action-dropdown-item.text-danger:hover {
    background: rgba(220, 53, 69, 0.1);
}

.action-dropdown-item.text-success:hover {
    background: rgba(0, 245, 160, 0.1);
}

.action-dropdown-divider {
    height: 1px;
    background: var(--j2b-gray-200);
    margin: 4px 0;
}

/* Asegurar que la celda de acciones muestre todo */
.j2b-table td:last-child {
    overflow: visible !important;
    min-width: 120px;
}

.j2b-table td:last-child > div {
    display: flex !important;
    flex-wrap: nowrap !important;
    gap: 4px;
}

.j2b-table-responsive {
    overflow-x: auto;
    overflow-y: visible;
}

/* Radio Card para selector de periodo */
.j2b-radio-card {
    flex: 1;
    padding: 15px;
    border: 2px solid var(--j2b-gray-200);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
}

.j2b-radio-card:hover {
    border-color: var(--j2b-primary);
    background: rgba(0, 245, 160, 0.05);
}

.j2b-radio-card.active {
    border-color: var(--j2b-primary);
    background: rgba(0, 245, 160, 0.1);
    box-shadow: 0 0 0 3px rgba(0, 245, 160, 0.2);
}
</style>
