<template>
    <div class="container-fluid p-3">
        <div class="row mb-3 g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Cliente</label>
                <select class="form-select" v-model="filtroClienteId" @change="cargar(1)">
                    <option :value="null">Todos los clientes</option>
                    <option v-for="c in clientesConToken" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Coincidencia</label>
                <select class="form-select" v-model="filtroMatched" @change="cargar(1)">
                    <option :value="null">Todas</option>
                    <option value="true">Solo con match</option>
                    <option value="false">Solo sin match</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary" @click="cargar(1)">
                    <i class="fa fa-refresh"></i> Refrescar
                </button>
            </div>
        </div>

        <div v-if="cargando" class="text-center py-5">
            <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
        </div>

        <div v-else-if="readings.length === 0" class="text-center py-5 border rounded bg-light">
            <i class="fa fa-cloud-download" style="font-size: 56px; opacity: 0.3;"></i>
            <p class="mt-3 mb-1 fs-5">No hay lecturas registradas</p>
            <p class="text-muted small">
                Cuando el agente SNMP envíe datos al endpoint, aparecerán aquí.
                Genera un token desde el perfil de un cliente para empezar.
            </p>
        </div>

        <div v-else class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Equipo</th>
                        <th>Serie</th>
                        <th class="text-end">Mono</th>
                        <th class="text-end">Color</th>
                        <th class="text-center" colspan="4">Tóner</th>
                        <th class="text-center">Match</th>
                    </tr>
                    <tr class="small text-muted">
                        <th colspan="6"></th>
                        <th class="text-center">K</th>
                        <th class="text-center">C</th>
                        <th class="text-center">M</th>
                        <th class="text-center">Y</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in readings" :key="r.id">
                        <td class="small">{{ formatFecha(r.read_at) }}</td>
                        <td>{{ r.client?.name || '—' }}</td>
                        <td>
                            <span v-if="r.rent_detail">
                                {{ r.rent_detail.trademark }} {{ r.rent_detail.model }}
                            </span>
                            <span v-else class="text-muted fst-italic">{{ r.raw_model || 'No identificado' }}</span>
                        </td>
                        <td><code>{{ r.raw_serial }}</code></td>
                        <td class="text-end">{{ formatNum(r.counter_mono) }}</td>
                        <td class="text-end">{{ formatNum(r.counter_color) }}</td>
                        <td class="text-center"><span v-if="r.toner_k != null" class="badge bg-dark">{{ r.toner_k }}%</span></td>
                        <td class="text-center"><span v-if="r.toner_c != null" class="badge bg-info">{{ r.toner_c }}%</span></td>
                        <td class="text-center"><span v-if="r.toner_m != null" class="badge" style="background:#d63384">{{ r.toner_m }}%</span></td>
                        <td class="text-center"><span v-if="r.toner_y != null" class="badge bg-warning text-dark">{{ r.toner_y }}%</span></td>
                        <td class="text-center">
                            <span class="badge" :class="r.matched ? 'bg-success' : 'bg-secondary'">
                                <i class="fa" :class="r.matched ? 'fa-check' : 'fa-times'"></i>
                                {{ r.matched ? 'Sí' : 'No' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="pagination && pagination.last_page > 1" class="mt-2">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item" :class="{disabled: pagination.current_page === 1}">
                    <a href="#" class="page-link" @click.prevent="cargar(pagination.current_page - 1)">«</a>
                </li>
                <li class="page-item active">
                    <span class="page-link">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                </li>
                <li class="page-item" :class="{disabled: pagination.current_page === pagination.last_page}">
                    <a href="#" class="page-link" @click.prevent="cargar(pagination.current_page + 1)">»</a>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script>
export default {
    name: 'SnmpReadingsComponent',

    data() {
        return {
            cargando: false,
            readings: [],
            pagination: null,
            filtroClienteId: null,
            filtroMatched: null,
            clientesConToken: [],
        };
    },

    mounted() {
        this.cargarClientes();
        this.cargar(1);
    },

    methods: {
        async cargarClientes() {
            try {
                const res = await axios.get('/admin/snmp-readings/clients');
                this.clientesConToken = res.data.clients || [];
            } catch (err) {
                /* silencioso, no es crítico */
            }
        },

        async cargar(page = 1) {
            this.cargando = true;
            try {
                const params = { page, per_page: 20 };
                if (this.filtroClienteId) params.client_id = this.filtroClienteId;
                if (this.filtroMatched !== null) params.matched = this.filtroMatched;

                const res = await axios.get('/admin/snmp-readings/get', { params });
                this.readings = res.data.data || [];
                this.pagination = {
                    current_page: res.data.current_page,
                    last_page: res.data.last_page,
                    total: res.data.total,
                };
            } catch (err) {
                Swal.fire('Error', 'No se pudieron cargar las lecturas SNMP.', 'error');
            } finally {
                this.cargando = false;
            }
        },

        formatFecha(iso) {
            if (!iso) return '—';
            return this.$filters.formatDate(iso, 'DD/MM/YYYY HH:mm');
        },

        formatNum(n) {
            if (n == null) return '—';
            return new Intl.NumberFormat('es-MX').format(n);
        },
    },
};
</script>
