<template>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h5 class="mb-0 me-auto"><i class="fa fa-history"></i> Bitácora de Timbrado</h5>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <input type="date" class="form-control form-control-sm" v-model="filtros.fecha_inicio" style="width:150px">
                    <input type="date" class="form-control form-control-sm" v-model="filtros.fecha_fin" style="width:150px">

                    <select class="form-control form-control-sm" v-model="filtros.event_type" style="width:170px" @change="cargar">
                        <option value="todos">Todos los eventos</option>
                        <option value="cfdi.timbrado">Timbrado</option>
                        <option value="cfdi.cancelacion">Cancelación</option>
                        <option value="cfdi.complemento_pago">Complemento Pago</option>
                        <option value="cfdi.hub">Hub (TBT)</option>
                    </select>

                    <select class="form-control form-control-sm" v-model="filtros.status" style="width:130px" @change="cargar">
                        <option value="todos">Cualquier estado</option>
                        <option value="success">Éxito</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="pending">Pendiente</option>
                    </select>

                    <select class="form-control form-control-sm" v-model="filtros.source" style="width:130px" @change="cargar">
                        <option value="todos">Cualquier origen</option>
                        <option value="plataforma">Plataforma</option>
                        <option value="ionic">Ionic</option>
                        <option value="system">Sistema</option>
                        <option value="unknown">Desconocido</option>
                    </select>

                    <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="filtros.buscar"
                        placeholder="UUID, request_id o error..."
                        style="width:220px"
                        @keyup.enter="cargar"
                    >

                    <button class="btn btn-sm btn-primary" @click="cargar" :disabled="loading">
                        <i class="fa fa-search"></i> Buscar
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div v-if="loading" class="text-center py-5">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p class="mt-2">Cargando bitácora...</p>
                </div>

                <div class="table-responsive" v-if="!loading">
                    <table class="table table-hover table-sm" v-if="rows.length">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Evento</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Origen</th>
                                <th>UUID</th>
                                <th class="text-right">Duración</th>
                                <th>Error</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in rows" :key="r.id">
                                <td><small>{{ r.created_at }}</small></td>
                                <td>
                                    <code class="small">{{ r.event_type }}</code>
                                </td>
                                <td class="text-center">
                                    <span class="badge" :class="statusClass(r.status)">{{ r.status }}</span>
                                </td>
                                <td class="text-center">
                                    <i :class="sourceIcon(r.source)" :title="r.source"></i>
                                    <small class="ms-1">{{ r.source }}</small>
                                </td>
                                <td>
                                    <small class="text-muted" v-if="r.uuid">{{ r.uuid }}</small>
                                    <small class="text-muted" v-else>—</small>
                                </td>
                                <td class="text-right">
                                    <small v-if="r.duration_ms">{{ r.duration_ms }} ms</small>
                                    <small v-else>—</small>
                                </td>
                                <td>
                                    <small v-if="r.error_message" class="text-danger" :title="r.error_message">
                                        {{ truncate(r.error_message, 60) }}
                                    </small>
                                    <small v-else>—</small>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-outline-primary" @click="verDetalle(r.id)" title="Ver payload completo">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="text-center text-muted py-4">
                        <i class="fa fa-inbox fa-3x mb-2"></i>
                        <p>No hay registros con los filtros aplicados.</p>
                    </div>
                </div>

                <!-- Paginación -->
                <div class="d-flex justify-content-between align-items-center mt-3" v-if="!loading && meta.total > 0">
                    <small class="text-muted">
                        Mostrando {{ rows.length }} de {{ meta.total }} registros
                    </small>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary"
                                @click="paginar(meta.current_page - 1)"
                                :disabled="meta.current_page <= 1 || loading">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <span class="mx-2">Página {{ meta.current_page }} / {{ meta.last_page }}</span>
                        <button class="btn btn-sm btn-outline-secondary"
                                @click="paginar(meta.current_page + 1)"
                                :disabled="meta.current_page >= meta.last_page || loading">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal detalle -->
        <div class="modal fade" :class="{ 'show d-block': showModal }" tabindex="-1" v-if="showModal" @click.self="cerrarModal">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa fa-history"></i> Detalle del log #{{ detalle?.id }}
                        </h5>
                        <button type="button" class="btn-close" @click="cerrarModal"></button>
                    </div>
                    <div class="modal-body" v-if="detalle">
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Fecha:</strong> {{ detalle.created_at }}</div>
                            <div class="col-md-3"><strong>Evento:</strong> <code>{{ detalle.event_type }}</code></div>
                            <div class="col-md-2"><strong>Status:</strong>
                                <span class="badge" :class="statusClass(detalle.status)">{{ detalle.status }}</span>
                            </div>
                            <div class="col-md-2"><strong>Origen:</strong> {{ detalle.source }}</div>
                            <div class="col-md-2"><strong>Duración:</strong> {{ detalle.duration_ms || '—' }} ms</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4"><strong>Request ID:</strong> <code class="small">{{ detalle.request_id }}</code></div>
                            <div class="col-md-4" v-if="detalle.uuid"><strong>UUID CFDI:</strong> <code class="small">{{ detalle.uuid }}</code></div>
                            <div class="col-md-4" v-if="detalle.http_status"><strong>HTTP:</strong> {{ detalle.http_status }}</div>
                        </div>

                        <div v-if="detalle.error_message" class="alert alert-danger">
                            <strong>Error:</strong> <span v-if="detalle.error_code">[{{ detalle.error_code }}]</span><br>
                            <code>{{ detalle.error_message }}</code>
                        </div>

                        <div v-if="detalle.metadata">
                            <h6 class="mt-3">Metadata</h6>
                            <pre class="bg-light p-2 small">{{ jsonPretty(detalle.metadata) }}</pre>
                        </div>

                        <div v-if="detalle.request_payload">
                            <h6 class="mt-3">Request payload (sanitizado)</h6>
                            <pre class="bg-light p-2 small" style="max-height:400px;overflow:auto">{{ jsonPretty(detalle.request_payload) }}</pre>
                        </div>

                        <div v-if="detalle.response_payload">
                            <h6 class="mt-3">Response payload</h6>
                            <pre class="bg-light p-2 small" style="max-height:400px;overflow:auto">{{ jsonPretty(detalle.response_payload) }}</pre>
                        </div>

                        <div v-if="detalle.attempts && detalle.attempts.length">
                            <h6 class="mt-3">Intentos</h6>
                            <pre class="bg-light p-2 small">{{ jsonPretty(detalle.attempts) }}</pre>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="cerrarModal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" v-if="showModal"></div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            loading: false,
            rows: [],
            meta: { current_page: 1, last_page: 1, per_page: 50, total: 0 },
            filtros: {
                fecha_inicio: this.defaultFechaInicio(),
                fecha_fin: this.defaultFechaFin(),
                status: 'todos',
                source: 'todos',
                event_type: 'todos',
                buscar: '',
            },
            showModal: false,
            detalle: null,
        };
    },
    mounted() {
        this.cargar();
    },
    methods: {
        defaultFechaInicio() {
            const d = new Date();
            d.setDate(d.getDate() - 7);
            return d.toISOString().slice(0, 10);
        },
        defaultFechaFin() {
            return new Date().toISOString().slice(0, 10);
        },
        async cargar(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page,
                    per_page: 50,
                    fecha_inicio: this.filtros.fecha_inicio,
                    fecha_fin: this.filtros.fecha_fin,
                    status: this.filtros.status,
                    source: this.filtros.source,
                    event_type: this.filtros.event_type,
                    buscar: this.filtros.buscar,
                });
                const { data } = await axios.get('/admin/facturacion/bitacora/get?' + params);
                if (data.ok) {
                    this.rows = data.data;
                    this.meta = data.meta;
                }
            } catch (e) {
                console.error('Error cargando bitácora', e);
            } finally {
                this.loading = false;
            }
        },
        paginar(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.cargar(page);
        },
        async verDetalle(id) {
            try {
                const { data } = await axios.get(`/admin/facturacion/bitacora/${id}`);
                if (data.ok) {
                    this.detalle = data.data;
                    this.showModal = true;
                }
            } catch (e) {
                console.error('Error cargando detalle', e);
            }
        },
        cerrarModal() {
            this.showModal = false;
            this.detalle = null;
        },
        statusClass(s) {
            return {
                'badge-success': s === 'success',
                'badge-danger': s === 'error',
                'badge-warning': s === 'warning',
                'badge-secondary': s === 'pending',
            };
        },
        sourceIcon(s) {
            return {
                'fa fa-desktop text-primary': s === 'plataforma',
                'fa fa-mobile text-info': s === 'ionic',
                'fa fa-cog text-secondary': s === 'system',
                'fa fa-question text-muted': s === 'unknown',
            };
        },
        truncate(s, n) {
            return s && s.length > n ? s.substring(0, n) + '…' : s;
        },
        jsonPretty(obj) {
            try { return JSON.stringify(obj, null, 2); } catch (e) { return String(obj); }
        },
    },
};
</script>
