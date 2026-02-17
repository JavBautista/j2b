<template>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h5 class="mb-0 me-auto"><i class="fa fa-file-text-o"></i> Facturas Emitidas</h5>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <input type="date" class="form-control form-control-sm" v-model="filtros.fecha_inicio" style="width:150px">
                    <input type="date" class="form-control form-control-sm" v-model="filtros.fecha_fin" style="width:150px">
                    <select class="form-control form-control-sm" v-model="filtros.status" style="width:130px">
                        <option value="todos">Todos</option>
                        <option value="vigente">Vigentes</option>
                        <option value="cancelada">Canceladas</option>
                    </select>
                    <input type="text" class="form-control form-control-sm" v-model="filtros.buscar" placeholder="RFC o nombre..." style="width:180px" @keyup.enter="cargar">
                    <button class="btn btn-sm btn-primary" @click="cargar" :disabled="loading">
                        <i class="fa fa-search"></i> Buscar
                    </button>
                    <button class="btn btn-sm btn-success" @click="exportar" :disabled="loading || !facturas.length">
                        <i class="fa fa-file-excel-o"></i> Excel
                    </button>
                </div>
            </div>

            <div class="card-body">
                <!-- KPI Cards -->
                <div class="row mb-4" v-if="totales">
                    <div class="col-md-3 mb-2">
                        <div class="card bg-primary text-white">
                            <div class="card-body py-2 px-3">
                                <h6 class="card-title mb-1">Total Facturas</h6>
                                <h3 class="mb-0">{{ totales.count }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card bg-success text-white">
                            <div class="card-body py-2 px-3">
                                <h6 class="card-title mb-1">Vigentes</h6>
                                <h3 class="mb-0">{{ totales.vigentes }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card bg-danger text-white">
                            <div class="card-body py-2 px-3">
                                <h6 class="card-title mb-1">Canceladas</h6>
                                <h3 class="mb-0">{{ totales.canceladas }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="card bg-info text-white">
                            <div class="card-body py-2 px-3">
                                <h6 class="card-title mb-1">Monto Total (vigentes)</h6>
                                <h3 class="mb-0">{{ formatMoney(totales.total) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="text-center py-5">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p class="mt-2">Cargando facturas...</p>
                </div>

                <!-- Tabla -->
                <div class="table-responsive" v-if="!loading">
                    <table class="table table-hover table-sm" v-if="facturas.length">
                        <thead>
                            <tr>
                                <th>Serie-Folio</th>
                                <th>Fecha Emisión</th>
                                <th>Receptor</th>
                                <th>Nota #</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">IVA</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="f in facturas" :key="f.id">
                                <td>{{ f.serie }}{{ f.folio }}</td>
                                <td>{{ f.fecha_emision }}</td>
                                <td>
                                    <small class="text-muted">{{ f.receptor_rfc }}</small><br>
                                    {{ f.receptor_nombre }}
                                </td>
                                <td>{{ f.receipt_folio || '-' }}</td>
                                <td class="text-right">{{ formatMoney(f.subtotal) }}</td>
                                <td class="text-right">{{ formatMoney(f.total_impuestos) }}</td>
                                <td class="text-right"><strong>{{ formatMoney(f.total) }}</strong></td>
                                <td class="text-center">
                                    <span class="badge" :class="f.status === 'vigente' ? 'badge-success' : 'badge-danger'">
                                        {{ f.status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-outline-primary" @click="descargar(f.id, 'xml')" title="Descargar XML">
                                        <i class="fa fa-code"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-danger ms-1" @click="descargar(f.id, 'pdf')" title="Descargar PDF">
                                        <i class="fa fa-file-pdf-o"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="text-center text-muted py-4">
                        <i class="fa fa-inbox fa-3x mb-2"></i>
                        <p>No se encontraron facturas en el periodo seleccionado.</p>
                    </div>
                </div>

                <div v-if="periodo && !loading" class="text-muted small mt-2">
                    Periodo: {{ periodo }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            loading: false,
            facturas: [],
            totales: null,
            periodo: '',
            filtros: {
                fecha_inicio: this.defaultFechaInicio(),
                fecha_fin: this.defaultFechaFin(),
                status: 'todos',
                buscar: '',
            },
        };
    },
    mounted() {
        this.cargar();
    },
    methods: {
        defaultFechaInicio() {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
        },
        defaultFechaFin() {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        },
        async cargar() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    fecha_inicio: this.filtros.fecha_inicio,
                    fecha_fin: this.filtros.fecha_fin,
                    status: this.filtros.status,
                    buscar: this.filtros.buscar,
                });
                const { data } = await axios.get('/admin/facturacion/facturas/get?' + params);
                if (data.ok) {
                    this.facturas = data.facturas;
                    this.totales = data.totales;
                    this.periodo = data.periodo;
                }
            } catch (e) {
                console.error('Error cargando facturas', e);
            } finally {
                this.loading = false;
            }
        },
        exportar() {
            const params = new URLSearchParams({
                fecha_inicio: this.filtros.fecha_inicio,
                fecha_fin: this.filtros.fecha_fin,
                status: this.filtros.status,
            });
            window.location.href = '/admin/facturacion/facturas/export?' + params;
        },
        descargar(id, formato) {
            window.open(`/admin/facturacion/descargar/${id}/${formato}`, '_blank');
        },
        formatMoney(val) {
            if (val === null || val === undefined) return '$0.00';
            return '$' + Number(val).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    },
};
</script>
