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

        <!-- Botón Nueva Venta (solo admin full) -->
        <div class="mb-3" v-if="!userLimited">
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
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-outline-primary"
                                                @click="verEnPantalla(receipt)" title="Ver detalle">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button v-if="canEdit(receipt) && !userLimited" class="btn btn-outline-warning"
                                                @click="editarNota(receipt)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary"
                                                @click="descargarPDF(receipt)" title="Descargar PDF">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </button>
                                    </div>
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

    </div>
</template>

<script>
export default {
    name: 'ReceiptListComponent',
    props: ['userLimited'],
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
            }
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
        async descargarPDF(receipt) {
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
                let url = `/print-receipt-rent?id=${receipt.id}&name_file=nota_${receipt.folio}`;
                if (result.value.withImages) {
                    url += '&with_images=1';
                }
                window.open(url, '_blank');
            }
        },
        canEdit(receipt) {
            // No se puede editar si está facturado
            if (receipt.is_tax_invoiced) return false;
            // Se puede editar siempre (cotizaciones y notas)
            return true;
        },
        editarNota(receipt) {
            window.location.href = `/admin/receipts/${receipt.id}/edit`;
        },
        verEnPantalla(receipt) {
            window.location.href = `/admin/receipts/${receipt.id}/show`;
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
