<template>
    <div>
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item" v-for="t in tabs" :key="t.key">
                <a href="#" class="nav-link" :class="{ active: tabActivo === t.key }"
                   @click.prevent="tabActivo = t.key">
                    <i class="fa" :class="t.icon"></i> {{ t.label }}
                    <span class="badge bg-secondary ms-1">{{ (catalogos[t.key] || []).length }}</span>
                </a>
            </li>
        </ul>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted">
                Activa/desactiva con el interruptor <strong>Vigente</strong>. Los cambios aplican al instante en web e Ionic.
                <span v-if="tabActivo === 'regimenes'">La matriz régimen→uso se administra desde el seeder.</span>
            </small>
            <button class="btn btn-success btn-sm" @click="abrirAlta">
                <i class="fa fa-plus"></i> Agregar clave
            </button>
        </div>

        <div v-if="cargando" class="text-center py-4">
            <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
        </div>

        <div v-else class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:90px">Clave</th>
                        <th>Descripción</th>
                        <th v-if="tieneFlagsPersona" class="text-center" style="width:90px">Física</th>
                        <th v-if="tieneFlagsPersona" class="text-center" style="width:90px">Moral</th>
                        <th v-if="tabActivo === 'regimenes'" class="text-center" style="width:90px">Emisor</th>
                        <th class="text-center" style="width:90px">Vigente</th>
                        <th class="text-end" style="width:80px">Editar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in itemsActivos" :key="item.id" :class="{ 'text-muted': !item.vigente }">
                        <td><code>{{ item.code }}</code></td>
                        <td>{{ item.description }}</td>
                        <td v-if="tieneFlagsPersona" class="text-center">
                            <input type="checkbox" class="form-check-input" v-model="item.aplica_fisica"
                                   @change="guardarInline(item)">
                        </td>
                        <td v-if="tieneFlagsPersona" class="text-center">
                            <input type="checkbox" class="form-check-input" v-model="item.aplica_moral"
                                   @change="guardarInline(item)">
                        </td>
                        <td v-if="tabActivo === 'regimenes'" class="text-center">
                            <input type="checkbox" class="form-check-input" v-model="item.aplica_emisor"
                                   @change="guardarInline(item)">
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input type="checkbox" class="form-check-input" role="switch"
                                       v-model="item.vigente" @change="guardarInline(item)">
                            </div>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-outline-primary btn-sm" @click="abrirEditar(item)">
                                <i class="fa fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!itemsActivos.length">
                        <td colspan="7" class="text-center text-muted py-3">Sin registros.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal alta / edición -->
        <div class="modal-backdrop-custom" :class="{ mostrar: showModal }" @click.self="cerrarModal">
            <div class="modal-card">
                <div class="modal-card-header">
                    <h5 class="mb-0">
                        <i class="fa" :class="modoAlta ? 'fa-plus' : 'fa-edit'"></i>
                        {{ modoAlta ? 'Agregar clave' : 'Editar' }} — {{ tabLabel }}
                    </h5>
                    <button type="button" class="btn-close" @click="cerrarModal"></button>
                </div>
                <div class="modal-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Clave SAT</label>
                        <input type="text" class="form-control form-control-sm" v-model="form.code"
                               :disabled="!modoAlta" placeholder="Ej. 626, G03, 03, PUE">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Descripción</label>
                        <input type="text" class="form-control form-control-sm" v-model="form.description"
                               placeholder="Descripción oficial SAT">
                    </div>
                    <div class="row" v-if="tieneFlagsPersona">
                        <div class="col-6 mb-2 form-check">
                            <input type="checkbox" class="form-check-input" id="f_fisica" v-model="form.aplica_fisica">
                            <label class="form-check-label small" for="f_fisica">Aplica Persona Física</label>
                        </div>
                        <div class="col-6 mb-2 form-check">
                            <input type="checkbox" class="form-check-input" id="f_moral" v-model="form.aplica_moral">
                            <label class="form-check-label small" for="f_moral">Aplica Persona Moral</label>
                        </div>
                    </div>
                    <div class="mb-2 form-check" v-if="tabActivo === 'regimenes'">
                        <input type="checkbox" class="form-check-input" id="f_emisor" v-model="form.aplica_emisor">
                        <label class="form-check-label small" for="f_emisor">Aplica como Emisor (régimen de la tienda)</label>
                    </div>
                    <div class="mb-2 form-check form-switch">
                        <input type="checkbox" class="form-check-input" role="switch" id="f_vigente" v-model="form.vigente">
                        <label class="form-check-label small" for="f_vigente">Vigente (se ofrece en los selects)</label>
                    </div>
                </div>
                <div class="modal-card-footer">
                    <button class="btn btn-secondary btn-sm" @click="cerrarModal" :disabled="guardando">Cancelar</button>
                    <button class="btn btn-primary btn-sm" @click="guardarModal" :disabled="guardando">
                        <i class="fa" :class="guardando ? 'fa-spinner fa-spin' : 'fa-save'"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'SatCatalogsComponent',
    data() {
        return {
            tabs: [
                { key: 'regimenes', label: 'Régimen Fiscal', icon: 'fa-building' },
                { key: 'usos', label: 'Uso CFDI', icon: 'fa-file-invoice' },
                { key: 'formas_pago', label: 'Forma de Pago', icon: 'fa-money-bill' },
                { key: 'metodos_pago', label: 'Método de Pago', icon: 'fa-credit-card' },
            ],
            tabActivo: 'regimenes',
            catalogos: { regimenes: [], usos: [], formas_pago: [], metodos_pago: [] },
            cargando: false,
            showModal: false,
            modoAlta: false,
            guardando: false,
            form: this.formVacio(),
        };
    },
    created() {
        this.cargarDatos();
    },
    computed: {
        itemsActivos() {
            return this.catalogos[this.tabActivo] || [];
        },
        tieneFlagsPersona() {
            return this.tabActivo === 'regimenes' || this.tabActivo === 'usos';
        },
        tabLabel() {
            return (this.tabs.find(t => t.key === this.tabActivo) || {}).label || '';
        },
        // tab (key con _) → segmento de URL (con -)
        tipoUrl() {
            return this.tabActivo.replace('_', '-');
        },
    },
    methods: {
        formVacio() {
            return {
                id: null, code: '', description: '',
                aplica_fisica: false, aplica_moral: false, aplica_emisor: false, vigente: true,
            };
        },
        cargarDatos() {
            this.cargando = true;
            axios.get('/superadmin/sat-catalogs/data')
                .then(res => { this.catalogos = res.data; })
                .catch(() => Swal.fire('Error', 'No se pudieron cargar los catálogos', 'error'))
                .finally(() => { this.cargando = false; });
        },
        payloadDe(item) {
            return {
                code: item.code,
                description: item.description,
                aplica_fisica: !!item.aplica_fisica,
                aplica_moral: !!item.aplica_moral,
                aplica_emisor: !!item.aplica_emisor,
                vigente: !!item.vigente,
            };
        },
        guardarInline(item) {
            axios.put(`/superadmin/sat-catalogs/${this.tipoUrl}/${item.id}`, this.payloadDe(item))
                .catch(() => {
                    Swal.fire('Error', 'No se pudo guardar el cambio', 'error');
                    this.cargarDatos();
                });
        },
        abrirAlta() {
            this.modoAlta = true;
            this.form = this.formVacio();
            this.showModal = true;
        },
        abrirEditar(item) {
            this.modoAlta = false;
            this.form = { ...this.formVacio(), ...item };
            this.showModal = true;
        },
        cerrarModal() {
            this.showModal = false;
        },
        guardarModal() {
            if (!this.form.code || !this.form.description) {
                Swal.fire('Faltan datos', 'Clave y descripción son obligatorias', 'warning');
                return;
            }
            this.guardando = true;
            const req = this.modoAlta
                ? axios.post(`/superadmin/sat-catalogs/${this.tipoUrl}`, this.payloadDe(this.form))
                : axios.put(`/superadmin/sat-catalogs/${this.tipoUrl}/${this.form.id}`, this.payloadDe(this.form));
            req.then(() => {
                Swal.fire({ icon: 'success', title: 'Guardado', timer: 1200, showConfirmButton: false });
                this.showModal = false;
                this.cargarDatos();
            }).catch(err => {
                const msg = err.response?.data?.message || 'No se pudo guardar';
                Swal.fire('Error', msg, 'error');
            }).finally(() => { this.guardando = false; });
        },
    },
};
</script>

<style scoped>
.modal-backdrop-custom {
    position: fixed; inset: 0; background: rgba(0,0,0,.5);
    display: none; align-items: center; justify-content: center; z-index: 1060;
}
.modal-backdrop-custom.mostrar { display: flex; }
.modal-card {
    background: #fff; border-radius: .5rem; width: 100%; max-width: 480px;
    box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.3);
}
.modal-card-header, .modal-card-footer {
    padding: .75rem 1rem; display: flex; align-items: center;
}
.modal-card-header { justify-content: space-between; border-bottom: 1px solid #dee2e6; }
.modal-card-footer { justify-content: flex-end; gap: .5rem; border-top: 1px solid #dee2e6; }
.modal-card-body { padding: 1rem; }
</style>
