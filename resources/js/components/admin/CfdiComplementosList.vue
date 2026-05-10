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

            <!-- Panel comercial vs fiscal (solo cuando hay retenciones) -->
            <div v-if="(invoice.total_retenciones || 0) > 0"
                class="alert alert-info py-2 mb-2 small">
                <div class="fw-bold mb-1">
                    <i class="fa fa-info-circle me-1"></i>Esta factura tiene retenciones
                </div>
                <div class="d-flex justify-content-between">
                    <span>Saldo comercial del cliente:</span>
                    <span class="fw-bold">{{ formatCurrency(receiptTotalComercial) }}</span>
                </div>
                <div class="d-flex justify-content-between text-warning">
                    <span>Retenido (lo entrega al SAT):</span>
                    <span class="fw-bold">-{{ formatCurrency(invoice.total_retenciones) }}</span>
                </div>
                <div class="d-flex justify-content-between border-top pt-1 mt-1">
                    <span>Te transfiere (total fiscal):</span>
                    <span class="fw-bold text-success">{{ formatCurrency(invoice.total) }}</span>
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

            <!-- Cards apilados de complementos -->
            <div v-if="complementos.length > 0" class="complemento-list">
                <div v-for="c in complementos" :key="c.id" class="complemento-card">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <span class="parcialidad-badge">Parcialidad {{ c.num_parcialidad }}</span>
                            <span class="folio-text ms-1">{{ c.serie }}-{{ c.folio }}</span>
                        </div>
                        <span class="badge" :class="badgeClass(c.status)">{{ statusLabel(c.status) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div>
                            <div class="monto-grande text-success">{{ formatCurrency(c.imp_pagado) }}</div>
                            <div class="saldo-text">
                                Saldo restante: <strong>{{ formatCurrency(c.imp_saldo_insoluto) }}</strong>
                            </div>
                            <div class="fecha-text">
                                <i class="fa fa-calendar-o me-1"></i>{{ formatDate(c.fecha_timbrado || c.fecha_emision) }}
                            </div>
                        </div>
                        <div class="acciones-col">
                            <template v-if="c.status === 'vigente'">
                                <button class="btn btn-sm btn-outline-primary mb-1 w-100"
                                    @click="descargar(c, 'xml')"
                                    :disabled="descargando === c.id"
                                    title="Descargar XML">
                                    <i class="fa fa-file-code-o me-1"></i>XML
                                </button>
                                <button class="btn btn-sm btn-outline-danger w-100"
                                    @click="descargar(c, 'pdf')"
                                    :disabled="descargando === c.id"
                                    title="Descargar PDF">
                                    <i class="fa fa-file-pdf-o me-1"></i>PDF
                                </button>
                            </template>
                            <template v-else-if="c.status === 'failed'">
                                <button class="btn btn-sm btn-outline-warning w-100"
                                    @click="reemitir(c)"
                                    :disabled="reemitiendo === c.id">
                                    <i class="fa fa-refresh me-1" :class="{ 'fa-spin': reemitiendo === c.id }"></i>Re-emitir
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
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
            receiptTotalComercial: 0,
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
                    this.receiptTotalComercial = parseFloat(res.data.receipt_total_comercial || 0);
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

<style scoped>
.complemento-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.complemento-card {
    background: #fff;
    border: 1px solid #e3e7ed;
    border-left: 3px solid #0d6efd;
    border-radius: 6px;
    padding: 0.6rem 0.75rem;
    transition: box-shadow 0.15s ease;
}
.complemento-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}
.parcialidad-badge {
    background: #e7f1ff;
    color: #0d6efd;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.folio-text {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 500;
}
.monto-grande {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.2;
}
.saldo-text {
    font-size: 0.72rem;
    color: #6c757d;
    margin-top: 1px;
}
.saldo-text strong {
    color: #495057;
}
.fecha-text {
    font-size: 0.7rem;
    color: #adb5bd;
    margin-top: 2px;
}
.acciones-col {
    min-width: 80px;
    margin-left: 0.5rem;
}
.acciones-col .btn {
    font-size: 0.72rem;
    padding: 0.2rem 0.4rem;
}
</style>
