<template>
    <div class="j2b-modal-overlay" :class="{ mostrar: show }" @click.self="cerrar">
        <div class="j2b-modal history-modal">
            <div class="j2b-modal-header">
                <div>
                    <h5 class="mb-0">
                        <i class="fa fa-list"></i>
                        Recorridos de la tarea
                    </h5>
                    <small v-if="task" class="text-light opacity-75">
                        {{ task.title }} <span v-if="task.assigned_user?.name">· {{ task.assigned_user.name }}</span>
                    </small>
                </div>
                <button class="j2b-modal-close" @click="cerrar" title="Cerrar">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="j2b-modal-body">
                <p class="text-muted mb-3">
                    <i class="fa fa-info-circle"></i>
                    Esta tarea tiene {{ history.length }} recorridos guardados. Selecciona uno para ver el mapa.
                </p>

                <div class="recorrido-list">
                    <div
                        v-for="(recorrido, idx) in history"
                        :key="recorrido.id"
                        class="recorrido-card"
                        @click="seleccionar(recorrido)"
                    >
                        <div class="recorrido-num">#{{ history.length - idx }}</div>
                        <div class="recorrido-info">
                            <div class="recorrido-fecha">
                                <i class="fa fa-calendar"></i>
                                {{ formatFecha(recorrido.start_timestamp) }}
                            </div>
                            <div class="recorrido-metrics">
                                <span class="metric-pill">
                                    <i class="fa fa-road text-primary"></i>
                                    {{ formatDistancia(recorrido.distance_km) }}
                                </span>
                                <span class="metric-pill">
                                    <i class="fa fa-clock-o text-info"></i>
                                    {{ formatDuracion(recorrido.duration_minutes) }}
                                </span>
                                <span class="metric-pill">
                                    <i class="fa fa-map-pin text-success"></i>
                                    {{ recorrido.gps_points_count || 0 }} puntos
                                </span>
                            </div>
                        </div>
                        <div class="recorrido-arrow">
                            <i class="fa fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TrackingHistoryListModal',
    props: {
        show: { type: Boolean, default: false },
        task: { type: Object, default: null },
        history: { type: Array, default: () => [] },
    },
    emits: ['close', 'select'],
    methods: {
        seleccionar(recorrido) {
            this.$emit('select', recorrido);
        },
        cerrar() {
            this.$emit('close');
        },
        formatFecha(iso) {
            if (!iso) return 'Sin fecha';
            const d = new Date(iso);
            return d.toLocaleString('es-MX', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        },
        formatDistancia(km) {
            const n = parseFloat(km) || 0;
            return `${n.toFixed(2)} km`;
        },
        formatDuracion(minutos) {
            if (!minutos) return '—';
            if (minutos < 60) return `${minutos} min`;
            const h = Math.floor(minutos / 60);
            const m = minutos % 60;
            return m === 0 ? `${h} h` : `${h}h ${m}m`;
        },
    },
};
</script>

<style scoped>
.j2b-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1050;
    overflow-y: auto;
    background-color: rgba(26, 26, 46, 0.8);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.j2b-modal-overlay.mostrar {
    display: flex !important;
    opacity: 1 !important;
    align-items: center;
    justify-content: center;
}

.j2b-modal.history-modal {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 700px;
    margin: 1rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 2rem);
}

.j2b-modal-header {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    color: #fff;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.j2b-modal-close {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.j2b-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.j2b-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
}

.recorrido-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.recorrido-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-left: 4px solid #007cbf;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.recorrido-card:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background: #fff;
}

.recorrido-num {
    background: #007cbf;
    color: #fff;
    font-weight: 700;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.recorrido-info {
    flex: 1;
    min-width: 0;
}

.recorrido-fecha {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.5rem;
}
.recorrido-fecha i {
    color: #6c757d;
    margin-right: 0.4rem;
}

.recorrido-metrics {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.metric-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.6rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    font-size: 0.85rem;
    color: #495057;
}

.recorrido-arrow {
    color: #adb5bd;
    flex-shrink: 0;
}

@media (max-width: 576px) {
    .recorrido-metrics {
        gap: 0.4rem;
    }
    .metric-pill {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
    }
}
</style>
