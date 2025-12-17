<template>
<div class="purchase-order-create">
    <div class="row">
        <!-- Columna Izquierda: Proveedor + Items -->
        <div class="col-lg-8">
            <!-- Sección Proveedor -->
            <div class="card mb-3">
                <div class="card-header bg-success text-white py-2">
                    <i class="fa fa-truck me-2"></i>Proveedor
                </div>
                <div class="card-body">
                    <div v-if="!supplier" class="text-center py-3">
                        <button class="btn btn-outline-success btn-lg" @click="showModalProveedor = true">
                            <i class="fa fa-truck me-2"></i>Seleccionar Proveedor
                        </button>
                    </div>
                    <div v-else class="supplier-selected">
                        <div class="d-flex align-items-center">
                            <div class="supplier-avatar me-3">
                                <i class="fa fa-truck"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ supplier.name || supplier.company }}</h5>
                                <small class="text-muted">
                                    <span v-if="supplier.company && supplier.name" class="me-3">
                                        <i class="fa fa-building me-1"></i>{{ supplier.company }}
                                    </span>
                                    <span v-if="supplier.phone || supplier.movil" class="me-3">
                                        <i class="fa fa-phone me-1"></i>{{ supplier.phone || supplier.movil }}
                                    </span>
                                </small>
                            </div>
                            <button class="btn btn-outline-danger btn-sm" @click="removeSupplier">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón Agregar Productos -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="fa fa-plus-circle me-2"></i>Agregar Productos
                </div>
                <div class="card-body py-2">
                    <button class="btn btn-success w-100" @click="showModalProducto = true" :disabled="!supplier">
                        <i class="fa fa-box me-1"></i>Agregar Producto
                    </button>
                </div>
            </div>

            <!-- Lista de Items -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-list me-2"></i>Detalle de la Compra</span>
                    <span class="badge bg-secondary">{{ items.length }} item(s)</span>
                </div>
                <div class="card-body p-0">
                    <div v-if="items.length === 0" class="text-center py-5 text-muted">
                        <i class="fa fa-shopping-cart fa-3x mb-3"></i>
                        <p>No hay productos agregados</p>
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
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="fa fa-comment me-2"></i>Observaciones
                </div>
                <div class="card-body">
                    <textarea
                        class="form-control"
                        v-model="order.observation"
                        rows="2"
                        placeholder="Observaciones adicionales..."
                    ></textarea>
                </div>
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
                            <i class="fa fa-save me-2"></i>Guardar Orden de Compra
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
    data() {
        return {
            // UI
            isGuardando: false,

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
        puedeGuardar() {
            return this.supplier && this.items.length > 0;
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
                const response = await axios.post('/admin/purchase-orders/store', payload);

                if (response.data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Orden de Compra Guardada',
                        text: `Folio: ${response.data.order.folio}`,
                        confirmButtonText: 'Aceptar'
                    });

                    // Redireccionar a la lista o al detalle
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

.supplier-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.supplier-selected {
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
