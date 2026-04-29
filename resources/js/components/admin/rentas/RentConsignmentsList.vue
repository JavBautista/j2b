<template>
    <div class="rent-consignments-list">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fa fa-list-alt"></i> Consignas de Material
                    <span class="badge bg-primary ms-2">{{ consignments.length }}</span>
                </h5>
                <button class="btn btn-success btn-sm" @click="abrirModalCrear()" :disabled="!rentId">
                    <i class="fa fa-plus"></i> Nueva Consigna
                </button>
            </div>
            <div class="card-body">
                <div v-if="cargando" class="text-center py-3">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                </div>
                <div v-else-if="consignments.length === 0" class="text-center py-4 text-muted">
                    <i class="fa fa-inbox fa-2x"></i>
                    <p class="mt-2 mb-0">No hay consignas registradas para esta renta</p>
                </div>
                <div v-else>
                    <div v-for="cons in consignments" :key="cons.id" class="consigna-card mb-3">
                        <div class="card border" :class="cons.status === 'cancelada' ? 'border-danger' : 'border-primary'">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h6 class="mb-1">
                                            <strong>{{ folioFmt(cons.folio) }}</strong>
                                            <span v-if="cons.status === 'vigente'" class="badge bg-success ms-2">Vigente</span>
                                            <span v-else class="badge bg-danger ms-2">Cancelada</span>
                                            <span v-if="cons.signed_at" class="badge bg-info ms-1">
                                                <i class="fa fa-pencil-square-o"></i> Firmada
                                            </span>
                                            <span v-else-if="cons.status === 'vigente'" class="badge bg-warning text-dark ms-1">
                                                Pendiente firma
                                            </span>
                                        </h6>
                                        <small class="text-muted d-block">
                                            <i class="fa fa-calendar"></i> Entrega: {{ formatearFecha(cons.delivery_date) }}
                                            <span v-if="cons.received_by_name" class="ms-2">
                                                · Recibe: <strong>{{ cons.received_by_name }}</strong>
                                            </span>
                                        </small>
                                        <small v-if="cons.notes" class="text-muted d-block">
                                            <i class="fa fa-comment"></i> {{ cons.notes }}
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a :href="urlPdf(cons.id)" target="_blank" class="btn btn-outline-danger" title="Ver PDF">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </a>
                                        <button class="btn btn-outline-primary" @click="abrirModalFirma(cons)" :disabled="cons.status === 'cancelada'" title="Subir firma">
                                            <i class="fa fa-pencil-square-o"></i>
                                        </button>
                                        <button class="btn btn-outline-info" @click="verFirma(cons)" v-if="cons.signature_path" title="Ver firma">
                                            <i class="fa fa-picture-o"></i>
                                        </button>
                                        <button class="btn btn-outline-warning" @click="abrirModalCancelar(cons)" :disabled="cons.status === 'cancelada'" title="Cancelar">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <table class="table table-sm mt-2 mb-0 small">
                                    <thead>
                                        <tr class="text-muted">
                                            <th>Producto</th>
                                            <th class="text-center" style="width:80px;">Cantidad</th>
                                            <th class="text-center" style="width:80px;">Devuelto</th>
                                            <th class="text-center" style="width:100px;">En consigna</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in cons.items" :key="item.id">
                                            <td>{{ item.description || (item.product && item.product.name) || 'Producto' }}</td>
                                            <td class="text-center">{{ item.qty }}</td>
                                            <td class="text-center">{{ item.qty_returned }}</td>
                                            <td class="text-center fw-bold">{{ item.qty - item.qty_returned }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear Consigna -->
        <div class="modal fade" tabindex="-1" :class="{'mostrar':modalCrear}" role="dialog" style="display: none;">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fa fa-plus"></i> Nueva Consigna de Material</h5>
                        <button type="button" class="btn-close btn-close-white" @click="cerrarModalCrear()"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de entrega *</label>
                                <input type="date" class="form-control" v-model="formCrear.delivery_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Recibe (nombre)</label>
                                <input type="text" class="form-control" v-model="formCrear.received_by_name" placeholder="Nombre de quien recibe">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" v-model="formCrear.notes" rows="2" placeholder="Notas opcionales"></textarea>
                        </div>

                        <hr>
                        <h6><i class="fa fa-cubes"></i> Productos a entregar</h6>

                        <div class="mb-3 position-relative">
                            <label class="form-label">Buscar producto del inventario</label>
                            <input type="text" class="form-control" v-model="busquedaProducto" @input="buscarProductos()" placeholder="Nombre, código o código de barras">
                            <div v-if="resultadosProductos.length > 0" class="list-group position-absolute w-100 shadow" style="z-index:1000; max-height:240px; overflow-y:auto;">
                                <button v-for="p in resultadosProductos" :key="p.id" type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click="agregarProducto(p)">
                                    <span>
                                        <strong>{{ p.name }}</strong>
                                        <small class="text-muted ms-2" v-if="p.key">[{{ p.key }}]</small>
                                    </span>
                                    <span class="badge" :class="p.stock > 0 ? 'bg-success' : 'bg-danger'">
                                        Stock: {{ p.stock }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <table v-if="formCrear.items.length > 0" class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Descripcion</th>
                                    <th style="width:120px;">Cantidad</th>
                                    <th style="width:80px;">Stock</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(it, idx) in formCrear.items" :key="idx">
                                    <td>{{ it.product_name }}</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" v-model="it.description">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" v-model.number="it.qty" min="1" :max="it.product_stock">
                                    </td>
                                    <td>
                                        <span class="badge" :class="it.qty <= it.product_stock ? 'bg-success' : 'bg-danger'">{{ it.product_stock }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" @click="quitarProducto(idx)" title="Quitar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="text-center text-muted py-3 small">
                            Busca y agrega productos del inventario
                        </div>

                        <div v-if="errorCrear" class="alert alert-danger small mb-0">{{ errorCrear }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModalCrear()">Cancelar</button>
                        <button type="button" class="btn btn-success" @click="guardarCrear()" :disabled="guardandoCrear || formCrear.items.length === 0">
                            <i v-if="guardandoCrear" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-save"></i> Crear y generar PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Cancelar Consigna -->
        <div class="modal fade" tabindex="-1" :class="{'mostrar':modalCancelar}" role="dialog" style="display: none;">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="fa fa-times-circle"></i> Cancelar Consigna {{ consignaCancelar ? folioFmt(consignaCancelar.folio) : '' }}</h5>
                        <button type="button" class="btn-close" @click="cerrarModalCancelar()"></button>
                    </div>
                    <div class="modal-body" v-if="consignaCancelar">
                        <p class="text-muted small mb-3">
                            Indica cuántas unidades de cada producto regresan al inventario. Las que no regresen quedarán como consumidas.
                        </p>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Entregado</th>
                                    <th class="text-center">Ya devuelto</th>
                                    <th class="text-center">Pendiente</th>
                                    <th style="width:140px;">Devolver ahora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in consignaCancelar.items" :key="item.id">
                                    <td>{{ item.description || (item.product && item.product.name) }}</td>
                                    <td class="text-center">{{ item.qty }}</td>
                                    <td class="text-center">{{ item.qty_returned }}</td>
                                    <td class="text-center fw-bold">{{ item.qty - item.qty_returned }}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm"
                                            v-model.number="formCancelar.returns[item.id]"
                                            min="0" :max="item.qty - item.qty_returned">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mb-3">
                            <label class="form-label">Motivo de cancelación</label>
                            <textarea class="form-control" v-model="formCancelar.cancellation_reason" rows="2" placeholder="Opcional"></textarea>
                        </div>
                        <div v-if="errorCancelar" class="alert alert-danger small mb-0">{{ errorCancelar }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModalCancelar()">Cerrar</button>
                        <button type="button" class="btn btn-danger" @click="confirmarCancelar()" :disabled="guardandoCancelar">
                            <i v-if="guardandoCancelar" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-times"></i> Confirmar cancelación
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Subir Firma -->
        <div class="modal fade" tabindex="-1" :class="{'mostrar':modalFirma}" role="dialog" style="display: none;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fa fa-pencil-square-o"></i> Subir vale firmado {{ consignaFirma ? folioFmt(consignaFirma.folio) : '' }}</h5>
                        <button type="button" class="btn-close btn-close-white" @click="cerrarModalFirma()"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Sube la foto del vale impreso y firmado por el cliente. JPG o PNG, máx 10MB.</p>
                        <input type="file" class="form-control" accept="image/jpeg,image/png" @change="onFotoSeleccionada($event)">
                        <div v-if="previewFirma" class="text-center mt-3">
                            <img :src="previewFirma" alt="Preview" style="max-width:100%; max-height:300px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div v-if="errorFirma" class="alert alert-danger small mt-2 mb-0">{{ errorFirma }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModalFirma()">Cancelar</button>
                        <button type="button" class="btn btn-primary" @click="guardarFirma()" :disabled="!fotoFirma || guardandoFirma">
                            <i v-if="guardandoFirma" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-upload"></i> Subir
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ver Firma -->
        <div class="modal fade" tabindex="-1" :class="{'mostrar':modalVerFirma}" role="dialog" style="display: none;">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Vale firmado</h5>
                        <button type="button" class="btn-close btn-close-white" @click="modalVerFirma=false"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img v-if="urlFirmaActual" :src="urlFirmaActual" alt="Firma" style="max-width:100%;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RentConsignmentsList',
    props: {
        rentId: { type: [Number, String], default: null },
    },
    data() {
        return {
            consignments: [],
            cargando: false,

            modalCrear: false,
            formCrear: this.formCrearVacio(),
            errorCrear: null,
            guardandoCrear: false,
            busquedaProducto: '',
            resultadosProductos: [],
            timeoutBusqueda: null,

            modalCancelar: false,
            consignaCancelar: null,
            formCancelar: { returns: {}, cancellation_reason: '' },
            errorCancelar: null,
            guardandoCancelar: false,

            modalFirma: false,
            consignaFirma: null,
            fotoFirma: null,
            previewFirma: null,
            errorFirma: null,
            guardandoFirma: false,

            modalVerFirma: false,
            urlFirmaActual: null,
        };
    },
    watch: {
        rentId: {
            immediate: true,
            handler(v) {
                if (v) this.cargar();
                else this.consignments = [];
            },
        },
    },
    methods: {
        formCrearVacio() {
            return {
                delivery_date: new Date().toISOString().slice(0, 10),
                received_by_name: '',
                notes: '',
                items: [],
            };
        },
        folioFmt(folio) {
            return 'CSG-' + String(folio).padStart(3, '0');
        },
        formatearFecha(f) {
            if (!f) return '-';
            const d = new Date(f);
            return d.toLocaleDateString('es-MX');
        },
        urlPdf(id) {
            return `/admin/consignments/${id}/pdf`;
        },
        cargar() {
            if (!this.rentId) return;
            this.cargando = true;
            axios.get(`/admin/rents/${this.rentId}/consignments`)
                .then(r => {
                    if (r.data.ok) this.consignments = r.data.consignments;
                })
                .catch(e => {
                    Swal.fire('Error', 'No se pudieron cargar las consignas', 'error');
                })
                .finally(() => { this.cargando = false; });
        },

        // CREAR
        abrirModalCrear() {
            this.formCrear = this.formCrearVacio();
            this.errorCrear = null;
            this.busquedaProducto = '';
            this.resultadosProductos = [];
            this.modalCrear = true;
        },
        cerrarModalCrear() {
            this.modalCrear = false;
        },
        buscarProductos() {
            clearTimeout(this.timeoutBusqueda);
            const q = this.busquedaProducto.trim();
            if (q.length < 2) {
                this.resultadosProductos = [];
                return;
            }
            this.timeoutBusqueda = setTimeout(() => {
                axios.get('/admin/products/get', { params: { buscar: q, filtro_activo: 'ACTIVOS' } })
                    .then(r => {
                        const items = (r.data && r.data.data) ? r.data.data : [];
                        this.resultadosProductos = items.slice(0, 10);
                    })
                    .catch(() => { this.resultadosProductos = []; });
            }, 300);
        },
        agregarProducto(p) {
            if (this.formCrear.items.find(it => it.product_id === p.id)) {
                Swal.fire('Aviso', 'Ese producto ya está en la lista', 'info');
                return;
            }
            this.formCrear.items.push({
                product_id: p.id,
                product_name: p.name,
                product_stock: p.stock,
                description: p.name,
                qty: 1,
            });
            this.busquedaProducto = '';
            this.resultadosProductos = [];
        },
        quitarProducto(idx) {
            this.formCrear.items.splice(idx, 1);
        },
        guardarCrear() {
            this.errorCrear = null;
            for (const it of this.formCrear.items) {
                if (!it.qty || it.qty < 1) {
                    this.errorCrear = `Cantidad inválida para "${it.product_name}"`;
                    return;
                }
                if (it.qty > it.product_stock) {
                    this.errorCrear = `Stock insuficiente de "${it.product_name}" (disponible ${it.product_stock})`;
                    return;
                }
            }
            this.guardandoCrear = true;
            const payload = {
                delivery_date: this.formCrear.delivery_date,
                received_by_name: this.formCrear.received_by_name,
                notes: this.formCrear.notes,
                items: this.formCrear.items.map(it => ({
                    product_id: it.product_id,
                    qty: it.qty,
                    description: it.description,
                })),
            };
            axios.post(`/admin/rents/${this.rentId}/consignments`, payload)
                .then(r => {
                    if (r.data.ok) {
                        this.modalCrear = false;
                        this.cargar();
                        Swal.fire({
                            icon: 'success',
                            title: 'Consigna creada',
                            text: 'PDF generado. Imprímelo, fírmalo y luego sube la foto.',
                            confirmButtonText: 'OK',
                        });
                    } else {
                        this.errorCrear = r.data.message || 'Error al crear';
                    }
                })
                .catch(e => {
                    this.errorCrear = e.response?.data?.message || 'Error al crear consigna';
                })
                .finally(() => { this.guardandoCrear = false; });
        },

        // CANCELAR
        abrirModalCancelar(cons) {
            this.consignaCancelar = cons;
            this.formCancelar = {
                returns: Object.fromEntries(cons.items.map(it => [it.id, 0])),
                cancellation_reason: '',
            };
            this.errorCancelar = null;
            this.modalCancelar = true;
        },
        cerrarModalCancelar() {
            this.modalCancelar = false;
            this.consignaCancelar = null;
        },
        confirmarCancelar() {
            this.errorCancelar = null;
            this.guardandoCancelar = true;
            axios.post(`/admin/consignments/${this.consignaCancelar.id}/cancel`, this.formCancelar)
                .then(r => {
                    if (r.data.ok) {
                        this.modalCancelar = false;
                        this.cargar();
                        Swal.fire('Cancelada', r.data.message, 'success');
                    } else {
                        this.errorCancelar = r.data.message || 'Error al cancelar';
                    }
                })
                .catch(e => {
                    this.errorCancelar = e.response?.data?.message || 'Error al cancelar';
                })
                .finally(() => { this.guardandoCancelar = false; });
        },

        // FIRMA
        abrirModalFirma(cons) {
            this.consignaFirma = cons;
            this.fotoFirma = null;
            this.previewFirma = null;
            this.errorFirma = null;
            this.modalFirma = true;
        },
        cerrarModalFirma() {
            this.modalFirma = false;
            this.consignaFirma = null;
        },
        onFotoSeleccionada(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.fotoFirma = file;
            const reader = new FileReader();
            reader.onload = ev => { this.previewFirma = ev.target.result; };
            reader.readAsDataURL(file);
        },
        guardarFirma() {
            this.errorFirma = null;
            this.guardandoFirma = true;
            const fd = new FormData();
            fd.append('foto', this.fotoFirma);
            axios.post(`/admin/consignments/${this.consignaFirma.id}/signature`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
                .then(r => {
                    if (r.data.ok) {
                        this.modalFirma = false;
                        this.cargar();
                        Swal.fire('Firma registrada', '', 'success');
                    } else {
                        this.errorFirma = r.data.message || 'Error al subir firma';
                    }
                })
                .catch(e => {
                    this.errorFirma = e.response?.data?.message || 'Error al subir firma';
                })
                .finally(() => { this.guardandoFirma = false; });
        },
        verFirma(cons) {
            this.urlFirmaActual = `/admin/consignments/${cons.id}/signature/image?t=${Date.now()}`;
            this.modalVerFirma = true;
        },
    },
};
</script>

<style scoped>
.modal.mostrar {
    display: block !important;
    background-color: rgba(0,0,0,0.5);
}
.consigna-card .table {
    background: #fafbfc;
}
</style>
