<template>
<div>
    <!-- ============ Modal: Estado de cuenta (saldo a favor) ============ -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':show}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title">
                        <i class="fa fa-wallet"></i> Saldo a favor
                        <span v-if="client" class="fw-normal"> — {{ client.name }}</span>
                    </h4>
                    <button type="button" class="close text-white" @click="cerrar()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Saldo actual -->
                    <div class="card mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Saldo actual</small>
                                <h3 class="mb-0" :class="balance > 0 ? 'text-success' : (balance < 0 ? 'text-danger' : 'text-muted')">
                                    {{ formatMoney(balance) }}
                                </h3>
                                <small v-if="balance < 0" class="text-danger">Adeudo</small>
                                <small v-else-if="balance > 0" class="text-success">A favor del cliente</small>
                            </div>
                            <div v-if="!isLimitedUser" class="text-end">
                                <button class="btn btn-success btn-sm mb-1 d-block w-100" @click="abrirForm('deposito')">
                                    <i class="fa fa-plus-circle"></i> Registrar anticipo
                                </button>
                                <button class="btn btn-outline-secondary btn-sm d-block w-100" @click="abrirForm('ajuste')">
                                    <i class="fa fa-sliders-h"></i> Ajuste manual
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Estado de cuenta -->
                    <h6 class="text-muted"><i class="fa fa-list"></i> Estado de cuenta</h6>
                    <div v-if="loading" class="text-center py-4 text-muted">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div v-else-if="!movements.length" class="text-center py-4 text-muted">
                        Sin movimientos registrados.
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-end">Saldo</th>
                                    <th>Registró</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in movements" :key="m.id">
                                    <td><small>{{ formatDate(m.created_at) }}</small></td>
                                    <td>
                                        <span class="badge" :class="badgeTipo(m.type)">{{ labelTipo(m.type) }}</span>
                                        <div v-if="m.description"><small class="text-muted">{{ m.description }}</small></div>
                                    </td>
                                    <td class="text-end fw-bold" :class="Number(m.amount) >= 0 ? 'text-success' : 'text-danger'">
                                        {{ Number(m.amount) >= 0 ? '+' : '' }}{{ formatMoney(m.amount) }}
                                    </td>
                                    <td class="text-end">{{ formatMoney(m.balance_after) }}</td>
                                    <td><small>{{ m.creator ? m.creator.name : '—' }}</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación simple -->
                    <nav v-if="pagination.last_page > 1">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <li class="page-item" :class="{disabled: pagination.current_page <= 1}">
                                <a class="page-link" href="#" @click.prevent="cargar(pagination.current_page - 1)">Ant</a>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                            </li>
                            <li class="page-item" :class="{disabled: pagination.current_page >= pagination.last_page}">
                                <a class="page-link" href="#" @click.prevent="cargar(pagination.current_page + 1)">Sig</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrar()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ Sub-modal: Formulario anticipo / ajuste ============ -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':showForm}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" :class="formMode === 'deposito' ? 'bg-success text-white' : 'bg-secondary text-white'">
                    <h5 class="modal-title">
                        <i class="fa" :class="formMode === 'deposito' ? 'fa-plus-circle' : 'fa-sliders-h'"></i>
                        {{ formMode === 'deposito' ? 'Registrar anticipo' : 'Ajuste manual' }}
                    </h5>
                    <button type="button" class="close text-white" @click="cerrarForm()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">
                            Monto <span class="text-danger">*</span>
                            <small v-if="formMode === 'ajuste'" class="text-muted">(usa negativo para restar saldo)</small>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" v-model="formAmount"
                                   :placeholder="formMode === 'ajuste' ? 'Ej: 100 ó -50' : '0.00'" ref="amountInput">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            {{ formMode === 'ajuste' ? 'Motivo' : 'Concepto / Referencia' }}
                            <span v-if="formMode === 'ajuste'" class="text-danger">*</span>
                        </label>
                        <input type="text" maxlength="255" class="form-control" v-model="formDescription"
                               :placeholder="formMode === 'ajuste' ? 'Motivo del ajuste (obligatorio)' : 'Ej: Depósito en efectivo'">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarForm()" :disabled="saving">Cancelar</button>
                    <button type="button" class="btn" :class="formMode === 'deposito' ? 'btn-success' : 'btn-primary'"
                            @click="guardarForm()" :disabled="saving">
                        <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
export default {
    name: 'ClientAccountComponent',
    props: {
        isLimitedUser: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            show: false,
            client: null,
            balance: 0,
            movements: [],
            pagination: { current_page: 1, last_page: 1 },
            loading: false,

            // Sub-modal formulario
            showForm: false,
            formMode: 'deposito',
            formAmount: '',
            formDescription: '',
            saving: false,
        };
    },
    methods: {
        // API pública: el padre la invoca vía this.$refs.clientAccount.abrir(client)
        abrir(client) {
            this.client = client;
            this.balance = Number(client.account_balance || 0);
            this.show = true;
            this.cargar(1);
        },
        cerrar() {
            this.show = false;
            this.client = null;
            this.movements = [];
            this.$emit('close');
        },
        cargar(page) {
            if (!this.client) return;
            if (page < 1 || (this.pagination.last_page && page > this.pagination.last_page)) return;
            this.loading = true;
            axios.get(`/admin/clients/${this.client.id}/account?page=${page}`)
                .then(response => {
                    this.balance = Number(response.data.account_balance || 0);
                    this.movements = response.data.movements.data;
                    this.pagination = {
                        current_page: response.data.movements.current_page,
                        last_page: response.data.movements.last_page,
                    };
                })
                .catch(() => {
                    Swal.fire('Error', 'No se pudo cargar el estado de cuenta.', 'error');
                })
                .finally(() => { this.loading = false; });
        },
        abrirForm(mode) {
            this.formMode = mode;
            this.formAmount = '';
            this.formDescription = '';
            this.showForm = true;
            this.$nextTick(() => { if (this.$refs.amountInput) this.$refs.amountInput.focus(); });
        },
        cerrarForm() {
            this.showForm = false;
        },
        guardarForm() {
            const amount = parseFloat(this.formAmount);
            if (isNaN(amount) || amount === 0) {
                Swal.fire('Atención', 'Captura un monto válido distinto de cero.', 'warning');
                return;
            }
            if (this.formMode === 'deposito' && amount < 0) {
                Swal.fire('Atención', 'El anticipo debe ser un monto positivo.', 'warning');
                return;
            }
            if (this.formMode === 'ajuste' && !this.formDescription.trim()) {
                Swal.fire('Atención', 'El motivo del ajuste es obligatorio.', 'warning');
                return;
            }
            this.enviar(amount, false);
        },
        enviar(amount, allowNegative) {
            this.saving = true;
            const url = this.formMode === 'deposito'
                ? `/admin/clients/${this.client.id}/account/deposito`
                : `/admin/clients/${this.client.id}/account/ajuste`;
            const payload = { amount: amount, description: this.formDescription };
            if (this.formMode === 'ajuste') payload.allow_negative = allowNegative;

            axios.post(url, payload)
                .then(response => {
                    this.balance = Number(response.data.account_balance || 0);
                    this.showForm = false;
                    this.cargar(1);
                    this.$emit('updated', { clientId: this.client.id, balance: this.balance });
                    Swal.fire({ icon: 'success', title: 'Listo', text: response.data.message, timer: 1800, showConfirmButton: false });
                })
                .catch(error => {
                    const data = error.response?.data;
                    // Ajuste que deja la cuenta en negativo: pedir confirmación y reenviar.
                    if (error.response?.status === 422 && data?.requires_confirm) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Confirmar ajuste',
                            text: data.message,
                            showCancelButton: true,
                            confirmButtonText: 'Sí, dejar en negativo',
                            cancelButtonText: 'Cancelar',
                        }).then(result => {
                            if (result.isConfirmed) this.enviar(amount, true);
                        });
                        return;
                    }
                    Swal.fire('Error', data?.message || 'No se pudo registrar el movimiento.', 'error');
                })
                .finally(() => { this.saving = false; });
        },
        // ---- Utilidades de presentación ----
        formatMoney(v) {
            return Number(v || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
        },
        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            // Incluye hora:minuto para distinguir movimientos del mismo día (ordenados más reciente primero).
            return date.toLocaleString('es-MX', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        },
        labelTipo(type) {
            const map = {
                deposito_anticipo: 'Anticipo / Depósito',
                sobrepago_nota: 'Sobrepago de nota',
                devolucion: 'Devolución',
                ajuste_manual: 'Ajuste manual',
                aplicacion_venta: 'Aplicado a venta',
            };
            return map[type] || type;
        },
        badgeTipo(type) {
            const map = {
                deposito_anticipo: 'bg-success',
                sobrepago_nota: 'bg-info text-dark',
                devolucion: 'bg-warning text-dark',
                ajuste_manual: 'bg-secondary',
                aplicacion_venta: 'bg-primary',
            };
            return map[type] || 'bg-light text-dark';
        },
    },
};
</script>
