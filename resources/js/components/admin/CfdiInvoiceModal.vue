<template>
    <div class="modal fade" tabindex="-1" :class="{'mostrar': showModal}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-file-text-o me-2"></i>Facturar Nota #{{ receiptData?.folio }}
                    </h5>
                    <button type="button" class="close text-white" @click="cerrar" :disabled="timbrando" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Loading -->
                    <div v-if="cargando" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2 text-muted">Cargando datos...</p>
                    </div>

                    <!-- Error -->
                    <div v-else-if="error" class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle me-1"></i> {{ error }}
                    </div>

                    <!-- Post-timbrado: Factura exitosa -->
                    <div v-else-if="timbradoExitoso" class="text-center py-4">
                        <div class="mb-3">
                            <i class="fa fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-success mb-3">Factura Timbrada Exitosamente</h4>
                        <div class="card border-success mx-auto" style="max-width: 400px;">
                            <div class="card-body">
                                <p class="mb-1"><strong>Serie-Folio:</strong> {{ resultadoTimbrado.serie }}-{{ resultadoTimbrado.folio }}</p>
                                <p class="mb-0 text-muted" style="font-size: 0.8rem; word-break: break-all;">
                                    <strong>UUID:</strong> {{ resultadoTimbrado.uuid }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-center gap-2">
                            <button class="btn btn-outline-primary" @click="descargar('xml')" :disabled="descargando">
                                <i class="fa fa-code me-1"></i> Descargar XML
                            </button>
                            <button class="btn btn-outline-danger" @click="descargar('pdf')" :disabled="descargando">
                                <i class="fa fa-file-pdf-o me-1"></i> Descargar PDF
                            </button>
                        </div>
                    </div>

                    <!-- Formulario de facturación -->
                    <div v-else-if="receiptData">
                        <!-- Info emisor -->
                        <div class="alert alert-light border mb-3 py-2">
                            <small class="text-muted">
                                <strong>Emisor:</strong> {{ emisorData.razon_social }} | RFC: {{ emisorData.rfc }} |
                                Timbres disponibles: <span :class="emisorData.timbres_disponibles > 0 ? 'text-success' : 'text-danger'">{{ emisorData.timbres_disponibles }}</span>
                            </small>
                        </div>

                        <!-- Sección Receptor -->
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong><i class="fa fa-user me-1"></i> Datos del Receptor</strong>
                            </div>
                            <div class="card-body">
                                <!-- Perfiles fiscales guardados -->
                                <div v-if="perfilesFiscales.length > 0" class="mb-3">
                                    <label class="form-label fw-bold">Perfiles fiscales guardados</label>
                                    <div class="list-group list-group-flush">
                                        <button v-for="perfil in perfilesFiscales" :key="perfil.id"
                                            type="button" class="list-group-item list-group-item-action py-2 d-flex justify-content-between align-items-center"
                                            @click="usarPerfil(perfil)"
                                            :class="{ 'active': perfilSeleccionado === perfil.id }">
                                            <div>
                                                <strong>{{ perfil.rfc }}</strong> - {{ perfil.razon_social }}
                                                <span v-if="perfil.is_default" class="badge bg-info ms-1">Default</span>
                                            </div>
                                            <i class="fa fa-check" v-if="perfilSeleccionado === perfil.id"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Botón Público en General -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm"
                                        :class="esPublicoGeneral ? 'btn-success' : 'btn-outline-secondary'"
                                        @click="setPublicoGeneral">
                                        <i class="fa fa-users me-1"></i> Publico en General
                                    </button>
                                    <button v-if="perfilSeleccionado || esPublicoGeneral" type="button"
                                        class="btn btn-sm btn-outline-warning ms-1" @click="limpiarReceptor">
                                        <i class="fa fa-eraser me-1"></i> Limpiar
                                    </button>
                                </div>

                                <!-- Campos receptor -->
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">RFC *</label>
                                        <input type="text" class="form-control form-control-sm" v-model="receptor.rfc"
                                            maxlength="13" @input="receptor.rfc = receptor.rfc.toUpperCase()"
                                            :disabled="esPublicoGeneral">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Razon Social *</label>
                                        <input type="text" class="form-control form-control-sm" v-model="receptor.razon_social"
                                            :disabled="esPublicoGeneral">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Regimen Fiscal *</label>
                                        <select class="form-select form-select-sm" v-model="receptor.regimen_fiscal"
                                            :disabled="esPublicoGeneral">
                                            <option value="">Seleccionar...</option>
                                            <option v-for="r in catalogoRegimen" :key="r.clave" :value="r.clave">
                                                {{ r.clave }} - {{ r.nombre }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Uso CFDI *</label>
                                        <select class="form-select form-select-sm" v-model="receptor.uso_cfdi"
                                            :disabled="esPublicoGeneral">
                                            <option value="">Seleccionar...</option>
                                            <option v-for="u in catalogoUsoCfdi" :key="u.clave" :value="u.clave">
                                                {{ u.clave }} - {{ u.nombre }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Codigo Postal *</label>
                                        <input type="text" class="form-control form-control-sm" v-model="receptor.codigo_postal"
                                            maxlength="5" :disabled="esPublicoGeneral">
                                    </div>
                                </div>

                                <!-- Checkbox guardar datos -->
                                <div class="form-check mt-2" v-if="receiptData.client_id && !esPublicoGeneral">
                                    <input class="form-check-input" type="checkbox" v-model="guardarDatosCliente" id="chkGuardar">
                                    <label class="form-check-label" for="chkGuardar">
                                        <small>Guardar datos fiscales para este cliente</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Conceptos -->
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong><i class="fa fa-list me-1"></i> Conceptos</strong>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Descripcion</th>
                                                <th style="min-width: 160px;">Clave SAT</th>
                                                <th style="min-width: 130px;">Unidad</th>
                                                <th class="text-center">Cant.</th>
                                                <th class="text-end">P. Unit.</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, idx) in conceptosSat" :key="item.detail_id">
                                                <td>{{ item.descripcion }}</td>
                                                <td>
                                                    <div class="position-relative">
                                                        <input type="text" class="form-control form-control-sm"
                                                            v-model="item.productSearch"
                                                            placeholder="Buscar..."
                                                            @input="buscarSatProduct(idx)"
                                                            @focus="item.showProductResults = true"
                                                            style="font-size: 0.75rem;">
                                                        <div v-if="item.clave_prod_serv" class="mt-1">
                                                            <small :class="item.clave_prod_serv !== '01010101' ? 'text-success' : 'text-warning'">
                                                                <i class="fa fa-check" v-if="item.clave_prod_serv !== '01010101'"></i>
                                                                <i class="fa fa-exclamation-triangle" v-else></i>
                                                                {{ item.clave_prod_serv }}
                                                            </small>
                                                        </div>
                                                        <ul v-if="item.showProductResults && satProductResults.length > 0 && activeSearchIdx === idx"
                                                            class="list-group position-absolute w-100" style="z-index: 1060; max-height: 180px; overflow-y: auto;">
                                                            <li v-for="r in satProductResults" :key="r.code"
                                                                class="list-group-item list-group-item-action py-1 px-2"
                                                                style="cursor: pointer; font-size: 0.75rem;"
                                                                @mousedown.prevent="selectSatProduct(idx, r)">
                                                                <strong>{{ r.code }}</strong> — {{ r.description }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="position-relative">
                                                        <input type="text" class="form-control form-control-sm"
                                                            v-model="item.unitSearch"
                                                            placeholder="Buscar..."
                                                            @input="buscarSatUnit(idx)"
                                                            @focus="item.showUnitResults = true"
                                                            style="font-size: 0.75rem;">
                                                        <div v-if="item.clave_unidad" class="mt-1">
                                                            <small class="text-muted">{{ item.clave_unidad }}</small>
                                                        </div>
                                                        <ul v-if="item.showUnitResults && satUnitResults.length > 0 && activeSearchIdx === idx"
                                                            class="list-group position-absolute w-100" style="z-index: 1060; max-height: 180px; overflow-y: auto;">
                                                            <li v-for="r in satUnitResults" :key="r.code"
                                                                class="list-group-item list-group-item-action py-1 px-2"
                                                                style="cursor: pointer; font-size: 0.75rem;"
                                                                @mousedown.prevent="selectSatUnit(idx, r)">
                                                                <strong>{{ r.code }}</strong> — {{ r.name }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td class="text-center">{{ item.qty }}</td>
                                                <td class="text-end">${{ formatNumber(item.precio) }}</td>
                                                <td class="text-end">${{ formatNumber(item.subtotal) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                                <td class="text-end">${{ formatNumber(subtotalDisplay) }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end"><strong>IVA (16%):</strong></td>
                                                <td class="text-end">${{ formatNumber(ivaDisplay) }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                                <td class="text-end"><strong>${{ formatNumber(receiptData.total) }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Pago -->
                        <div class="card mb-3">
                            <div class="card-header bg-light py-2">
                                <strong><i class="fa fa-credit-card me-1"></i> Datos de Pago</strong>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Forma de Pago *</label>
                                        <select class="form-select form-select-sm" v-model="formaPago">
                                            <option v-for="fp in catalogoFormaPago" :key="fp.clave" :value="fp.clave">
                                                {{ fp.clave }} - {{ fp.nombre }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Metodo de Pago *</label>
                                        <select class="form-select form-select-sm" v-model="metodoPago">
                                            <option value="PUE">PUE - Pago en Una sola Exhibicion</option>
                                            <option value="PPD">PPD - Pago en Parcialidades o Diferido</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button v-if="timbradoExitoso" type="button" class="btn btn-secondary" @click="cerrar">
                        Cerrar
                    </button>
                    <template v-else>
                        <button type="button" class="btn btn-secondary" @click="cerrar" :disabled="timbrando">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" @click="timbrarFactura"
                            :disabled="timbrando || !formValido || cargando">
                            <span v-if="timbrando">
                                <span class="spinner-border spinner-border-sm me-1"></span> Timbrando...
                            </span>
                            <span v-else>
                                <i class="fa fa-file-text-o me-1"></i> Timbrar Factura
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CfdiInvoiceModal',
    props: {
        receiptId: { type: Number, default: null },
    },
    emits: ['closed', 'invoiced'],
    data() {
        return {
            showModal: false,
            cargando: false,
            timbrando: false,
            descargando: false,
            error: null,
            receiptData: null,
            emisorData: null,
            perfilesFiscales: [],
            perfilSeleccionado: null,
            esPublicoGeneral: false,
            guardarDatosCliente: true,
            timbradoExitoso: false,
            resultadoTimbrado: null,
            receptor: {
                rfc: '',
                razon_social: '',
                regimen_fiscal: '',
                uso_cfdi: 'G03',
                codigo_postal: '',
            },
            formaPago: '01',
            metodoPago: 'PUE',
            conceptosSat: [],
            satProductResults: [],
            satUnitResults: [],
            activeSearchIdx: null,
            satSearchTimer: null,
            catalogoRegimen: [
                { clave: '601', nombre: 'General de Ley PM' },
                { clave: '603', nombre: 'PM Fines no Lucrativos' },
                { clave: '612', nombre: 'PF Actividades Empresariales' },
                { clave: '616', nombre: 'Sin obligaciones fiscales' },
                { clave: '621', nombre: 'Incorporacion Fiscal' },
                { clave: '626', nombre: 'RESICO' },
            ],
            catalogoUsoCfdi: [
                { clave: 'G01', nombre: 'Adquisicion de mercancias' },
                { clave: 'G03', nombre: 'Gastos en general' },
                { clave: 'S01', nombre: 'Sin efectos fiscales' },
            ],
            catalogoFormaPago: [
                { clave: '01', nombre: 'Efectivo' },
                { clave: '02', nombre: 'Cheque nominativo' },
                { clave: '03', nombre: 'Transferencia electronica' },
                { clave: '04', nombre: 'Tarjeta de credito' },
                { clave: '28', nombre: 'Tarjeta de debito' },
                { clave: '99', nombre: 'Por definir' },
            ],
        };
    },
    computed: {
        formValido() {
            return this.receptor.rfc &&
                   this.receptor.razon_social &&
                   this.receptor.regimen_fiscal &&
                   this.receptor.uso_cfdi &&
                   this.receptor.codigo_postal &&
                   this.formaPago &&
                   this.metodoPago;
        },
        conceptosDisplay() {
            if (!this.receiptData) return [];
            const extraerIva = !(this.receiptData.iva > 0);
            return this.receiptData.detail.map(item => ({
                id: item.id,
                descripcion: item.descripcion,
                qty: item.qty,
                precio: extraerIva ? Math.round(item.price / 1.16 * 100) / 100 : item.price,
                subtotal: extraerIva ? Math.round(item.subtotal / 1.16 * 100) / 100 : item.subtotal,
                sat_product_code: item.product?.sat_product_code || null,
                sat_unit_code: item.product?.sat_unit_code || null,
            }));
        },
        subtotalDisplay() {
            if (!this.receiptData) return 0;
            if (this.receiptData.iva > 0) return this.receiptData.subtotal;
            return this.conceptosDisplay.reduce((sum, item) => sum + item.subtotal, 0);
        },
        ivaDisplay() {
            if (!this.receiptData) return 0;
            if (this.receiptData.iva > 0) return this.receiptData.iva;
            return this.receiptData.total - this.subtotalDisplay;
        },
    },
    watch: {
        receiptId(newVal) {
            if (newVal) {
                this.abrir();
            }
        },
    },
    methods: {
        abrir() {
            this.resetForm();
            this.showModal = true;
            this.cargarDatos();
        },
        cerrar() {
            if (this.timbradoExitoso) {
                this.$emit('invoiced');
            } else {
                this.$emit('closed');
            }
            this.showModal = false;
        },
        resetForm() {
            this.cargando = false;
            this.timbrando = false;
            this.error = null;
            this.receiptData = null;
            this.emisorData = null;
            this.perfilesFiscales = [];
            this.perfilSeleccionado = null;
            this.esPublicoGeneral = false;
            this.guardarDatosCliente = true;
            this.timbradoExitoso = false;
            this.resultadoTimbrado = null;
            this.receptor = { rfc: '', razon_social: '', regimen_fiscal: '', uso_cfdi: 'G03', codigo_postal: '' };
            this.formaPago = '01';
            this.metodoPago = 'PUE';
            this.conceptosSat = [];
            this.satProductResults = [];
            this.satUnitResults = [];
            this.activeSearchIdx = null;
        },
        async cargarDatos() {
            this.cargando = true;
            this.error = null;
            try {
                const res = await axios.get(`/admin/facturacion/receipt/${this.receiptId}/data`);
                if (res.data.ok) {
                    this.receiptData = res.data.receipt;
                    this.emisorData = res.data.emisor;
                    this.perfilesFiscales = res.data.receipt.client?.fiscal_data || [];
                    this.initConceptosSat();

                    const defaultPerfil = this.perfilesFiscales.find(p => p.is_default);
                    if (defaultPerfil) {
                        this.usarPerfil(defaultPerfil);
                    }

                    this.mapearFormaPago(this.receiptData.payment);
                } else {
                    this.error = res.data.message;
                }
            } catch (e) {
                this.error = e.response?.data?.message || 'Error al cargar datos';
            } finally {
                this.cargando = false;
            }
        },
        mapearFormaPago(payment) {
            const map = {
                'EFECTIVO': '01',
                'TRANSFERENCIA': '03',
                'TARJETA': '04',
                'CHEQUE': '02',
            };
            this.formaPago = map[payment] || '01';
        },
        usarPerfil(perfil) {
            this.perfilSeleccionado = perfil.id;
            this.esPublicoGeneral = false;
            this.receptor = {
                rfc: perfil.rfc,
                razon_social: perfil.razon_social,
                regimen_fiscal: perfil.regimen_fiscal,
                uso_cfdi: perfil.uso_cfdi,
                codigo_postal: perfil.codigo_postal,
            };
        },
        setPublicoGeneral() {
            this.esPublicoGeneral = true;
            this.perfilSeleccionado = null;
            this.receptor = {
                rfc: 'XAXX010101000',
                razon_social: 'PUBLICO EN GENERAL',
                regimen_fiscal: '616',
                uso_cfdi: 'S01',
                codigo_postal: this.emisorData?.codigo_postal || '',
            };
        },
        limpiarReceptor() {
            this.perfilSeleccionado = null;
            this.esPublicoGeneral = false;
            this.receptor = { rfc: '', razon_social: '', regimen_fiscal: '', uso_cfdi: 'G03', codigo_postal: '' };
        },
        async timbrarFactura() {
            const confirm = await Swal.fire({
                title: 'Timbrar Factura',
                html: `<p>Se generara la factura CFDI para la nota <strong>#${this.receiptData.folio}</strong>.</p>
                       <p><strong>Receptor:</strong> ${this.receptor.rfc} - ${this.receptor.razon_social}</p>
                       <p><strong>Total:</strong> $${this.formatNumber(this.receiptData.total)}</p>
                       <p class="text-warning"><small>Esta accion consume 1 timbre y no se puede deshacer.</small></p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Si, Timbrar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd',
            });

            if (!confirm.isConfirmed) return;

            this.timbrando = true;
            try {
                const res = await axios.post('/admin/facturacion/timbrar', {
                    receipt_id: this.receiptData.id,
                    receptor_rfc: this.receptor.rfc,
                    receptor_razon_social: this.receptor.razon_social,
                    receptor_regimen_fiscal: this.receptor.regimen_fiscal,
                    receptor_uso_cfdi: this.receptor.uso_cfdi,
                    receptor_codigo_postal: this.receptor.codigo_postal,
                    forma_pago: this.formaPago,
                    metodo_pago: this.metodoPago,
                    guardar_datos_cliente: this.guardarDatosCliente,
                    conceptos_sat: this.conceptosSat.map(c => ({
                        detail_id: c.detail_id,
                        clave_prod_serv: c.clave_prod_serv,
                        clave_unidad: c.clave_unidad,
                    })),
                });

                if (res.data.ok) {
                    this.timbradoExitoso = true;
                    this.resultadoTimbrado = res.data;
                } else {
                    Swal.fire('Error', res.data.message || 'Error al timbrar', 'error');
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.message || 'Error al timbrar factura', 'error');
            } finally {
                this.timbrando = false;
            }
        },
        async descargar(formato) {
            this.descargando = true;
            try {
                const response = await axios.get(
                    `/admin/facturacion/descargar/${this.resultadoTimbrado.invoice_id}/${formato}`,
                    { responseType: 'blob' }
                );

                if (response.headers['content-type']?.includes('application/json')) {
                    const text = await response.data.text();
                    const json = JSON.parse(text);
                    if (json.url) {
                        window.open(json.url, '_blank');
                    } else if (!json.ok) {
                        Swal.fire('Error', json.message, 'error');
                    }
                    return;
                }

                const url = window.URL.createObjectURL(response.data);
                const a = document.createElement('a');
                a.href = url;
                a.download = `factura_${this.resultadoTimbrado.serie}${this.resultadoTimbrado.folio}.${formato}`;
                a.click();
                window.URL.revokeObjectURL(url);
            } catch (e) {
                Swal.fire('Error', 'No se pudo descargar el archivo', 'error');
            } finally {
                this.descargando = false;
            }
        },
        initConceptosSat() {
            if (!this.receiptData) return;
            const extraerIva = !(this.receiptData.iva > 0);
            this.conceptosSat = this.receiptData.detail.map(item => ({
                detail_id: item.id,
                descripcion: item.descripcion,
                qty: item.qty,
                precio: extraerIva ? Math.round(item.price / 1.16 * 100) / 100 : item.price,
                subtotal: extraerIva ? Math.round(item.subtotal / 1.16 * 100) / 100 : item.subtotal,
                clave_prod_serv: item.product?.sat_product_code || '01010101',
                clave_unidad: item.product?.sat_unit_code || 'E48',
                productSearch: '',
                unitSearch: '',
                showProductResults: false,
                showUnitResults: false,
            }));
        },
        buscarSatProduct(idx) {
            clearTimeout(this.satSearchTimer);
            this.activeSearchIdx = idx;
            let q = this.conceptosSat[idx].productSearch;
            if (q.length < 2) { this.satProductResults = []; return; }
            this.satSearchTimer = setTimeout(() => {
                axios.get('/admin/sat/product-codes', { params: { q } }).then(res => {
                    this.satProductResults = res.data;
                    this.conceptosSat[idx].showProductResults = true;
                });
            }, 300);
        },
        selectSatProduct(idx, item) {
            this.conceptosSat[idx].clave_prod_serv = item.code;
            this.conceptosSat[idx].productSearch = '';
            this.conceptosSat[idx].showProductResults = false;
            this.satProductResults = [];
        },
        buscarSatUnit(idx) {
            clearTimeout(this.satSearchTimer);
            this.activeSearchIdx = idx;
            let q = this.conceptosSat[idx].unitSearch;
            if (q.length < 1) { this.satUnitResults = []; return; }
            this.satSearchTimer = setTimeout(() => {
                axios.get('/admin/sat/unit-codes', { params: { q } }).then(res => {
                    this.satUnitResults = res.data;
                    this.conceptosSat[idx].showUnitResults = true;
                });
            }, 300);
        },
        selectSatUnit(idx, item) {
            this.conceptosSat[idx].clave_unidad = item.code;
            this.conceptosSat[idx].unitSearch = '';
            this.conceptosSat[idx].showUnitResults = false;
            this.satUnitResults = [];
        },
        formatNumber(num) {
            if (!num) return '0.00';
            return parseFloat(num).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
    },
};
</script>

<style scoped>
.mostrar {
    display: list-item !important;
    opacity: 1 !important;
}
</style>
