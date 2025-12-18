<template>
<div class="purchase-order-show">
    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div v-else-if="order" class="row">
        <!-- Columna Izquierda: Info y Detalle -->
        <div class="col-lg-8">
            <!-- Encabezado con Estado -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center" :class="getHeaderClass()">
                    <div>
                        <h4 class="mb-0 text-white">
                            <i class="fa fa-truck me-2"></i>Orden #{{ order.folio }}
                        </h4>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark fs-6">{{ order.status }}</span>
                    </div>
                </div>
            </div>

            <!-- Info Proveedor -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="fa fa-truck me-2"></i>Proveedor
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="supplier-avatar me-3">
                            <i class="fa fa-truck"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">{{ order.supplier?.name || order.supplier?.company || 'N/A' }}</h5>
                            <div class="text-muted">
                                <span v-if="order.supplier?.company && order.supplier?.name" class="me-3">
                                    <i class="fa fa-building me-1"></i>{{ order.supplier.company }}
                                </span>
                                <span v-if="order.supplier?.phone || order.supplier?.movil" class="me-3">
                                    <i class="fa fa-phone me-1"></i>{{ order.supplier?.phone || order.supplier?.movil }}
                                </span>
                                <span v-if="order.supplier?.email">
                                    <i class="fa fa-envelope me-1"></i>{{ order.supplier?.email }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalle de Productos -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-list me-2"></i>Detalle de Productos</span>
                    <span class="badge bg-secondary">{{ order.detail?.length || 0 }} item(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Costo</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in order.detail" :key="item.id">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img
                                                v-if="item.product?.image"
                                                :src="getItemImage(item.product.image)"
                                                class="item-thumbnail me-2"
                                                @click="verImagen(item.product.image)"
                                                style="cursor: pointer;"
                                                @error="handleImageError"
                                            >
                                            <div>
                                                <div class="fw-bold">{{ item.description }}</div>
                                                <small class="text-muted">
                                                    Stock actual: {{ item.product?.stock || 0 }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ formatCurrency(item.price) }}</td>
                                    <td class="text-center">{{ item.qty }}</td>
                                    <td class="text-end fw-bold">{{ formatCurrency(item.subtotal) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                    <td class="text-end fw-bold text-success fs-5">{{ formatCurrency(order.total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Observaciones -->
            <div v-if="order.observation" class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="fa fa-comment me-2"></i>Observaciones
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ order.observation }}</p>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Resumen y Acciones -->
        <div class="col-lg-4">
            <!-- Panel de Resumen -->
            <div class="card mb-3">
                <div class="card-header bg-dark text-white py-2">
                    <i class="fa fa-info-circle me-2"></i>Información
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">Fecha:</td>
                                <td class="text-end">{{ formatDate(order.created_at) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Vencimiento:</td>
                                <td class="text-end">{{ formatDate(order.expiration) || 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Forma de Pago:</td>
                                <td class="text-end">{{ order.payment }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Estado Pago:</td>
                                <td class="text-end">
                                    <span v-if="order.payable" class="badge bg-warning text-dark">Por Pagar</span>
                                    <span v-else class="badge bg-success">Pagada</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Facturada:</td>
                                <td class="text-end">
                                    <span v-if="order.is_invoiced" class="badge bg-info">Sí</span>
                                    <span v-else class="badge bg-secondary">No</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagos Parciales -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-money me-2"></i>Pagos</span>
                    <button
                        v-if="order.status !== 'CANCELADA'"
                        class="btn btn-success btn-sm"
                        @click="showModalPago = true"
                    >
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
                <div class="card-body">
                    <!-- Lista de pagos -->
                    <div v-if="order.partial_payments?.length > 0" class="mb-3">
                        <div
                            v-for="pago in order.partial_payments"
                            :key="pago.id"
                            class="d-flex justify-content-between align-items-center border-bottom py-2"
                        >
                            <div>
                                <small class="text-muted">{{ formatDate(pago.created_at) }}</small>
                                <div>{{ formatCurrency(pago.amount) }}</div>
                            </div>
                            <button
                                class="btn btn-outline-danger btn-sm"
                                @click="eliminarPago(pago)"
                                :disabled="eliminandoPago"
                            >
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-center text-muted py-2">
                        <small>Sin pagos registrados</small>
                    </div>

                    <!-- Resumen de pagos -->
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Total Pagado:</span>
                        <span class="fw-bold text-success">{{ formatCurrency(totalPagado) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Adeudo:</span>
                        <span class="fw-bold" :class="adeudo > 0 ? 'text-danger' : 'text-success'">
                            {{ formatCurrency(adeudo) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="fa fa-cogs me-2"></i>Acciones
                </div>
                <div class="card-body d-grid gap-2">
                    <!-- PDF -->
                    <button class="btn btn-outline-secondary" @click="descargarPDF">
                        <i class="fa fa-file-pdf-o me-2"></i>Descargar PDF
                    </button>

                    <!-- Cambiar Payable -->
                    <button
                        v-if="order.status !== 'CANCELADA'"
                        class="btn"
                        :class="order.payable ? 'btn-outline-success' : 'btn-outline-warning'"
                        @click="cambiarPayable"
                        :disabled="actualizando"
                    >
                        <i class="fa fa-exchange me-2"></i>
                        {{ order.payable ? 'Marcar como Pagada' : 'Marcar Por Pagar' }}
                    </button>

                    <!-- Cambiar Facturado -->
                    <button
                        v-if="order.status !== 'CANCELADA'"
                        class="btn btn-outline-info"
                        @click="cambiarFacturado"
                        :disabled="actualizando"
                    >
                        <i class="fa fa-file-text-o me-2"></i>
                        {{ order.is_invoiced ? 'Quitar Facturado' : 'Marcar Facturado' }}
                    </button>

                    <!-- Acciones solo si está CREADA -->
                    <template v-if="order.status === 'CREADA'">
                        <hr>
                        <button class="btn btn-success" @click="completarOrden" :disabled="actualizando">
                            <i class="fa fa-check-circle me-2"></i>Completar Compra
                        </button>
                        <a :href="`/admin/purchase-orders/${order.id}/edit`" class="btn btn-warning">
                            <i class="fa fa-edit me-2"></i>Editar
                        </a>
                        <button class="btn btn-danger" @click="cancelarOrden" :disabled="actualizando">
                            <i class="fa fa-times-circle me-2"></i>Cancelar Orden
                        </button>
                    </template>
                </div>
            </div>

            <!-- Volver -->
            <a href="/admin/purchase-orders" class="btn btn-secondary w-100">
                <i class="fa fa-arrow-left me-2"></i>Volver a Lista
            </a>
        </div>
    </div>

    <!-- Modal Agregar Pago -->
    <div v-if="showModalPago" class="modal-overlay" @click.self="showModalPago = false">
        <div class="modal-container" style="max-width: 400px;">
            <div class="modal-content-custom">
                <div class="modal-header-custom bg-success">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-money me-2"></i>Agregar Pago
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="showModalPago = false"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input
                                type="number"
                                class="form-control"
                                v-model.number="nuevoPago.amount"
                                min="0"
                                step="0.01"
                                ref="inputPago"
                            >
                        </div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Adeudo actual:</span>
                        <span>{{ formatCurrency(adeudo) }}</span>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn btn-secondary" @click="showModalPago = false">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn-success"
                        @click="agregarPago"
                        :disabled="!nuevoPago.amount || nuevoPago.amount <= 0 || agregandoPago"
                    >
                        <span v-if="agregandoPago">
                            <i class="fa fa-spinner fa-spin me-1"></i>Guardando...
                        </span>
                        <span v-else>
                            <i class="fa fa-check me-1"></i>Agregar
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
export default {
    name: 'PurchaseOrderShowComponent',
    props: {
        orderId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            loading: true,
            order: null,
            actualizando: false,
            showModalPago: false,
            nuevoPago: { amount: 0 },
            agregandoPago: false,
            eliminandoPago: false
        }
    },
    computed: {
        totalPagado() {
            if (!this.order?.partial_payments) return 0;
            return this.order.partial_payments.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0);
        },
        adeudo() {
            return Math.max(0, parseFloat(this.order?.total || 0) - this.totalPagado);
        }
    },
    watch: {
        showModalPago(val) {
            if (val) {
                this.nuevoPago.amount = this.adeudo;
                this.$nextTick(() => {
                    if (this.$refs.inputPago) {
                        this.$refs.inputPago.focus();
                        this.$refs.inputPago.select();
                    }
                });
            }
        }
    },
    mounted() {
        this.cargarOrden();
    },
    methods: {
        async cargarOrden() {
            this.loading = true;
            try {
                const response = await axios.get(`/admin/purchase-orders/${this.orderId}/detail`);
                if (response.data.ok) {
                    this.order = response.data.order;
                }
            } catch (error) {
                console.error('Error cargando orden:', error);
                Swal.fire('Error', 'No se pudo cargar la orden', 'error');
            } finally {
                this.loading = false;
            }
        },

        // ==================== ACCIONES ====================
        async completarOrden() {
            const result = await Swal.fire({
                title: 'Completar Orden',
                html: `
                    <p>Al completar la orden:</p>
                    <ul class="text-start">
                        <li>Se sumará el stock de cada producto</li>
                        <li>Se actualizará el costo de los productos</li>
                        <li>No se podrá revertir</li>
                    </ul>
                    <p><strong>¿Desea continuar?</strong></p>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, Completar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#198754'
            });

            if (!result.isConfirmed) return;

            this.actualizando = true;
            try {
                const response = await axios.post(`/admin/purchase-orders/${this.order.id}/complete`);
                if (response.data.ok) {
                    Swal.fire('Completada', 'La orden ha sido completada y el stock actualizado', 'success');
                    this.cargarOrden();
                } else {
                    Swal.fire('Error', response.data.message || 'Error al completar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo completar la orden', 'error');
            } finally {
                this.actualizando = false;
            }
        },

        async cancelarOrden() {
            const result = await Swal.fire({
                title: 'Cancelar Orden',
                text: '¿Está seguro de cancelar esta orden de compra?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, Cancelar',
                cancelButtonText: 'No',
                confirmButtonColor: '#dc3545'
            });

            if (!result.isConfirmed) return;

            this.actualizando = true;
            try {
                const response = await axios.post(`/admin/purchase-orders/${this.order.id}/cancel`);
                if (response.data.ok) {
                    Swal.fire('Cancelada', 'La orden ha sido cancelada', 'success');
                    this.cargarOrden();
                } else {
                    Swal.fire('Error', response.data.message || 'Error al cancelar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo cancelar la orden', 'error');
            } finally {
                this.actualizando = false;
            }
        },

        async cambiarPayable() {
            this.actualizando = true;
            try {
                const response = await axios.post(`/admin/purchase-orders/${this.order.id}/toggle-payable`);
                if (response.data.ok) {
                    this.order.payable = response.data.payable;
                } else {
                    Swal.fire('Error', response.data.message || 'Error al actualizar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo actualizar', 'error');
            } finally {
                this.actualizando = false;
            }
        },

        async cambiarFacturado() {
            this.actualizando = true;
            try {
                const response = await axios.post(`/admin/purchase-orders/${this.order.id}/toggle-invoiced`);
                if (response.data.ok) {
                    this.order.is_invoiced = response.data.is_invoiced;
                } else {
                    Swal.fire('Error', response.data.message || 'Error al actualizar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo actualizar', 'error');
            } finally {
                this.actualizando = false;
            }
        },

        // ==================== PAGOS ====================
        async agregarPago() {
            if (!this.nuevoPago.amount || this.nuevoPago.amount <= 0) return;

            this.agregandoPago = true;
            try {
                const response = await axios.post(`/admin/purchase-orders/${this.order.id}/partial-payment`, {
                    amount: this.nuevoPago.amount
                });
                if (response.data.ok) {
                    this.showModalPago = false;
                    this.nuevoPago.amount = 0;
                    this.cargarOrden();
                } else {
                    Swal.fire('Error', response.data.message || 'Error al agregar pago', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo agregar el pago', 'error');
            } finally {
                this.agregandoPago = false;
            }
        },

        async eliminarPago(pago) {
            const result = await Swal.fire({
                title: 'Eliminar Pago',
                text: `¿Eliminar pago de ${this.formatCurrency(pago.amount)}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            });

            if (!result.isConfirmed) return;

            this.eliminandoPago = true;
            try {
                const response = await axios.delete(`/admin/purchase-orders/partial-payment/${pago.id}`);
                if (response.data.ok) {
                    this.cargarOrden();
                } else {
                    Swal.fire('Error', response.data.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo eliminar el pago', 'error');
            } finally {
                this.eliminandoPago = false;
            }
        },

        descargarPDF() {
            window.open(`/print-purchase-order?id=${this.order.id}&name_file=compra_${this.order.folio}`, '_blank');
        },

        // ==================== UTILIDADES ====================
        formatCurrency(amount) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(amount || 0);
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-MX', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        getHeaderClass() {
            switch (this.order?.status) {
                case 'CREADA': return 'bg-info';
                case 'COMPLETA': return 'bg-success';
                case 'CANCELADA': return 'bg-danger';
                default: return 'bg-secondary';
            }
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
        }
    }
}
</script>

<style scoped>
.purchase-order-show {
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

.item-thumbnail {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: 4px;
    background: #f8f9fa;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1055;
    padding: 1rem;
}

.modal-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    width: 100%;
}

.modal-content-custom {
    display: flex;
    flex-direction: column;
}

.modal-header-custom {
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header-custom h5 {
    margin: 0;
}

.modal-footer-custom {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}
</style>
