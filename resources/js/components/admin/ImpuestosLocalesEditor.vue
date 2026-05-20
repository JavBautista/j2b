<template>
    <div class="card mb-3">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <strong><i class="fa fa-map-marker me-1 text-info"></i> Impuestos Locales (estatales/municipales)</strong>
            <small class="text-muted">Cedular, ISH, ISA, Turismo, Espectáculos, etc.</small>
        </div>
        <div class="card-body py-3">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="chkImplocal" v-model="activo">
                <label class="form-check-label" for="chkImplocal">
                    <strong>Esta factura lleva impuestos locales</strong>
                </label>
            </div>

            <div v-if="activo">
                <div class="alert alert-warning py-2 small mb-3">
                    <i class="fa fa-exclamation-triangle me-1"></i>
                    Las facturas con impuestos locales se timbran por un pipeline alterno.
                    Limitaciones actuales: solo PUE, sin cortesías, sin descuento global, sin retenciones federales mezcladas.
                </div>

                <h6 class="mt-2"><i class="fa fa-minus-circle text-warning me-1"></i> Retenciones locales</h6>
                <table class="table table-sm table-bordered mb-2" v-if="retenciones.length">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 200px">Nombre</th>
                            <th style="width: 90px" class="text-end">Tasa %</th>
                            <th class="text-end">Base</th>
                            <th class="text-end">Importe</th>
                            <th style="width: 40px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in retenciones" :key="'ret-' + i">
                            <td>
                                <input type="text" class="form-control form-control-sm"
                                       v-model="r.nombre" list="implocal-nombres"
                                       maxlength="100" placeholder="CEDULAR / ISH / etc.">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end"
                                       v-model.number="r.tasa_porcentaje" step="0.01" min="0" max="100"
                                       @input="recalcular(r)">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end"
                                       v-model.number="r.base" step="0.01" min="0"
                                       @input="recalcular(r)">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end"
                                       v-model.number="r.importe" step="0.01" min="0">
                            </td>
                            <td class="text-center">
                                <button class="btn btn-xs btn-outline-danger" @click="eliminar('retencion', i)">
                                    <i class="fa fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button class="btn btn-sm btn-outline-warning mb-3" @click="agregar('retencion')">
                    <i class="fa fa-plus"></i> Agregar retención local
                </button>

                <h6 class="mt-2"><i class="fa fa-plus-circle text-info me-1"></i> Traslados locales</h6>
                <table class="table table-sm table-bordered mb-2" v-if="traslados.length">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 200px">Nombre</th>
                            <th style="width: 90px" class="text-end">Tasa %</th>
                            <th class="text-end">Base</th>
                            <th class="text-end">Importe</th>
                            <th style="width: 40px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(t, i) in traslados" :key="'tras-' + i">
                            <td>
                                <input type="text" class="form-control form-control-sm"
                                       v-model="t.nombre" list="implocal-nombres"
                                       maxlength="100" placeholder="ISH / TURISMO / etc.">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end"
                                       v-model.number="t.tasa_porcentaje" step="0.01" min="0" max="100"
                                       @input="recalcular(t)">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end"
                                       v-model.number="t.base" step="0.01" min="0"
                                       @input="recalcular(t)">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end"
                                       v-model.number="t.importe" step="0.01" min="0">
                            </td>
                            <td class="text-center">
                                <button class="btn btn-xs btn-outline-danger" @click="eliminar('traslado', i)">
                                    <i class="fa fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button class="btn btn-sm btn-outline-info mb-3" @click="agregar('traslado')">
                    <i class="fa fa-plus"></i> Agregar traslado local
                </button>

                <datalist id="implocal-nombres">
                    <option value="CEDULAR"></option>
                    <option value="ISH"></option>
                    <option value="ISA"></option>
                    <option value="TURISMO"></option>
                    <option value="ESPECTACULOS"></option>
                </datalist>

                <div v-if="defaults && defaults.length" class="mt-2">
                    <small class="text-muted d-block mb-1">Sugerencias rápidas:</small>
                    <button v-for="d in defaults" :key="d.id"
                            class="btn btn-xs btn-outline-secondary me-1 mb-1"
                            @click="aplicarDefault(d)">
                        <i class="fa fa-plus"></i> {{ d.nombre }} {{ d.tasa_porcentaje }}%
                        <small class="text-muted">({{ d.tipo === 'retencion' ? 'ret' : 'tras' }})</small>
                    </button>
                </div>

                <div v-if="errores.length" class="alert alert-danger py-2 mt-3 mb-0 small">
                    <ul class="mb-0">
                        <li v-for="(e, i) in errores" :key="i">{{ e }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        modelValue: { type: Array, default: () => [] },
        baseSugerida: { type: Number, default: 0 },
        defaults: { type: Array, default: () => [] },
    },
    emits: ['update:modelValue'],
    data() {
        return {
            activo: (this.modelValue || []).length > 0,
            retenciones: [],
            traslados: [],
            suppressEmit: false,
        };
    },
    computed: {
        impuestos() {
            return [...this.retenciones, ...this.traslados];
        },
        errores() {
            const errs = [];
            if (this.activo && this.impuestos.length === 0) {
                errs.push('Si activas impuestos locales debes agregar al menos una retención o un traslado.');
            }
            this.impuestos.forEach((i, idx) => {
                if (!i.nombre || i.nombre.length < 3) errs.push(`Renglón ${idx + 1}: nombre debe tener al menos 3 caracteres`);
                if (i.tasa_porcentaje < 0 || i.tasa_porcentaje > 100) errs.push(`Renglón ${idx + 1}: tasa fuera de rango 0-100`);
                if (i.importe < 0) errs.push(`Renglón ${idx + 1}: importe no puede ser negativo`);
            });
            return errs;
        },
    },
    watch: {
        activo(v) {
            if (!v) {
                this.retenciones = [];
                this.traslados = [];
            }
            this.emitir();
        },
        impuestos: {
            deep: true,
            handler() {
                if (this.suppressEmit) return;
                this.emitir();
            },
        },
        modelValue(v) {
            this.suppressEmit = true;
            this.cargarDesdeModel(v);
            this.$nextTick(() => { this.suppressEmit = false; });
        },
    },
    mounted() {
        this.cargarDesdeModel(this.modelValue);
    },
    methods: {
        cargarDesdeModel(v) {
            this.retenciones = (v || []).filter(x => x.tipo === 'retencion').map(x => ({ ...x }));
            this.traslados = (v || []).filter(x => x.tipo === 'traslado').map(x => ({ ...x }));
            if (this.retenciones.length + this.traslados.length > 0) {
                this.activo = true;
            }
        },
        agregar(tipo) {
            const linea = {
                tipo,
                nombre: '',
                tasa_porcentaje: 0,
                base: this.baseSugerida || 0,
                importe: 0,
            };
            if (tipo === 'retencion') this.retenciones.push(linea);
            else this.traslados.push(linea);
        },
        eliminar(tipo, idx) {
            if (tipo === 'retencion') this.retenciones.splice(idx, 1);
            else this.traslados.splice(idx, 1);
        },
        recalcular(linea) {
            const base = parseFloat(linea.base) || 0;
            const tasa = parseFloat(linea.tasa_porcentaje) || 0;
            linea.importe = Math.round(base * tasa) / 100;
        },
        aplicarDefault(d) {
            const linea = {
                tipo: d.tipo,
                nombre: d.nombre,
                tasa_porcentaje: parseFloat(d.tasa_porcentaje),
                base: this.baseSugerida || 0,
                importe: 0,
            };
            this.recalcular(linea);
            if (d.tipo === 'retencion') this.retenciones.push(linea);
            else this.traslados.push(linea);
        },
        emitir() {
            this.$emit('update:modelValue', this.activo ? this.impuestos : []);
        },
    },
};
</script>
