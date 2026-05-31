<template>
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0"><i class="fa fa-percent text-primary"></i> Tasas de Impuesto</h3>
                <small class="text-muted">Define las tasas que podrás elegir al crear una nota (ej. IVA 16%, Frontera 8%, Exento). La predeterminada se usa por defecto.</small>
            </div>
            <button type="button" class="btn btn-success" @click="abrirFormNueva()">
                <i class="fa fa-plus"></i> Nueva tasa
            </button>
        </div>

        <div v-if="cargando" class="text-center py-5">
            <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
        </div>

        <div v-if="!cargando && rates.length === 0" class="text-center py-5 border rounded bg-light">
            <i class="fa fa-percent" style="font-size: 56px; opacity: 0.3;"></i>
            <p class="mt-3 mb-1 fs-5">No hay tasas registradas</p>
            <p class="text-muted small">Agrega al menos una tasa para poder facturar y crear notas.</p>
            <button type="button" class="btn btn-primary mt-2" @click="abrirFormNueva()">
                <i class="fa fa-plus"></i> Agregar primera tasa
            </button>
        </div>

        <div v-if="!cargando && rates.length > 0" class="row">
            <div class="col-md-4 mb-3" v-for="rate in rates" :key="rate.id">
                <div class="card h-100" :class="{'border-success border-2': rate.is_default, 'opacity-75': !rate.active}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong class="fs-5">{{ rate.name }}</strong><br>
                                <span class="fs-3 fw-bold text-primary">{{ formatRate(rate.rate) }}%</span>
                            </div>
                            <div class="text-end">
                                <span v-if="rate.is_default" class="badge bg-success d-block mb-1">Predeterminada</span>
                                <span v-if="!rate.active" class="badge bg-secondary d-block">Inactiva</span>
                            </div>
                        </div>

                        <div class="mt-3 d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-outline-primary btn-sm" @click="abrirFormEditar(rate)">
                                <i class="fa fa-pencil"></i> Editar
                            </button>
                            <button v-if="!rate.is_default && rate.active" type="button" class="btn btn-outline-warning btn-sm" @click="marcarDefault(rate)">
                                <i class="fa fa-star-o"></i> Default
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" @click="eliminar(rate)">
                                <i class="fa fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal alta/edición -->
        <div class="modal fade" tabindex="-1" :class="{'mostrar':showModal}" role="dialog" style="display: none;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h4 class="modal-title">
                            <i class="fa fa-percent"></i>
                            {{ editando ? 'Editar tasa' : 'Nueva tasa' }}
                        </h4>
                        <button type="button" class="close text-white" @click="cerrarModal()" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Presets rápidos -->
                        <div class="alert alert-light border mb-3">
                            <small class="d-block mb-2 fw-bold"><i class="fa fa-magic text-primary"></i> Rápido:</small>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="aplicarPreset('IVA 16%', 16)">México IVA 16%</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" @click="aplicarPreset('IVA Frontera 8%', 8)">Frontera 8%</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="aplicarPreset('Exento', 0)">Exento 0%</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.name" maxlength="30" placeholder="Ej. IVA 16%, Frontera 8%">
                            <div class="text-danger small" v-if="errores.name">{{ errores.name }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tasa (%) <span class="text-danger">*</span></label>
                            <div class="input-group" style="max-width: 180px;">
                                <input type="number" class="form-control" v-model="form.rate" min="0" max="99.99" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Coloca 0 para una tasa exenta.</small>
                            <div class="text-danger small" v-if="errores.rate">{{ errores.rate }}</div>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="chkDefaultTax" v-model="form.is_default">
                            <label class="form-check-label" for="chkDefaultTax">Marcar como predeterminada</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkActiveTax" v-model="form.active">
                            <label class="form-check-label" for="chkActiveTax">Activa (seleccionable al crear notas)</label>
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
export default {
    data() {
        return {
            cargando: true,
            rates: [],
            showModal: false,
            editando: null,
            guardando: false,
            form: this.formVacio(),
            errores: {},
        };
    },

    mounted() {
        this.cargar();
    },

    methods: {
        formVacio() {
            return {
                name: '',
                rate: 16,
                is_default: false,
                active: true,
            };
        },

        formatRate(rate) {
            const n = parseFloat(rate);
            return Number.isInteger(n) ? n.toString() : n.toString().replace(/\.?0+$/, '');
        },

        aplicarPreset(name, rate) {
            this.form.name = name;
            this.form.rate = rate;
        },

        async cargar() {
            this.cargando = true;
            try {
                const res = await axios.get('/admin/configuracion/tasas-impuesto/data');
                if (res.data.ok) {
                    this.rates = res.data.rates || [];
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'No se pudieron cargar las tasas de impuesto.', 'error');
            } finally {
                this.cargando = false;
            }
        },

        abrirFormNueva() {
            this.editando = null;
            this.form = this.formVacio();
            this.errores = {};
            this.showModal = true;
        },

        abrirFormEditar(rate) {
            this.editando = rate.id;
            this.form = {
                name: rate.name,
                rate: parseFloat(rate.rate),
                is_default: !!rate.is_default,
                active: !!rate.active,
            };
            this.errores = {};
            this.showModal = true;
        },

        cerrarModal() {
            this.showModal = false;
        },

        validar() {
            const e = {};
            if (!this.form.name) e.name = 'Requerido';
            const r = parseFloat(this.form.rate);
            if (this.form.rate === '' || isNaN(r)) e.rate = 'Requerido';
            else if (r < 0 || r > 99.99) e.rate = 'Debe estar entre 0 y 99.99';
            this.errores = e;
            return Object.keys(e).length === 0;
        },

        async guardar() {
            if (!this.validar()) return;
            this.guardando = true;
            try {
                const payload = { ...this.form, rate: parseFloat(this.form.rate) };
                let res;
                if (this.editando) {
                    res = await axios.put(`/admin/configuracion/tasas-impuesto/${this.editando}`, payload);
                } else {
                    res = await axios.post('/admin/configuracion/tasas-impuesto', payload);
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

        async marcarDefault(rate) {
            const r = await Swal.fire({
                title: '¿Marcar como predeterminada?',
                text: `"${rate.name}" se preseleccionará al crear notas nuevas.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, marcar',
                cancelButtonText: 'Cancelar',
            });
            if (!r.isConfirmed) return;
            try {
                const res = await axios.patch(`/admin/configuracion/tasas-impuesto/${rate.id}/set-default`);
                if (res.data.ok) {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.data.message, showConfirmButton: false, timer: 2000 });
                    await this.cargar();
                }
            } catch (err) {
                Swal.fire('Error', err.response?.data?.message || 'No se pudo marcar.', 'error');
            }
        },

        async eliminar(rate) {
            const r = await Swal.fire({
                title: '¿Eliminar tasa?',
                html: `<strong>${rate.name}</strong> (${this.formatRate(rate.rate)}%)<br><small class="text-muted">Las notas ya creadas conservan su tasa; no se ven afectadas.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            });
            if (!r.isConfirmed) return;
            try {
                const res = await axios.delete(`/admin/configuracion/tasas-impuesto/${rate.id}`);
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
</style>
