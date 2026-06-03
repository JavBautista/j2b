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
import { loadFiscalCatalogs } from '../../services/satCatalogs';

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

            // Catálogos SAT: se cargan del endpoint (tablas sat_* en BD) vía services/satCatalogs.js.
            // Cada régimen trae aplica_fisica / aplica_moral para el filtrado por tipo de persona.
            catalogoRegimen: [],

            catalogoUsoCfdi: [],

            // matriz régimen→uso: { regimenClave: [usoClave, ...] } — del endpoint.
            matrizUsosPorRegimen: {},
        };
    },
    created() {
        loadFiscalCatalogs()
            .then(b => {
                this.catalogoRegimen = b.regimenes || [];
                this.catalogoUsoCfdi = b.usos || [];
                this.matrizUsosPorRegimen = b.matriz || {};
            })
            .catch(() => {
                Swal.fire('Error', 'No se pudieron cargar los catálogos fiscales del SAT. Recarga la página e intenta de nuevo.', 'error');
            });
    },
    computed: {
        regimenesFiltrados() {
            const rfc = (this.form.rfc || '').toUpperCase();
            if (rfc.length === 12) return this.catalogoRegimen.filter(r => r.aplica_moral);
            if (rfc.length === 13) return this.catalogoRegimen.filter(r => r.aplica_fisica);
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
            } else {
                const reg = this.catalogoRegimen.find(r => r.clave === this.form.regimen_fiscal);
                if (reg && rfc.length === 13 && !reg.aplica_fisica) {
                    this.errores.regimen_fiscal = 'Régimen no válido para persona física';
                } else if (reg && rfc.length === 12 && !reg.aplica_moral) {
                    this.errores.regimen_fiscal = 'Régimen no válido para persona moral';
                }
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
