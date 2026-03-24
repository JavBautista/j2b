<template>
<div class="purchase-order-create">
    <div class="row">
        <!-- Columna Izquierda: Proveedor + Items -->
        <div class="col-lg-8">
            <!-- Sección Proveedor compacta -->
            <div class="supplier-section mb-2">
                <div v-if="!supplier" class="supplier-bar supplier-bar-empty" @click="showModalProveedor = true">
                    <i class="fa fa-truck me-2" style="color: var(--j2b-primary);"></i>
                    <span class="fw-500" style="color: var(--j2b-primary);">Seleccionar Proveedor</span>
                </div>
                <div v-else class="supplier-bar supplier-bar-filled">
                    <div class="supplier-avatar-sm me-2">
                        <i class="fa fa-truck"></i>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <span class="fw-600">{{ supplier.name || supplier.company }}</span>
                        <small class="text-muted ms-2" v-if="supplier.company && supplier.name">
                            <i class="fa fa-building me-1"></i>{{ supplier.company }}
                        </small>
                        <small class="text-muted ms-2" v-if="supplier.phone || supplier.movil">
                            <i class="fa fa-phone me-1"></i>{{ supplier.phone || supplier.movil }}
                        </small>
                    </div>
                    <button class="btn btn-outline-danger btn-sm py-0" @click="removeSupplier">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Lista de Items con botón agregar integrado en header -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-list me-2"></i>Detalle <span class="badge bg-secondary ms-1">{{ items.length }}</span></span>
                    <button class="btn btn-success btn-sm py-0" @click="showModalProducto = true" :disabled="!supplier">
                        <i class="fa fa-cube me-1"></i>Agregar Producto
                    </button>
                </div>
                <div class="card-body p-0">
                    <div v-if="items.length === 0" class="text-center py-3 text-muted">
                        <i class="fa fa-shopping-cart fa-2x mb-2"></i>
                        <p class="mb-0 small">No hay productos agregados</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45%">Producto</th>
                                    <th style="width: 20%" class="text-center">Costo</th>
                                    <th style="width: 20%" class="text-center">Cantidad</th>
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
                                                @click="verImagen(item.image)"
                                                style="cursor: pointer;"
                                                @error="handleImageError"
                                            >
                                            <div>
                                                <div class="fw-bold">{{ item.name }}</div>
                                                <small class="text-muted">
                                                    Stock actual: {{ item.stock }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input
                                                type="number"
                                                class="form-control text-center"
                                                v-model.number="item.cost"
                                                @input="calcularSubtotalItem(item)"
                                                min="0"
                                                step="0.01"
                                            >
                                        </div>
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
                                                style="width: 60px"
                                            >
                                            <button class="btn btn-outline-secondary" @click="incrementQty(item)">
                                                <i class="fa fa-plus"></i>
                                            </button>
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

            <!-- Observaciones -->
            <div class="notes-section mb-3">
                <textarea
                    class="form-control form-control-sm"
                    v-model="order.observation"
                    rows="2"
                    placeholder="Observaciones adicionales..."
                ></textarea>
            </div>
        </div>

        <!-- Columna Derecha: Totales y Configuración -->
        <div class="col-lg-4">
            <div class="card mb-3 sticky-top" style="top: 20px">
                <div class="card-header bg-dark text-white py-2">
                    <i class="fa fa-calculator me-2"></i>Resumen
                </div>
                <div class="card-body">
                    <!-- Total -->
                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5 mb-0">TOTAL:</span>
                        <span class="h4 mb-0 text-success fw-bold">{{ formatCurrency(total) }}</span>
                    </div>

                    <hr>

                    <!-- Configuración -->
                    <h6 class="mb-3"><i class="fa fa-cog me-2"></i>Configuración</h6>

                    <!-- Forma de Pago -->
                    <div class="mb-3">
                        <label class="form-label small">Forma de Pago</label>
                        <select class="form-select" v-model="order.payment">
                            <option value="EFECTIVO">EFECTIVO</option>
                            <option value="TARJETA">TARJETA</option>
                            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                        </select>
                    </div>

                    <!-- Por Pagar / Pagada -->
                    <div class="mb-3">
                        <label class="form-label small">Estado de Pago</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="payable" id="payable1" :value="1" v-model="order.payable">
                            <label class="btn btn-outline-warning" for="payable1">Por Pagar</label>

                            <input type="radio" class="btn-check" name="payable" id="payable0" :value="0" v-model="order.payable">
                            <label class="btn btn-outline-success" for="payable0">Pagada</label>
                        </div>
                    </div>

                    <!-- Fecha de Vencimiento -->
                    <div class="mb-3">
                        <label class="form-label small">
                            <i class="fa fa-calendar me-1"></i>Fecha de Vencimiento
                        </label>
                        <input type="date" class="form-control" v-model="order.expiration">
                    </div>

                    <hr>

                    <!-- Botón Guardar -->
                    <button
                        class="btn btn-success btn-lg w-100"
                        @click="guardarOrden"
                        :disabled="!puedeGuardar || isGuardando"
                    >
                        <span v-if="isGuardando">
                            <i class="fa fa-spinner fa-spin me-2"></i>Guardando...
                        </span>
                        <span v-else>
                            <i class="fa fa-save me-2"></i>{{ isEditMode ? 'Actualizar Orden' : 'Guardar Orden de Compra' }}
                        </span>
                    </button>

                    <!-- Validaciones -->
                    <div v-if="!puedeGuardar" class="alert alert-warning mt-3 mb-0 small">
                        <ul class="mb-0 ps-3">
                            <li v-if="!supplier">Seleccione un proveedor</li>
                            <li v-if="items.length === 0">Agregue al menos un producto</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales de Selección -->
    <modal-select-supplier
        :show="showModalProveedor"
        @close="showModalProveedor = false"
        @select="onProveedorSeleccionado"
    />

    <modal-select-product
        :show="showModalProducto"
        :show-out-of-stock="true"
        :all-products="true"
        @close="showModalProducto = false"
        @select="onProductoSeleccionado"
    />
</div>
</template>

<script>
export default {
    name: 'PurchaseOrderCreateComponent',
    props: {
        orderData: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            // UI
            isGuardando: false,
            orderId: null,

            // Modales
            showModalProveedor: false,
            showModalProducto: false,

            // Proveedor
            supplier: null,

            // Items
            items: [],

            // Total
            total: 0,

            // Order
            order: {
                supplier_id: 0,
                status: 'CREADA',
                payment: 'EFECTIVO',
                payable: 1,
                expiration: this.getDefaultExpirationDate(),
                observation: '',
                total: 0
            }
        }
    },
    computed: {
        isEditMode() {
            return this.orderId !== null;
        },
        puedeGuardar() {
            return this.supplier && this.items.length > 0;
        }
    },
    mounted() {
        if (this.orderData) {
            this.cargarOrdenExistente(this.orderData);
        }
    },
    methods: {
        // ==================== PROVEEDOR ====================
        onProveedorSeleccionado(proveedor) {
            this.supplier = proveedor;
            this.order.supplier_id = proveedor.id;
        },
        removeSupplier() {
            this.supplier = null;
            this.order.supplier_id = 0;
        },

        // ==================== PRODUCTOS ====================
        onProductoSeleccionado(producto) {
            // Verificar si ya existe
            const existe = this.items.find(i => i.product_id === producto.id);
            if (existe) {
                existe.qty++;
                this.calcularSubtotalItem(existe);
                this.calcularTotal();
                return;
            }

            const item = {
                product_id: producto.id,
                name: producto.name,
                description: producto.name,
                cost: producto.cost || 0,
                qty: 1,
                stock: producto.stock,
                subtotal: producto.cost || 0,
                image: producto.image
            };
            this.items.push(item);
            this.calcularTotal();
        },

        // ==================== ITEMS ====================
        removeItem(index) {
            this.items.splice(index, 1);
            this.calcularTotal();
        },
        incrementQty(item) {
            item.qty++;
            this.calcularSubtotalItem(item);
            this.calcularTotal();
        },
        decrementQty(item) {
            if (item.qty > 1) {
                item.qty--;
                this.calcularSubtotalItem(item);
                this.calcularTotal();
            }
        },
        onQtyChange(item) {
            if (item.qty < 1) item.qty = 1;
            this.calcularSubtotalItem(item);
            this.calcularTotal();
        },
        calcularSubtotalItem(item) {
            const cost = parseFloat(item.cost) || 0;
            const qty = parseInt(item.qty) || 1;
            item.subtotal = cost * qty;
        },

        // ==================== TOTALES ====================
        calcularTotal() {
            this.total = this.items.reduce((sum, item) => {
                return sum + (parseFloat(item.subtotal) || 0);
            }, 0);
            this.order.total = this.total;
        },

        // ==================== CARGAR ORDEN EXISTENTE ====================
        cargarOrdenExistente(data) {
            this.orderId = data.id;

            // Proveedor
            this.supplier = data.supplier;
            this.order.supplier_id = data.supplier_id;

            // Datos de la orden
            this.order.payment = data.payment || 'EFECTIVO';
            this.order.payable = data.payable ?? 1;
            this.order.observation = data.observation || '';
            this.order.expiration = data.expiration ? data.expiration.split(' ')[0] : this.getDefaultExpirationDate();

            // Items del detalle
            if (data.detail && data.detail.length > 0) {
                this.items = data.detail.map(d => ({
                    product_id: d.product_id,
                    name: d.product ? d.product.name : d.description,
                    description: d.description,
                    cost: parseFloat(d.price) || 0,
                    qty: parseInt(d.qty) || 1,
                    stock: d.product ? d.product.stock : 0,
                    subtotal: parseFloat(d.subtotal) || 0,
                    image: d.product ? d.product.image : null
                }));
            }

            this.calcularTotal();
        },

        // ==================== GUARDAR ====================
        async guardarOrden() {
            if (!this.puedeGuardar) return;

            // Preparar detalle
            const detail = this.items.map(item => ({
                product_id: item.product_id,
                description: item.description,
                price: item.cost,
                qty: item.qty,
                subtotal: item.subtotal
            }));

            const payload = {
                purchase_order: this.order,
                detail: detail
            };

            this.isGuardando = true;

            try {
                let url, titleOk;
                if (this.isEditMode) {
                    url = `/admin/purchase-orders/${this.orderId}/update`;
                    titleOk = 'Orden Actualizada';
                } else {
                    url = '/admin/purchase-orders/store';
                    titleOk = 'Orden de Compra Guardada';
                }

                const response = await axios.post(url, payload);

                if (response.data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: titleOk,
                        text: `Folio: ${response.data.order.folio}`,
                        confirmButtonText: 'Aceptar'
                    });

                    window.location.href = '/admin/purchase-orders';
                } else {
                    Swal.fire('Error', response.data.message || 'Error al guardar', 'error');
                }
            } catch (error) {
                console.error('Error al guardar:', error);
                const mensaje = error.response?.data?.message || 'Error al guardar la orden';
                Swal.fire('Error', mensaje, 'error');
            } finally {
                this.isGuardando = false;
            }
        },

        // ==================== UTILIDADES ====================
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
        verImagen(imagePath) {
            if (imagePath) {
                this.$viewImage(`/storage/${imagePath}`);
            }
        },
        getDefaultExpirationDate() {
            const date = new Date();
            date.setDate(date.getDate() + 30);
            return date.toISOString().split('T')[0];
        }
    }
}
</script>

<style scoped>
.purchase-order-create {
    max-width: 1400px;
    margin: 0 auto;
}

/* ============ PROVEEDOR COMPACTO ============ */
.supplier-bar {
    display: flex;
    align-items: center;
    border: 1px solid var(--j2b-gray-200);
    border-radius: var(--j2b-radius-md);
    padding: 0.5rem 0.75rem;
    background: #fff;
    min-height: 42px;
    transition: var(--j2b-transition-fast);
}
.supplier-bar-empty {
    cursor: pointer;
    border-style: dashed;
    border-color: var(--j2b-primary);
    background: rgba(0, 245, 160, 0.05);
}
.supplier-bar-empty:hover {
    background: rgba(0, 245, 160, 0.1);
}
.supplier-avatar-sm {
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

/* ============ NOTES SECTION ============ */
.notes-section {
    background: var(--j2b-gray-100);
    border: 1px solid var(--j2b-gray-200);
    border-radius: var(--j2b-radius-md);
    padding: 0.5rem;
}

/* ============ TABLA ============ */
.item-thumbnail {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: var(--j2b-radius-sm);
    background: var(--j2b-gray-100);
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
</style>
