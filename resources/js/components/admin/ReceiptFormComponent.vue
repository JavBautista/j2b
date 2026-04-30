<template>
<div class="receipt-form">
    <!-- Header con modo -->
    <div v-if="isViewMode" class="alert alert-info mb-3">
        <i class="fa fa-eye me-2"></i>
        <strong>Modo visualización</strong> - Solo lectura
        <button class="btn btn-sm btn-outline-primary float-end" @click="goToEdit" v-if="canEdit">
            <i class="fa fa-edit me-1"></i>Editar
        </button>
    </div>

    <!-- Barra superior compacta: Toggles + Tarea -->
    <div class="toolbar-compact mb-2">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <!-- Toggle Nota/Cotización -->
            <div class="form-check form-switch mb-0 me-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="toggleCotizacion"
                    v-model="cotizacion"
                    @change="onChangeCotizacion"
                    :disabled="isViewMode"
                >
                <label class="form-check-label" for="toggleCotizacion">
                    <span v-if="cotizacion" class="badge bg-warning text-dark">
                        <i class="fa fa-file-alt me-1"></i>Cotización
                    </span>
                    <span v-else class="badge bg-success">
                        <i class="fa fa-file-invoice-dollar me-1"></i>Nota de Venta
                    </span>
                </label>
            </div>
            <!-- Toggle Sin inventario (solo cotizaciones, paridad con Ionic) -->
            <div v-if="cotizacion && !isViewMode" class="form-check form-switch mb-0">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="toggleSinInventario"
                    v-model="sinInventario"
                >
                <label class="form-check-label small text-muted" for="toggleSinInventario">
                    Sin validar inventario
                </label>
            </div>
            <!-- Separador visual -->
            <div class="vr d-none d-sm-block" style="height: 20px;"></div>
            <!-- Tarea vinculada o botón cargar -->
            <div v-if="fromTaskId" class="d-flex align-items-center text-info">
                <i class="fa fa-clipboard-check me-1"></i>
                <small>Tarea <strong>#{{ fromTaskId }}</strong></small>
                <button v-if="isCreateMode" class="btn btn-outline-danger btn-sm ms-2 py-0 px-1" @click="clearTaskData" title="Desvincular tarea">
                    <i class="fa fa-unlink" style="font-size: 0.7rem;"></i>
                </button>
            </div>
            <button v-else-if="isCreateMode" class="btn btn-outline-info btn-sm py-0" @click="openTaskModal">
                <i class="fa fa-clipboard-list me-1"></i>Desde Tarea
            </button>
        </div>
        <!-- Warning al cambiar de nota a cotización en modo edición -->
        <div v-if="isEditMode && showCotizacionWarning" class="alert alert-danger mt-2 mb-0 small py-1">
            <i class="fa fa-exclamation-triangle me-1"></i>
            Al cambiar este valor se reiniciarán los datos relacionados a la nota o cotización
        </div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Cliente + Items -->
        <div class="col-lg-8">
            <!-- Sección Cliente compacta -->
            <div class="client-section mb-2">
                <div v-if="!client" class="client-bar client-bar-empty" @click="isViewMode ? null : showModalCliente = true" :class="{ 'clickable': !isViewMode }">
                    <i class="fa fa-user-plus text-primary me-2"></i>
                    <span class="text-primary fw-500">Seleccionar Cliente</span>
                    <span v-if="clientFromTask" class="badge bg-secondary ms-2"><i class="fa fa-lock me-1"></i>De tarea</span>
                </div>
                <div v-else class="client-bar client-bar-filled">
                    <div class="client-avatar-sm me-2">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <span class="fw-600">{{ client.name }}</span>
                        <small class="text-muted ms-2" v-if="client.movil">
                            <i class="fa fa-phone me-1"></i>{{ client.movil }}
                        </small>
                        <span class="badge ms-2" :class="getLevelBadgeClass(client.level)">
                            Nv{{ client.level || 1 }}
                        </span>
                        <span v-if="clientFromTask" class="badge bg-secondary ms-1"><i class="fa fa-lock me-1"></i>Tarea</span>
                    </div>
                    <template v-if="!clientFromTask">
                        <button v-if="!isViewMode" class="btn btn-outline-secondary btn-sm py-0 me-1" @click="showModalCliente = true" title="Cambiar cliente">
                            <i class="fa fa-exchange"></i>
                        </button>
                        <button v-if="!isViewMode" class="btn btn-outline-danger btn-sm py-0" @click="removeClient">
                            <i class="fa fa-times"></i>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Campos Extra Dinámicos (inline si pocos) -->
            <div v-if="extraFields.length > 0" class="extra-fields-bar mb-2">
                <div class="row g-2">
                    <div
                        v-for="field in extraFields"
                        :key="field.id"
                        class="col-auto"
                        style="min-width: 150px; flex: 1;"
                    >
                        <div class="input-group input-group-sm">
                            <span class="input-group-text py-0 small">{{ field.field_name }}</span>
                            <input
                                type="text"
                                class="form-control form-control-sm"
                                v-model="fieldValueMap[field.id]"
                                :placeholder="field.field_name"
                                :disabled="isViewMode"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Items con botones de agregar integrados en el header -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-list me-2"></i>Detalle <span class="badge bg-secondary ms-1">{{ items.length }}</span></span>
                    <div v-if="!isViewMode" class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-success btn-sm py-0" @click="showModalProducto = true" :disabled="!client" title="Agregar Producto">
                            <i class="fa fa-cube me-1"></i>Producto
                        </button>
                        <button class="btn btn-info btn-sm py-0" @click="showModalServicio = true" :disabled="!client" title="Agregar Servicio">
                            <i class="fa fa-wrench me-1"></i>Servicio
                        </button>
                        <button class="btn btn-warning btn-sm py-0" @click="showModalEquipo = true" :disabled="!client" title="Agregar Equipo">
                            <i class="fa fa-print me-1"></i>Equipo
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="items.length === 0" class="text-center py-3 text-muted">
                        <i class="fa fa-shopping-cart fa-2x mb-2"></i>
                        <p class="mb-0 small">No hay items agregados</p>
                    </div>

                    <!-- Items como Cards -->
                    <div v-else class="items-list">
                        <div v-for="(item, index) in items" :key="index" class="item-card">
                            <!-- Fila 1: Imagen + Info + Subtotal + Eliminar -->
                            <div class="item-row-top">
                                <div class="item-image">
                                    <img
                                        v-if="item.image"
                                        :src="getItemImage(item.image)"
                                        @error="handleImageError"
                                        @click="openImageViewer(item.image)"
                                    >
                                    <div v-else class="item-image-placeholder">
                                        <i class="fa" :class="getTypeIcon(item.type)"></i>
                                    </div>
                                </div>
                                <div class="item-info">
                                    <div class="item-name">
                                        {{ item.name }}
                                        <button
                                            v-if="item.type === 'service' && !isViewMode"
                                            class="btn btn-link btn-sm p-0 ms-1"
                                            @click="editServiceDescription(item)"
                                            title="Editar descripción"
                                        >
                                            <i class="fa fa-pencil-alt text-info"></i>
                                        </button>
                                        <button
                                            v-if="item.type === 'equipment'"
                                            class="btn btn-link btn-sm p-0 ms-1"
                                            @click="showEquipmentDetails(item)"
                                            title="Ver detalles"
                                        >
                                            <i class="fa fa-info-circle text-primary"></i>
                                        </button>
                                    </div>
                                    <div class="item-meta">
                                        <span class="badge" :class="getTypeBadgeClass(item.type)">
                                            {{ getTypeLabel(item.type) }}
                                        </span>
                                        <span v-if="item.from_task_product_id" class="badge bg-secondary ms-1" title="Stock ya descontado desde tarea">
                                            <i class="fa fa-clipboard"></i> De tarea
                                        </span>
                                        <span v-if="item.is_complimentary" class="badge bg-warning text-dark ms-1" title="No se incluirá en la factura CFDI">
                                            <i class="fa fa-gift me-1"></i>Cortesía
                                        </span>
                                        <span v-if="item.type === 'product' && !item.from_task_product_id" class="text-muted ms-2">
                                            Stock: {{ item.stock }}
                                        </span>
                                    </div>
                                </div>
                                <div class="item-subtotal">
                                    <template v-if="item.is_complimentary">
                                        <del class="text-muted small">{{ formatCurrency((parseFloat(item.price) || 0) * (parseInt(item.qty) || 0)) }}</del>
                                        <strong class="text-warning d-block">{{ formatCurrency(0) }}</strong>
                                    </template>
                                    <strong v-else>{{ formatCurrency(item.subtotal) }}</strong>
                                </div>
                                <div v-if="!isViewMode" class="item-delete d-flex gap-1">
                                    <button
                                        class="btn btn-sm"
                                        :class="item.is_complimentary ? 'btn-warning' : 'btn-outline-warning'"
                                        @click="toggleComplimentary(item)"
                                        :title="item.is_complimentary ? 'Quitar cortesía' : 'Marcar como cortesía (no se facturará)'"
                                    >
                                        <i class="fa fa-gift"></i>
                                    </button>
                                    <button
                                        class="btn btn-outline-danger btn-sm"
                                        @click="removeItem(index)"
                                        :disabled="item.from_task_product_id"
                                        :title="item.from_task_product_id ? 'No se puede eliminar (viene de tarea)' : 'Eliminar'"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Fila 2: Controles (Precio, Cantidad, Descuento) -->
                            <div class="item-row-bottom" :class="{ 'item-complimentary': item.is_complimentary }">
                                <!-- Precio -->
                                <div class="item-control">
                                    <label>Precio</label>
                                    <input
                                        v-if="!isViewMode"
                                        type="number"
                                        class="form-control form-control-sm"
                                        v-model.number="item.price"
                                        @input="calcularSubtotalItem(item)"
                                        :disabled="item.is_complimentary"
                                        min="0"
                                        step="0.01"
                                    >
                                    <span v-else class="form-control-plaintext">{{ formatCurrency(item.price) }}</span>
                                </div>

                                <!-- Cantidad -->
                                <div class="item-control">
                                    <label>Cantidad</label>
                                    <!-- Si viene de tarea: mostrar solo texto (bloqueado) -->
                                    <div v-if="item.from_task_product_id" class="qty-locked">
                                        <span class="form-control form-control-sm text-center bg-light" title="Cantidad fija (viene de tarea)">
                                            {{ item.qty }}
                                            <i class="fa fa-lock text-muted ms-1"></i>
                                        </span>
                                    </div>
                                    <div v-else-if="!isViewMode" class="qty-control">
                                        <button class="btn btn-outline-secondary btn-sm" @click="decrementQty(item)" :disabled="item.is_complimentary">
                                            <i class="fa fa-minus"></i>
                                        </button>
                                        <input
                                            type="number"
                                            class="form-control form-control-sm text-center"
                                            v-model.number="item.qty"
                                            @input="onQtyChange(item)"
                                            :disabled="item.is_complimentary"
                                            min="1"
                                        >
                                        <button class="btn btn-outline-secondary btn-sm" @click="incrementQty(item)" :disabled="item.is_complimentary">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                    <span v-else class="form-control-plaintext text-center">{{ item.qty }}</span>
                                </div>

                                <!-- Descuento -->
                                <div class="item-control">
                                    <label>Descuento</label>
                                    <div v-if="!isViewMode" class="discount-control">
                                        <input
                                            type="number"
                                            class="form-control form-control-sm"
                                            v-model.number="item.discount"
                                            @input="calcularSubtotalItem(item)"
                                            :disabled="item.is_complimentary"
                                            min="0"
                                            placeholder="0"
                                        >
                                        <select
                                            class="form-select form-select-sm"
                                            v-model="item.discount_concept"
                                            @change="calcularSubtotalItem(item)"
                                            :disabled="item.is_complimentary"
                                        >
                                            <option value="$">$</option>
                                            <option value="%">%</option>
                                        </select>
                                        <button
                                            v-if="item.discount > 0 && !item.is_complimentary"
                                            class="btn btn-outline-danger btn-sm"
                                            @click="removeItemDiscount(item)"
                                            title="Quitar"
                                        >
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                    <span v-else class="form-control-plaintext">
                                        <span v-if="item.discount > 0">
                                            {{ item.discount_concept === '%' ? item.discount + '%' : formatCurrency(item.discount) }}
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descripción y Observaciones (compacto) -->
            <div class="notes-section mb-3">
                <div class="row g-2">
                    <div class="col-md-6">
                        <textarea
                            class="form-control form-control-sm"
                            v-model="receipt.description"
                            rows="2"
                            placeholder="Descripción de la nota..."
                            :disabled="isViewMode"
                        ></textarea>
                    </div>
                    <div class="col-md-6">
                        <textarea
                            class="form-control form-control-sm"
                            v-model="receipt.observation"
                            rows="2"
                            placeholder="Observaciones adicionales..."
                            :disabled="isViewMode"
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Totales y Pago -->
        <div class="col-lg-4">
            <!-- Panel de Totales -->
            <div class="card mb-3 sticky-top" style="top: 20px">
                <div class="card-header bg-dark text-white py-2">
                    <i class="fa fa-calculator me-2"></i>Resumen
                    <span v-if="isEditMode" class="badge bg-warning text-dark float-end">
                        Folio: {{ receiptOriginal?.folio }}
                    </span>
                </div>
                <div class="card-body">
                    <!-- Descuento General -->
                    <div class="mb-3">
                        <label class="form-label small">Descuento General</label>
                        <div class="input-group">
                            <input
                                type="number"
                                class="form-control"
                                v-model.number="descuentoGeneral"
                                @input="calcularTotales"
                                min="0"
                                :disabled="isViewMode"
                            >
                            <select
                                class="form-select"
                                v-model="descuentoGeneralTipo"
                                @change="calcularTotales"
                                style="max-width: 70px"
                                :disabled="isViewMode"
                            >
                                <option value="$">$</option>
                                <option value="%">%</option>
                            </select>
                        </div>
                    </div>

                    <!-- Subtotal -->
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span class="fw-bold">{{ formatCurrency(subtotal) }}</span>
                    </div>

                    <!-- Descuento -->
                    <div v-if="descuentoMonto > 0" class="d-flex justify-content-between mb-2 text-danger">
                        <span>Descuento:</span>
                        <span>-{{ formatCurrency(descuentoMonto) }}</span>
                    </div>

                    <!-- IVA -->
                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="toggleIVA"
                            v-model="conIVA"
                            @change="calcularTotales"
                            :disabled="isViewMode"
                        >
                        <label class="form-check-label" for="toggleIVA">
                            Agregar {{ $shopTaxName || 'IVA' }} ({{ $shopTaxRate }}%)
                        </label>
                    </div>
                    <div v-if="conIVA" class="d-flex justify-content-between mb-2">
                        <span>{{ $shopTaxName || 'IVA' }}:</span>
                        <span>{{ formatCurrency(ivaMonto) }}</span>
                    </div>

                    <hr>

                    <!-- Total -->
                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5 mb-0">TOTAL:</span>
                        <span class="h4 mb-0 text-success fw-bold">{{ formatCurrency(total) }}</span>
                    </div>

                    <!-- Pagos Parciales / Abonos (solo notas existentes, no crear) -->
                    <div v-if="!cotizacion && !isCreateMode">
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fa fa-money me-2"></i>Abonos</h6>
                            <button
                                v-if="adeudo > 0"
                                class="btn btn-success btn-sm"
                                @click="abrirModalAbono"
                                :disabled="agregandoAbono"
                            >
                                <i class="fa fa-plus me-1"></i>Abonar
                            </button>
                        </div>

                        <!-- Lista de pagos -->
                        <div v-if="partialPayments.length > 0" class="mb-2">
                            <div
                                v-for="pago in partialPayments"
                                :key="pago.id"
                                class="d-flex justify-content-between align-items-center border-bottom py-2"
                            >
                                <div>
                                    <small class="text-muted">{{ formatDate(pago.payment_date || pago.created_at) }}</small>
                                    <span class="badge bg-secondary ms-1" style="font-size: 0.65em;">{{ pago.payment_type }}</span>
                                    <div class="fw-bold">{{ formatCurrency(pago.amount) }}</div>
                                </div>
                                <button
                                    class="btn btn-outline-danger btn-sm"
                                    @click="eliminarAbono(pago)"
                                    :disabled="eliminandoAbono"
                                    title="Eliminar abono"
                                >
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-center text-muted py-2">
                            <small>Sin abonos registrados</small>
                        </div>

                        <!-- Resumen -->
                        <div class="d-flex justify-content-between small">
                            <span>Pagado:</span>
                            <span class="fw-bold text-success">{{ formatCurrency(totalPagado) }}</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span>Adeudo:</span>
                            <span class="fw-bold" :class="adeudo > 0 ? 'text-danger' : 'text-success'">
                                {{ formatCurrency(adeudo) }}
                            </span>
                        </div>
                    </div>

                    <!-- Complementos de pago (solo si la nota está facturada como PPD) -->
                    <div v-if="!isCreateMode && receiptOriginal && receiptOriginal.is_tax_invoiced" class="border rounded p-2 bg-light mt-3">
                        <cfdi-complementos-list
                            :ref="'complementosList'"
                            :receipt-id="receiptId"
                        ></cfdi-complementos-list>
                    </div>

                    <!-- Modal Agregar Abono -->
                    <div v-if="showModalAbono" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
                        <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header py-2 bg-success text-white">
                                    <h6 class="modal-title"><i class="fa fa-money me-2"></i>Agregar Abono</h6>
                                    <button type="button" class="btn-close btn-close-white" @click="showModalAbono = false"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Monto -->
                                    <label class="form-label small">Monto del abono <span class="text-danger">*</span></label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">$</span>
                                        <input
                                            ref="inputAbono"
                                            type="number"
                                            class="form-control"
                                            v-model.number="nuevoAbono.amount"
                                            min="0.01"
                                            :max="adeudo"
                                            step="0.01"
                                        >
                                    </div>
                                    <small class="text-muted d-block mb-3">Adeudo actual: {{ formatCurrency(adeudo) }}</small>

                                    <!-- Forma de pago SAT -->
                                    <label class="form-label small">Forma de pago <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm mb-3" v-model="nuevoAbono.payment_method">
                                        <option value="01">01 — Efectivo</option>
                                        <option value="02">02 — Cheque nominativo</option>
                                        <option value="03">03 — Transferencia electrónica</option>
                                        <option value="04">04 — Tarjeta de crédito</option>
                                        <option value="05">05 — Monedero electrónico</option>
                                        <option value="06">06 — Dinero electrónico</option>
                                        <option value="28">28 — Tarjeta de débito</option>
                                        <option value="29">29 — Tarjeta de servicios</option>
                                        <option value="99">99 — Por definir</option>
                                    </select>

                                    <!-- Bloque condicional bancarizado -->
                                    <div v-if="abonoEsBancarizado" class="border rounded p-2 bg-light mb-2">
                                        <div class="text-muted small mb-2">
                                            <i class="fa fa-university"></i> Información bancaria (opcional, para complemento de pago)
                                        </div>

                                        <!-- Cuenta beneficiaria (cuál cuenta de la tienda recibió) -->
                                        <label class="form-label small mb-1">Cuenta que recibió el pago</label>
                                        <select class="form-select form-select-sm mb-2" v-model="nuevoAbono.shop_bank_account_id">
                                            <option :value="null">— Usar cuenta predeterminada —</option>
                                            <option v-for="c in cuentasBancarias" :key="c.id" :value="c.id">
                                                {{ c.alias }} — {{ c.bank_name }}
                                            </option>
                                        </select>
                                        <div v-if="cuentasBancarias.length === 0" class="alert alert-warning py-1 px-2 small mb-2">
                                            <i class="fa fa-info-circle"></i> No tienes cuentas bancarias configuradas.
                                            <a href="/admin/configuracion/cuentas-bancarias" target="_blank">Configurarlas</a>.
                                        </div>

                                        <!-- Banco ordenante (cliente) -->
                                        <label class="form-label small mb-1">Banco del cliente</label>
                                        <select class="form-select form-select-sm mb-2" v-model="nuevoAbono.bank_ord_code">
                                            <option value="">— No especificar —</option>
                                            <option v-for="b in bancosOrdenantes" :key="b.code" :value="b.code">
                                                {{ b.code }} — {{ b.name }}
                                            </option>
                                        </select>
                                        <div v-if="nuevoAbono.bank_ord_code === '999'" class="form-check form-check-inline mb-2">
                                            <input class="form-check-input" type="checkbox" id="chkExtranjero" v-model="nuevoAbono.is_foreign_bank_ord">
                                            <label class="form-check-label small" for="chkExtranjero">Banco extranjero (XEXX010101000)</label>
                                        </div>

                                        <!-- Cuenta ordenante -->
                                        <label class="form-label small mb-1">Cuenta del cliente (opcional)</label>
                                        <input type="text" class="form-control form-control-sm mb-2" v-model="nuevoAbono.cta_ordenante" maxlength="50" placeholder="10-50 caracteres alfanuméricos">

                                        <!-- Núm operación -->
                                        <label class="form-label small mb-1">Núm. de operación / referencia</label>
                                        <input type="text" class="form-control form-control-sm" v-model="nuevoAbono.num_operacion" maxlength="100" placeholder="Ej. SPEI-20260429-001">
                                    </div>
                                </div>
                                <div class="modal-footer py-2">
                                    <button class="btn btn-secondary btn-sm" @click="showModalAbono = false">Cancelar</button>
                                    <button
                                        class="btn btn-success btn-sm"
                                        @click="agregarAbono"
                                        :disabled="!nuevoAbono.amount || nuevoAbono.amount <= 0 || agregandoAbono"
                                    >
                                        <span v-if="agregandoAbono"><i class="fa fa-spinner fa-spin me-1"></i>Guardando...</span>
                                        <span v-else><i class="fa fa-check me-1"></i>Agregar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opciones de Pago (solo notas, no cotizaciones) -->
                    <div v-if="!cotizacion">
                        <hr>
                        <h6 class="mb-3"><i class="fa fa-credit-card me-2"></i>Pago</h6>

                        <!-- Estatus -->
                        <div class="mb-3">
                            <label class="form-label small">Estatus</label>
                            <select class="form-select" v-model="receipt.status" :disabled="isViewMode">
                                <option value="POR COBRAR">POR COBRAR</option>
                                <option value="PAGADA">PAGADA</option>
                                <option value="POR FACTURAR">POR FACTURAR</option>
                            </select>
                        </div>

                        <!-- Forma de Pago -->
                        <div class="mb-3">
                            <label class="form-label small">Forma de Pago</label>
                            <select class="form-select" v-model="receipt.payment" :disabled="isViewMode">
                                <option value="EFECTIVO">EFECTIVO</option>
                                <option value="TARJETA">TARJETA</option>
                                <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                            </select>
                        </div>

                        <!-- Cantidad Recibida: editable solo al crear, lectura al editar -->
                        <div class="mb-3">
                            <label class="form-label small">Cantidad Recibida</label>
                            <div v-if="isCreateMode" class="input-group">
                                <span class="input-group-text">$</span>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model.number="receipt.received"
                                    min="0"
                                    :max="total"
                                    step="0.01"
                                >
                                <button
                                    class="btn btn-outline-secondary"
                                    @click="receipt.received = total"
                                    title="Copiar total"
                                >
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                            <div v-else class="form-control-plaintext fw-bold">
                                {{ formatCurrency(totalPagado) }}
                                <small class="text-muted fw-normal">(suma de abonos)</small>
                            </div>
                        </div>

                        <!-- Crédito -->
                        <div class="form-check mb-2">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="toggleCredito"
                                v-model="esCredito"
                                :disabled="isViewMode"
                            >
                            <label class="form-check-label" for="toggleCredito">
                                Es crédito
                            </label>
                        </div>
                        <div v-if="esCredito" class="credit-options ps-3">
                            <div class="mb-2">
                                <label class="form-label small">Fecha notificación</label>
                                <input
                                    type="date"
                                    class="form-control form-control-sm"
                                    v-model="creditoFecha"
                                    :disabled="isViewMode"
                                >
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Tipo</label>
                                <select class="form-select form-select-sm" v-model="creditoTipo" :disabled="isViewMode">
                                    <option value="semanal">Semanal</option>
                                    <option value="quincenal">Quincenal</option>
                                    <option value="mensual">Mensual</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Fecha vencimiento (solo cotizaciones) -->
                    <div v-if="cotizacion">
                        <hr>
                        <div class="mb-3">
                            <label class="form-label small">
                                <i class="fa fa-calendar me-1"></i>Fecha de Vencimiento
                            </label>
                            <input
                                type="date"
                                class="form-control"
                                v-model="cotizacionVencimiento"
                                :disabled="isViewMode"
                            >
                        </div>
                    </div>

                    <hr>

                    <!-- Botón Guardar (solo si no es viewMode) -->
                    <button
                        v-if="!isViewMode"
                        class="btn btn-success btn-lg w-100"
                        @click="confirmarGuardar"
                        :disabled="!puedeGuardar || isGuardando"
                    >
                        <span v-if="isGuardando">
                            <i class="fa fa-spinner fa-spin me-2"></i>Guardando...
                        </span>
                        <span v-else>
                            <i class="fa fa-save me-2"></i>
                            {{ getButtonLabel }}
                        </span>
                    </button>

                    <!-- Botones viewMode: PDF y Volver -->
                    <div v-if="isViewMode" class="d-flex gap-2">
                        <button
                            class="btn btn-primary btn-lg flex-grow-1"
                            @click="descargarPDF"
                        >
                            <i class="fa fa-file-pdf-o me-2"></i>PDF
                        </button>
                        <button
                            class="btn btn-secondary btn-lg flex-grow-1"
                            @click="goBack"
                        >
                            <i class="fa fa-arrow-left me-2"></i>Volver
                        </button>
                    </div>

                    <!-- Validaciones -->
                    <div v-if="!isViewMode && !puedeGuardar" class="alert alert-warning mt-3 mb-0 small">
                        <ul class="mb-0 ps-3">
                            <li v-if="!client">Seleccione un cliente</li>
                            <li v-if="items.length === 0">Agregue al menos un item</li>
                        </ul>
                    </div>

                    <!-- Acciones rápidas (solo notas existentes, no en modo solo lectura) -->
                    <div v-if="!isCreateMode && !readOnly && receiptOriginal" class="mt-3">
                        <hr>
                        <h6 class="mb-2"><i class="fa fa-cogs me-2"></i>Acciones</h6>

                        <!-- Convertir cotización a venta -->
                        <button
                            v-if="receiptOriginal.quotation"
                            class="btn btn-outline-primary btn-sm w-100 mb-2"
                            @click="convertirAVenta"
                            :disabled="accionEnProceso"
                        >
                            <i class="fa fa-exchange me-1"></i>Cambiar a Nota de Venta
                        </button>

                        <!-- Toggle facturado -->
                        <button
                            v-if="!receiptOriginal.quotation"
                            class="btn btn-sm w-100 mb-2"
                            :class="receiptOriginal.is_tax_invoiced ? 'btn-outline-warning' : 'btn-outline-info'"
                            @click="toggleFacturado"
                            :disabled="accionEnProceso"
                        >
                            <i class="fa fa-file-text-o me-1"></i>
                            {{ receiptOriginal.is_tax_invoiced ? 'Marcar como NO Facturado' : 'Marcar como Facturado' }}
                        </button>

                        <!-- Cancelar nota -->
                        <button
                            v-if="receiptOriginal.status !== 'CANCELADA' && receiptOriginal.status !== 'DEVOLUCION'"
                            class="btn btn-outline-danger btn-sm w-100"
                            @click="cancelarNota"
                            :disabled="accionEnProceso"
                        >
                            <i class="fa fa-ban me-1"></i>Cancelar Nota
                        </button>

                        <!-- Badge cancelada -->
                        <div v-if="receiptOriginal.status === 'CANCELADA'" class="alert alert-danger text-center mb-0 py-2">
                            <i class="fa fa-ban me-1"></i><strong>NOTA CANCELADA</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales de Selección (Componentes Shared) -->
    <modal-select-client
        :show="showModalCliente"
        @close="showModalCliente = false"
        @select="onClienteSeleccionado"
    />

    <modal-select-product
        :show="showModalProducto"
        :show-out-of-stock="cotizacion || sinInventario"
        :client-level="client?.level || 1"
        @close="showModalProducto = false"
        @select="onProductoSeleccionado"
    />

    <modal-select-service
        :show="showModalServicio"
        @close="showModalServicio = false"
        @select="onServicioSeleccionado"
    />

    <modal-select-equipment
        :show="showModalEquipo"
        @close="showModalEquipo = false"
        @select="onEquipoSeleccionado"
    />

    <!-- Modal Seleccionar Tarea -->
    <div class="modal" :class="{ 'show d-block': showModalTarea }" tabindex="-1" v-if="showModalTarea">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-clipboard-list me-2"></i>Seleccionar Tarea
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="showModalTarea = false"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input
                            type="text"
                            class="form-control"
                            v-model="taskSearchQuery"
                            @input="searchTasks"
                            placeholder="Buscar por ID, título o cliente..."
                            ref="taskSearchInput"
                        >
                    </div>
                    <div v-if="taskSearchLoading" class="text-center py-3">
                        <i class="fa fa-spinner fa-spin"></i> Cargando...
                    </div>
                    <div v-else-if="taskSearchResults.length === 0" class="text-center text-muted py-3">
                        <i class="fa fa-info-circle me-1"></i>
                        No hay tareas con productos pendientes de facturar
                    </div>
                    <div v-else class="list-group">
                        <a
                            v-for="task in taskSearchResults"
                            :key="task.id"
                            href="#"
                            class="list-group-item list-group-item-action"
                            @click.prevent="selectTask(task)"
                        >
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>#{{ task.id }}</strong> - {{ task.title }}
                                    <br>
                                    <small class="text-muted">
                                        <span v-if="task.client">
                                            <i class="fa fa-user me-1"></i>{{ task.client.name }}
                                        </span>
                                        <span v-else class="text-warning">
                                            <i class="fa fa-user-slash me-1"></i>Sin cliente
                                        </span>
                                    </small>
                                </div>
                                <span class="badge bg-success">{{ task.pending_products_count }} productos</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showModalTarea = false">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showModalTarea" @click="showModalTarea = false"></div>
</div>
</template>

<script>
import CfdiComplementosList from './CfdiComplementosList.vue';

export default {
    name: 'ReceiptFormComponent',
    components: { CfdiComplementosList },
    props: {
        // null = crear, número = editar/ver
        receiptId: {
            type: Number,
            default: null
        },
        // true = solo lectura
        readOnly: {
            type: Boolean,
            default: false
        },
        // true = usuario admin limitado
        userLimited: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            // Estado de carga
            isLoading: true,
            isGuardando: false,

            // Datos originales (para edición)
            receiptOriginal: null,
            clientOriginal: null,
            detailOriginal: [],
            currentStockDetail: [], // Stock actual de productos

            // UI
            cotizacion: false,
            cotizacionOriginal: false, // Para detectar cambio
            showCotizacionWarning: false,
            sinInventario: false,
            conIVA: false,
            esCredito: false,

            // Modales
            showModalCliente: false,
            showModalProducto: false,
            showModalServicio: false,
            showModalEquipo: false,

            // Cliente
            client: null,

            // Items
            items: [],
            equipos: [], // Equipos agregados (para ver detalles)

            // Campos Extra
            extraFields: [],
            fieldValueMap: {},

            // Totales
            subtotal: 0,
            descuentoGeneral: 0,
            descuentoGeneralTipo: '$',
            descuentoMonto: 0,
            ivaMonto: 0,
            total: 0,

            // Crédito
            creditoFecha: this.getDefaultCreditDate(),
            creditoTipo: 'semanal',

            // Cotización
            cotizacionVencimiento: this.getDefaultQuotationDate(),

            // Receipt
            receipt: {
                client_id: 0,
                rent_id: 0,
                task_id: null,
                type: 'venta',
                description: '',
                observation: '',
                discount_concept: '$',
                status: 'POR COBRAR',
                payment: 'EFECTIVO',
                subtotal: 0,
                discount: 0,
                received: 0,
                total: 0,
                iva: 0,
                quotation: false,
                quotation_expiration: null,
                credit: false,
                credit_date_notification: null,
                credit_type: 'semanal'
            },

            // Tarea origen (para notas generadas desde tareas)
            fromTaskId: null,
            clientFromTask: false, // Si el cliente viene de la tarea (bloqueado)

            // Búsqueda de tareas
            taskSearchQuery: '',
            taskSearchResults: [],
            taskSearchTimeout: null,
            showModalTarea: false,
            taskSearchLoading: false,

            // Pagos parciales
            showModalAbono: false,
            nuevoAbono: this.crearAbonoVacio(),
            agregandoAbono: false,
            eliminandoAbono: false,
            cuentasBancarias: [],
            bancosOrdenantes: [
                { code: '002', name: 'Banamex (Citibanamex)' },
                { code: '012', name: 'BBVA México' },
                { code: '014', name: 'Santander' },
                { code: '021', name: 'HSBC' },
                { code: '044', name: 'Scotiabank' },
                { code: '072', name: 'Banorte' },
                { code: '999', name: 'Otro / Extranjero' },
            ],

            // Acciones rápidas
            accionEnProceso: false
        }
    },
    computed: {
        isCreateMode() {
            return this.receiptId === null;
        },
        isEditMode() {
            return this.receiptId !== null && !this.readOnly;
        },
        isViewMode() {
            return this.receiptId !== null && this.readOnly;
        },
        abonoEsBancarizado() {
            return ['02','03','04','05','06','28','29'].includes(this.nuevoAbono.payment_method);
        },
        puedeGuardar() {
            return this.client && this.items.length > 0;
        },
        canEdit() {
            // Puede editar si no está facturado
            return this.receiptOriginal && !this.receiptOriginal.is_tax_invoiced;
        },
        partialPayments() {
            return this.receiptOriginal?.partial_payments || [];
        },
        totalPagado() {
            return this.partialPayments.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0);
        },
        adeudo() {
            return Math.max(0, parseFloat(this.receiptOriginal?.total || 0) - this.totalPagado);
        },
        getButtonLabel() {
            if (this.isCreateMode) {
                return this.cotizacion ? 'Guardar Cotización' : 'Guardar Nota de Venta';
            }
            return this.cotizacion ? 'Actualizar Cotización' : 'Actualizar Nota de Venta';
        }
    },
    mounted() {
        // Usuarios limitados no pueden crear ni editar
        if (this.userLimited && !this.readOnly) {
            Swal.fire('Acceso denegado', 'No tienes permisos para crear o editar notas de venta', 'warning');
            window.location.href = '/admin/receipts';
            return;
        }

        this.loadExtraFields();

        if (this.receiptId) {
            this.loadReceiptData();
        } else {
            // Detectar si viene de una tarea
            const urlParams = new URLSearchParams(window.location.search);
            const fromTaskId = urlParams.get('from_task');
            if (fromTaskId) {
                this.loadFromTask(fromTaskId);
            } else {
                this.isLoading = false;
            }
        }
    },
    methods: {
        // ==================== CARGA DE DATOS ====================
        async loadReceiptData() {
            this.isLoading = true;
            try {
                // Cargar datos del receipt
                const response = await axios.get(`/admin/receipts/${this.receiptId}/detail`);
                if (response.data.ok) {
                    this.receiptOriginal = response.data.receipt;
                    this.clientOriginal = response.data.receipt.client;
                    this.detailOriginal = response.data.receipt.detail || [];

                    // Cargar stock actual (solo para edición)
                    if (this.isEditMode) {
                        await this.loadCurrentStockDetail();
                    }

                    // Inicializar formulario con datos cargados
                    this.initializeFromOriginal();
                } else {
                    Swal.fire('Error', 'No se pudo cargar la nota', 'error');
                }
            } catch (error) {
                console.error('Error al cargar receipt:', error);
                Swal.fire('Error', 'Error al cargar los datos', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        async loadCurrentStockDetail() {
            try {
                const response = await axios.get(`/admin/receipts/${this.receiptId}/stock-detail`);
                if (response.data.ok) {
                    this.currentStockDetail = response.data.detail_current_stock || [];
                }
            } catch (error) {
                console.error('Error al cargar stock:', error);
            }
        },

        // Cargar datos desde una tarea
        async loadFromTask(taskId) {
            this.isLoading = true;
            try {
                const response = await axios.get(`/admin/tasks/${taskId}/products-for-receipt`);
                if (response.data.ok) {
                    const data = response.data;

                    // Guardar referencia a la tarea
                    this.fromTaskId = taskId;
                    this.receipt.task_id = taskId;

                    // Pre-cargar cliente de la tarea (si existe)
                    if (data.task.client) {
                        this.client = data.task.client;
                        this.receipt.client_id = data.task.client.id;
                        this.clientFromTask = true; // Bloquear edición del cliente
                    } else {
                        this.clientFromTask = false; // Permitir seleccionar cliente
                    }

                    // Pre-cargar productos usados
                    data.usedProducts.forEach(up => {
                        const item = {
                            id: up.product_id,
                            type: 'product',
                            name: up.product.key ? `${up.product.key} ${up.product.name}` : up.product.name,
                            price: parseFloat(up.price),
                            qty: up.qty_used,
                            stock: 999, // No validar stock (ya descontado)
                            discount: 0,
                            discount_concept: '$',
                            subtotal: parseFloat(up.price) * up.qty_used,
                            image: up.product.image,
                            from_task_product_id: up.task_product_id // Marca clave
                        };
                        this.items.push(item);
                    });

                    this.calcularTotales();

                    // Mensaje según si tiene cliente o no
                    const clienteMsg = data.task.client
                        ? `Cliente: <strong>${data.task.client.name}</strong> (de la tarea)`
                        : '<span class="text-warning">La tarea no tiene cliente asignado. Debe seleccionar uno.</span>';

                    Swal.fire({
                        icon: 'info',
                        title: 'Productos cargados desde tarea #' + taskId,
                        html: `Se cargaron <strong>${data.usedProducts.length}</strong> productos usados.<br>
                               ${clienteMsg}<br>
                               <small class="text-muted">El stock de estos productos NO se descontará al guardar.</small>`,
                        confirmButtonText: 'Entendido'
                    });
                }
            } catch (error) {
                console.error('Error al cargar productos de tarea:', error);
                if (error.response && error.response.data && error.response.data.message) {
                    Swal.fire('Error', error.response.data.message, 'warning');
                } else {
                    Swal.fire('Error', 'Error al cargar productos de la tarea', 'error');
                }
            } finally {
                this.isLoading = false;
            }
        },

        // Abrir modal de búsqueda de tareas
        openTaskModal() {
            this.showModalTarea = true;
            this.taskSearchQuery = '';
            this.taskSearchResults = [];
            // Cargar tareas recientes automáticamente
            this.loadRecentTasks();
        },

        // Cargar tareas recientes con productos pendientes
        async loadRecentTasks() {
            this.taskSearchLoading = true;
            try {
                const response = await axios.get('/admin/tasks-with-pending-products');
                if (response.data.ok) {
                    this.taskSearchResults = response.data.tasks;
                }
            } catch (error) {
                console.error('Error al cargar tareas:', error);
            } finally {
                this.taskSearchLoading = false;
            }
        },

        // Buscar/filtrar tareas con productos pendientes
        searchTasks() {
            clearTimeout(this.taskSearchTimeout);
            this.taskSearchTimeout = setTimeout(async () => {
                this.taskSearchLoading = true;
                try {
                    const response = await axios.get('/admin/tasks-with-pending-products', {
                        params: { q: this.taskSearchQuery }
                    });
                    if (response.data.ok) {
                        this.taskSearchResults = response.data.tasks;
                    }
                } catch (error) {
                    console.error('Error al buscar tareas:', error);
                } finally {
                    this.taskSearchLoading = false;
                }
            }, 300);
        },

        // Seleccionar tarea del buscador
        selectTask(task) {
            this.showModalTarea = false;
            this.taskSearchQuery = '';
            this.taskSearchResults = [];
            this.loadFromTask(task.id);
        },

        // Limpiar datos de tarea (desvincular)
        clearTaskData() {
            this.fromTaskId = null;
            this.receipt.task_id = null;
            this.clientFromTask = false;
            this.client = null;
            this.receipt.client_id = 0;
            // Eliminar solo items que vienen de tarea
            this.items = this.items.filter(item => !item.from_task_product_id);
            this.calcularTotales();
        },

        initializeFromOriginal() {
            const r = this.receiptOriginal;

            // Cliente
            this.client = this.clientOriginal;
            this.receipt.client_id = r.client_id;

            // Si la nota viene de una tarea, bloquear cliente
            if (r.task_id) {
                this.fromTaskId = r.task_id;
                this.receipt.task_id = r.task_id;
                this.clientFromTask = true;
            }

            // Flags
            this.cotizacion = r.quotation === 1 || r.quotation === true;
            this.cotizacionOriginal = this.cotizacion;
            this.conIVA = r.iva > 0;
            this.esCredito = r.credit === 1 || r.credit === true;

            // Preservar tipo y rent_id del recibo original
            this.receipt.type = r.type || 'venta';
            this.receipt.rent_id = r.rent_id || 0;

            // Datos generales
            this.receipt.description = r.description || '';
            this.receipt.observation = r.observation || '';
            this.receipt.status = r.status || 'POR COBRAR';
            this.receipt.payment = r.payment || 'EFECTIVO';
            this.receipt.received = r.received || 0;

            // Descuento general
            this.descuentoGeneral = r.discount || 0;
            this.descuentoGeneralTipo = r.discount_concept || '$';

            // Cotización
            if (r.quotation_expiration) {
                this.cotizacionVencimiento = r.quotation_expiration.split(' ')[0];
            }

            // Crédito
            if (r.credit_date_notification) {
                this.creditoFecha = r.credit_date_notification.split(' ')[0];
            }
            this.creditoTipo = r.credit_type || 'semanal';

            // Info Extra
            if (r.info_extra && r.info_extra.length > 0) {
                r.info_extra.forEach(extra => {
                    const field = this.extraFields.find(f => f.field_name === extra.field_name);
                    if (field) {
                        this.fieldValueMap[field.id] = extra.value;
                    }
                });
            }

            // Cargar items del detalle
            this.detailOriginal.forEach(item => {
                this.loadItemFromDetail(item);
            });

            // Calcular totales
            this.calcularTotales();
        },

        loadItemFromDetail(detail) {
            // Calcular stock disponible para edición
            let stock = 0;
            if (detail.type === 'product') {
                const stockInfo = this.currentStockDetail.find(s => s.product_id === detail.product_id);
                if (stockInfo) {
                    // Stock disponible = stock actual + cantidad ya guardada
                    stock = Number(detail.qty) + Number(stockInfo.stock);
                }
            }

            const item = {
                id: detail.product_id,
                type: detail.type,
                name: detail.descripcion,
                price: detail.price,
                qty: detail.qty,
                qtyOriginal: detail.qty, // Guardar cantidad original
                stock: stock,
                discount: detail.discount || 0,
                discount_concept: detail.discount_concept || '$',
                is_complimentary: !!detail.is_complimentary,
                subtotal: detail.subtotal,
                image: detail.image || null, // Usar imagen del detalle (viene del backend)
                isFromOriginal: true,
                from_task_product_id: detail.from_task_product_id || null // Productos de tarea
            };

            this.items.push(item);
        },

        // ==================== CAMPOS EXTRA ====================
        async loadExtraFields() {
            try {
                const response = await axios.get('/admin/receipts/extra-fields');
                if (response.data.ok) {
                    this.extraFields = response.data.extra_fields;
                    // Inicializar el mapa de valores
                    this.extraFields.forEach(field => {
                        this.fieldValueMap[field.id] = '';
                    });
                }
            } catch (error) {
                console.error('Error al cargar campos extra:', error);
            }
        },

        // ==================== CLIENTE ====================
        onClienteSeleccionado(cliente) {
            this.client = cliente;
            this.receipt.client_id = cliente.id;
            // Recalcular precios si cambia el nivel del cliente
            this.recalcularPreciosCliente();
        },
        removeClient() {
            this.client = null;
            this.receipt.client_id = 0;
        },
        recalcularPreciosCliente() {
            // Recalcular precios de productos según nivel del cliente
            this.items.forEach(item => {
                if (item.type === 'product' && item.originalProduct) {
                    item.price = this.getPrecioSegunNivel(item.originalProduct);
                    this.calcularSubtotalItem(item);
                }
            });
            this.calcularTotales();
        },

        // ==================== PRODUCTOS ====================
        onProductoSeleccionado(producto) {
            // Verificar si ya existe
            const existe = this.items.find(i => i.id === producto.id && i.type === 'product');
            if (existe) {
                // Incrementar cantidad
                if (this.validarStock(existe, existe.qty + 1)) {
                    existe.qty++;
                    this.calcularSubtotalItem(existe);
                    this.calcularTotales();
                }
                return;
            }

            const precio = this.getPrecioSegunNivel(producto);
            const item = {
                id: producto.id,
                type: 'product',
                name: producto.key ? `${producto.key} ${producto.name}` : producto.name,
                price: precio,
                qty: 1,
                stock: producto.stock,
                discount: 0,
                discount_concept: '$',
                is_complimentary: false,
                subtotal: precio,
                image: producto.image,
                originalProduct: producto
            };
            this.items.push(item);
            this.calcularTotales();
        },
        getPrecioSegunNivel(producto) {
            if (!this.client) return producto.retail;
            switch (this.client.level) {
                case 2:
                    return producto.wholesale > 0 ? producto.wholesale : producto.retail;
                case 3:
                    return producto.wholesale_premium > 0 ? producto.wholesale_premium : producto.retail;
                default:
                    return producto.retail;
            }
        },

        // ==================== SERVICIOS ====================
        onServicioSeleccionado(servicio) {
            const existe = this.items.find(i => i.id === servicio.id && i.type === 'service');
            if (existe) {
                existe.qty++;
                this.calcularSubtotalItem(existe);
                this.calcularTotales();
                return;
            }

            const item = {
                id: servicio.id,
                type: 'service',
                name: servicio.name,
                price: servicio.price,
                qty: 1,
                stock: 999,
                discount: 0,
                discount_concept: '$',
                is_complimentary: false,
                subtotal: servicio.price,
                image: null
            };
            this.items.push(item);
            this.calcularTotales();
        },

        async editServiceDescription(item) {
            const { value: newDesc } = await Swal.fire({
                title: 'Editar Descripción del Servicio',
                input: 'textarea',
                inputValue: item.name,
                inputPlaceholder: 'Escriba la descripción del servicio',
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar'
            });

            if (newDesc) {
                item.name = newDesc;
            }
        },

        // ==================== EQUIPOS ====================
        onEquipoSeleccionado(equipo) {
            const existe = this.items.find(i => i.id === equipo.id && i.type === 'equipment');
            if (existe) {
                Swal.fire('Atención', 'Este equipo ya está agregado', 'warning');
                return;
            }

            // Guardar equipo para ver detalles después
            this.equipos.push(equipo);

            const precio = equipo.type_sale === 1 ? equipo.retail : equipo.rent_price;
            const item = {
                id: equipo.id,
                type: 'equipment',
                name: `${equipo.trademark} ${equipo.model}`,
                fullName: `${equipo.trademark} | ${equipo.model}\n${equipo.serial_number}`,
                price: precio || 0,
                qty: 1,
                stock: 1,
                discount: 0,
                discount_concept: '$',
                is_complimentary: false,
                subtotal: precio || 0,
                image: equipo.images && equipo.images.length > 0 ? equipo.images[0].image : null,
                equipmentData: equipo
            };
            this.items.push(item);
            this.calcularTotales();
        },

        async showEquipmentDetails(item) {
            let equipo = item.equipmentData;

            // Si no tenemos los datos del equipo, buscarlo
            if (!equipo) {
                equipo = this.equipos.find(e => e.id === item.id);
            }

            let message = '';
            if (equipo) {
                message = `
                    <p><strong>${equipo.trademark} | ${equipo.model}</strong></p>
                    <p>${equipo.serial_number || ''}</p>
                    <p>${equipo.description || ''}</p>
                    <hr>
                `;

                if (equipo.type_sale === 1) {
                    message += `
                        <p><strong>Venta</strong></p>
                        <p>Menudeo: $${equipo.retail} MXN</p>
                        <p>Mayoreo: $${equipo.wholesale} MXN</p>
                    `;
                } else {
                    message += `<p><strong>Renta</strong></p>
                        <p>Precio Renta: $${equipo.rent_price} MXN</p>`;

                    if (equipo.monochrome) {
                        message += `
                            <p><strong>Blanco y Negro</strong></p>
                            <p>Pág. incluidas: ${equipo.pages_included_mono}</p>
                            <p>Costo extra: $${equipo.extra_page_cost_mono}</p>
                            <p>Contador: ${equipo.counter_mono}</p>
                        `;
                    }
                    if (equipo.color) {
                        message += `
                            <p><strong>Color</strong></p>
                            <p>Pág. incluidas: ${equipo.pages_included_color}</p>
                            <p>Costo extra: $${equipo.extra_page_cost_color}</p>
                            <p>Contador: ${equipo.counter_color}</p>
                        `;
                    }
                }
            } else {
                message = item.name || 'Sin información adicional';
            }

            await Swal.fire({
                title: 'Detalles del Equipo',
                html: message,
                confirmButtonText: 'OK'
            });
        },

        // ==================== ITEMS ====================
        removeItem(index) {
            this.items.splice(index, 1);
            this.calcularTotales();
        },
        removeItemDiscount(item) {
            item.discount = 0;
            item.discount_concept = '$';
            this.calcularSubtotalItem(item);
            this.calcularTotales();
        },
        incrementQty(item) {
            if (this.validarStock(item, item.qty + 1)) {
                item.qty++;
                this.calcularSubtotalItem(item);
                this.calcularTotales();
            }
        },
        decrementQty(item) {
            if (item.qty > 1) {
                item.qty--;
                this.calcularSubtotalItem(item);
                this.calcularTotales();
            }
        },
        onQtyChange(item) {
            if (item.qty < 1) item.qty = 1;
            if (!this.validarStock(item, item.qty)) {
                item.qty = item.stock;
            }
            this.calcularSubtotalItem(item);
            this.calcularTotales();
        },
        validarStock(item, nuevaQty) {
            if (this.cotizacion || this.sinInventario) return true;
            if (item.type !== 'product') return true;
            if (item.type === 'equipment') return true;
            if (nuevaQty > item.stock) {
                Swal.fire('Sin Stock', `Solo hay ${item.stock} unidades disponibles`, 'warning');
                return false;
            }
            return true;
        },
        calcularSubtotalItem(item) {
            // Ítem cortesía: siempre subtotal 0 (no suma al total de la nota)
            if (item.is_complimentary) {
                item.subtotal = 0;
                this.calcularTotales();
                return;
            }

            const price = parseFloat(item.price) || 0;
            const qty = parseInt(item.qty) || 1;
            const discount = parseFloat(item.discount) || 0;

            let subtotal = price * qty;
            if (discount > 0) {
                if (item.discount_concept === '%') {
                    subtotal -= (subtotal * discount / 100);
                } else {
                    subtotal -= (discount * qty);
                }
            }
            item.subtotal = Math.max(0, subtotal);
            // Recalcular totales generales
            this.calcularTotales();
        },
        toggleComplimentary(item) {
            item.is_complimentary = !item.is_complimentary;
            if (item.is_complimentary) {
                // Al marcar: limpiar descuento por ítem (ya no aplica)
                item.discount = 0;
                item.discount_concept = '$';
            }
            this.calcularSubtotalItem(item);
        },

        // ==================== TOTALES ====================
        calcularTotales() {
            // Subtotal de items
            this.subtotal = this.items.reduce((sum, item) => {
                return sum + (parseFloat(item.subtotal) || 0);
            }, 0);

            // Descuento general
            if (this.descuentoGeneral > 0) {
                if (this.descuentoGeneralTipo === '%') {
                    this.descuentoMonto = this.subtotal * this.descuentoGeneral / 100;
                } else {
                    this.descuentoMonto = this.descuentoGeneral;
                }
            } else {
                this.descuentoMonto = 0;
            }

            // Base después de descuento
            let base = this.subtotal - this.descuentoMonto;

            // IVA
            if (this.conIVA) {
                this.ivaMonto = base * this.$taxDecimal;
            } else {
                this.ivaMonto = 0;
            }

            // Total
            this.total = base + this.ivaMonto;

            // Actualizar receipt
            this.receipt.subtotal = this.subtotal;
            this.receipt.discount = this.descuentoGeneral;
            this.receipt.discount_concept = this.descuentoGeneralTipo;
            this.receipt.iva = this.ivaMonto;
            this.receipt.total = this.total;
        },

        // ==================== CAMBIO COTIZACIÓN ====================
        getItemsSinStock() {
            return this.items.filter(item => item.type === 'product' && !item.from_task_product_id && item.stock !== undefined && item.qty > item.stock);
        },
        onChangeCotizacion() {
            // Resetear sinInventario al cambiar tipo (paridad con Ionic)
            this.sinInventario = false;

            if (this.isEditMode && this.cotizacion !== this.cotizacionOriginal) {
                this.showCotizacionWarning = true;
                // Reiniciar datos relacionados
                this.receipt.received = 0;
                this.receipt.status = 'POR COBRAR';
                this.receipt.payment = 'EFECTIVO';
                this.cotizacionVencimiento = this.getDefaultQuotationDate();
            } else {
                this.showCotizacionWarning = false;
            }

            // Si cambia de cotización a nota de venta, validar stock
            if (!this.cotizacion && this.items.length > 0) {
                const sinStock = this.getItemsSinStock();
                if (sinStock.length > 0) {
                    const lista = sinStock.map(i => `• ${i.name} (stock: ${i.stock}, cant: ${i.qty})`).join('\n');
                    Swal.fire({
                        title: 'Productos sin stock suficiente',
                        html: `<p>Los siguientes productos no tienen stock suficiente para una nota de venta:</p><pre style="text-align:left;font-size:0.85rem;">${sinStock.map(i => `• ${i.name} (stock: ${i.stock}, cant: ${i.qty})`).join('\n')}</pre><p class="text-muted small">Elimínelos o ajuste las cantidades antes de guardar.</p>`,
                        icon: 'warning',
                        confirmButtonText: 'Entendido'
                    });
                }
            }
        },

        // ==================== GUARDAR ====================
        async confirmarGuardar() {
            const result = await Swal.fire({
                title: this.isEditMode ? 'ACTUALIZAR NOTA' : 'GENERAR NOTA',
                text: '¿Sus datos están correctos?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: this.isEditMode ? 'Actualizar' : 'Generar',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                this.guardarNota();
            }
        },

        async guardarNota() {
            if (!this.puedeGuardar) return;

            // Validar stock si es nota de venta (no cotización, no sinInventario)
            if (!this.cotizacion && !this.sinInventario) {
                const sinStock = this.getItemsSinStock();
                if (sinStock.length > 0) {
                    Swal.fire({
                        title: 'No se puede guardar',
                        html: `<p>Los siguientes productos no tienen stock suficiente:</p><pre style="text-align:left;font-size:0.85rem;">${sinStock.map(i => `• ${i.name} (stock: ${i.stock}, cant: ${i.qty})`).join('\n')}</pre><p class="text-muted small">Elimínelos o ajuste las cantidades.</p>`,
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
            }

            // Preparar datos
            this.receipt.quotation = this.cotizacion ? 1 : 0;
            this.receipt.credit = this.esCredito ? 1 : 0;

            if (this.cotizacion) {
                this.receipt.quotation_expiration = this.cotizacionVencimiento;
            }

            if (this.esCredito) {
                this.receipt.credit_date_notification = this.creditoFecha;
                this.receipt.credit_type = this.creditoTipo;
            }

            // Preparar detalle
            const detail = this.items.map(item => ({
                id: item.id,
                type: item.type,
                name: item.type === 'equipment' ? this.getEquipmentDetailString(item) : item.name,
                cost: item.price,
                qty: item.qty,
                discount: item.discount,
                discount_concept: item.discount_concept,
                is_complimentary: !!item.is_complimentary,
                subtotal: item.subtotal,
                from_task_product_id: item.from_task_product_id || null
            }));

            // Preparar info_extra
            const infoExtra = {};
            Object.entries(this.fieldValueMap).forEach(([fieldId, value]) => {
                if (value && value.toString().trim() !== '') {
                    const field = this.extraFields.find(f => f.id === parseInt(fieldId));
                    if (field) {
                        infoExtra[field.field_name] = value;
                    }
                }
            });

            const payload = {
                receipt: this.receipt,
                detail: JSON.stringify(detail),
                info_extra: JSON.stringify(infoExtra)
            };

            this.isGuardando = true;

            try {
                let response;
                if (this.isCreateMode) {
                    response = await axios.post('/admin/receipts/store', payload);
                } else {
                    response = await axios.post(`/admin/receipts/${this.receiptId}/update`, payload);
                }

                if (response.data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: this.isCreateMode ?
                            (this.cotizacion ? 'Cotización Guardada' : 'Nota de Venta Guardada') :
                            (this.cotizacion ? 'Cotización Actualizada' : 'Nota Actualizada'),
                        text: `Folio: ${response.data.receipt.folio}`,
                        confirmButtonText: 'Aceptar'
                    });

                    if (this.isCreateMode) {
                        this.limpiarFormulario();
                    } else {
                        // Redireccionar a la lista
                        window.location.href = '/admin/receipts';
                    }
                } else {
                    Swal.fire('Error', response.data.message || 'Error al guardar', 'error');
                }
            } catch (error) {
                console.error('Error al guardar:', error);
                const mensaje = error.response?.data?.message || 'Error al guardar la nota';
                Swal.fire('Error', mensaje, 'error');
            } finally {
                this.isGuardando = false;
            }
        },

        getEquipmentDetailString(item) {
            const equipo = item.equipmentData || this.equipos.find(e => e.id === item.id);
            if (!equipo) return item.name;

            if (equipo.type_sale === 1) {
                return `${equipo.trademark} | ${equipo.model}\n${equipo.serial_number}\n` +
                    (equipo.monochrome ? `Blanco y Negro\nB/N Contador ${equipo.counter_mono}\n` : '') +
                    (equipo.color ? `Color\nColor Contador ${equipo.counter_color}\n` : '');
            } else {
                return `${equipo.trademark} | ${equipo.model}\n${equipo.serial_number}\n` +
                    (equipo.monochrome ? `Blanco y Negro\nB/N Pag. incluidas ${equipo.pages_included_mono}\nB/N Costo Extra ${equipo.extra_page_cost_mono}\nB/N Contador ${equipo.counter_mono}\n` : '') +
                    (equipo.color ? `Color\nColor Pag. incluidas ${equipo.pages_included_color}\nColor Costo Extra ${equipo.extra_page_cost_color}\nColor Contador ${equipo.counter_color}\n` : '');
            }
        },

        limpiarFormulario() {
            this.client = null;
            this.items = [];
            this.equipos = [];
            this.subtotal = 0;
            this.descuentoGeneral = 0;
            this.descuentoMonto = 0;
            this.ivaMonto = 0;
            this.total = 0;
            this.conIVA = false;
            this.esCredito = false;
            // Limpiar campos extra
            this.extraFields.forEach(field => {
                this.fieldValueMap[field.id] = '';
            });
            this.receipt = {
                client_id: 0,
                rent_id: 0,
                type: 'venta',
                description: '',
                observation: '',
                discount_concept: '$',
                status: 'POR COBRAR',
                payment: 'EFECTIVO',
                subtotal: 0,
                discount: 0,
                received: 0,
                total: 0,
                iva: 0,
                quotation: this.cotizacion ? 1 : 0,
                quotation_expiration: null,
                credit: false,
                credit_date_notification: null,
                credit_type: 'semanal'
            };
        },

        // ==================== NAVEGACIÓN ====================
        goToEdit() {
            window.location.href = `/admin/receipts/${this.receiptId}/edit`;
        },
        goBack() {
            window.location.href = '/admin/receipts';
        },
        async descargarPDF() {
            const result = await Swal.fire({
                title: 'Generar PDF',
                html: `
                    <div style="text-align: left; padding: 10px 0;">
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="pdfWithImages" style="width: 18px; height: 18px; cursor: pointer;">
                            <i class="fa fa-image"></i> Incluir imágenes de productos
                        </label>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fa fa-file-pdf-o me-1"></i> Generar PDF',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    return { withImages: document.getElementById('pdfWithImages').checked };
                }
            });

            if (result.isConfirmed) {
                const folio = this.receiptOriginal?.folio || this.receiptId;
                let url = `/print-receipt-rent?id=${this.receiptId}&name_file=nota_${folio}`;
                if (result.value.withImages) {
                    url += '&with_images=1';
                }
                window.open(url, '_blank');
            }
        },

        // ==================== PAGOS PARCIALES / ABONOS ====================
        crearAbonoVacio() {
            return {
                amount: 0,
                payment_method: '01',
                shop_bank_account_id: null,
                bank_ord_code: '',
                cta_ordenante: '',
                is_foreign_bank_ord: false,
                num_operacion: '',
            };
        },

        async cargarCuentasBancariasShop() {
            // Solo carga una vez por sesión del componente
            if (this.cuentasBancarias.length > 0) return;
            try {
                const res = await axios.get('/admin/configuracion/cuentas-bancarias/data');
                if (res.data.ok) {
                    this.cuentasBancarias = (res.data.accounts || []).filter(c => c.is_active);
                }
            } catch (e) {
                // Silencioso: el aviso "no hay cuentas" ya cubre el caso de fallo
                console.warn('No se pudieron cargar las cuentas bancarias.', e);
            }
        },

        mapearFormaPagoReceipt(payment) {
            const m = String(payment || '').trim().toUpperCase();
            return ({
                'EFECTIVO': '01',
                'CHEQUE': '02',
                'TRANSFERENCIA': '03',
                'TARJETA': '04',
            })[m] || '01';
        },

        abrirModalAbono() {
            this.nuevoAbono = this.crearAbonoVacio();
            this.nuevoAbono.amount = this.adeudo;
            // Forma de pago default: la de la nota original (mapeada a SAT)
            this.nuevoAbono.payment_method = this.mapearFormaPagoReceipt(this.receipt.payment);
            this.showModalAbono = true;
            this.cargarCuentasBancariasShop();
            this.$nextTick(() => {
                if (this.$refs.inputAbono) {
                    this.$refs.inputAbono.focus();
                    this.$refs.inputAbono.select();
                }
            });
        },

        async agregarAbono() {
            if (!this.nuevoAbono.amount || this.nuevoAbono.amount <= 0) return;

            this.agregandoAbono = true;
            try {
                // Solo enviar campos bancarios si la forma es bancarizada
                const bancarizada = ['02','03','04','05','06','28','29'].includes(this.nuevoAbono.payment_method);
                const payload = {
                    amount: this.nuevoAbono.amount,
                    payment_method: this.nuevoAbono.payment_method,
                };
                if (bancarizada) {
                    if (this.nuevoAbono.shop_bank_account_id) payload.shop_bank_account_id = this.nuevoAbono.shop_bank_account_id;
                    if (this.nuevoAbono.bank_ord_code) payload.bank_ord_code = this.nuevoAbono.bank_ord_code;
                    if (this.nuevoAbono.cta_ordenante) payload.cta_ordenante = this.nuevoAbono.cta_ordenante;
                    if (this.nuevoAbono.is_foreign_bank_ord) payload.is_foreign_bank_ord = true;
                    if (this.nuevoAbono.num_operacion) payload.num_operacion = this.nuevoAbono.num_operacion;
                }
                const response = await axios.post(`/admin/receipts/${this.receiptId}/partial-payment`, payload);
                if (response.data.ok) {
                    this.showModalAbono = false;
                    this.nuevoAbono = this.crearAbonoVacio();
                    // Actualizar receiptOriginal con datos frescos del server
                    this.receiptOriginal = response.data.receipt;
                    this.receipt.received = response.data.receipt.received;
                    this.receipt.status = response.data.receipt.status;

                    // Mensaje contextual según haya disparado complemento PPD
                    const comp = response.data.complemento;
                    if (comp && comp.ok) {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Abono y complemento de pago registrados', showConfirmButton: false, timer: 2500 });
                    } else if (comp && !comp.ok) {
                        Swal.fire('Abono registrado', 'El abono se guardo correctamente, pero el complemento de pago fallo: ' + comp.message + '. Puedes re-emitirlo desde la lista.', 'warning');
                    } else {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Abono registrado', showConfirmButton: false, timer: 2000 });
                    }

                    // Refrescar lista de complementos si está montada
                    if (this.$refs.complementosList) {
                        this.$refs.complementosList.cargar();
                    }
                } else {
                    Swal.fire('Error', response.data.message || 'Error al agregar abono', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo agregar el abono', 'error');
            } finally {
                this.agregandoAbono = false;
            }
        },

        async eliminarAbono(pago) {
            const result = await Swal.fire({
                title: 'Eliminar Abono',
                text: `¿Eliminar abono de ${this.formatCurrency(pago.amount)}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            });

            if (!result.isConfirmed) return;

            this.eliminandoAbono = true;
            try {
                const response = await axios.delete(`/admin/receipts/partial-payment/${pago.id}`);
                if (response.data.ok) {
                    this.receiptOriginal = response.data.receipt;
                    this.receipt.received = response.data.receipt.received;
                    this.receipt.status = response.data.receipt.status;
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Abono eliminado', showConfirmButton: false, timer: 2000 });
                } else {
                    Swal.fire('Error', response.data.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo eliminar el abono', 'error');
            } finally {
                this.eliminandoAbono = false;
            }
        },

        // ==================== ACCIONES RÁPIDAS ====================
        async cancelarNota() {
            const result = await Swal.fire({
                title: 'Cancelar Nota',
                text: '¿Está seguro de cancelar esta nota? Se restaurará el stock de los productos.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, Cancelar Nota',
                cancelButtonText: 'No',
                confirmButtonColor: '#dc3545'
            });
            if (!result.isConfirmed) return;

            this.accionEnProceso = true;
            try {
                const response = await axios.post(`/admin/receipts/${this.receiptId}/cancel`);
                if (response.data.ok) {
                    this.receiptOriginal.status = 'CANCELADA';
                    this.receipt.status = 'CANCELADA';
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Nota cancelada', showConfirmButton: false, timer: 2000 });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo cancelar la nota', 'error');
            } finally {
                this.accionEnProceso = false;
            }
        },

        async toggleFacturado() {
            const nuevoValor = !this.receiptOriginal.is_tax_invoiced;
            const texto = nuevoValor ? 'Marcar como Facturado' : 'Marcar como NO Facturado';

            const result = await Swal.fire({
                title: texto,
                text: `¿${texto}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí',
                cancelButtonText: 'No'
            });
            if (!result.isConfirmed) return;

            this.accionEnProceso = true;
            try {
                const response = await axios.patch(`/admin/receipts/${this.receiptId}/toggle-invoiced`, {
                    is_facturado: nuevoValor
                });
                if (response.data.ok) {
                    this.receiptOriginal.is_tax_invoiced = nuevoValor;
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: nuevoValor ? 'Marcada como facturada' : 'Marcada como no facturada', showConfirmButton: false, timer: 2000 });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo actualizar', 'error');
            } finally {
                this.accionEnProceso = false;
            }
        },

        async convertirAVenta() {
            // Validar stock antes de convertir
            const sinStock = this.getItemsSinStock();
            if (sinStock.length > 0) {
                Swal.fire({
                    title: 'No se puede convertir',
                    html: `<p>Los siguientes productos no tienen stock suficiente:</p><pre style="text-align:left;font-size:0.85rem;">${sinStock.map(i => `• ${i.name} (stock: ${i.stock}, cant: ${i.qty})`).join('\n')}</pre><p class="text-muted small">Ajuste las cantidades o elimine los productos sin stock antes de convertir.</p>`,
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            const result = await Swal.fire({
                title: 'Cambiar a Nota de Venta',
                text: '¿Realmente desea cambiar a nota de venta? Se descontará el stock de su inventario.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, Cambiar',
                cancelButtonText: 'No',
                confirmButtonColor: '#0d6efd'
            });
            if (!result.isConfirmed) return;

            this.accionEnProceso = true;
            try {
                const response = await axios.post(`/admin/receipts/${this.receiptId}/convert-to-sale`);
                if (response.data.ok) {
                    this.receiptOriginal.quotation = 0;
                    this.cotizacion = false;
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Convertida a nota de venta', showConfirmButton: false, timer: 2000 });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo convertir', 'error');
            } finally {
                this.accionEnProceso = false;
            }
        },

        // ==================== UTILIDADES ====================
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
        },
        formatCurrency(amount) {
            const curr = this.$shopCurrency || 'MXN';
            const locale = curr === 'USD' ? 'en-US' : 'es-MX';
            return new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: curr
            }).format(amount || 0);
        },
        getItemImage(imagePath) {
            if (!imagePath) return null;
            return `/storage/${imagePath}`;
        },
        handleImageError(event) {
            event.target.style.display = 'none';
        },
        openImageViewer(imagePath) {
            if (imagePath) {
                this.$viewImage(imagePath);
            }
        },
        getLevelBadgeClass(level) {
            switch (level) {
                case 2: return 'bg-info';
                case 3: return 'bg-warning text-dark';
                default: return 'bg-secondary';
            }
        },
        getTypeBadgeClass(type) {
            switch (type) {
                case 'product': return 'bg-success';
                case 'service': return 'bg-info';
                case 'equipment': return 'bg-warning text-dark';
                default: return 'bg-secondary';
            }
        },
        getTypeLabel(type) {
            switch (type) {
                case 'product': return 'Producto';
                case 'service': return 'Servicio';
                case 'equipment': return 'Equipo';
                default: return type;
            }
        },
        getTypeIcon(type) {
            switch (type) {
                case 'product': return 'fa-cube';
                case 'service': return 'fa-cog';
                case 'equipment': return 'fa-print';
                default: return 'fa-cube';
            }
        },
        getDefaultCreditDate() {
            const date = new Date();
            date.setDate(date.getDate() + 7);
            return date.toISOString().split('T')[0];
        },
        getDefaultQuotationDate() {
            const date = new Date();
            date.setDate(date.getDate() + 15);
            return date.toISOString().split('T')[0];
        }
    }
}
</script>

<style scoped>
.receipt-form {
    max-width: 1400px;
    margin: 0 auto;
}

/* ============ TOOLBAR COMPACTA ============ */
.toolbar-compact {
    background: var(--j2b-gray-100);
    border: 1px solid var(--j2b-gray-200);
    border-radius: var(--j2b-radius-md);
    padding: 0.4rem 0.75rem;
}

/* ============ CLIENTE COMPACTO ============ */
.client-bar {
    display: flex;
    align-items: center;
    border: 1px solid var(--j2b-gray-200);
    border-radius: var(--j2b-radius-md);
    padding: 0.5rem 0.75rem;
    background: #fff;
    min-height: 42px;
    transition: var(--j2b-transition-fast);
}
.client-bar-empty.clickable {
    cursor: pointer;
    border-style: dashed;
    border-color: var(--j2b-info);
    background: rgba(0, 217, 245, 0.05);
}
.client-bar-empty.clickable:hover {
    background: rgba(0, 217, 245, 0.1);
    border-color: var(--j2b-primary);
}
.client-avatar-sm {
    width: 32px;
    height: 32px;
    background: var(--j2b-gradient-primary);
    border-radius: var(--j2b-radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--j2b-dark);
    font-size: 0.85rem;
    flex-shrink: 0;
}
.fw-500 { font-weight: 500; }
.fw-600 { font-weight: 600; }
.min-width-0 { min-width: 0; }

/* ============ EXTRA FIELDS INLINE ============ */
.extra-fields-bar {
    background: var(--j2b-gray-100);
    border: 1px solid var(--j2b-gray-200);
    border-radius: var(--j2b-radius-md);
    padding: 0.4rem 0.5rem;
}

/* ============ NOTES SECTION ============ */
.notes-section {
    background: var(--j2b-gray-100);
    border: 1px solid var(--j2b-gray-200);
    border-radius: var(--j2b-radius-md);
    padding: 0.5rem;
}

/* Legacy support */
.client-avatar {
    width: 50px;
    height: 50px;
    background: var(--j2b-gradient-primary);
    border-radius: var(--j2b-radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--j2b-dark);
    font-size: 1.5rem;
}

.client-selected {
    padding: 0.5rem;
    background: var(--j2b-gray-100);
    border-radius: var(--j2b-radius-md);
}

.credit-options {
    border-left: 3px solid var(--j2b-info);
    background: var(--j2b-gray-100);
    padding: 0.75rem;
    border-radius: 0 var(--j2b-radius-md) var(--j2b-radius-md) 0;
}

/* ============ ITEMS LIST - Card Style ============ */
.items-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.item-card {
    border: 1px solid var(--j2b-gray-200);
    border-radius: var(--j2b-radius-md);
    padding: 1rem;
    background: #fff;
    transition: var(--j2b-transition-fast);
}

.item-card:hover {
    box-shadow: var(--j2b-shadow-sm);
}

/* Fila Superior: Imagen + Info + Subtotal + Delete */
.item-row-top {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--j2b-gray-100);
}

.item-image {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: var(--j2b-radius-sm);
    background: var(--j2b-gray-100);
    cursor: pointer;
}

.item-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--j2b-gray-100);
    border-radius: var(--j2b-radius-sm);
    color: var(--j2b-gray-400);
    font-size: 1.5rem;
}

.item-info {
    flex: 1;
    min-width: 0;
}

.item-name {
    font-weight: var(--j2b-font-semibold);
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
    word-break: break-word;
    color: var(--j2b-dark);
}

.item-meta {
    font-size: var(--j2b-font-xs);
}

.item-subtotal {
    flex-shrink: 0;
    font-size: 1.1rem;
    color: var(--j2b-success);
    font-weight: var(--j2b-font-semibold);
}

.item-delete {
    flex-shrink: 0;
}

/* Fila Inferior: Controles */
.item-row-bottom {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.item-control {
    flex: 1;
    min-width: 120px;
}

.item-control label {
    display: block;
    font-size: var(--j2b-font-xs);
    color: var(--j2b-gray-500);
    margin-bottom: 0.25rem;
    font-weight: var(--j2b-font-medium);
}

/* Control de Cantidad */
.qty-control {
    display: flex;
    align-items: center;
    gap: 0;
}

.qty-control button {
    flex-shrink: 0;
}

.qty-control input {
    width: 50px;
    border-radius: 0;
    border-left: none;
    border-right: none;
}

.qty-control button:first-child {
    border-radius: var(--j2b-radius-sm) 0 0 var(--j2b-radius-sm);
}

.qty-control button:last-child {
    border-radius: 0 var(--j2b-radius-sm) var(--j2b-radius-sm) 0;
}

/* Control de Descuento */
.discount-control {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.discount-control input {
    width: 70px;
}

.discount-control select {
    width: 55px;
}

.table th {
    font-size: var(--j2b-font-sm);
    font-weight: var(--j2b-font-semibold);
}

.table td {
    vertical-align: middle;
}

.sticky-top {
    z-index: 100;
}

@media (max-width: 992px) {
    .sticky-top {
        position: relative !important;
        top: 0 !important;
    }
}

/* Ítem marcado como cortesía: inputs con apariencia tachada/atenuada */
.item-row-bottom.item-complimentary input,
.item-row-bottom.item-complimentary select {
    text-decoration: line-through;
    color: #999 !important;
    background-color: #f8f9fa !important;
    opacity: 0.8;
}
</style>
