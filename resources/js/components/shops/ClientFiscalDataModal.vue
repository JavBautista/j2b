<template>
    <div>
        <!-- Modal principal -->
        <div class="modal fade" tabindex="-1" :class="{'mostrar':showModal}" role="dialog" style="display: none;">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h4 class="modal-title">
                            <i class="fa fa-file-text-o"></i>
                            <span v-if="vista === 'lista'">Datos Fiscales — {{ client.name || 'Cliente' }}</span>
                            <span v-else>{{ perfilEditando ? 'Editar perfil fiscal' : 'Nuevo perfil fiscal' }}</span>
                        </h4>
                        <button type="button" class="close text-white" @click="cerrarModal()" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <!-- Banner modo selección -->
                        <div v-if="modoSeleccion && vista === 'lista'" class="alert alert-warning text-center py-2 mb-3">
                            <i class="fa fa-info-circle"></i> Selecciona un perfil para facturar
                        </div>

                        <!-- VISTA LISTA -->
                        <div v-if="vista === 'lista'">
                            <div v-if="cargando" class="text-center py-4">
                                <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                            </div>

                            <div v-if="!cargando && perfiles.length === 0" class="text-center py-4">
                                <i class="fa fa-file-text-o" style="font-size: 48px; opacity: 0.4;"></i>
                                <p class="mt-3 text-muted">Este cliente aún no tiene perfiles fiscales.</p>
                                <button type="button" class="btn btn-primary" @click="abrirFormNuevo()">
                                    <i class="fa fa-plus"></i> Agregar primer perfil fiscal
                                </button>
                            </div>

                            <div v-if="!cargando && perfiles.length > 0">
                                <div class="row">
                                    <div class="col-md-6 mb-3" v-for="perfil in perfiles" :key="perfil.id">
                                        <div class="card h-100" :class="{'border-success': perfil.is_default}">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span v-if="perfil.is_default" class="badge bg-success">Predeterminado</span>
                                                    <small v-if="perfil.nickname" class="text-muted ms-2">{{ perfil.nickname }}</small>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="card-title">{{ perfil.razon_social }}</h6>
                                                <p class="mb-1"><strong>RFC:</strong> {{ perfil.rfc }}</p>
                                                <p class="mb-1"><strong>Régimen:</strong> {{ perfil.regimen_fiscal }} — {{ nombreRegimen(perfil.regimen_fiscal) }}</p>
                                                <p class="mb-1"><strong>Uso CFDI:</strong> {{ perfil.uso_cfdi }} — {{ nombreUso(perfil.uso_cfdi) }}</p>
                                                <p class="mb-1"><strong>CP:</strong> {{ perfil.codigo_postal }}</p>
                                                <p v-if="perfil.email" class="mb-1"><strong>Correo:</strong> {{ perfil.email }}</p>

                                                <div class="mt-3">
                                                    <button v-if="modoSeleccion" type="button" class="btn btn-primary btn-sm w-100 mb-2" @click="usarPerfil(perfil)">
                                                        <i class="fa fa-check-circle"></i> Usar este perfil
                                                    </button>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" @click="abrirFormEditar(perfil)">
                                                            <i class="fa fa-pencil"></i> Editar
                                                        </button>
                                                        <button v-if="!perfil.is_default" type="button" class="btn btn-outline-warning btn-sm" @click="marcarDefault(perfil)">
                                                            <i class="fa fa-star-o"></i> Marcar default
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" @click="eliminar(perfil)">
                                                            <i class="fa fa-trash"></i> Eliminar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-2">
                                    <button type="button" class="btn btn-success" @click="abrirFormNuevo()">
                                        <i class="fa fa-plus"></i> Agregar perfil fiscal
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- VISTA FORM -->
                        <div v-if="vista === 'form'">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Alias / Nickname (opcional)</label>
                                    <input type="text" class="form-control" v-model="form.nickname" maxlength="80" placeholder="Persona Moral / Sucursal Norte">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">RFC <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" :class="{'is-invalid': errores.rfc}" v-model="form.rfc" @input="onRfcInput" maxlength="13" placeholder="Persona moral 12, física 13">
                                    <div v-if="errores.rfc" class="invalid-feedback">{{ errores.rfc }}</div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Razón Social <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" :class="{'is-invalid': errores.razon_social}" v-model="form.razon_social" maxlength="255">
                                    <div v-if="errores.razon_social" class="invalid-feedback">{{ errores.razon_social }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Régimen Fiscal <span class="text-danger">*</span></label>
                                    <select class="form-select" :class="{'is-invalid': errores.regimen_fiscal}" v-model="form.regimen_fiscal" @change="onRegimenChange">
                                        <option value="">Seleccionar...</option>
                                        <option v-for="r in regimenesFiltrados" :key="r.clave" :value="r.clave">
                                            {{ r.clave }} — {{ r.nombre }}
                                        </option>
                                    </select>
                                    <div v-if="errores.regimen_fiscal" class="invalid-feedback">{{ errores.regimen_fiscal }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Uso CFDI <span class="text-danger">*</span></label>
                                    <select class="form-select" :class="{'is-invalid': errores.uso_cfdi}" v-model="form.uso_cfdi">
                                        <option value="">Seleccionar...</option>
                                        <option v-for="u in usosFiltrados" :key="u.clave" :value="u.clave">
                                            {{ u.clave }} — {{ u.nombre }}
                                        </option>
                                    </select>
                                    <div v-if="errores.uso_cfdi" class="invalid-feedback">{{ errores.uso_cfdi }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Código Postal <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" :class="{'is-invalid': errores.codigo_postal}" v-model="form.codigo_postal" maxlength="5" placeholder="5 dígitos">
                                    <div v-if="errores.codigo_postal" class="invalid-feedback">{{ errores.codigo_postal }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Correo para envío de facturas (opcional)</label>
                                    <input type="email" class="form-control" :class="{'is-invalid': errores.email}" v-model="form.email" maxlength="150" placeholder="facturacion@empresa.com">
                                    <div v-if="errores.email" class="invalid-feedback">{{ errores.email }}</div>
                                </div>

                                <div class="col-12 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="fiscalIsDefault" v-model="form.is_default">
                                        <label class="form-check-label" for="fiscalIsDefault">
                                            Marcar como perfil predeterminado
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <template v-if="vista === 'lista'">
                            <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cerrar</button>
                        </template>
                        <template v-else>
                            <button type="button" class="btn btn-secondary" @click="cancelarForm()" :disabled="guardando">Cancelar</button>
                            <button type="button" class="btn btn-primary" @click="guardar()" :disabled="guardando">
                                <i class="fa" :class="guardando ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                                {{ perfilEditando ? 'Actualizar perfil' : 'Guardar perfil' }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ClientFiscalDataModal',
    emits: ['closed', 'seleccionado'],
    data() {
        return {
            showModal: false,
            modoSeleccion: false,
            vista: 'lista',
            cargando: false,
            guardando: false,
            client: {},
            perfiles: [],
            perfilEditando: null,
            form: this.formVacio(),
            errores: {},

            catalogoRegimen: [
                { clave: '601', nombre: 'General de Ley PM' },
                { clave: '603', nombre: 'PM Fines no Lucrativos' },
                { clave: '605', nombre: 'Sueldos y Salarios e Ingresos Asimilados a Salarios' },
                { clave: '606', nombre: 'Arrendamiento' },
                { clave: '607', nombre: 'Enajenación o Adquisición de Bienes' },
                { clave: '608', nombre: 'Demás ingresos' },
                { clave: '610', nombre: 'Residentes en el Extranjero sin EP en México' },
                { clave: '611', nombre: 'Ingresos por Dividendos' },
                { clave: '612', nombre: 'PF con Actividades Empresariales y Profesionales' },
                { clave: '614', nombre: 'Ingresos por intereses' },
                { clave: '615', nombre: 'Ingresos por Obtención de Premios' },
                { clave: '616', nombre: 'Sin obligaciones fiscales' },
                { clave: '620', nombre: 'Sociedades Cooperativas de Producción' },
                { clave: '621', nombre: 'Incorporación Fiscal' },
                { clave: '622', nombre: 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras' },
                { clave: '623', nombre: 'Opcional para Grupos de Sociedades' },
                { clave: '624', nombre: 'Coordinados' },
                { clave: '625', nombre: 'Régimen de Actividades Empresariales con ingresos a través de Plataformas Tecnológicas' },
                { clave: '626', nombre: 'RESICO' },
            ],

            catalogoUsoCfdi: [
                { clave: 'G01', nombre: 'Adquisición de mercancías' },
                { clave: 'G02', nombre: 'Devoluciones, descuentos o bonificaciones' },
                { clave: 'G03', nombre: 'Gastos en general' },
                { clave: 'I01', nombre: 'Construcciones' },
                { clave: 'I02', nombre: 'Mobiliario y equipo de oficina por inversiones' },
                { clave: 'I03', nombre: 'Equipo de transporte' },
                { clave: 'I04', nombre: 'Equipo de cómputo y accesorios' },
                { clave: 'I05', nombre: 'Dados, troqueles, moldes, matrices y herramental' },
                { clave: 'I06', nombre: 'Comunicaciones telefónicas' },
                { clave: 'I07', nombre: 'Comunicaciones satelitales' },
                { clave: 'I08', nombre: 'Otra maquinaria y equipo' },
                { clave: 'D01', nombre: 'Honorarios médicos, dentales y gastos hospitalarios' },
                { clave: 'D02', nombre: 'Gastos médicos por incapacidad o discapacidad' },
                { clave: 'D03', nombre: 'Gastos funerales' },
                { clave: 'D04', nombre: 'Donativos' },
                { clave: 'D05', nombre: 'Intereses reales pagados por créditos hipotecarios' },
                { clave: 'D06', nombre: 'Aportaciones voluntarias al SAR' },
                { clave: 'D07', nombre: 'Primas por seguros de gastos médicos' },
                { clave: 'D08', nombre: 'Gastos de transportación escolar obligatoria' },
                { clave: 'D09', nombre: 'Depósitos en cuentas para el ahorro / planes de pensiones' },
                { clave: 'D10', nombre: 'Pagos por servicios educativos (colegiaturas)' },
                { clave: 'S01', nombre: 'Sin efectos fiscales' },
                { clave: 'CP01', nombre: 'Pagos' },
            ],

            // Espejo de App\Http\Requests\Api\ClientFiscalDataRequest::matrizUsosCfdi()
            matrizUsosPorRegimen: {
                '601': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '603': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '605': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '606': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '607': ['CP01','S01'],
                '608': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '610': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','S01','CP01'],
                '611': ['CP01','S01'],
                '612': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '614': ['CP01','S01'],
                '615': ['CP01','S01'],
                '616': ['CP01','S01'],
                '620': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '621': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '622': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '623': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'],
                '624': ['CP01','S01'],
                '625': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
                '626': ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'],
            },

            regimenesFisica: ['605','606','607','608','610','611','612','614','615','621','625','626','616'],
            regimenesMoral:  ['601','603','620','622','623','624','616'],
        };
    },
    computed: {
        regimenesFiltrados() {
            const rfc = (this.form.rfc || '').toUpperCase();
            if (rfc.length === 12) return this.catalogoRegimen.filter(r => this.regimenesMoral.includes(r.clave));
            if (rfc.length === 13) return this.catalogoRegimen.filter(r => this.regimenesFisica.includes(r.clave));
            return this.catalogoRegimen;
        },
        usosFiltrados() {
            const compatibles = this.matrizUsosPorRegimen[this.form.regimen_fiscal];
            if (!compatibles) return this.catalogoUsoCfdi;
            return this.catalogoUsoCfdi.filter(u => compatibles.includes(u.clave));
        },
    },
    methods: {
        formVacio() {
            return {
                rfc: '',
                razon_social: '',
                regimen_fiscal: '',
                uso_cfdi: '',
                codigo_postal: '',
                email: '',
                nickname: '',
                is_default: false,
            };
        },
        abrirModal(client, modoSeleccion = false) {
            this.client = client || {};
            this.modoSeleccion = modoSeleccion;
            this.vista = 'lista';
            this.perfilEditando = null;
            this.errores = {};
            this.form = this.formVacio();
            this.perfiles = [];
            this.showModal = true;
            if (!this.client?.id) {
                Swal.fire('Cliente no válido', 'No se pudo identificar el cliente para cargar sus datos fiscales.', 'warning');
                return;
            }
            this.cargar();
        },
        cerrarModal() {
            this.showModal = false;
            this.$emit('closed');
        },
        cargar() {
            if (!this.client?.id) return;
            this.cargando = true;
            axios.get(`/admin/clients/${this.client.id}/fiscal-data`)
                .then(res => { this.perfiles = res.data || []; })
                .catch(() => { Swal.fire('Error', 'No se pudieron cargar los perfiles fiscales', 'error'); })
                .finally(() => { this.cargando = false; });
        },
        usarPerfil(perfil) {
            this.$emit('seleccionado', perfil);
            this.showModal = false;
        },
        abrirFormNuevo() {
            this.perfilEditando = null;
            this.form = this.formVacio();
            this.errores = {};
            this.vista = 'form';
        },
        abrirFormEditar(perfil) {
            this.perfilEditando = perfil;
            this.form = {
                rfc: perfil.rfc,
                razon_social: perfil.razon_social,
                regimen_fiscal: perfil.regimen_fiscal,
                uso_cfdi: perfil.uso_cfdi,
                codigo_postal: perfil.codigo_postal,
                email: perfil.email || '',
                nickname: perfil.nickname || '',
                is_default: !!perfil.is_default,
            };
            this.errores = {};
            this.vista = 'form';
        },
        cancelarForm() {
            this.vista = 'lista';
            this.perfilEditando = null;
            this.errores = {};
        },
        onRfcInput() {
            if (this.form.rfc) this.form.rfc = this.form.rfc.toUpperCase();
        },
        onRegimenChange() {
            const compatibles = this.matrizUsosPorRegimen[this.form.regimen_fiscal];
            if (compatibles && this.form.uso_cfdi && !compatibles.includes(this.form.uso_cfdi)) {
                this.form.uso_cfdi = '';
            }
        },
        validar() {
            this.errores = {};
            const rfcRegex = /^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/i;
            const rfc = (this.form.rfc || '').trim().toUpperCase();

            if (!rfc) this.errores.rfc = 'RFC requerido';
            else if (!rfcRegex.test(rfc)) this.errores.rfc = 'RFC con formato inválido';

            if (!this.form.razon_social || !this.form.razon_social.trim()) {
                this.errores.razon_social = 'Razón social requerida';
            }

            if (!this.form.regimen_fiscal) {
                this.errores.regimen_fiscal = 'Régimen fiscal requerido';
            } else if (rfc.length === 13 && !this.regimenesFisica.includes(this.form.regimen_fiscal)) {
                this.errores.regimen_fiscal = 'Régimen no válido para persona física';
            } else if (rfc.length === 12 && !this.regimenesMoral.includes(this.form.regimen_fiscal)) {
                this.errores.regimen_fiscal = 'Régimen no válido para persona moral';
            }

            if (!this.form.uso_cfdi) {
                this.errores.uso_cfdi = 'Uso CFDI requerido';
            } else if (this.form.regimen_fiscal && this.matrizUsosPorRegimen[this.form.regimen_fiscal]) {
                const compatibles = this.matrizUsosPorRegimen[this.form.regimen_fiscal];
                if (!compatibles.includes(this.form.uso_cfdi)) {
                    this.errores.uso_cfdi = `Uso ${this.form.uso_cfdi} no compatible con régimen ${this.form.regimen_fiscal}`;
                }
            }

            const cp = this.form.codigo_postal || '';
            if (!cp) this.errores.codigo_postal = 'Código postal requerido';
            else if (!/^\d{5}$/.test(cp)) this.errores.codigo_postal = 'Código postal debe ser 5 dígitos';

            if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                this.errores.email = 'Correo inválido';
            }

            return Object.keys(this.errores).length === 0;
        },
        guardar() {
            if (!this.validar()) {
                Swal.fire('Revisa los datos', 'Hay campos con errores', 'warning');
                return;
            }
            if (!this.client?.id) return;

            const payload = { ...this.form, rfc: (this.form.rfc || '').toUpperCase() };
            this.guardando = true;

            const req = this.perfilEditando
                ? axios.put(`/admin/fiscal-data/${this.perfilEditando.id}`, payload)
                : axios.post(`/admin/clients/${this.client.id}/fiscal-data`, payload);

            req.then(res => {
                Swal.fire({
                    icon: 'success',
                    title: this.perfilEditando ? 'Perfil actualizado' : 'Perfil agregado',
                    timer: 1500,
                    showConfirmButton: false,
                });
                if (this.modoSeleccion && !this.perfilEditando) {
                    this.$emit('seleccionado', res.data);
                    this.showModal = false;
                    return;
                }
                this.vista = 'lista';
                this.perfilEditando = null;
                this.cargar();
            }).catch(err => {
                if (err.response?.status === 422 && err.response.data?.errors) {
                    const errs = err.response.data.errors;
                    Object.keys(errs).forEach(k => { this.errores[k] = errs[k][0]; });
                    Swal.fire('Datos inválidos', 'Hay campos con errores', 'warning');
                } else {
                    Swal.fire('Error', 'No se pudo guardar el perfil', 'error');
                }
            }).finally(() => {
                this.guardando = false;
            });
        },
        marcarDefault(perfil) {
            if (perfil.is_default) return;
            axios.patch(`/admin/fiscal-data/${perfil.id}/set-default`)
                .then(() => {
                    Swal.fire({ icon: 'success', title: 'Perfil predeterminado', timer: 1500, showConfirmButton: false });
                    this.cargar();
                })
                .catch(() => { Swal.fire('Error', 'No se pudo cambiar el predeterminado', 'error'); });
        },
        eliminar(perfil) {
            Swal.fire({
                title: 'Eliminar perfil fiscal',
                text: '¿Estás seguro? Si tiene facturas relacionadas, se archivará en lugar de borrarse permanentemente.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
            }).then(result => {
                if (!result.isConfirmed) return;
                axios.delete(`/admin/fiscal-data/${perfil.id}`)
                    .then(res => {
                        Swal.fire({
                            icon: 'success',
                            title: res.data?.soft ? 'Perfil archivado (tenía facturas)' : 'Perfil eliminado',
                            timer: 1800,
                            showConfirmButton: false,
                        });
                        this.cargar();
                    })
                    .catch(() => { Swal.fire('Error', 'No se pudo eliminar el perfil', 'error'); });
            });
        },
        nombreRegimen(clave) {
            return this.catalogoRegimen.find(r => r.clave === clave)?.nombre || clave;
        },
        nombreUso(clave) {
            return this.catalogoUsoCfdi.find(u => u.clave === clave)?.nombre || clave;
        },
    },
};
</script>

<style scoped>
.modal.mostrar {
    display: block !important;
    background-color: rgba(0,0,0,.5);
}
.gap-1 {
    gap: .25rem;
}
</style>
