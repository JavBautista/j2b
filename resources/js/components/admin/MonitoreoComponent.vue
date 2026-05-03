<template>
    <div class="monitoreo-container">
        <!-- Card resumen contadores -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card counter-card border-success">
                    <div class="card-body text-center">
                        <div class="counter-icon text-success">
                            <i class="fa fa-circle-o-notch fa-spin"></i>
                        </div>
                        <h3 class="counter-value">{{ counters.activos }}</h3>
                        <small class="text-muted text-uppercase">En progreso</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card counter-card border-primary">
                    <div class="card-body text-center">
                        <div class="counter-icon text-primary">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <h3 class="counter-value">{{ counters.finalizados }}</h3>
                        <small class="text-muted text-uppercase">Finalizados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card counter-card border-danger">
                    <div class="card-body text-center">
                        <div class="counter-icon text-danger">
                            <i class="fa fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="counter-value">{{ counters.sospechosos }}</h3>
                        <small class="text-muted text-uppercase">Sospechosos (0 km)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtro búsqueda -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                    <input
                        v-model="buscar"
                        @input="onSearchInput"
                        type="text"
                        class="form-control"
                        placeholder="Buscar por título, folio, colaborador o cliente..."
                    >
                    <button v-if="buscar" class="btn btn-outline-secondary" @click="limpiarBuscar">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-3">Cargando trackings...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="!tasks.length" class="text-center py-5">
            <i class="fa fa-map-o fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Sin trackings registrados</h5>
            <p class="text-muted">
                {{ buscar ? 'No hay resultados para tu búsqueda.' : 'Cuando un colaborador inicie un recorrido GPS desde la app aparecerá aquí.' }}
            </p>
        </div>

        <!-- Grid de tarjetas -->
        <div v-else class="row">
            <div v-for="task in tasks" :key="task.id" class="col-lg-4 col-md-6 mb-3">
                <div class="card task-card h-100" :class="cardClass(task)" @click="abrirTracking(task)">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge" :class="badgeClass(task)">
                                <i :class="badgeIcon(task)"></i> {{ badgeText(task) }}
                            </span>
                            <small class="text-muted">#{{ task.id }}</small>
                        </div>
                        <h6 class="card-title mb-2 text-truncate" :title="task.title">{{ task.title || 'Sin título' }}</h6>
                        <div class="task-meta">
                            <div class="small text-muted mb-1">
                                <i class="fa fa-user"></i> {{ task.assigned_user?.name || 'Sin asignar' }}
                            </div>
                            <div class="small text-muted mb-1">
                                <i class="fa fa-building"></i> {{ task.client?.name || 'Sin cliente' }}
                            </div>
                            <div class="small text-muted">
                                <i class="fa fa-clock-o"></i> {{ formatFecha(task.tracking_started_at) }}
                            </div>
                        </div>
                        <div v-if="!task.tracking_active && task.tracking_finished_at" class="task-metrics mt-3 pt-2 border-top">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="metric-value">{{ task.tracking_distance_km ?? '0.00' }} km</div>
                                    <small class="text-muted">Distancia</small>
                                </div>
                                <div class="col-6">
                                    <div class="metric-value">{{ formatDuracion(task.tracking_duration_minutes) }}</div>
                                    <small class="text-muted">Duración</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paginación -->
        <div v-if="lastPage > 1" class="d-flex justify-content-center mt-4">
            <nav>
                <ul class="pagination">
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                        <button class="page-link" @click="cambiarPagina(currentPage - 1)">Anterior</button>
                    </li>
                    <li v-for="page in pageRange" :key="page" class="page-item" :class="{ active: page === currentPage }">
                        <button class="page-link" @click="cambiarPagina(page)">{{ page }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage === lastPage }">
                        <button class="page-link" @click="cambiarPagina(currentPage + 1)">Siguiente</button>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Modal de mapa -->
        <tracking-map-modal
            :show="showMapModal"
            :task="selectedTask"
            :mode="selectedMode"
            :route-points="selectedRoute"
            :metrics="selectedMetrics"
            :shop-id="selectedShopId"
            :task-id="selectedTaskId"
            @close="cerrarMapa"
        ></tracking-map-modal>

        <!-- Modal de lista de recorridos (cuando hay múltiples) -->
        <tracking-history-list-modal
            :show="showHistoryModal"
            :task="selectedTask"
            :history="selectedHistory"
            @close="cerrarHistorial"
            @select="seleccionarRecorrido"
        ></tracking-history-list-modal>
    </div>
</template>

<script>
export default {
    name: 'MonitoreoComponent',
    data() {
        return {
            loading: false,
            tasks: [],
            counters: { activos: 0, finalizados: 0, sospechosos: 0 },
            buscar: '',
            currentPage: 1,
            lastPage: 1,
            searchTimer: null,
            showMapModal: false,
            showHistoryModal: false,
            selectedTask: null,
            selectedHistory: [],
            selectedRoute: [],
            selectedMetrics: null,
            selectedMode: 'historic',
            selectedShopId: null,
            selectedTaskId: null,
        };
    },
    computed: {
        pageRange() {
            const max = 5;
            const start = Math.max(1, this.currentPage - 2);
            const end = Math.min(this.lastPage, start + max - 1);
            const pages = [];
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },
    },
    methods: {
        async loadTasks(page = 1) {
            this.loading = true;
            try {
                const { data } = await axios.get('/admin/monitoreo/get', {
                    params: { page, buscar: this.buscar },
                });
                this.tasks = data.data || [];
                this.currentPage = data.current_page || 1;
                this.lastPage = data.last_page || 1;
            } catch (err) {
                this.showToast('error', 'Error al cargar trackings');
            } finally {
                this.loading = false;
            }
        },

        async loadCounters() {
            try {
                const { data } = await axios.get('/admin/monitoreo/counters');
                this.counters = data;
            } catch (err) {
                // silencioso
            }
        },

        onSearchInput() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.loadTasks(1);
            }, 400);
        },

        limpiarBuscar() {
            this.buscar = '';
            this.loadTasks(1);
        },

        cambiarPagina(page) {
            if (page < 1 || page > this.lastPage) return;
            this.loadTasks(page);
        },

        async abrirTracking(task) {
            if (task.tracking_active) {
                // Modo tiempo real: subscribir a Firebase
                this.selectedTask = task;
                this.selectedMode = 'realtime';
                this.selectedShopId = task.shop_id;
                this.selectedTaskId = task.id;
                this.selectedRoute = [];
                this.selectedMetrics = null;
                this.showMapModal = true;
                return;
            }

            // Histórico: pedir route_points al backend
            try {
                const { data } = await axios.get(`/admin/monitoreo/${task.id}/history`);
                const history = data.history || [];

                if (history.length === 0) {
                    this.showToast('error', 'Esta tarea no tiene recorridos guardados.');
                    return;
                }

                if (history.length > 1) {
                    // Múltiples recorridos: mostrar selector
                    this.selectedTask = task;
                    this.selectedHistory = history;
                    this.showHistoryModal = true;
                    return;
                }

                this.abrirRecorrido(task, history[0]);
            } catch (err) {
                this.showToast('error', 'Error al cargar el histórico');
            }
        },

        abrirRecorrido(task, recorrido) {
            this.selectedTask = task;
            this.selectedMode = 'historic';
            this.selectedRoute = recorrido.route_points || [];
            this.selectedMetrics = {
                distance: parseFloat(recorrido.distance_km).toFixed(2),
                duration: this.formatDuracion(recorrido.duration_minutes),
                points: recorrido.gps_points_count || (recorrido.route_points?.length ?? 0),
            };
            this.showMapModal = true;
        },

        seleccionarRecorrido(recorrido) {
            // Cierra modal de lista y abre modal de mapa con el recorrido seleccionado
            this.showHistoryModal = false;
            this.abrirRecorrido(this.selectedTask, recorrido);
        },

        cerrarHistorial() {
            this.showHistoryModal = false;
            this.selectedHistory = [];
            this.selectedTask = null;
        },

        cerrarMapa() {
            this.showMapModal = false;
            this.selectedTask = null;
            this.selectedRoute = [];
            this.selectedMetrics = null;
            this.selectedMode = 'historic';
            this.selectedShopId = null;
            this.selectedTaskId = null;
        },

        cardClass(task) {
            if (task.tracking_active) return 'border-success';
            if (parseFloat(task.tracking_distance_km) === 0) return 'border-danger';
            return 'border-primary';
        },

        badgeClass(task) {
            if (task.tracking_active) return 'bg-success';
            if (parseFloat(task.tracking_distance_km) === 0) return 'bg-danger';
            return 'bg-primary';
        },

        badgeIcon(task) {
            if (task.tracking_active) return 'fa fa-circle-o-notch fa-spin';
            if (parseFloat(task.tracking_distance_km) === 0) return 'fa fa-exclamation-triangle';
            return 'fa fa-check';
        },

        badgeText(task) {
            if (task.tracking_active) return 'En progreso';
            if (parseFloat(task.tracking_distance_km) === 0) return 'Sospechoso';
            return 'Finalizado';
        },

        formatFecha(iso) {
            if (!iso) return 'Sin fecha';
            const d = new Date(iso);
            return d.toLocaleString('es-MX', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        },

        formatDuracion(minutos) {
            if (!minutos) return '—';
            if (minutos < 60) return `${minutos} min`;
            const h = Math.floor(minutos / 60);
            const m = minutos % 60;
            return m === 0 ? `${h} h` : `${h}h ${m}m`;
        },

        showToast(type, message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type === 'success' ? 'success' : 'error',
                    title: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                });
            } else {
                alert(message);
            }
        },
    },
    mounted() {
        this.loadCounters();
        this.loadTasks();
    },
};
</script>

<style scoped>
.monitoreo-container {
    padding: 1rem 0;
}

.counter-card {
    border-left-width: 4px;
    transition: transform 0.15s ease;
}
.counter-card:hover {
    transform: translateY(-2px);
}
.counter-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}
.counter-value {
    font-weight: 700;
    margin-bottom: 0;
}

.task-card {
    border-left-width: 4px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.task-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.task-meta i {
    width: 16px;
    text-align: center;
}

.metric-value {
    font-weight: 600;
    font-size: 0.95rem;
}

.badge i {
    margin-right: 4px;
}
</style>
