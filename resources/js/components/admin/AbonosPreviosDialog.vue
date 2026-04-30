<template>
    <Teleport to="body">
    <div v-if="show" class="modal d-block abonos-previos-overlay" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fa fa-exclamation-triangle"></i>
                        Abonos previos sin forma de pago
                    </h5>
                    <button type="button" class="btn-close" @click="cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Esta nota tiene <strong>{{ abonos.length }}</strong> abono(s) registrado(s) sin forma de pago SAT.
                        El SAT no acepta forma "99 - Por definir" en complementos. Decide qué hacer:
                    </p>

                    <!-- Lista de abonos previos -->
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th class="text-end">Monto</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(a, i) in abonos" :key="a.id">
                                <td>{{ i + 1 }}</td>
                                <td>{{ a.payment_date }}</td>
                                <td class="text-end">${{ formatNumber(a.amount) }}</td>
                                <td>{{ a.payment_type }}</td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Total:</td>
                                <td class="text-end">${{ formatNumber(totalAbonos) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Estrategia -->
                    <label class="form-label fw-bold">Estrategia</label>
                    <div class="d-flex gap-3 mb-3 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="estSep" value="separar" v-model="estrategia">
                            <label class="form-check-label" for="estSep">
                                <strong>Un complemento por abono</strong>
                                <small class="d-block text-muted">Respeta fechas y montos individuales (recomendado para auditoría).</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="estCons" value="consolidar" v-model="estrategia">
                            <label class="form-check-label" for="estCons">
                                <strong>Un solo complemento consolidado</strong>
                                <small class="d-block text-muted">Suma todos los abonos en un solo CFDI de pago.</small>
                            </label>
                        </div>
                    </div>

                    <!-- ESTRATEGIA SEPARAR -->
                    <div v-if="estrategia === 'separar'" class="border rounded p-3 bg-light">
                        <label class="form-label small">Forma de pago (aplicada a TODOS los abonos)</label>
                        <select class="form-select form-select-sm mb-2" v-model="separar.global.payment_method" @change="aplicarGlobal">
                            <option v-for="f in formasSAT" :key="f.code" :value="f.code">{{ f.code }} — {{ f.name }}</option>
                        </select>

                        <!-- Bloque bancario global -->
                        <div v-if="esBancarizada(separar.global.payment_method) && !separar.personalizar" class="mb-2">
                            <bloque-bancario
                                :data="separar.global"
                                :cuentas-bancarias="cuentasBancarias"
                                :bancos="bancos"
                            ></bloque-bancario>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="chkPers" v-model="separar.personalizar">
                            <label class="form-check-label small" for="chkPers">
                                Personalizar forma de pago por cada abono (caso atípico: el cliente pagó con métodos distintos).
                            </label>
                        </div>

                        <!-- Selectores individuales -->
                        <div v-if="separar.personalizar">
                            <hr class="my-2">
                            <div v-for="(asig, idx) in separar.asignaciones" :key="asig.partial_payment_id" class="border rounded p-2 mb-2 bg-white">
                                <div class="small fw-bold mb-1">
                                    Abono #{{ idx + 1 }} — ${{ formatNumber(abonoMonto(asig.partial_payment_id)) }} ({{ abonoFecha(asig.partial_payment_id) }})
                                </div>
                                <select class="form-select form-select-sm mb-1" v-model="asig.payment_method">
                                    <option v-for="f in formasSAT" :key="f.code" :value="f.code">{{ f.code }} — {{ f.name }}</option>
                                </select>
                                <bloque-bancario
                                    v-if="esBancarizada(asig.payment_method)"
                                    :data="asig"
                                    :cuentas-bancarias="cuentasBancarias"
                                    :bancos="bancos"
                                ></bloque-bancario>
                            </div>
                        </div>
                    </div>

                    <!-- ESTRATEGIA CONSOLIDAR -->
                    <div v-if="estrategia === 'consolidar'" class="border rounded p-3 bg-light">
                        <label class="form-label small">Forma de pago del consolidado</label>
                        <select class="form-select form-select-sm mb-2" v-model="consolidado.payment_method">
                            <option v-for="f in formasSAT" :key="f.code" :value="f.code">{{ f.code }} — {{ f.name }}</option>
                        </select>

                        <label class="form-label small">Fecha del consolidado</label>
                        <input type="date" class="form-control form-control-sm mb-2" v-model="consolidado.fecha_pago">
                        <small class="text-muted d-block mb-2">Default: la fecha del último abono ({{ ultimaFechaAbono }}).</small>

                        <bloque-bancario
                            v-if="esBancarizada(consolidado.payment_method)"
                            :data="consolidado"
                            :cuentas-bancarias="cuentasBancarias"
                            :bancos="bancos"
                        ></bloque-bancario>

                        <div class="alert alert-info py-1 px-2 small mb-0">
                            <strong>Resultado:</strong> 1 complemento por <strong>${{ formatNumber(totalAbonos) }}</strong>,
                            referenciando los IDs <code>{{ abonosIdsList }}</code>.
                        </div>
                    </div>

                    <div v-if="errorMsg" class="alert alert-danger small mt-2 mb-0">{{ errorMsg }}</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary btn-sm" @click="cerrar">Cancelar</button>
                    <button class="btn btn-primary btn-sm" @click="enviar" :disabled="!estrategiaValida">
                        <i class="fa fa-check"></i> Confirmar y Timbrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    </Teleport>
</template>

<script>
const FORMAS_SAT = [
    { code: '01', name: 'Efectivo' },
    { code: '02', name: 'Cheque nominativo' },
    { code: '03', name: 'Transferencia electrónica' },
    { code: '04', name: 'Tarjeta de crédito' },
    { code: '05', name: 'Monedero electrónico' },
    { code: '06', name: 'Dinero electrónico' },
    { code: '28', name: 'Tarjeta de débito' },
    { code: '29', name: 'Tarjeta de servicios' },
];

const BANCOS = [
    { code: '002', name: 'Banamex (Citibanamex)' },
    { code: '012', name: 'BBVA México' },
    { code: '014', name: 'Santander' },
    { code: '021', name: 'HSBC' },
    { code: '044', name: 'Scotiabank' },
    { code: '072', name: 'Banorte' },
    { code: '999', name: 'Otro / Extranjero' },
];

// Sub-componente reutilizable para captura de datos bancarios
const BloqueBancario = {
    props: ['data', 'cuentasBancarias', 'bancos'],
    template: `
        <div class="border-top pt-2 mt-2">
            <div class="text-muted small mb-1"><i class="fa fa-university"></i> Información bancaria (opcional)</div>
            <label class="form-label small mb-1">Cuenta que recibió</label>
            <select class="form-select form-select-sm mb-2" v-model="data.shop_bank_account_id">
                <option :value="null">— Predeterminada —</option>
                <option v-for="c in cuentasBancarias" :key="c.id" :value="c.id">{{ c.alias }} — {{ c.bank_name }}</option>
            </select>
            <label class="form-label small mb-1">Banco del cliente</label>
            <select class="form-select form-select-sm mb-2" v-model="data.bank_ord_code">
                <option value="">— No especificar —</option>
                <option v-for="b in bancos" :key="b.code" :value="b.code">{{ b.code }} — {{ b.name }}</option>
            </select>
            <div v-if="data.bank_ord_code === '999'" class="form-check mb-2">
                <input class="form-check-input" type="checkbox" :id="'chkExt' + uid" v-model="data.is_foreign_bank_ord">
                <label class="form-check-label small" :for="'chkExt' + uid">Banco extranjero (XEXX010101000)</label>
            </div>
            <label class="form-label small mb-1">Cuenta del cliente</label>
            <input type="text" class="form-control form-control-sm mb-2" v-model="data.cta_ordenante" maxlength="50" placeholder="opcional, 10-50 alfanuméricos">
            <label class="form-label small mb-1">Núm. operación / referencia</label>
            <input type="text" class="form-control form-control-sm" v-model="data.num_operacion" maxlength="100" placeholder="Ej. SPEI-20260429-001">
        </div>
    `,
    data() { return { uid: Math.random().toString(36).slice(2, 8) }; },
};

export default {
    name: 'AbonosPreviosDialog',
    components: { BloqueBancario },
    props: {
        abonos: { type: Array, default: () => [] },
        cuentasBancarias: { type: Array, default: () => [] },
    },
    emits: ['submitted', 'cancelled'],
    data() {
        return {
            show: false,
            estrategia: 'separar',
            formasSAT: FORMAS_SAT,
            bancos: BANCOS,
            separar: {
                personalizar: false,
                global: this.crearAsignacionVacia(),
                asignaciones: [],
            },
            consolidado: this.crearAsignacionVacia(),
            errorMsg: '',
        };
    },
    computed: {
        totalAbonos() {
            return this.abonos.reduce((s, a) => s + Number(a.amount || 0), 0);
        },
        ultimaFechaAbono() {
            if (!this.abonos.length) return '';
            return [...this.abonos].sort((a, b) => (a.payment_date < b.payment_date ? 1 : -1))[0].payment_date;
        },
        abonosIdsList() {
            return this.abonos.map(a => a.id).join(', ');
        },
        estrategiaValida() {
            if (this.estrategia === 'consolidar') {
                return !!this.consolidado.payment_method && this.consolidado.payment_method !== '99';
            }
            if (this.estrategia === 'separar') {
                if (!this.separar.personalizar) {
                    return !!this.separar.global.payment_method;
                }
                return this.separar.asignaciones.every(a => !!a.payment_method);
            }
            return false;
        },
    },
    methods: {
        crearAsignacionVacia() {
            return {
                payment_method: '03',
                shop_bank_account_id: null,
                bank_ord_code: '',
                cta_ordenante: '',
                is_foreign_bank_ord: false,
                num_operacion: '',
                fecha_pago: '',
            };
        },
        formatNumber(n) {
            return Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        esBancarizada(code) {
            return ['02', '03', '04', '05', '06', '28', '29'].includes(code);
        },
        abonoMonto(id) { return this.abonos.find(a => a.id === id)?.amount ?? 0; },
        abonoFecha(id) { return this.abonos.find(a => a.id === id)?.payment_date ?? ''; },
        aplicarGlobal() {
            if (!this.separar.personalizar) return;
            this.separar.asignaciones.forEach(a => { a.payment_method = this.separar.global.payment_method; });
        },
        abrir() {
            this.errorMsg = '';
            // Inicializar asignaciones individuales (1 por abono)
            this.separar.asignaciones = this.abonos.map(a => ({
                partial_payment_id: a.id,
                ...this.crearAsignacionVacia(),
            }));
            this.consolidado = this.crearAsignacionVacia();
            this.consolidado.fecha_pago = this.ultimaFechaAbono;
            this.show = true;
        },
        cerrar() {
            this.show = false;
            this.$emit('cancelled');
        },
        enviar() {
            this.errorMsg = '';

            const armarBancarios = (data) => {
                const bancarizada = this.esBancarizada(data.payment_method);
                if (!bancarizada) return {};
                const out = {};
                if (data.shop_bank_account_id) out.shop_bank_account_id = data.shop_bank_account_id;
                if (data.bank_ord_code) out.bank_ord_code = data.bank_ord_code;
                if (data.cta_ordenante) out.cta_ordenante = data.cta_ordenante.toUpperCase();
                if (data.is_foreign_bank_ord) out.is_foreign_bank_ord = true;
                if (data.num_operacion) out.num_operacion = data.num_operacion;
                return out;
            };

            let payload;
            if (this.estrategia === 'consolidar') {
                payload = {
                    estrategia: 'consolidar',
                    consolidado: {
                        payment_method: this.consolidado.payment_method,
                        fecha_pago: this.consolidado.fecha_pago || this.ultimaFechaAbono,
                        ...armarBancarios(this.consolidado),
                    },
                };
            } else {
                let asigs;
                if (this.separar.personalizar) {
                    asigs = this.separar.asignaciones.map(a => ({
                        partial_payment_id: a.partial_payment_id,
                        payment_method: a.payment_method,
                        ...armarBancarios(a),
                    }));
                } else {
                    asigs = this.abonos.map(a => ({
                        partial_payment_id: a.id,
                        payment_method: this.separar.global.payment_method,
                        ...armarBancarios(this.separar.global),
                    }));
                }
                payload = { estrategia: 'separar', asignaciones: asigs };
            }

            this.$emit('submitted', payload);
            this.show = false;
        },
    },
};
</script>

<style scoped>
.abonos-previos-overlay {
    position: fixed !important;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.6);
    z-index: 10050;
    overflow-y: auto;
    display: block;
}
</style>
