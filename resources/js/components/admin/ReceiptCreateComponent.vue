<template>
<div class="receipt-create">
    <!-- Toggle Nota/Cotización -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex align-items-center justify-content-between">
                <div class="form-check form-switch">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="toggleCotizacion"
                        v-model="cotizacion"
                        @change="onChangeCotizacion"
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
                <div v-if="!cotizacion" class="form-check form-switch">
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
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Cliente + Items -->
        <div class="col-lg-8">
            <!-- Sección Cliente -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white py-2">
                    <i class="fa fa-user me-2"></i>Cliente
                </div>
                <div class="card-body">
                    <div v-if="!client" class="text-center py-3">
                        <button class="btn btn-outline-primary btn-lg" @click="showModalCliente = true">
                            <i class="fa fa-user-plus me-2"></i>Seleccionar Cliente
                        </button>
                    </div>
                    <div v-else class="client-selected">
                        <div class="d-flex align-items-center">
                            <div class="client-avatar me-3">
                                <i class="fa fa-user"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ client.name }}</h5>
                                <small class="text-muted">
                                    <span v-if="client.movil" class="me-3">
                                        <i class="fa fa-phone me-1"></i>{{ client.movil }}
                                    </span>
                                    <span class="badge" :class="getLevelBadgeClass(client.level)">
                                        Nivel {{ client.level || 1 }}
                                    </span>
                                </small>
                            </div>
                            <button class="btn btn-outline-danger btn-sm" @click="removeClient">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campos Extra Dinámicos -->
            <div v-if="extraFields.length > 0" class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="fa fa-list-alt me-2"></i>Información Adicional
                </div>
                <div class="card-body">
                    <div class="row">
                        <div
                            v-for="field in extraFields"
                            :key="field.id"
                            class="col-md-6 mb-2"
                        >
                            <label class="form-label small">{{ field.field_name }}</label>
                            <input
                                type="text"
                                class="form-control form-control-sm"
                                v-model="fieldValueMap[field.id]"
                                :placeholder="field.field_name"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones Agregar Items -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="fa fa-plus-circle me-2"></i>Agregar Items
                </div>
                <div class="card-body py-2">
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-success" @click="showModalProducto = true" :disabled="!client">
                            <i class="fa fa-box me-1"></i>Producto
                        </button>
                        <button class="btn btn-info" @click="showModalServicio = true" :disabled="!client">
                            <i class="fa fa-concierge-bell me-1"></i>Servicio
                        </button>
                        <button class="btn btn-warning" @click="showModalEquipo = true" :disabled="!client">
                            <i class="fa fa-print me-1"></i>Equipo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista de Items -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-list me-2"></i>Detalle de la Nota</span>
                    <span class="badge bg-secondary">{{ items.length }} item(s)</span>
                </div>
                <div class="card-body p-0">
                    <div v-if="items.length === 0" class="text-center py-5 text-muted">
                        <i class="fa fa-shopping-cart fa-3x mb-3"></i>
                        <p>No hay items agregados</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%">Descripción</th>
                                    <th style="width: 15%" class="text-center">Precio</th>
                                    <th style="width: 15%" class="text-center">Cantidad</th>
                                    <th style="width: 15%" class="text-center">Desc.</th>
                                    <th style="width: 10%" class="text-end">Subtotal</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in items" :key="index">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img
                                                v-if="item.image"
                                                :src="getItemImage(item.image)"
                                                class="item-thumbnail me-2"
                                                @error="handleImageError"
                                            >
                                            <div>
                                                <div class="fw-bold">{{ item.name }}</div>
                                                <small class="text-muted">
                                                    <span class="badge" :class="getTypeBadgeClass(item.type)">
                                                        {{ getTypeLabel(item.type) }}
                                                    </span>
                                                    <span v-if="item.type === 'product'" class="ms-1">
                                                        Stock: {{ item.stock }}
                                                    </span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <input
                                            type="number"
                                            class="form-control form-control-sm text-center"
                                            v-model.number="item.price"
                                            @input="calcularSubtotalItem(item)"
                                            min="0"
                                            step="0.01"
                                        >
                                    </td>
                                    <td class="text-center">
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary" @click="decrementQty(item)">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input
                                                type="number"
                                                class="form-control text-center"
                                                v-model.number="item.qty"
                                                @input="onQtyChange(item)"
                                                min="1"
                                                style="width: 50px"
                                            >
                                            <button class="btn btn-outline-secondary" @click="incrementQty(item)">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="number"
                                                class="form-control text-center"
                                                v-model.number="item.discount"
                                                @input="calcularSubtotalItem(item)"
                                                min="0"
                                                style="width: 60px"
                                            >
                                            <select
                                                class="form-select"
                                                v-model="item.discount_concept"
                                                @change="calcularSubtotalItem(item)"
                                                style="width: 50px"
                                            >
                                                <option value="$">$</option>
                                                <option value="%">%</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ formatCurrency(item.subtotal) }}
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-danger btn-sm" @click="removeItem(index)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Descripción y Observaciones -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="fa fa-comment me-2"></i>Notas
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label small">Descripción</label>
                            <textarea
                                class="form-control"
                                v-model="receipt.description"
                                rows="2"
                                placeholder="Descripción de la nota..."
                            ></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Observaciones</label>
                            <textarea
                                class="form-control"
                                v-model="receipt.observation"
                                rows="2"
                                placeholder="Observaciones adicionales..."
                            ></textarea>
                        </div>
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
                            >
                            <select class="form-select" v-model="descuentoGeneralTipo" @change="calcularTotales" style="max-width: 70px">
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
                        <input class="form-check-input" type="checkbox" id="toggleIVA" v-model="conIVA" @change="calcularTotales">
                        <label class="form-check-label" for="toggleIVA">
                            Agregar IVA (16%)
                        </label>
                    </div>
                    <div v-if="conIVA" class="d-flex justify-content-between mb-2">
                        <span>IVA:</span>
                        <span>{{ formatCurrency(ivaMonto) }}</span>
                    </div>

                    <hr>

                    <!-- Total -->
                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5 mb-0">TOTAL:</span>
                        <span class="h4 mb-0 text-success fw-bold">{{ formatCurrency(total) }}</span>
                    </div>

                    <!-- Opciones de Pago (solo notas, no cotizaciones) -->
                    <div v-if="!cotizacion">
                        <hr>
                        <h6 class="mb-3"><i class="fa fa-credit-card me-2"></i>Pago</h6>

                        <!-- Estatus -->
                        <div class="mb-3">
                            <label class="form-label small">Estatus</label>
                            <select class="form-select" v-model="receipt.status">
                                <option value="POR COBRAR">POR COBRAR</option>
                                <option value="PAGADA">PAGADA</option>
                                <option value="POR FACTURAR">POR FACTURAR</option>
                            </select>
                        </div>

                        <!-- Forma de Pago -->
                        <div class="mb-3">
                            <label class="form-label small">Forma de Pago</label>
                            <select class="form-select" v-model="receipt.payment">
                                <option value="EFECTIVO">EFECTIVO</option>
                                <option value="TARJETA">TARJETA</option>
                                <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                            </select>
                        </div>

                        <!-- Cantidad Recibida -->
                        <div class="mb-3">
                            <label class="form-label small">Cantidad Recibida</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model.number="receipt.received"
                                    min="0"
                                    step="0.01"
                                >
                                <button class="btn btn-outline-secondary" @click="receipt.received = total" title="Copiar total">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Crédito -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="toggleCredito" v-model="esCredito">
                            <label class="form-check-label" for="toggleCredito">
                                Es crédito
                            </label>
                        </div>
                        <div v-if="esCredito" class="credit-options ps-3">
                            <div class="mb-2">
                                <label class="form-label small">Fecha notificación</label>
                                <input type="date" class="form-control form-control-sm" v-model="creditoFecha">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Tipo</label>
                                <select class="form-select form-select-sm" v-model="creditoTipo">
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
                            <input type="date" class="form-control" v-model="cotizacionVencimiento">
                        </div>
                    </div>

                    <hr>

                    <!-- Botón Guardar -->
                    <button
                        class="btn btn-success btn-lg w-100"
                        @click="guardarNota"
                        :disabled="!puedeGuardar || isGuardando"
                    >
                        <span v-if="isGuardando">
                            <i class="fa fa-spinner fa-spin me-2"></i>Guardando...
                        </span>
                        <span v-else>
                            <i class="fa fa-save me-2"></i>
                            {{ cotizacion ? 'Guardar Cotización' : 'Guardar Nota de Venta' }}
                        </span>
                    </button>

                    <!-- Validaciones -->
                    <div v-if="!puedeGuardar" class="alert alert-warning mt-3 mb-0 small">
                        <ul class="mb-0 ps-3">
                            <li v-if="!client">Seleccione un cliente</li>
                            <li v-if="items.length === 0">Agregue al menos un item</li>
                        </ul>
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
</div>
</template>

<script>
export default {
    name: 'ReceiptCreateComponent',
    data() {
        return {
            // UI
            cotizacion: false,
            sinInventario: false,
            conIVA: false,
            esCredito: false,
            isGuardando: false,

            // Modales
            showModalCliente: false,
            showModalProducto: false,
            showModalServicio: false,
            showModalEquipo: false,

            // Cliente
            client: null,

            // Items
            items: [],

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
            }
        }
    },
    computed: {
        puedeGuardar() {
            return this.client && this.items.length > 0;
        }
    },
    watch: {
        items: {
            handler(newItems) {
                console.log('🔄 WATCH items disparado', JSON.stringify(newItems.map(i => ({
                    name: i.name,
                    price: i.price,
                    qty: i.qty,
                    discount: i.discount,
                    subtotal: i.subtotal
                }))));
                // Recalcular subtotales y totales cuando cambie cualquier item
                this.items.forEach(item => {
                    this.calcularSubtotalItem(item, false);
                });
                this.calcularTotales();
            },
            deep: true
        }
    },
    mounted() {
        this.loadExtraFields();
    },
    methods: {
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
                name: producto.name,
                price: precio,
                qty: 1,
                stock: producto.stock,
                discount: 0,
                discount_concept: '$',
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
                subtotal: servicio.price,
                image: null
            };
            this.items.push(item);
            this.calcularTotales();
        },

        // ==================== EQUIPOS ====================
        onEquipoSeleccionado(equipo) {
            const existe = this.items.find(i => i.id === equipo.id && i.type === 'equipment');
            if (existe) {
                Swal.fire('Atención', 'Este equipo ya está agregado', 'warning');
                return;
            }

            const item = {
                id: equipo.id,
                type: 'equipment',
                name: `${equipo.trademark} ${equipo.model} - ${equipo.serial_number}`,
                price: equipo.sale_price || 0,
                qty: 1,
                stock: 1,
                discount: 0,
                discount_concept: '$',
                subtotal: equipo.sale_price || 0,
                image: equipo.image || (equipo.images && equipo.images.length > 0 ? equipo.images[0].image : null)
            };
            this.items.push(item);
            this.calcularTotales();
        },

        // ==================== ITEMS ====================
        removeItem(index) {
            this.items.splice(index, 1);
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
            if (nuevaQty > item.stock) {
                Swal.fire('Sin Stock', `Solo hay ${item.stock} unidades disponibles`, 'warning');
                return false;
            }
            return true;
        },
        calcularSubtotalItem(item, recalcularTotal = true) {
            const price = parseFloat(item.price) || 0;
            const qty = parseInt(item.qty) || 1;
            const discount = parseFloat(item.discount) || 0;

            console.log('📦 calcularSubtotalItem:', { price, qty, discount, discount_concept: item.discount_concept });

            let subtotal = price * qty;
            if (discount > 0) {
                if (item.discount_concept === '%') {
                    subtotal -= (subtotal * discount / 100);
                } else {
                    subtotal -= discount;
                }
            }
            item.subtotal = Math.max(0, subtotal);
            console.log('📦 subtotal calculado:', item.subtotal);

            // Recalcular total general
            if (recalcularTotal) {
                this.calcularTotales();
            }
        },

        // ==================== TOTALES ====================
        calcularTotales() {
            console.log('💰 calcularTotales LLAMADO');
            console.log('💰 items subtotales:', this.items.map(i => i.subtotal));

            // Subtotal de items (asegurar que sean números)
            this.subtotal = this.items.reduce((sum, item) => {
                return sum + (parseFloat(item.subtotal) || 0);
            }, 0);

            console.log('💰 this.subtotal =', this.subtotal);
            console.log('💰 this.total ANTES =', this.total);

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
                this.ivaMonto = base * 0.16;
            } else {
                this.ivaMonto = 0;
            }

            // Total
            this.total = base + this.ivaMonto;

            console.log('💰 this.total DESPUÉS =', this.total);

            // Actualizar receipt
            this.receipt.subtotal = this.subtotal;
            this.receipt.discount = this.descuentoMonto;
            this.receipt.discount_concept = this.descuentoGeneralTipo;
            this.receipt.iva = this.ivaMonto;
            this.receipt.total = this.total;
        },

        // ==================== GUARDAR ====================
        async guardarNota() {
            if (!this.puedeGuardar) return;

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
                name: item.name,
                cost: item.price,
                qty: item.qty,
                discount: item.discount,
                discount_concept: item.discount_concept,
                subtotal: item.subtotal
            }));

            // Preparar info_extra: convertir fieldValueMap a formato {field_name: value}
            const infoExtra = {};
            Object.entries(this.fieldValueMap).forEach(([fieldId, value]) => {
                if (value && value.trim() !== '') {
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
                const response = await axios.post('/admin/receipts/store', payload);

                if (response.data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: this.cotizacion ? 'Cotización Guardada' : 'Nota de Venta Guardada',
                        text: `Folio: ${response.data.receipt.folio}`,
                        confirmButtonText: 'Aceptar'
                    });

                    // Limpiar formulario o redireccionar
                    this.limpiarFormulario();
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
        limpiarFormulario() {
            this.client = null;
            this.items = [];
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

        // ==================== UTILIDADES ====================
        onChangeCotizacion() {
            this.receipt.quotation = this.cotizacion ? 1 : 0;
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(amount || 0);
        },
        getItemImage(imagePath) {
            if (!imagePath) return null;
            return `/storage/${imagePath}`;
        },
        handleImageError(event) {
            event.target.style.display = 'none';
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
.receipt-create {
    max-width: 1400px;
    margin: 0 auto;
}

.client-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.client-selected {
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.item-thumbnail {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: 4px;
    background: #f8f9fa;
}

.credit-options {
    border-left: 3px solid #0dcaf0;
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 0 8px 8px 0;
}

.table th {
    font-size: 0.85rem;
    font-weight: 600;
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
</style>
