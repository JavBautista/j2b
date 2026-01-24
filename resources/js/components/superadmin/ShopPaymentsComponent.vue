<template>
  <div>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header con titulo y boton volver -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="/superadmin/subscription-management" class="j2b-btn j2b-btn-outline mb-2" style="font-size: 0.85rem;">
                    <i class="fa fa-arrow-left"></i> Volver a Suscripciones
                </a>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-money" style="color: var(--j2b-primary);"></i> Pagos de {{ shopName }}
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Gestiona los pagos y genera recibos</p>
            </div>
            <button type="button" @click="abrirModalNuevoPago()" class="j2b-btn j2b-btn-primary">
                <i class="fa fa-plus"></i> Registrar Pago
            </button>
        </div>

        <!-- Info de la tienda -->
        <div class="j2b-card mb-4" v-if="shop">
            <div class="j2b-card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <small class="j2b-text-muted">Estado Suscripcion</small>
                        <div>
                            <span class="j2b-badge" :class="getStatusBadgeClass(shop.subscription_status)">
                                {{ getStatusLabel(shop.subscription_status) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <small class="j2b-text-muted">Plan</small>
                        <div><strong>{{ shop.plan_name || 'Sin plan' }}</strong></div>
                    </div>
                    <div class="col-md-2">
                        <small class="j2b-text-muted">Ciclo</small>
                        <div>
                            <span class="j2b-badge" :class="shop.billing_cycle === 'yearly' ? 'j2b-badge-warning' : 'j2b-badge-info'">
                                {{ shop.billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <small class="j2b-text-muted">Dia de Corte</small>
                        <div><strong>{{ shop.cutoff || '-' }}</strong></div>
                    </div>
                    <div class="col-md-3">
                        <small class="j2b-text-muted">Vencimiento</small>
                        <div><strong>{{ shop.subscription_ends_at || '-' }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadisticas -->
        <div class="j2b-stat-grid mb-4">
            <div class="j2b-stat j2b-card-hover-glow">
                <div class="j2b-stat-icon j2b-stat-icon-primary">
                    <i class="fa fa-list"></i>
                </div>
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">{{ stats.total_payments }}</div>
                    <div class="j2b-stat-label">Total Pagos</div>
                </div>
            </div>
            <div class="j2b-stat j2b-card-hover-glow">
                <div class="j2b-stat-icon j2b-stat-icon-success">
                    <i class="fa fa-dollar"></i>
                </div>
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">${{ formatNumber(stats.total_paid) }}</div>
                    <div class="j2b-stat-label">Total Pagado</div>
                </div>
            </div>
            <div class="j2b-stat j2b-card-hover-glow">
                <div class="j2b-stat-icon j2b-stat-icon-info">
                    <i class="fa fa-calendar"></i>
                </div>
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">{{ stats.last_payment || '-' }}</div>
                    <div class="j2b-stat-label">Ultimo Pago</div>
                </div>
            </div>
        </div>

        <!-- Card principal -->
        <div class="j2b-card">
            <!-- Filtros -->
            <div class="j2b-card-header">
                <div class="row align-items-end g-2">
                    <div class="col-md-2">
                        <label class="j2b-label mb-1"><i class="fa fa-refresh"></i> Periodo</label>
                        <select v-model="filters.billing_period" class="j2b-select">
                            <option value="">Todos</option>
                            <option value="monthly">Mensual</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="j2b-label mb-1"><i class="fa fa-credit-card"></i> Metodo</label>
                        <select v-model="filters.payment_method" class="j2b-select">
                            <option value="">Todos</option>
                            <option value="transfer">Transferencia</option>
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="j2b-label mb-1"><i class="fa fa-calendar"></i> Desde</label>
                        <input type="date" v-model="filters.date_from" class="j2b-input">
                    </div>
                    <div class="col-md-2">
                        <label class="j2b-label mb-1"><i class="fa fa-calendar"></i> Hasta</label>
                        <input type="date" v-model="filters.date_to" class="j2b-input">
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="button" @click="loadPayments(1)" class="j2b-btn j2b-btn-primary">
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
                <div v-if="loading" class="text-center py-5">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color: var(--j2b-primary);"></i>
                    <p class="mt-2 j2b-text-muted">Cargando pagos...</p>
                </div>

                <div v-else-if="payments.length === 0" class="text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                    <p class="j2b-text-muted">No hay pagos registrados</p>
                    <button type="button" @click="abrirModalNuevoPago()" class="j2b-btn j2b-btn-primary mt-2">
                        <i class="fa fa-plus"></i> Registrar Primer Pago
                    </button>
                </div>

                <div v-else class="j2b-table-responsive">
                    <table class="j2b-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 100px;">Fecha Pago</th>
                                <th style="width: 80px;">Periodo</th>
                                <th style="width: 100px;">Subtotal</th>
                                <th style="width: 80px;">IVA</th>
                                <th style="width: 100px;">Total</th>
                                <th style="width: 100px;">Metodo</th>
                                <th style="width: 120px;">Referencia</th>
                                <th style="width: 100px;">Vigencia</th>
                                <th style="width: 80px;">Estado</th>
                                <th style="width: 120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pago in payments" :key="pago.id" :class="{ 'row-cancelled': pago.status === 'cancelled' }">
                                <td>
                                    <span class="j2b-badge j2b-badge-dark">{{ pago.id }}</span>
                                </td>
                                <td>
                                    <small>{{ pago.date }}</small>
                                </td>
                                <td>
                                    <span class="j2b-badge" :class="pago.billing_period === 'yearly' ? 'j2b-badge-warning' : 'j2b-badge-info'">
                                        {{ pago.billing_period === 'yearly' ? 'Anual' : 'Mensual' }}
                                    </span>
                                </td>
                                <td>
                                    <small>${{ formatNumber(pago.price_without_iva) }}</small>
                                </td>
                                <td>
                                    <small class="j2b-text-muted">${{ formatNumber(pago.iva_amount) }}</small>
                                </td>
                                <td>
                                    <strong style="color: var(--j2b-success);">${{ formatNumber(pago.total_amount) }}</strong>
                                </td>
                                <td>
                                    <small>{{ getPaymentMethodLabel(pago.payment_method) }}</small>
                                </td>
                                <td>
                                    <small class="j2b-text-muted" :title="pago.transaction_id">
                                        {{ truncate(pago.transaction_id, 15) }}
                                    </small>
                                </td>
                                <td>
                                    <small>{{ pago.starts_at }} - {{ pago.ends_at }}</small>
                                </td>
                                <td>
                                    <span class="j2b-badge" :class="pago.status === 'active' ? 'j2b-badge-success' : 'j2b-badge-danger'">
                                        {{ pago.status === 'active' ? 'Activo' : 'Cancelado' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="j2b-btn-icon j2b-btn-icon-sm" title="Ver detalle" @click="verDetalle(pago)">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button type="button" class="j2b-btn-icon j2b-btn-icon-sm text-primary" title="Descargar PDF" @click="descargarPdf(pago)" v-if="pago.status === 'active'">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </button>
                                        <button type="button" class="j2b-btn-icon j2b-btn-icon-sm" title="Editar" @click="editarPago(pago)" v-if="pago.status === 'active'">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button type="button" class="j2b-btn-icon j2b-btn-icon-sm text-danger" title="Cancelar" @click="confirmarCancelar(pago)" v-if="pago.status === 'active'">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginacion -->
                <div v-if="pagination.last_page > 1" class="j2b-card-footer">
                    <nav class="j2b-pagination">
                        <button class="j2b-page-btn" :disabled="pagination.current_page === 1" @click="loadPayments(pagination.current_page - 1)">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <span class="j2b-page-info">
                            Pagina {{ pagination.current_page }} de {{ pagination.last_page }}
                        </span>
                        <button class="j2b-page-btn" :disabled="pagination.current_page === pagination.last_page" @click="loadPayments(pagination.current_page + 1)">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Modal Ver/Editar Pago -->
        <div class="j2b-modal-overlay" :class="{ 'mostrar': modalView }" @click.self="cerrarModal()">
            <div class="j2b-modal modal-lg">
                <div class="j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa" :class="modalMode === 'view' ? 'fa-eye' : 'fa-pencil'" style="color: var(--j2b-primary);"></i>
                        {{ modalMode === 'view' ? 'Detalle del Pago' : 'Editar Pago' }}
                    </h5>
                    <button type="button" class="j2b-btn-close" @click="cerrarModal()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="j2b-modal-body" v-if="selectedPayment">
                    <!-- Modo Vista -->
                    <div v-if="modalMode === 'view'">
                        <div class="j2b-banner-alert j2b-banner-info mb-3">
                            <strong>Recibo #REC-{{ String(selectedPayment.id).padStart(6, '0') }}</strong>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="j2b-label">Fecha del Pago</label>
                                <p><strong>{{ selectedPayment.date }}</strong></p>
                            </div>
                            <div class="col-md-6">
                                <label class="j2b-label">Registrado</label>
                                <p><small>{{ selectedPayment.created_at }} por {{ selectedPayment.registered_by }}</small></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="j2b-label">Periodo</label>
                                <p>
                                    <span class="j2b-badge" :class="selectedPayment.billing_period === 'yearly' ? 'j2b-badge-warning' : 'j2b-badge-info'">
                                        {{ selectedPayment.billing_period === 'yearly' ? 'Anual' : 'Mensual' }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="j2b-label">Metodo de Pago</label>
                                <p><strong>{{ getPaymentMethodLabel(selectedPayment.payment_method) }}</strong></p>
                            </div>
                            <div class="col-md-4">
                                <label class="j2b-label">Referencia</label>
                                <p><strong>{{ selectedPayment.transaction_id || '-' }}</strong></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="j2b-label">Vigencia</label>
                                <p><strong>{{ selectedPayment.starts_at }} - {{ selectedPayment.ends_at }}</strong></p>
                            </div>
                            <div class="col-md-6">
                                <label class="j2b-label">Estado</label>
                                <p>
                                    <span class="j2b-badge" :class="selectedPayment.status === 'active' ? 'j2b-badge-success' : 'j2b-badge-danger'">
                                        {{ selectedPayment.status === 'active' ? 'Activo' : 'Cancelado' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="j2b-card" style="background: rgba(0, 245, 160, 0.1); border: 2px solid var(--j2b-primary);">
                            <div class="j2b-card-body py-3">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <small class="j2b-text-muted">Subtotal</small>
                                        <div><strong>${{ formatNumber(selectedPayment.price_without_iva) }}</strong></div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <small class="j2b-text-muted">IVA (16%)</small>
                                        <div><strong>${{ formatNumber(selectedPayment.iva_amount) }}</strong></div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <small class="j2b-text-muted">Total</small>
                                        <div style="font-size: 1.3em; font-weight: 700; color: var(--j2b-primary);">
                                            ${{ formatNumber(selectedPayment.total_amount) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedPayment.notes" class="mt-3">
                            <label class="j2b-label">Notas</label>
                            <p class="j2b-text-muted" style="white-space: pre-line;">{{ selectedPayment.notes }}</p>
                        </div>
                    </div>

                    <!-- Modo Edicion -->
                    <div v-if="modalMode === 'edit'">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="j2b-label"><i class="fa fa-dollar j2b-text-success"></i> Monto Total</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="j2b-badge j2b-badge-dark">$</span>
                                    <input type="number" class="j2b-input" v-model.number="editForm.total_amount" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="j2b-label"><i class="fa fa-calendar"></i> Fecha del Pago</label>
                                <input type="date" class="j2b-input" v-model="editForm.starts_at">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="j2b-label"><i class="fa fa-credit-card"></i> Metodo de Pago</label>
                                <select class="j2b-select" v-model="editForm.payment_method">
                                    <option value="transfer">Transferencia</option>
                                    <option value="cash">Efectivo</option>
                                    <option value="card">Tarjeta</option>
                                    <option value="other">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="j2b-label"><i class="fa fa-hashtag"></i> Referencia</label>
                                <input type="text" class="j2b-input" v-model="editForm.transaction_id" placeholder="Folio transferencia">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="j2b-label"><i class="fa fa-sticky-note"></i> Notas</label>
                            <textarea class="j2b-input" v-model="editForm.admin_notes" rows="3" placeholder="Observaciones..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">
                        <i class="fa fa-times"></i> Cerrar
                    </button>
                    <button v-if="modalMode === 'view' && selectedPayment?.status === 'active'" type="button" class="j2b-btn j2b-btn-outline" @click="descargarPdf(selectedPayment)">
                        <i class="fa fa-file-pdf-o"></i> Descargar PDF
                    </button>
                    <button v-if="modalMode === 'view'" type="button" class="j2b-btn j2b-btn-primary" @click="modalMode = 'edit'" :disabled="selectedPayment?.status !== 'active'">
                        <i class="fa fa-pencil"></i> Editar
                    </button>
                    <button v-if="modalMode === 'edit'" type="button" class="j2b-btn j2b-btn-primary" @click="guardarEdicion()" :disabled="saving">
                        <i class="fa fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Nuevo Pago -->
        <div class="j2b-modal-overlay" :class="{ 'mostrar': modalNuevoPago }" @click.self="cerrarModalNuevoPago()">
            <div class="j2b-modal modal-lg">
                <div class="j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-plus" style="color: var(--j2b-success);"></i> Registrar Nuevo Pago
                    </h5>
                    <button type="button" class="j2b-btn-close" @click="cerrarModalNuevoPago()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="j2b-modal-body">
                    <!-- Alerta si ya hay pago vigente (BLOQUEA registro) -->
                    <div v-if="pagoVigente" class="j2b-banner-alert j2b-banner-danger mb-3">
                        <i class="fa fa-ban"></i>
                        <strong>No se puede registrar pago:</strong> Ya existe un pago vigente hasta {{ pagoVigente.ends_at }}.
                        <br><small>El período ya está cubierto. Si necesitas corregir algo, cancela el pago anterior primero.</small>
                    </div>

                    <!-- Info del período que se va a pagar -->
                    <div v-if="nextPeriod && !pagoVigente" class="j2b-banner-alert j2b-banner-success mb-3">
                        <i class="fa fa-calendar-check-o"></i>
                        <strong>Período a pagar:</strong> {{ nextPeriod.start }} - {{ nextPeriod.end }} ({{ nextPeriod.label }})
                        <br>
                        <small v-if="nextPeriod.type === 'primer_pago'" class="j2b-text-muted">
                            <i class="fa fa-info-circle"></i> Primer pago - Se establecerá el día de corte
                        </small>
                        <small v-else-if="nextPeriod.type === 'reactivacion'" class="j2b-text-muted">
                            <i class="fa fa-refresh"></i> Reactivación - Se recalculará el día de corte
                        </small>
                        <small v-else class="j2b-text-muted">
                            <i class="fa fa-calendar"></i> Día de corte: {{ nextPeriod.cutoff }}
                        </small>
                    </div>

                    <!-- Loading período -->
                    <div v-if="loadingPeriod" class="text-center mb-3">
                        <i class="fa fa-spinner fa-spin"></i> Calculando período...
                    </div>

                    <div class="j2b-banner-alert j2b-banner-info mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="j2b-icon-circle j2b-icon-circle-sm j2b-icon-primary">
                                {{ shopName?.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <strong>{{ shopName }}</strong>
                                <br>
                                <small>
                                    Plan: <span class="j2b-badge j2b-badge-primary">{{ shop?.plan_name || 'Sin plan' }}</span>
                                    <span v-if="nuevoForm.billing_cycle === 'yearly'" class="j2b-badge j2b-badge-warning ml-1">Anual</span>
                                    <span v-else class="j2b-badge j2b-badge-info ml-1">Mensual</span>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Fila principal: Ciclo, Monto, Fecha -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="j2b-label"><i class="fa fa-refresh j2b-text-primary"></i> Ciclo de Pago</label>
                            <select class="j2b-select" v-model="nuevoForm.billing_cycle" @change="onCicloChange()">
                                <option value="monthly">Mensual</option>
                                <option value="yearly">Anual</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="j2b-label"><i class="fa fa-dollar j2b-text-success"></i> Monto del Pago</label>
                            <div class="d-flex align-items-center gap-2">
                                <span class="j2b-badge j2b-badge-dark">$</span>
                                <input type="number" class="j2b-input" v-model.number="nuevoForm.amount" step="0.01" min="0">
                            </div>
                            <small class="j2b-text-muted">Sugerido: ${{ formatNumber(montoSugerido) }}</small>
                        </div>
                        <div class="col-md-4">
                            <label class="j2b-label"><i class="fa fa-calendar-check-o j2b-text-success"></i> Fecha del Pago</label>
                            <input type="date" class="j2b-input" v-model="nuevoForm.payment_date">
                            <small class="j2b-text-muted">Cuando el cliente pago</small>
                        </div>
                    </div>

                    <!-- Fila secundaria: Metodo, Referencia, IVA -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="j2b-label"><i class="fa fa-credit-card j2b-text-primary"></i> Metodo de Pago</label>
                            <select class="j2b-select" v-model="nuevoForm.payment_method">
                                <option value="transfer">Transferencia</option>
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="j2b-label"><i class="fa fa-hashtag j2b-text-secondary"></i> Referencia</label>
                            <input type="text" class="j2b-input" v-model="nuevoForm.reference" placeholder="Folio transferencia">
                        </div>
                        <div class="col-md-4">
                            <label class="d-flex align-items-center gap-2 mt-4" style="cursor: pointer;">
                                <input type="checkbox" v-model="nuevoForm.include_iva" style="width: 18px; height: 18px;">
                                <span class="j2b-label mb-0"><i class="fa fa-percent j2b-text-info"></i> Incluye IVA (16%)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="j2b-label"><i class="fa fa-sticky-note j2b-text-warning"></i> Notas</label>
                        <input type="text" class="j2b-input" v-model="nuevoForm.notes" placeholder="Observaciones...">
                    </div>

                    <!-- Resumen -->
                    <div class="j2b-card" style="background: rgba(0, 245, 160, 0.1); border: 2px solid var(--j2b-primary);">
                        <div class="j2b-card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong style="color: var(--j2b-dark);">Resumen del Pago</strong>
                                    <br>
                                    <small class="j2b-text-muted">
                                        {{ nuevoForm.billing_cycle === 'yearly' ? 'Pago anual (12 meses)' : 'Pago mensual (30 dias)' }}
                                        <span v-if="nuevoForm.include_iva"> - IVA incluido</span>
                                    </small>
                                </div>
                                <div class="text-right">
                                    <span style="font-size: 1.5em; font-weight: 700; color: var(--j2b-primary);">
                                        ${{ formatNumber(nuevoForm.amount || 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModalNuevoPago()">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="registrarNuevoPago()" :disabled="saving || !nuevoForm.amount || pagoVigente">
                        <i class="fa fa-money"></i> {{ pagoVigente ? 'Período ya pagado' : 'Registrar Pago' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Confirmar Cancelacion -->
        <div class="j2b-modal-overlay" :class="{ 'mostrar': modalConfirmarCancelar }" @click.self="modalConfirmarCancelar = false">
            <div class="j2b-modal modal-sm">
                <div class="j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-exclamation-triangle text-danger"></i> Confirmar Cancelacion
                    </h5>
                    <button type="button" class="j2b-btn-close" @click="modalConfirmarCancelar = false">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="j2b-modal-body">
                    <p>¿Estas seguro de cancelar este pago?</p>
                    <p class="j2b-text-muted">
                        <strong>Pago #{{ pagoACancelar?.id }}</strong><br>
                        Monto: ${{ formatNumber(pagoACancelar?.total_amount) }}<br>
                        Fecha: {{ pagoACancelar?.date }}
                    </p>
                    <p class="text-danger"><small>Esta accion no se puede deshacer.</small></p>
                </div>
                <div class="j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="modalConfirmarCancelar = false">
                        No, mantener
                    </button>
                    <button type="button" class="j2b-btn j2b-btn-danger" @click="cancelarPago()" :disabled="saving">
                        <i class="fa fa-times"></i> Si, cancelar
                    </button>
                </div>
            </div>
        </div>

    </div>
  </div>
</template>

<script>
export default {
    props: {
        shopId: {
            type: Number,
            required: true
        },
        shopName: {
            type: String,
            required: true
        }
    },

    data() {
        return {
            loading: false,
            saving: false,

            // Datos
            shop: null,
            payments: [],
            stats: {
                total_payments: 0,
                total_paid: 0,
                last_payment: null
            },
            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0
            },

            // Filtros
            filters: {
                billing_period: '',
                payment_method: '',
                date_from: '',
                date_to: ''
            },

            // Modal Ver/Editar
            modalView: false,
            modalMode: 'view', // 'view' o 'edit'
            selectedPayment: null,
            editForm: {
                total_amount: 0,
                payment_method: '',
                transaction_id: '',
                admin_notes: '',
                starts_at: ''
            },

            // Modal Nuevo Pago
            modalNuevoPago: false,
            nuevoForm: {
                billing_cycle: 'monthly',
                amount: 0,
                include_iva: false,
                payment_method: 'transfer',
                reference: '',
                notes: '',
                payment_date: ''
            },
            montoSugerido: 0,
            pagoVigente: null,
            nextPeriod: null, // Info del próximo período a pagar
            loadingPeriod: false,

            // Modal Confirmar Cancelar
            modalConfirmarCancelar: false,
            pagoACancelar: null
        }
    },

    mounted() {
        this.loadPayments(1);
    },

    methods: {
        loadPayments(page = 1) {
            this.loading = true;

            const params = new URLSearchParams({
                page: page,
                ...this.filters
            });

            axios.get(`/superadmin/subscription-management/${this.shopId}/payments/get?${params}`)
                .then(response => {
                    this.shop = response.data.shop;
                    this.payments = response.data.payments;
                    this.pagination = response.data.pagination;
                    this.stats = response.data.stats;
                })
                .catch(error => {
                    this.mostrarError('Error al cargar los pagos');
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        limpiarFiltros() {
            this.filters = {
                billing_period: '',
                payment_method: '',
                date_from: '',
                date_to: ''
            };
            this.loadPayments(1);
        },

        // Modal Ver/Editar
        verDetalle(pago) {
            this.selectedPayment = pago;
            this.modalMode = 'view';
            this.modalView = true;
        },

        editarPago(pago) {
            this.selectedPayment = pago;
            this.editForm = {
                total_amount: pago.total_amount,
                payment_method: pago.payment_method,
                transaction_id: pago.transaction_id || '',
                admin_notes: pago.notes || '',
                starts_at: this.formatDateForInput(pago.starts_at)
            };
            this.modalMode = 'edit';
            this.modalView = true;
        },

        guardarEdicion() {
            this.saving = true;

            axios.put(`/superadmin/subscription-management/${this.shopId}/payments/${this.selectedPayment.id}`, this.editForm)
                .then(response => {
                    this.mostrarExito('Pago actualizado correctamente');
                    this.cerrarModal();
                    this.loadPayments(this.pagination.current_page);
                })
                .catch(error => {
                    this.mostrarError(error.response?.data?.message || 'Error al actualizar el pago');
                })
                .finally(() => {
                    this.saving = false;
                });
        },

        cerrarModal() {
            this.modalView = false;
            this.selectedPayment = null;
            this.modalMode = 'view';
        },

        // Modal Nuevo Pago
        abrirModalNuevoPago() {
            this.nuevoForm = {
                billing_cycle: this.shop?.billing_cycle || 'monthly',
                amount: 0,
                include_iva: false,
                payment_method: 'transfer',
                reference: '',
                notes: '',
                payment_date: new Date().toISOString().split('T')[0]
            };
            this.actualizarMontoSugerido();
            this.consultarProximoPeriodo();
            this.modalNuevoPago = true;
        },

        actualizarMontoSugerido() {
            if (this.nuevoForm.billing_cycle === 'yearly') {
                this.montoSugerido = this.shop?.yearly_price || (this.shop?.monthly_price * 12) || 0;
            } else {
                this.montoSugerido = this.shop?.monthly_price || 0;
            }
            this.nuevoForm.amount = this.montoSugerido;
        },

        onCicloChange() {
            this.actualizarMontoSugerido();
            this.consultarProximoPeriodo();
        },

        consultarProximoPeriodo() {
            this.loadingPeriod = true;
            this.nextPeriod = null;
            this.pagoVigente = null;

            axios.get(`/superadmin/subscription-management/${this.shopId}/next-period`, {
                params: { billing_cycle: this.nuevoForm.billing_cycle }
            })
            .then(response => {
                this.nextPeriod = response.data.period;
                if (response.data.already_paid) {
                    this.pagoVigente = response.data.existing_payment;
                }
            })
            .catch(error => {
                console.error('Error al consultar período:', error);
            })
            .finally(() => {
                this.loadingPeriod = false;
            });
        },

        registrarNuevoPago() {
            if (!this.nuevoForm.amount || this.nuevoForm.amount <= 0) {
                this.mostrarError('El monto debe ser mayor a 0');
                return;
            }

            this.saving = true;

            axios.post(`/superadmin/subscription-management/${this.shopId}/register-payment`, {
                billing_cycle: this.nuevoForm.billing_cycle,
                amount: this.nuevoForm.amount,
                include_iva: this.nuevoForm.include_iva,
                payment_method: this.nuevoForm.payment_method,
                reference: this.nuevoForm.reference,
                notes: this.nuevoForm.notes,
                payment_date: this.nuevoForm.payment_date
            })
            .then(response => {
                this.mostrarExito(response.data.message || 'Pago registrado correctamente');
                this.cerrarModalNuevoPago();
                this.loadPayments(1);
            })
            .catch(error => {
                this.mostrarError(error.response?.data?.message || 'Error al registrar el pago');
            })
            .finally(() => {
                this.saving = false;
            });
        },

        cerrarModalNuevoPago() {
            this.modalNuevoPago = false;
        },

        // Modal Confirmar Cancelar
        confirmarCancelar(pago) {
            this.pagoACancelar = pago;
            this.modalConfirmarCancelar = true;
        },

        cancelarPago() {
            this.saving = true;

            axios.delete(`/superadmin/subscription-management/${this.shopId}/payments/${this.pagoACancelar.id}`)
                .then(response => {
                    this.mostrarExito('Pago cancelado correctamente');
                    this.modalConfirmarCancelar = false;
                    this.pagoACancelar = null;
                    this.loadPayments(this.pagination.current_page);
                })
                .catch(error => {
                    this.mostrarError(error.response?.data?.message || 'Error al cancelar el pago');
                })
                .finally(() => {
                    this.saving = false;
                });
        },

        descargarPdf(pago) {
            // Abrir en nueva pestaña para descargar el PDF
            window.open(`/superadmin/subscription-management/${this.shopId}/payments/${pago.id}/pdf`, '_blank');
        },

        // Utilidades
        formatNumber(value) {
            if (value === null || value === undefined) return '0.00';
            return Number(value).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        truncate(text, length) {
            if (!text) return '-';
            return text.length > length ? text.substring(0, length) + '...' : text;
        },

        formatDateForInput(dateStr) {
            if (!dateStr) return '';
            // Convertir de dd/mm/yyyy a yyyy-mm-dd
            const parts = dateStr.split('/');
            if (parts.length === 3) {
                return `${parts[2]}-${parts[1]}-${parts[0]}`;
            }
            return dateStr;
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

        getStatusLabel(status) {
            const labels = {
                'trial': 'Trial',
                'active': 'Activo',
                'grace_period': 'En Gracia',
                'expired': 'Vencido',
                'cancelled': 'Cancelado'
            };
            return labels[status] || status;
        },

        getStatusBadgeClass(status) {
            const classes = {
                'trial': 'j2b-badge-info',
                'active': 'j2b-badge-success',
                'grace_period': 'j2b-badge-warning',
                'expired': 'j2b-badge-danger',
                'cancelled': 'j2b-badge-secondary'
            };
            return classes[status] || 'j2b-badge-secondary';
        },

        mostrarExito(mensaje) {
            // Usar toastr si está disponible, sino alert
            if (typeof toastr !== 'undefined') {
                toastr.success(mensaje);
            } else {
                alert(mensaje);
            }
        },

        mostrarError(mensaje) {
            if (typeof toastr !== 'undefined') {
                toastr.error(mensaje);
            } else {
                alert('Error: ' + mensaje);
            }
        }
    }
}
</script>

<style scoped>
/* Modal Overlay - oculto por defecto */
.j2b-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(26, 26, 46, 0.8);
    z-index: 1050;
    overflow-y: auto;
    padding: 2rem 1rem;
}

.j2b-modal-overlay.mostrar {
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

/* Modal Container */
.j2b-modal {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 600px;
    margin: auto;
    animation: modalFadeIn 0.2s ease-out;
}

.j2b-modal.modal-lg {
    max-width: 800px;
}

.j2b-modal.modal-sm {
    max-width: 400px;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Modal Header */
.j2b-modal-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: #fff;
    padding: 1rem 1.5rem;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.j2b-modal-header .modal-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.j2b-btn-close {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 1.2rem;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.j2b-btn-close:hover {
    opacity: 1;
}

/* Modal Body */
.j2b-modal-body {
    padding: 1.5rem;
}

/* Modal Footer */
.j2b-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* Botones de Icono para acciones en tabla */
.j2b-btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
}

.j2b-btn-icon:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #374151;
}

.j2b-btn-icon.text-danger {
    color: #ef4444;
}

.j2b-btn-icon.text-danger:hover {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #dc2626;
}

.j2b-btn-icon.text-primary {
    color: #3b82f6;
}

.j2b-btn-icon.text-primary:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1d4ed8;
}

.j2b-btn-icon-sm {
    width: 28px;
    height: 28px;
    font-size: 0.85rem;
}

/* Otras utilidades */
.row-cancelled {
    opacity: 0.6;
    background: #fef2f2;
}

.row-cancelled td {
    text-decoration: line-through;
}

.row-cancelled td:last-child {
    text-decoration: none;
}

.gap-1 {
    gap: 0.25rem;
}

.gap-2 {
    gap: 0.5rem;
}

.ml-1 {
    margin-left: 0.25rem;
}

.mt-4 {
    margin-top: 1.5rem;
}
</style>
