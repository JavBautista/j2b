<template>
    <div class="cfdi-complementos-list" v-if="invoice">
        <div v-if="invoice.metodo_pago === 'PPD'">
            <!-- Badge de saldo insoluto -->
            <div class="alert mb-2 py-2"
                :class="saldoInsoluto > 0 ? 'alert-warning' : 'alert-success'">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa fa-file-text-o me-1"></i>
                        <strong>Factura {{ invoice.serie }}-{{ invoice.folio }}</strong>
                        <span class="badge bg-info ms-1">PPD</span>
                    </div>
                    <div>
                        <small class="me-2">Saldo insoluto:</small>
                        <span class="fw-bold">{{ formatCurrency(saldoInsoluto) }}</span>
                    </div>
                </div>
            </div>

            <!-- Encabezado lista -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">
                    <i class="fa fa-list me-1"></i>Complementos de pago
                    <span class="badge bg-secondary">{{ complementos.length }}</span>
                </h6>
                <button class="btn btn-sm btn-outline-secondary" @click="cargar" :disabled="cargando">
                    <i class="fa fa-refresh" :class="{ 'fa-spin': cargando }"></i>
                </button>
            </div>

            <!-- Lista vacía -->
            <div v-if="complementos.length === 0 && !cargando" class="text-center text-muted py-2">
                <small>Aun no se han emitido complementos. Cada abono futuro generara uno automaticamente.</small>
            </div>

            <!-- Tabla de complementos -->
            <div v-if="complementos.length > 0" class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width: 110px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in complementos" :key="c.id">
                            <td class="text-center">{{ c.num_parcialidad }}</td>
                            <td>{{ c.serie }}-{{ c.folio }}</td>
                            <td>
                                <small>{{ formatDate(c.fecha_timbrado || c.fecha_emision) }}</small>
                            </td>
                            <td class="text-end">{{ formatCurrency(c.imp_pagado) }}</td>
                            <td class="text-end">
                                <small class="text-muted">{{ formatCurrency(c.imp_saldo_insoluto) }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge" :class="badgeClass(c.status)">{{ statusLabel(c.status) }}</span>
                            </td>
                            <td class="text-center">
                                <template v-if="c.status === 'vigente'">
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                        @click="descargar(c, 'xml')"
                                        :disabled="descargando === c.id"
                                        title="Descargar XML">
                                        <i class="fa fa-file-code-o"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                        @click="descargar(c, 'pdf')"
                                        :disabled="descargando === c.id"
                                        title="Descargar PDF">
                                        <i class="fa fa-file-pdf-o"></i>
                                    </button>
                                </template>
                                <template v-else-if="c.status === 'failed'">
                                    <button class="btn btn-sm btn-outline-warning"
                                        @click="reemitir(c)"
                                        :disabled="reemitiendo === c.id"
                                        title="Re-emitir complemento">
                                        <i class="fa fa-refresh" :class="{ 'fa-spin': reemitiendo === c.id }"></i>
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mensaje de error inline si hay alguno -->
            <div v-if="error" class="alert alert-danger py-2 mt-2 mb-0 small">
                <i class="fa fa-exclamation-triangle me-1"></i>{{ error }}
            </div>
        </div>

        <!-- PUE: solo informativo si quisiera mostrarse, pero la regla de negocio dice
             que PUE no maneja complementos. No mostramos nada para no ensuciar la UI. -->
    </div>
</template>

<script>
export default {
    name: 'CfdiComplementosList',
    props: {
        receiptId: {
            type: [Number, String],
            required: true,
        },
    },
    data() {
        return {
            invoice: null,
            saldoInsoluto: 0,
            complementos: [],
            cargando: false,
            descargando: null,
            reemitiendo: null,
            error: null,
        };
    },
    mounted() {
        this.cargar();
    },
    methods: {
        async cargar() {
            this.cargando = true;
            this.error = null;
            try {
                const res = await axios.get(`/admin/facturacion/nota/${this.receiptId}/complementos`);
                if (res.data.ok) {
                    this.invoice = res.data.invoice;
                    this.saldoInsoluto = parseFloat(res.data.saldo_insoluto || 0);
                    this.complementos = res.data.complementos || [];
                }
            } catch (e) {
                this.error = e.response?.data?.message || 'Error al cargar complementos';
            } finally {
                this.cargando = false;
            }
        },
        async descargar(complemento, formato) {
            this.descargando = complemento.id;
            try {
                const res = await axios.get(
                    `/admin/facturacion/complemento/${complemento.id}/descargar/${formato}`,
                    { responseType: 'blob' }
                );
                const blob = new Blob([res.data], {
                    type: formato === 'xml' ? 'application/xml' : 'application/pdf',
                });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `complemento_${complemento.serie}${complemento.folio}.${formato}`;
                link.click();
                window.URL.revokeObjectURL(url);
            } catch (e) {
                let msg = 'No se pudo descargar el complemento';
                if (e.response?.data) {
                    try {
                        const text = await e.response.data.text();
                        const data = JSON.parse(text);
                        msg = data.message || msg;
                    } catch {}
                }
                this.$swal('Error', msg, 'error');
            } finally {
                this.descargando = null;
            }
        },
        async reemitir(complemento) {
            const confirmacion = await this.$swal({
                title: 'Re-emitir complemento',
                text: `Se intentara timbrar nuevamente el complemento de la parcialidad ${complemento.num_parcialidad}.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si, re-emitir',
                cancelButtonText: 'Cancelar',
            });
            if (!confirmacion.isConfirmed) return;

            this.reemitiendo = complemento.id;
            try {
                const res = await axios.post(`/admin/facturacion/complemento/${complemento.id}/reemitir`);
                if (res.data.ok) {
                    this.$swal('Listo', res.data.message || 'Complemento re-emitido.', 'success');
                    await this.cargar();
                } else {
                    this.$swal('Error', res.data.message || 'No se pudo re-emitir.', 'error');
                }
            } catch (e) {
                const msg = e.response?.data?.message || 'Error al re-emitir';
                this.$swal('Error', msg, 'error');
            } finally {
                this.reemitiendo = null;
            }
        },
        badgeClass(status) {
            return {
                vigente: 'bg-success',
                pending: 'bg-secondary',
                failed: 'bg-danger',
                cancelado: 'bg-dark',
            }[status] || 'bg-secondary';
        },
        statusLabel(status) {
            return {
                vigente: 'Vigente',
                pending: 'En proceso',
                failed: 'Fallido',
                cancelado: 'Cancelado',
            }[status] || status;
        },
        formatCurrency(value) {
            const n = parseFloat(value || 0);
            return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
        },
    },
};
</script>
