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

        <!-- Botón Nueva Venta y Toggle Vista -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div v-if="!userLimited">
                <a href="/admin/receipts/create" class="btn btn-success">
                    <i class="fa fa-plus me-1"></i> Nueva Venta
                </a>
            </div>
            <div v-else></div>
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
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <!-- VISTA CARDS -->
        <div v-if="!loading && vistaActual === 'cards'" class="row">
            <div v-if="receipts.length === 0" class="col-12 text-center py-5">
                <i class="fa fa-receipt fa-3x text-muted mb-3"></i>
                <p class="text-muted">No se encontraron registros</p>
            </div>
            <div class="col-md-4 col-lg-3 mb-4" v-for="receipt in receipts" :key="receipt.id">
                <div class="card receipt-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>#{{ receipt.folio }}</strong>
                            <span v-if="receipt.quotation" class="badge bg-info ms-1">Cotización</span>
                            <span v-else class="badge bg-primary ms-1">Nota</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" @click.prevent="verEnPantalla(receipt)">
                                    <i class="fa fa-eye text-primary"></i> Ver Detalle
                                </a></li>
                                <li v-if="canEdit(receipt) && !userLimited"><a class="dropdown-item" href="#" @click.prevent="editarNota(receipt)">
                                    <i class="fa fa-edit text-warning"></i> Editar
                                </a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="descargarPDF(receipt)">
                                    <i class="fa fa-file-pdf-o text-danger"></i> Descargar PDF
                                </a></li>
                                <li v-if="canInvoice(receipt)"><hr class="dropdown-divider"></li>
                                <li v-if="canInvoice(receipt)"><a class="dropdown-item" href="#" @click.prevent="facturar(receipt)">
                                    <i class="fa fa-file-text-o text-success"></i> Facturar
                                </a></li>
                                <li v-if="receipt.is_tax_invoiced && receipt.cfdi_invoice"><hr class="dropdown-divider"></li>
                                <li v-if="receipt.is_tax_invoiced && receipt.cfdi_invoice"><a class="dropdown-item" href="#" @click.prevent="descargarCfdi(receipt, 'xml')">
                                    <i class="fa fa-code text-primary"></i> Descargar XML
                                </a></li>
                                <li v-if="receipt.is_tax_invoiced && receipt.cfdi_invoice"><a class="dropdown-item" href="#" @click.prevent="descargarCfdi(receipt, 'pdf')">
                                    <i class="fa fa-file-pdf-o text-danger"></i> Descargar Factura PDF
                                </a></li>
                                <li v-if="canCancelInvoice(receipt)"><a class="dropdown-item" href="#" @click.prevent="cancelarFactura(receipt)">
                                    <i class="fa fa-ban text-danger"></i> Cancelar Factura
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body" @click="verEnPantalla(receipt)" style="cursor: pointer;">
                        <div class="receipt-info">
                            <div class="info-item">
                                <i class="fa fa-user text-muted"></i>
                                <span>{{ receipt.client?.name || 'N/A' }}</span>
                            </div>
                            <div class="info-item" v-if="receipt.client?.movil">
                                <i class="fa fa-phone text-muted"></i>
                                <span>{{ receipt.client.movil }}</span>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-money text-muted"></i>
                                <span><strong>${{ formatNumber(receipt.total) }}</strong></span>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-check-circle text-muted"></i>
                                <span :class="receipt.received >= receipt.total ? 'text-success' : 'text-warning'">
                                    Recibido: ${{ formatNumber(receipt.received) }}
                                </span>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-calendar text-muted"></i>
                                <span>{{ formatDate(receipt.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                        <span :class="getStatusClass(receipt.status)">{{ receipt.status }}</span>
                        <span v-if="receipt.is_tax_invoiced" class="badge bg-success"><i class="fa fa-check me-1"></i>Facturada</span>
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
                                    <span v-if="receipt.is_tax_invoiced" class="badge bg-success ms-1">Facturada</span>
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
                                        <button v-if="canInvoice(receipt)" class="btn btn-outline-success"
                                                @click="facturar(receipt)" title="Facturar">
                                            <i class="fa fa-file-text-o"></i>
                                        </button>
                                        <button v-if="receipt.is_tax_invoiced && receipt.cfdi_invoice" class="btn btn-outline-info"
                                                @click="descargarCfdi(receipt, 'xml')" title="Descargar XML">
                                            <i class="fa fa-code"></i>
                                        </button>
                                        <button v-if="receipt.is_tax_invoiced && receipt.cfdi_invoice" class="btn btn-outline-danger"
                                                @click="descargarCfdi(receipt, 'pdf')" title="Descargar Factura PDF">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </button>
                                        <button v-if="canCancelInvoice(receipt)" class="btn btn-outline-danger"
                                                @click="cancelarFactura(receipt)" title="Cancelar Factura">
                                            <i class="fa fa-ban"></i>
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

        <!-- Modal Facturación CFDI -->
        <cfdi-invoice-modal
            v-if="cfdiActivo"
            :receipt-id="facturarReceiptId"
            @invoiced="onInvoiced"
            @closed="onModalClosed"
        />
    </div>
</template>

<script>
import CfdiInvoiceModal from './CfdiInvoiceModal.vue';

export default {
    name: 'ReceiptListComponent',
    components: { CfdiInvoiceModal },
    props: ['userLimited', 'cfdiActivo'],
    data() {
        return {
            loading: false,
            receipts: [],
            facturarReceiptId: null,
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
            vistaActual: localStorage.getItem('admin_vista') || 'cards'
        };
    },
    watch: {
        vistaActual(newVal) {
            localStorage.setItem('admin_vista', newVal);
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
        async descargarCfdi(receipt, formato) {
            if (!receipt.cfdi_invoice) return;
            try {
                const response = await axios.get(
                    `/admin/facturacion/descargar/${receipt.cfdi_invoice.id}/${formato}`,
                    { responseType: 'blob' }
                );
                if (response.headers['content-type']?.includes('application/json')) {
                    const text = await response.data.text();
                    const json = JSON.parse(text);
                    if (json.url) { window.open(json.url, '_blank'); }
                    else if (!json.ok) { Swal.fire('Error', json.message, 'error'); }
                    return;
                }
                const url = window.URL.createObjectURL(response.data);
                const a = document.createElement('a');
                a.href = url;
                a.download = `factura_${receipt.cfdi_invoice.serie}${receipt.cfdi_invoice.folio}.${formato}`;
                a.click();
                window.URL.revokeObjectURL(url);
            } catch (e) {
                Swal.fire('Error', 'No se pudo descargar el archivo', 'error');
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
        canInvoice(receipt) {
            if (!this.cfdiActivo) return false;
            if (receipt.quotation) return false;
            if (receipt.is_tax_invoiced) return false;
            return ['PAGADA', 'POR FACTURAR'].includes(receipt.status);
        },
        facturar(receipt) {
            this.facturarReceiptId = receipt.id;
        },
        onInvoiced() {
            this.facturarReceiptId = null;
            this.cargarReceipts();
        },
        onModalClosed() {
            this.facturarReceiptId = null;
        },
        canCancelInvoice(receipt) {
            if (!this.cfdiActivo) return false;
            if (!receipt.is_tax_invoiced) return false;
            if (!receipt.cfdi_invoice) return false;
            return receipt.cfdi_invoice.status === 'vigente';
        },
        async cancelarFactura(receipt) {
            const { value: motivo } = await Swal.fire({
                title: 'Cancelar Factura',
                html: `<p>Factura <strong>${receipt.cfdi_invoice.serie}-${receipt.cfdi_invoice.folio}</strong></p>
                       <p class="text-muted mb-2" style="font-size:0.8rem;">UUID: ${receipt.cfdi_invoice.uuid}</p>
                       <label class="form-label fw-bold">Motivo de cancelacion (SAT):</label>
                       <select id="swal-motivo" class="form-select">
                           <option value="03">03 - No se llevo a cabo la operacion</option>
                           <option value="02">02 - Comprobante con errores sin relacion</option>
                           <option value="01">01 - Comprobante con errores con relacion</option>
                           <option value="04">04 - Operacion nominativa en factura global</option>
                       </select>
                       <div id="swal-folio-div" style="display:none; margin-top:10px;">
                           <label class="form-label fw-bold">UUID factura que sustituye:</label>
                           <input id="swal-folio" class="form-control" placeholder="UUID de la factura nueva">
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Cancelar Factura',
                cancelButtonText: 'No, volver',
                confirmButtonColor: '#dc3545',
                didOpen: () => {
                    const sel = document.getElementById('swal-motivo');
                    const div = document.getElementById('swal-folio-div');
                    sel.addEventListener('change', () => {
                        div.style.display = sel.value === '01' ? 'block' : 'none';
                    });
                },
                preConfirm: () => {
                    const motivo = document.getElementById('swal-motivo').value;
                    const folio = document.getElementById('swal-folio')?.value || '';
                    if (motivo === '01' && !folio.trim()) {
                        Swal.showValidationMessage('El motivo 01 requiere el UUID de la factura que sustituye');
                        return false;
                    }
                    return { motivo, folio_sustitucion: folio.trim() || null };
                }
            });

            if (!motivo) return;

            const confirm2 = await Swal.fire({
                title: 'Confirmar cancelacion',
                html: `<p class="text-danger"><strong>Esta accion es irreversible ante el SAT.</strong></p>
                       <p>¿Estas seguro de cancelar la factura <strong>${receipt.cfdi_invoice.serie}-${receipt.cfdi_invoice.folio}</strong>?</p>`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Si, cancelar',
                cancelButtonText: 'No',
                confirmButtonColor: '#dc3545',
            });

            if (!confirm2.isConfirmed) return;

            try {
                const res = await axios.post('/admin/facturacion/cancelar', {
                    invoice_id: receipt.cfdi_invoice.id,
                    motivo: motivo.motivo,
                    folio_sustitucion: motivo.folio_sustitucion,
                });

                if (res.data.ok) {
                    Swal.fire('Cancelada', 'La factura fue cancelada exitosamente.', 'success');
                    this.cargarReceipts();
                } else {
                    Swal.fire('Error', res.data.message || 'Error al cancelar', 'error');
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.message || 'Error al cancelar factura', 'error');
            }
        },
        canEdit(receipt) {
            if (receipt.is_tax_invoiced) return false;
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

/* Estilos para Cards de Recibos */
.receipt-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.receipt-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.receipt-card .card-header {
    background: linear-gradient(135deg, #00F5A0 0%, #00D9F5 100%);
    color: #0D1117;
    border: none;
    padding: 0.75rem 1rem;
}

.receipt-info {
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

.receipt-card .card-footer {
    background: transparent;
    border-top: 1px solid #eee;
    padding: 0.75rem 1rem;
}
</style>
