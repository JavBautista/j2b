<template>
    <div class="receipt-list">
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" class="form-control" v-model="filtros.buscar"
                               placeholder="Folio, cliente..." @keyup.enter="buscar">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" v-model="filtros.tipo" @change="buscar">
                            <option value="">Todos</option>
                            <option value="venta">Notas de Venta</option>
                            <option value="cotizacion">Cotizaciones</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select class="form-select" v-model="filtros.status" @change="buscar">
                            <option value="">Todos</option>
                            <option value="POR COBRAR">Por Cobrar</option>
                            <option value="PAGADA">Pagada</option>
                            <option value="POR FACTURAR">Por Facturar</option>
                            <option value="CANCELADA">Cancelada</option>
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
                        <button class="btn btn-primary w-100" @click="buscar">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón Nueva Venta -->
        <div class="mb-3">
            <a href="/admin/receipts/create" class="btn btn-success">
                <i class="fa fa-plus me-1"></i> Nueva Venta
            </a>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div v-else class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Folio</th>
                                <th>Tipo</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Recibido</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="receipts.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No se encontraron registros
                                </td>
                            </tr>
                            <tr v-for="receipt in receipts" :key="receipt.id">
                                <td>
                                    <strong>#{{ receipt.folio }}</strong>
                                </td>
                                <td>
                                    <span v-if="receipt.quotation" class="badge bg-info">Cotización</span>
                                    <span v-else class="badge bg-primary">Nota</span>
                                </td>
                                <td>
                                    <div>{{ receipt.client?.name || 'N/A' }}</div>
                                    <small class="text-muted">{{ receipt.client?.movil }}</small>
                                </td>
                                <td>
                                    <strong>${{ formatNumber(receipt.total) }}</strong>
                                </td>
                                <td>
                                    <span :class="receipt.received >= receipt.total ? 'text-success' : 'text-warning'">
                                        ${{ formatNumber(receipt.received) }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="getStatusClass(receipt.status)">
                                        {{ receipt.status }}
                                    </span>
                                </td>
                                <td>
                                    {{ formatDate(receipt.created_at) }}
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                            @click="verDetalle(receipt)" title="Ver detalle">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary"
                                            @click="descargarPDF(receipt)" title="Descargar PDF">
                                        <i class="fa fa-file-pdf-o"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div class="card-footer" v-if="pagination.last_page > 1">
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

        <!-- Modal Detalle -->
        <div class="modal fade show" v-if="modalDetalle" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-xl">
                <div class="modal-content" v-if="receiptDetalle">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa fa-file-text me-2"></i>
                            {{ receiptDetalle.quotation ? 'Cotización' : 'Nota de Venta' }} #{{ receiptDetalle.folio }}
                        </h5>
                        <button type="button" class="btn-close" @click="cerrarModal()"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Info General -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <strong><i class="fa fa-user me-1"></i> Cliente</strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>{{ receiptDetalle.client?.name }}</strong></p>
                                        <p class="mb-1 text-muted">{{ receiptDetalle.client?.company }}</p>
                                        <p class="mb-1"><i class="fa fa-phone me-1"></i> {{ receiptDetalle.client?.movil }}</p>
                                        <p class="mb-0"><i class="fa fa-envelope me-1"></i> {{ receiptDetalle.client?.mail }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <strong><i class="fa fa-info-circle me-1"></i> Información</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <p class="mb-1"><strong>Folio:</strong> #{{ receiptDetalle.folio }}</p>
                                                <p class="mb-1"><strong>Fecha:</strong> {{ formatDate(receiptDetalle.created_at) }}</p>
                                                <p class="mb-1"><strong>Creado por:</strong> {{ receiptDetalle.created_by }}</p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1">
                                                    <strong>Estado:</strong>
                                                    <span :class="getStatusClass(receiptDetalle.status)">{{ receiptDetalle.status }}</span>
                                                </p>
                                                <p class="mb-1"><strong>Forma de pago:</strong> {{ receiptDetalle.payment }}</p>
                                                <p class="mb-0" v-if="receiptDetalle.quotation">
                                                    <strong>Vence:</strong> {{ receiptDetalle.quotation_expiration }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos Extra -->
                        <div class="card mb-4" v-if="receiptDetalle.info_extra && receiptDetalle.info_extra.length > 0">
                            <div class="card-header bg-light">
                                <strong><i class="fa fa-list-alt me-1"></i> Campos Adicionales</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4" v-for="extra in receiptDetalle.info_extra" :key="extra.id">
                                        <p class="mb-1"><strong>{{ extra.field_name }}:</strong> {{ extra.value }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Descripción y Observaciones -->
                        <div class="row mb-4" v-if="receiptDetalle.description || receiptDetalle.observation">
                            <div class="col-md-6" v-if="receiptDetalle.description">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <strong>Descripción</strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0">{{ receiptDetalle.description }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6" v-if="receiptDetalle.observation">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <strong>Observaciones</strong>
                                    </div>
                                    <div class="card-body">
                                        <pre class="mb-0" style="white-space: pre-wrap;">{{ receiptDetalle.observation }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalle de Items -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <strong><i class="fa fa-shopping-cart me-1"></i> Detalle de Artículos</strong>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Descripción</th>
                                                <th class="text-end">Precio</th>
                                                <th class="text-center">Descuento</th>
                                                <th class="text-center">Cant.</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in receiptDetalle.detail" :key="item.id">
                                                <td>
                                                    <span class="badge bg-secondary me-1">{{ item.type }}</span>
                                                    {{ item.descripcion }}
                                                </td>
                                                <td class="text-end">${{ formatNumber(item.price) }}</td>
                                                <td class="text-center">
                                                    <span v-if="item.discount > 0">
                                                        {{ item.discount_concept === '%' ? item.discount + '%' : '$' + formatNumber(item.discount) }}
                                                    </span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td class="text-center">{{ item.qty }}</td>
                                                <td class="text-end">${{ formatNumber(item.subtotal) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Totales -->
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Pagos Parciales (solo si no es cotización) -->
                                <div class="card" v-if="!receiptDetalle.quotation && receiptDetalle.partial_payments && receiptDetalle.partial_payments.length > 0">
                                    <div class="card-header bg-light">
                                        <strong><i class="fa fa-money me-1"></i> Pagos Parciales</strong>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th class="text-end">Monto</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="pago in receiptDetalle.partial_payments" :key="pago.id">
                                                    <td>{{ formatDate(pago.payment_date) }}</td>
                                                    <td class="text-end">${{ formatNumber(pago.amount) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <strong><i class="fa fa-calculator me-1"></i> Resumen</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span>${{ formatNumber(receiptDetalle.subtotal) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2" v-if="receiptDetalle.discount > 0">
                                            <span>Descuento ({{ receiptDetalle.discount_concept }}):</span>
                                            <span class="text-danger">
                                                -{{ receiptDetalle.discount_concept === '%' ? receiptDetalle.discount + '%' : '$' + formatNumber(receiptDetalle.discount) }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2" v-if="receiptDetalle.iva > 0">
                                            <span>IVA 16%:</span>
                                            <span>${{ formatNumber(receiptDetalle.iva) }}</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Total:</strong>
                                            <strong class="text-primary fs-5">${{ formatNumber(receiptDetalle.total) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2" v-if="!receiptDetalle.quotation">
                                            <span>Recibido:</span>
                                            <span class="text-success">${{ formatNumber(receiptDetalle.received) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between" v-if="!receiptDetalle.quotation && receiptDetalle.received < receiptDetalle.total">
                                            <span>Adeudo:</span>
                                            <span class="text-danger fw-bold">${{ formatNumber(receiptDetalle.total - receiptDetalle.received) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cerrar</button>
                        <button type="button" class="btn btn-primary" @click="descargarPDF(receiptDetalle)">
                            <i class="fa fa-file-pdf-o me-1"></i> Descargar PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: 'ReceiptListComponent',
    data() {
        return {
            loading: false,
            receipts: [],
            pagination: {
                total: 0,
                current_page: 1,
                per_page: 15,
                last_page: 1
            },
            filtros: {
                buscar: '',
                tipo: '',
                status: '',
                fecha_desde: '',
                fecha_hasta: ''
            },
            receiptDetalle: null,
            modalDetalle: false
        };
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
        this.cargarReceipts();
    },
    methods: {
        async cargarReceipts() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    buscar: this.filtros.buscar,
                    tipo: this.filtros.tipo,
                    status: this.filtros.status,
                    fecha_desde: this.filtros.fecha_desde,
                    fecha_hasta: this.filtros.fecha_hasta
                });

                const response = await axios.get(`/admin/receipts/list/get?${params}`);

                if (response.data.ok) {
                    this.receipts = response.data.receipts;
                    this.pagination = response.data.pagination;
                }
            } catch (error) {
                console.error('Error cargando receipts:', error);
                Swal.fire('Error', 'No se pudieron cargar las notas de venta', 'error');
            } finally {
                this.loading = false;
            }
        },
        buscar() {
            this.pagination.current_page = 1;
            this.cargarReceipts();
        },
        cambiarPagina(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.cargarReceipts();
            }
        },
        async verDetalle(receipt) {
            try {
                const response = await axios.get(`/admin/receipts/${receipt.id}/detail`);
                if (response.data.ok) {
                    this.receiptDetalle = response.data.receipt;
                    this.modalDetalle = true;
                }
            } catch (error) {
                console.error('Error cargando detalle:', error);
                Swal.fire('Error', 'No se pudo cargar el detalle', 'error');
            }
        },
        cerrarModal() {
            this.modalDetalle = false;
            this.receiptDetalle = null;
        },
        descargarPDF(receipt) {
            const url = `/print-receipt-rent?id=${receipt.id}&name_file=nota_${receipt.folio}`;
            window.open(url, '_blank');
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
                'POR COBRAR': 'badge bg-warning text-dark',
                'PAGADA': 'badge bg-success',
                'POR FACTURAR': 'badge bg-info',
                'CANCELADA': 'badge bg-danger'
            };
            return classes[status] || 'badge bg-secondary';
        }
    }
};
</script>

<style scoped>
.receipt-list .table th {
    font-weight: 600;
    font-size: 0.85rem;
}
.receipt-list .badge {
    font-weight: 500;
}
pre {
    font-family: inherit;
    font-size: inherit;
}
</style>
