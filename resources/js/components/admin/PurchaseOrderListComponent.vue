<template>
    <div class="purchase-order-list">
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" class="form-control" v-model="filtros.buscar"
                               placeholder="Folio, proveedor..." @keyup.enter="buscar">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select class="form-select" v-model="filtros.status" @change="buscar">
                            <option value="">Todos</option>
                            <option value="CREADA">Creada</option>
                            <option value="COMPLETA">Completa</option>
                            <option value="CANCELADA">Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Pago</label>
                        <select class="form-select" v-model="filtros.payable" @change="buscar">
                            <option value="">Todos</option>
                            <option value="1">Por Pagar</option>
                            <option value="0">Pagada</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Desde</label>
                        <input type="date" class="form-control" v-model="filtros.fecha_desde" @change="buscar">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Hasta</label>
                        <input type="date" class="form-control" v-model="filtros.fecha_hasta" @change="buscar">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-success w-100" @click="buscar">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón Nueva Compra y Toggle Vista -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="/admin/purchase-orders/create" class="btn btn-success">
                    <i class="fa fa-plus me-1"></i> Nueva Compra
                </a>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-sm" :class="vistaActual === 'cards' ? 'btn-secondary' : 'btn-outline-secondary'" @click="vistaActual = 'cards'" title="Vista Cards">
                    <i class="fa fa-th-large"></i>
                </button>
                <button type="button" class="btn btn-sm" :class="vistaActual === 'tabla' ? 'btn-secondary' : 'btn-outline-secondary'" @click="vistaActual = 'tabla'" title="Vista Tabla">
                    <i class="fa fa-list"></i>
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <!-- VISTA CARDS -->
        <div v-if="!loading && vistaActual === 'cards'" class="row">
            <div v-if="orders.length === 0" class="col-12 text-center py-5">
                <i class="fa fa-truck fa-3x text-muted mb-3"></i>
                <p class="text-muted">No se encontraron órdenes de compra</p>
            </div>
            <div class="col-md-4 col-lg-3 mb-4" v-for="order in orders" :key="order.id">
                <div class="card order-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>#{{ order.folio }}</strong>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" @click.prevent="verDetalle(order)">
                                    <i class="fa fa-eye text-primary"></i> Ver Detalle
                                </a></li>
                                <li v-if="order.status === 'CREADA'"><a class="dropdown-item" href="#" @click.prevent="editarOrden(order)">
                                    <i class="fa fa-edit text-warning"></i> Editar
                                </a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="descargarPDF(order)">
                                    <i class="fa fa-file-pdf-o text-danger"></i> Descargar PDF
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body" @click="verDetalle(order)" style="cursor: pointer;">
                        <div class="order-info">
                            <div class="info-item">
                                <i class="fa fa-truck text-muted"></i>
                                <span>{{ order.supplier?.name || order.supplier?.company || 'N/A' }}</span>
                            </div>
                            <div class="info-item" v-if="order.supplier?.phone || order.supplier?.movil">
                                <i class="fa fa-phone text-muted"></i>
                                <span>{{ order.supplier?.phone || order.supplier?.movil }}</span>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-money text-muted"></i>
                                <span><strong>${{ formatNumber(order.total) }}</strong></span>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-credit-card text-muted"></i>
                                <span :class="getAdeudoClass(order)">
                                    Adeudo: ${{ formatNumber(calcularAdeudo(order)) }}
                                </span>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-calendar text-muted"></i>
                                <span>{{ formatDate(order.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                        <span :class="getStatusClass(order.status)">{{ order.status }}</span>
                        <span v-if="order.payable" class="badge bg-warning text-dark">Por Pagar</span>
                        <span v-else class="badge bg-success">Pagada</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- VISTA TABLA -->
        <div v-if="!loading && vistaActual === 'tabla'" class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Folio</th>
                                <th>Proveedor</th>
                                <th>Total</th>
                                <th>Adeudo</th>
                                <th>Estado</th>
                                <th>Pago</th>
                                <th>Fecha</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="orders.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No se encontraron órdenes de compra
                                </td>
                            </tr>
                            <tr v-for="order in orders" :key="order.id">
                                <td>
                                    <strong>#{{ order.folio }}</strong>
                                </td>
                                <td>
                                    <div>{{ order.supplier?.name || order.supplier?.company || 'N/A' }}</div>
                                    <small class="text-muted">{{ order.supplier?.phone || order.supplier?.movil }}</small>
                                </td>
                                <td>
                                    <strong>${{ formatNumber(order.total) }}</strong>
                                </td>
                                <td>
                                    <span :class="getAdeudoClass(order)">
                                        ${{ formatNumber(calcularAdeudo(order)) }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="getStatusClass(order.status)">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="order.payable" class="badge bg-warning text-dark">Por Pagar</span>
                                    <span v-else class="badge bg-success">Pagada</span>
                                </td>
                                <td>
                                    {{ formatDate(order.created_at) }}
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-outline-primary"
                                                @click="verDetalle(order)" title="Ver detalle">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button v-if="order.status === 'CREADA'" class="btn btn-outline-warning"
                                                @click="editarOrden(order)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary"
                                                @click="descargarPDF(order)" title="Descargar PDF">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paginación -->
        <div class="card mt-3" v-if="!loading && pagination.last_page > 1">
            <div class="card-footer">
                <nav>
                    <ul class="pagination mb-0 justify-content-center">
                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page - 1)">
                                Anterior
                            </a>
                        </li>
                        <li class="page-item" v-for="page in paginationPages" :key="page"
                            :class="{ active: page === pagination.current_page }">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page + 1)">
                                Siguiente
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: 'PurchaseOrderListComponent',
    data() {
        return {
            loading: false,
            orders: [],
            pagination: {
                total: 0,
                current_page: 1,
                per_page: 15,
                last_page: 1
            },
            filtros: {
                buscar: '',
                status: '',
                payable: '',
                fecha_desde: '',
                fecha_hasta: ''
            },
            vistaActual: localStorage.getItem('admin_compras_vista') || 'cards'
        };
    },
    watch: {
        vistaActual(newVal) {
            localStorage.setItem('admin_compras_vista', newVal);
        }
    },
    computed: {
        paginationPages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            let start = Math.max(1, current - 2);
            let end = Math.min(last, current + 2);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        }
    },
    mounted() {
        this.cargarOrdenes();
    },
    methods: {
        async cargarOrdenes() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    buscar: this.filtros.buscar,
                    status: this.filtros.status,
                    payable: this.filtros.payable,
                    fecha_desde: this.filtros.fecha_desde,
                    fecha_hasta: this.filtros.fecha_hasta
                });

                const response = await axios.get(`/admin/purchase-orders/list/get?${params}`);

                if (response.data.ok) {
                    this.orders = response.data.orders;
                    this.pagination = response.data.pagination;
                }
            } catch (error) {
                console.error('Error cargando órdenes:', error);
                Swal.fire('Error', 'No se pudieron cargar las órdenes de compra', 'error');
            } finally {
                this.loading = false;
            }
        },
        buscar() {
            this.pagination.current_page = 1;
            this.cargarOrdenes();
        },
        cambiarPagina(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.cargarOrdenes();
            }
        },
        descargarPDF(order) {
            window.open(`/print-purchase-order?id=${order.id}&name_file=compra_${order.folio}`, '_blank');
        },
        editarOrden(order) {
            window.location.href = `/admin/purchase-orders/${order.id}/edit`;
        },
        verDetalle(order) {
            window.location.href = `/admin/purchase-orders/${order.id}/show`;
        },
        calcularAdeudo(order) {
            const pagado = order.partial_payments?.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0) || 0;
            return Math.max(0, parseFloat(order.total || 0) - pagado);
        },
        formatNumber(num) {
            if (!num) return '0.00';
            return parseFloat(num).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
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
        getStatusClass(status) {
            const classes = {
                'CREADA': 'badge bg-info',
                'COMPLETA': 'badge bg-success',
                'CANCELADA': 'badge bg-danger'
            };
            return classes[status] || 'badge bg-secondary';
        },
        getAdeudoClass(order) {
            const adeudo = this.calcularAdeudo(order);
            return adeudo > 0 ? 'text-danger' : 'text-success';
        }
    }
};
</script>

<style scoped>
.purchase-order-list .table th {
    font-weight: 600;
    font-size: 0.85rem;
}
.purchase-order-list .badge {
    font-weight: 500;
}

/* Estilos para Cards de Órdenes */
.order-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.order-card .card-header {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 0.75rem 1rem;
}

.order-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}

.info-item i {
    width: 20px;
    text-align: center;
}

.order-card .card-footer {
    background: transparent;
    border-top: 1px solid #eee;
    padding: 0.75rem 1rem;
}
</style>
