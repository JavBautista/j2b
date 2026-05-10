<template>
    <div>
        <!-- Filtros -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-end gap-2">
                    <div>
                        <label class="form-label small mb-1">Mes</label>
                        <input type="month" class="form-control form-control-sm" v-model="filtros.mes" style="width:160px">
                    </div>
                    <div class="text-muted small mx-2">— o rango —</div>
                    <div>
                        <label class="form-label small mb-1">Desde</label>
                        <input type="date" class="form-control form-control-sm" v-model="filtros.fecha_inicio" style="width:150px">
                    </div>
                    <div>
                        <label class="form-label small mb-1">Hasta</label>
                        <input type="date" class="form-control form-control-sm" v-model="filtros.fecha_fin" style="width:150px">
                    </div>
                    <button class="btn btn-sm btn-primary" @click="cargar" :disabled="loading">
                        <i class="fa fa-search"></i> Generar
                    </button>
                    <button class="btn btn-sm btn-success" @click="exportar" :disabled="loading || !facturas.length">
                        <i class="fa fa-file-excel-o"></i> Excel
                    </button>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="fa fa-info-circle"></i>
                    Si capturas mes, ignora el rango. Solo se cuentan facturas <strong>vigentes</strong> con retenciones &gt; $0.
                </small>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
            <p class="mt-2 text-muted">Generando reporte...</p>
        </div>

        <div v-else>
            <!-- KPIs -->
            <div class="row mb-3" v-if="totales">
                <div class="col-md-3 mb-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body py-2 px-3">
                            <h6 class="card-title mb-1">Facturas con retenciones</h6>
                            <h3 class="mb-0">{{ totales.count }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body py-2 px-3">
                            <h6 class="card-title mb-1">Total ISR retenido</h6>
                            <h3 class="mb-0">{{ formatMoney(totales.total_isr) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body py-2 px-3">
                            <h6 class="card-title mb-1">Total IVA retenido</h6>
                            <h3 class="mb-0">{{ formatMoney(totales.total_iva) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="card bg-info text-white">
                        <div class="card-body py-2 px-3">
                            <h6 class="card-title mb-1">Total acreditable</h6>
                            <h3 class="mb-0">{{ formatMoney(totales.total_general) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top clientes -->
            <div class="card mb-3" v-if="topClientes.length">
                <div class="card-header py-2">
                    <strong><i class="fa fa-users"></i> Top 5 clientes que retienen</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>RFC</th>
                                <th>Razón Social</th>
                                <th class="text-center">Facturas</th>
                                <th class="text-end">Total Retenido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(c, i) in topClientes" :key="i">
                                <td><small class="text-muted">{{ c.receptor_rfc }}</small></td>
                                <td>{{ c.receptor_nombre }}</td>
                                <td class="text-center">{{ c.count }}</td>
                                <td class="text-end fw-bold text-warning">{{ formatMoney(c.total_retenciones) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla detalle -->
            <div class="card" v-if="facturas.length">
                <div class="card-header py-2">
                    <strong><i class="fa fa-list"></i> Detalle de facturas con retenciones</strong>
                    <small class="text-muted ms-2">Periodo: {{ periodo }}</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Serie-Folio</th>
                                <th>Fecha</th>
                                <th>Receptor</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">IVA</th>
                                <th class="text-end text-warning">Ret. ISR</th>
                                <th class="text-end text-warning">Ret. IVA</th>
                                <th class="text-end">Total CFDI</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="f in facturas" :key="f.id">
                                <td>{{ f.serie_folio }}</td>
                                <td>{{ f.fecha_emision }}</td>
                                <td>
                                    <small class="text-muted">{{ f.receptor_rfc }}</small><br>
                                    {{ f.receptor_nombre }}
                                </td>
                                <td class="text-end">{{ formatMoney(f.subtotal) }}</td>
                                <td class="text-end">{{ formatMoney(f.total_impuestos) }}</td>
                                <td class="text-end text-warning">{{ f.ret_isr > 0 ? '-' + formatMoney(f.ret_isr) : '—' }}</td>
                                <td class="text-end text-warning">{{ f.ret_iva > 0 ? '-' + formatMoney(f.ret_iva) : '—' }}</td>
                                <td class="text-end fw-bold">{{ formatMoney(f.total) }}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger" @click="descargar(f.id, 'pdf')" title="Descargar PDF">
                                        <i class="fa fa-file-pdf-o"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="totales" class="text-center text-muted py-5">
                <i class="fa fa-inbox fa-3x mb-2"></i>
                <p>No se encontraron facturas con retenciones en el periodo.</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RetencionesReporteComponent',
    data() {
        const d = new Date();
        const mesActual = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        return {
            loading: false,
            filtros: {
                mes: mesActual,
                fecha_inicio: '',
                fecha_fin: '',
            },
            totales: null,
            topClientes: [],
            facturas: [],
            periodo: '',
        };
    },
    mounted() {
        this.cargar();
    },
    methods: {
        async cargar() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filtros.mes) params.append('mes', this.filtros.mes);
                if (this.filtros.fecha_inicio) params.append('fecha_inicio', this.filtros.fecha_inicio);
                if (this.filtros.fecha_fin) params.append('fecha_fin', this.filtros.fecha_fin);
                const { data } = await axios.get('/admin/reportes/retenciones/data?' + params);
                if (data.ok) {
                    this.totales = data.totales;
                    this.topClientes = data.top_clientes || [];
                    this.facturas = data.facturas || [];
                    this.periodo = data.periodo;
                } else {
                    Swal.fire('Error', data.message || 'Error generando reporte', 'error');
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.message || 'Error de red', 'error');
            } finally {
                this.loading = false;
            }
        },
        exportar() {
            const params = new URLSearchParams();
            if (this.filtros.mes) params.append('mes', this.filtros.mes);
            if (this.filtros.fecha_inicio) params.append('fecha_inicio', this.filtros.fecha_inicio);
            if (this.filtros.fecha_fin) params.append('fecha_fin', this.filtros.fecha_fin);
            window.location.href = '/admin/reportes/retenciones/export?' + params;
        },
        descargar(id, formato) {
            window.open(`/admin/facturacion/descargar/${id}/${formato}`, '_blank');
        },
        formatMoney(val) {
            if (val === null || val === undefined) return '$0.00';
            const locale = (this.$shopCurrency === 'USD') ? 'en-US' : 'es-MX';
            return '$' + Number(val).toLocaleString(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    },
};
</script>
