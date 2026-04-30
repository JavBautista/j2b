<template>
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0"><i class="fa fa-university text-primary"></i> Cuentas Bancarias de la Tienda</h3>
                <small class="text-muted">Se usan en complementos de pago (CFDI Pagos 2.0) y en el PDF de notas con saldo pendiente.</small>
            </div>
            <button type="button" class="btn btn-success" @click="abrirFormNueva()">
                <i class="fa fa-plus"></i> Nueva cuenta
            </button>
        </div>

        <div v-if="cargando" class="text-center py-5">
            <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
        </div>

        <div v-if="!cargando && cuentas.length === 0" class="text-center py-5 border rounded bg-light">
            <i class="fa fa-university" style="font-size: 56px; opacity: 0.3;"></i>
            <p class="mt-3 mb-1 fs-5">No hay cuentas bancarias registradas</p>
            <p class="text-muted small">Agrega al menos una cuenta para que aparezca en los PDFs de tus notas y se use al timbrar complementos de pago.</p>
            <button type="button" class="btn btn-primary mt-2" @click="abrirFormNueva()">
                <i class="fa fa-plus"></i> Agregar primera cuenta
            </button>
        </div>

        <div v-if="!cargando && cuentas.length > 0" class="row">
            <div class="col-md-6 mb-3" v-for="cuenta in cuentas" :key="cuenta.id">
                <div class="card h-100" :class="{'border-success border-2': cuenta.is_default, 'opacity-75': !cuenta.is_active}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ cuenta.alias }}</strong>
                            <span v-if="cuenta.is_default" class="badge bg-success ms-2">Predeterminada</span>
                            <span v-if="!cuenta.is_active" class="badge bg-secondary ms-2">Inactiva</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Banco:</strong> {{ cuenta.bank_name }}</p>
                        <p class="mb-1"><strong>RFC banco:</strong> {{ cuenta.bank_rfc }}</p>
                        <p class="mb-1">
                            <strong>CLABE:</strong>
                            <code>{{ formatearClabe(cuenta.clabe) }}</code>
                        </p>
                        <p v-if="cuenta.account_number" class="mb-1"><strong>Núm. cuenta:</strong> {{ cuenta.account_number }}</p>
                        <p class="mb-1"><strong>Titular:</strong> {{ cuenta.holder_name }}</p>
                        <p v-if="cuenta.notes" class="mb-1 text-muted small"><i class="fa fa-info-circle"></i> {{ cuenta.notes }}</p>

                        <div class="mt-3 d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-outline-primary btn-sm" @click="abrirFormEditar(cuenta)">
                                <i class="fa fa-pencil"></i> Editar
                            </button>
                            <button v-if="!cuenta.is_default" type="button" class="btn btn-outline-warning btn-sm" @click="marcarDefault(cuenta)">
                                <i class="fa fa-star-o"></i> Marcar default
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" @click="eliminar(cuenta)">
                                <i class="fa fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal alta/edición -->
        <div class="modal fade" tabindex="-1" :class="{'mostrar':showModal}" role="dialog" style="display: none;">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h4 class="modal-title">
                            <i class="fa fa-university"></i>
                            {{ editando ? 'Editar cuenta bancaria' : 'Nueva cuenta bancaria' }}
                        </h4>
                        <button type="button" class="close text-white" @click="cerrarModal()" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Alias <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="form.alias" maxlength="80" placeholder="Ej. BBVA Principal">
                                <div class="text-danger small" v-if="errores.alias">{{ errores.alias }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Banco <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.bank_code" @change="onBancoCambio">
                                    <option value="">— Selecciona —</option>
                                    <option v-for="b in bancos" :key="b.code" :value="b.code">{{ b.code }} — {{ b.name }}</option>
                                </select>
                                <div class="text-danger small" v-if="errores.bank_code">{{ errores.bank_code }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre del banco <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="form.bank_name" maxlength="100" :readonly="form.bank_code !== '999' && form.bank_code !== ''">
                                <small v-if="form.bank_code === '999'" class="text-muted">Editable porque seleccionaste "Otro / Extranjero".</small>
                                <div class="text-danger small" v-if="errores.bank_name">{{ errores.bank_name }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">RFC del banco <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="form.bank_rfc" maxlength="13" :readonly="form.bank_code !== '999' && form.bank_code !== ''" style="text-transform: uppercase">
                                <small v-if="form.bank_code === '999'" class="text-muted">Si banco extranjero usa <code>XEXX010101000</code>.</small>
                                <div class="text-danger small" v-if="errores.bank_rfc">{{ errores.bank_rfc }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">CLABE (18 dígitos) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="form.clabe" maxlength="18" @input="onClabeInput" :class="{'is-invalid': errores.clabe, 'is-valid': clabeOk}">
                                <small class="text-muted" v-if="!errores.clabe && !clabeOk">Se valida con el algoritmo oficial al capturar los 18 dígitos.</small>
                                <small class="text-success" v-if="clabeOk"><i class="fa fa-check"></i> CLABE válida</small>
                                <div class="text-danger small" v-if="errores.clabe">{{ errores.clabe }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Núm. cuenta corto (opcional)</label>
                                <input type="text" class="form-control" v-model="form.account_number" maxlength="20" placeholder="Ej. 0123456789">
                                <small class="text-muted">Algunos bancos lo usan en estados de cuenta.</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Titular de la cuenta <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="form.holder_name" maxlength="150" placeholder="Como aparece en el banco">
                                <div class="text-danger small" v-if="errores.holder_name">{{ errores.holder_name }}</div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Notas internas (opcional)</label>
                                <textarea class="form-control" v-model="form.notes" rows="2" maxlength="500"></textarea>
                            </div>

                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="chkDefault" v-model="form.is_default">
                                    <label class="form-check-label" for="chkDefault">
                                        Marcar como predeterminada
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="chkActive" v-model="form.is_active">
                                    <label class="form-check-label" for="chkActive">
                                        Activa (aparece en PDFs y selección de complementos)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarModal()">
                            <i class="fa fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" :disabled="guardando" @click="guardar()">
                            <i class="fa" :class="guardando ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                            {{ editando ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { BANCOS, findBanco } from '../../catalogos/bancos.js';

export default {
    data() {
        return {
            cargando: true,
            cuentas: [],
            bancos: BANCOS,
            showModal: false,
            editando: null,
            guardando: false,
            form: this.formVacio(),
            errores: {},
            clabeOk: false,
        };
    },

    mounted() {
        this.cargar();
    },

    methods: {
        formVacio() {
            return {
                alias: '',
                bank_code: '',
                bank_name: '',
                bank_rfc: '',
                clabe: '',
                account_number: '',
                holder_name: '',
                notes: '',
                is_default: false,
                is_active: true,
            };
        },

        formatearClabe(clabe) {
            if (!clabe) return '';
            return clabe.replace(/(\d{4})(\d{4})(\d{4})(\d{4})(\d{2})/, '$1 $2 $3 $4 $5');
        },

        async cargar() {
            this.cargando = true;
            try {
                const res = await axios.get('/admin/configuracion/cuentas-bancarias/data');
                if (res.data.ok) {
                    this.cuentas = res.data.accounts || [];
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'No se pudieron cargar las cuentas bancarias.', 'error');
            } finally {
                this.cargando = false;
            }
        },

        abrirFormNueva() {
            this.editando = null;
            this.form = this.formVacio();
            this.errores = {};
            this.clabeOk = false;
            this.showModal = true;
        },

        abrirFormEditar(cuenta) {
            this.editando = cuenta.id;
            this.form = {
                alias: cuenta.alias,
                bank_code: cuenta.bank_code,
                bank_name: cuenta.bank_name,
                bank_rfc: cuenta.bank_rfc,
                clabe: cuenta.clabe,
                account_number: cuenta.account_number || '',
                holder_name: cuenta.holder_name,
                notes: cuenta.notes || '',
                is_default: !!cuenta.is_default,
                is_active: !!cuenta.is_active,
            };
            this.errores = {};
            this.clabeOk = this.validarClabe(cuenta.clabe);
            this.showModal = true;
        },

        cerrarModal() {
            this.showModal = false;
        },

        onBancoCambio() {
            const b = findBanco(this.form.bank_code);
            if (!b) return;
            if (b.code === '999') {
                this.form.bank_name = '';
                this.form.bank_rfc = '';
            } else {
                this.form.bank_name = b.name;
                this.form.bank_rfc = b.rfc;
            }
        },

        onClabeInput() {
            this.form.clabe = (this.form.clabe || '').replace(/\D/g, '').slice(0, 18);
            this.errores.clabe = '';
            this.clabeOk = false;
            if (this.form.clabe.length === 18) {
                if (this.validarClabe(this.form.clabe)) {
                    this.clabeOk = true;
                } else {
                    this.errores.clabe = 'CLABE inválida (dígito de control incorrecto).';
                }
            }
        },

        // Algoritmo CLABE oficial (espejo del backend ShopBankAccountRequest::clabeValida)
        validarClabe(clabe) {
            if (!/^\d{18}$/.test(clabe)) return false;
            const pesos = [3,7,1,3,7,1,3,7,1,3,7,1,3,7,1,3,7];
            let suma = 0;
            for (let i = 0; i < 17; i++) {
                suma += (parseInt(clabe[i], 10) * pesos[i]) % 10;
            }
            const control = (10 - (suma % 10)) % 10;
            return control === parseInt(clabe[17], 10);
        },

        validar() {
            const e = {};
            if (!this.form.alias) e.alias = 'Requerido';
            if (!this.form.bank_code) e.bank_code = 'Selecciona un banco';
            if (!this.form.bank_name) e.bank_name = 'Requerido';
            if (!this.form.bank_rfc) e.bank_rfc = 'Requerido';
            else if (!/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/i.test(this.form.bank_rfc)) e.bank_rfc = 'RFC inválido';
            if (!this.form.clabe) e.clabe = 'Requerido';
            else if (!/^\d{18}$/.test(this.form.clabe)) e.clabe = 'Debe tener 18 dígitos';
            else if (!this.validarClabe(this.form.clabe)) e.clabe = 'CLABE inválida (dígito de control incorrecto)';
            if (!this.form.holder_name) e.holder_name = 'Requerido';
            this.errores = e;
            return Object.keys(e).length === 0;
        },

        async guardar() {
            if (!this.validar()) return;
            this.guardando = true;
            try {
                const payload = { ...this.form, bank_rfc: this.form.bank_rfc.toUpperCase() };
                let res;
                if (this.editando) {
                    res = await axios.put(`/admin/configuracion/cuentas-bancarias/${this.editando}`, payload);
                } else {
                    res = await axios.post('/admin/configuracion/cuentas-bancarias', payload);
                }
                if (res.data.ok) {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.data.message, showConfirmButton: false, timer: 2200 });
                    this.showModal = false;
                    await this.cargar();
                }
            } catch (err) {
                if (err.response?.status === 422 && err.response?.data?.errors) {
                    const flat = {};
                    for (const [k, arr] of Object.entries(err.response.data.errors)) {
                        flat[k] = Array.isArray(arr) ? arr[0] : arr;
                    }
                    this.errores = flat;
                } else {
                    Swal.fire('Error', err.response?.data?.message || 'No se pudo guardar.', 'error');
                }
            } finally {
                this.guardando = false;
            }
        },

        async marcarDefault(cuenta) {
            const r = await Swal.fire({
                title: '¿Marcar como predeterminada?',
                text: `"${cuenta.alias}" será la cuenta default. Las demás dejarán de ser default.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, marcar',
                cancelButtonText: 'Cancelar',
            });
            if (!r.isConfirmed) return;
            try {
                const res = await axios.patch(`/admin/configuracion/cuentas-bancarias/${cuenta.id}/set-default`);
                if (res.data.ok) {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.data.message, showConfirmButton: false, timer: 2000 });
                    await this.cargar();
                }
            } catch (err) {
                Swal.fire('Error', err.response?.data?.message || 'No se pudo marcar.', 'error');
            }
        },

        async eliminar(cuenta) {
            const r = await Swal.fire({
                title: '¿Eliminar cuenta?',
                html: `<strong>${cuenta.alias}</strong><br>${cuenta.bank_name}<br><code>${this.formatearClabe(cuenta.clabe)}</code>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            });
            if (!r.isConfirmed) return;
            try {
                const res = await axios.delete(`/admin/configuracion/cuentas-bancarias/${cuenta.id}`);
                if (res.data.ok) {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.data.message, showConfirmButton: false, timer: 2000 });
                    await this.cargar();
                }
            } catch (err) {
                Swal.fire('Error', err.response?.data?.message || 'No se pudo eliminar.', 'error');
            }
        },
    },
};
</script>

<style scoped>
.modal.mostrar {
    display: block !important;
    background-color: rgba(0,0,0,0.5);
}
code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    color: #495057;
}
</style>
