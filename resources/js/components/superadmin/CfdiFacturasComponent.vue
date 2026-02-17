<template>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-file-text-o" style="color: var(--j2b-primary);"></i> Facturas Emitidas
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Todas las facturas CFDI de todas las tiendas</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="j2b-card mb-4">
            <div class="j2b-card-header">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <input type="date" class="j2b-input" v-model="filtros.fecha_inicio" style="width:150px">
                    <input type="date" class="j2b-input" v-model="filtros.fecha_fin" style="width:150px">
                    <select class="j2b-select" v-model="filtros.status" style="width:130px">
                        <option value="todos">Todos</option>
                        <option value="vigente">Vigentes</option>
                        <option value="cancelada">Canceladas</option>
                    </select>
                    <select class="j2b-select" v-model="filtros.shop_id" style="width:180px">
                        <option value="">Todas las tiendas</option>
                        <option v-for="s in shops" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <div class="j2b-input-icon" style="min-width: 180px;">
                        <i class="fa fa-search"></i>
                        <input type="text" class="j2b-input" v-model="filtros.buscar" placeholder="RFC o nombre..." @keyup.enter="cargar">
                    </div>
                    <button class="j2b-btn j2b-btn-primary" @click="cargar" :disabled="loading">
                        <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-search'"></i> Buscar
                    </button>
                    <button class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="exportar" :disabled="loading || !facturas.length">
                        <i class="fa fa-file-excel-o"></i> Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row mb-4" v-if="totales">
            <div class="col-md-3 mb-2">
                <div class="stat-card stat-card-primary">
                    <div class="stat-number">{{ totales.count }}</div>
                    <div class="stat-label">Total Facturas</div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="stat-card stat-card-success">
                    <div class="stat-number">{{ totales.vigentes }}</div>
                    <div class="stat-label">Vigentes</div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="stat-card stat-card-danger">
                    <div class="stat-number">{{ totales.canceladas }}</div>
                    <div class="stat-label">Canceladas</div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="stat-card stat-card-warning">
                    <div class="stat-number">{{ formatMoney(totales.total) }}</div>
                    <div class="stat-label">Monto Total (vigentes)</div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="j2b-card">
            <div class="j2b-card-body p-0">
                <div v-if="loading" class="text-center py-5">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color: var(--j2b-primary);"></i>
                    <p class="mt-2" style="color: var(--j2b-gray-500);">Cargando facturas...</p>
                </div>
                <div class="j2b-table-responsive" v-if="!loading">
                    <table class="j2b-table" v-if="facturas.length">
                        <thead>
                            <tr>
                                <th>Serie-Folio</th>
                                <th>Fecha</th>
                                <th>Tienda</th>
                                <th>Receptor</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">IVA</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="f in facturas" :key="f.id">
                                <td><strong>{{ f.serie }}{{ f.folio }}</strong></td>
                                <td>{{ f.fecha_emision }}</td>
                                <td>{{ f.shop_name }}</td>
                                <td>
                                    <small style="color: var(--j2b-gray-500);">{{ f.receptor_rfc }}</small><br>
                                    {{ f.receptor_nombre }}
                                </td>
                                <td class="text-right">{{ formatMoney(f.subtotal) }}</td>
                                <td class="text-right">{{ formatMoney(f.total_impuestos) }}</td>
                                <td class="text-right"><strong>{{ formatMoney(f.total) }}</strong></td>
                                <td class="text-center">
                                    <span class="j2b-badge" :class="f.status === 'vigente' ? 'j2b-badge-success' : 'j2b-badge-danger'">
                                        {{ f.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="text-center py-5">
                        <i class="fa fa-inbox fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                        <p style="color: var(--j2b-gray-500);">No se encontraron facturas en el periodo seleccionado.</p>
                    </div>
                </div>
                <div v-if="periodo && !loading" class="p-3" style="border-top: 1px solid var(--j2b-gray-200);">
                    <small style="color: var(--j2b-gray-500);">Periodo: {{ periodo }}</small>
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
            shops: [],
            filtros: {
                fecha_inicio: this.defaultFechaInicio(),
                fecha_fin: this.defaultFechaFin(),
                status: 'todos',
                shop_id: '',
                buscar: '',
            },
        };
    },
    mounted() {
        this.loadShops();
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
        async loadShops() {
            try {
                const { data } = await axios.get('/superadmin/cfdi/shops?page=1&buscar=&estatus=cfdi_active');
                if (data.shops && data.shops.data) {
                    this.shops = data.shops.data;
                }
            } catch (e) {
                console.error('Error cargando tiendas', e);
            }
        },
        async cargar() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    fecha_inicio: this.filtros.fecha_inicio,
                    fecha_fin: this.filtros.fecha_fin,
                    status: this.filtros.status,
                    shop_id: this.filtros.shop_id,
                    buscar: this.filtros.buscar,
                });
                const { data } = await axios.get('/superadmin/cfdi/facturas/get?' + params);
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
                shop_id: this.filtros.shop_id,
            });
            window.location.href = '/superadmin/cfdi/facturas/export?' + params;
        },
        formatMoney(val) {
            if (val === null || val === undefined) return '$0.00';
            return '$' + Number(val).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    },
};
</script>
