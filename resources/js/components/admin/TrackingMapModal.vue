<template>
    <div class="j2b-modal-overlay" :class="{ mostrar: show }" @click.self="cerrar">
        <div class="j2b-modal map-modal">
            <div class="j2b-modal-header">
                <div>
                    <h5 class="mb-0">
                        <i class="fa fa-map-marker"></i>
                        {{ task?.title || 'Tracking GPS' }}
                        <span v-if="mode === 'realtime'" class="badge bg-success ms-2 live-badge">
                            <i class="fa fa-circle"></i> EN VIVO
                        </span>
                    </h5>
                    <small v-if="metaText" class="text-light opacity-75">{{ metaText }}</small>
                </div>
                <button class="j2b-modal-close" @click="cerrar" title="Cerrar">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="j2b-modal-body p-0">
                <div v-if="loading" class="map-loading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-3 mb-0">{{ mode === 'realtime' ? 'Conectando con la posición en vivo...' : 'Cargando mapa...' }}</p>
                </div>

                <div v-else-if="errorMsg" class="map-error">
                    <i class="fa fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <p class="text-muted">{{ errorMsg }}</p>
                </div>

                <div ref="mapContainer" class="map-container" :class="{ hidden: loading || errorMsg }"></div>

                <!-- Métricas inferiores -->
                <div v-if="!loading && !errorMsg && (metrics || mode === 'realtime')" class="map-metrics">
                    <template v-if="mode === 'realtime'">
                        <div class="metric-item">
                            <i class="fa fa-map-pin text-success"></i>
                            <span class="metric-label">Puntos</span>
                            <span class="metric-value">{{ realtimePointsCount }}</span>
                        </div>
                        <div class="metric-item">
                            <i class="fa fa-tachometer text-info"></i>
                            <span class="metric-label">Velocidad</span>
                            <span class="metric-value">{{ realtimeSpeedKmh }}</span>
                        </div>
                        <div class="metric-item">
                            <i class="fa fa-bullseye text-warning"></i>
                            <span class="metric-label">Precisión</span>
                            <span class="metric-value">{{ realtimeAccuracy }}</span>
                        </div>
                    </template>
                    <template v-else-if="metrics">
                        <div class="metric-item">
                            <i class="fa fa-road text-primary"></i>
                            <span class="metric-label">Distancia</span>
                            <span class="metric-value">{{ metrics.distance }} km</span>
                        </div>
                        <div class="metric-item">
                            <i class="fa fa-clock-o text-info"></i>
                            <span class="metric-label">Duración</span>
                            <span class="metric-value">{{ metrics.duration }}</span>
                        </div>
                        <div class="metric-item">
                            <i class="fa fa-map-pin text-success"></i>
                            <span class="metric-label">Puntos</span>
                            <span class="metric-value">{{ metrics.points }}</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import { listenToTaskTracking, pointsObjectToArray } from '../../services/firebase-realtime';

export default {
    name: 'TrackingMapModal',
    props: {
        show: { type: Boolean, default: false },
        task: { type: Object, default: null },
        mode: { type: String, default: 'historic' }, // 'historic' | 'realtime'
        routePoints: { type: Array, default: () => [] },
        metrics: { type: Object, default: null },
        shopId: { type: [Number, String], default: null },
        taskId: { type: [Number, String], default: null },
    },
    emits: ['close'],
    data() {
        return {
            map: null,
            loading: false,
            errorMsg: '',
            resizeObserver: null,
            firebaseUnsubscribe: null,
            currentPositionMarker: null,
            startMarker: null,
            realtimePoints: [],
            realtimeLastPosition: null,
            fitBoundsCounter: 0,
        };
    },
    computed: {
        metaText() {
            if (!this.task) return '';
            const partes = [];
            if (this.task.assigned_user?.name) partes.push(this.task.assigned_user.name);
            if (this.task.client?.name) partes.push(this.task.client.name);
            return partes.join(' · ');
        },
        realtimePointsCount() {
            return this.realtimePoints.length;
        },
        realtimeSpeedKmh() {
            const speed = this.realtimeLastPosition?.speed;
            if (typeof speed !== 'number' || speed < 0) return '—';
            return `${(speed * 3.6).toFixed(1)} km/h`;
        },
        realtimeAccuracy() {
            const acc = this.realtimeLastPosition?.accuracy;
            if (typeof acc !== 'number') return '—';
            return `${Math.round(acc)} m`;
        },
    },
    watch: {
        show(val) {
            if (val) {
                setTimeout(() => this.initMap(), 350);
            } else {
                this.destroyMap();
            }
        },
    },
    methods: {
        initMap() {
            this.errorMsg = '';
            this.realtimePoints = [];
            this.realtimeLastPosition = null;
            this.fitBoundsCounter = 0;

            const token = window.j2bConfig?.mapbox?.token;
            if (!token) {
                this.errorMsg = 'Token de Mapbox no configurado.';
                return;
            }
            mapboxgl.accessToken = token;
            this.loading = true;

            // Validaciones por modo
            if (this.mode === 'historic' && (!this.routePoints || this.routePoints.length < 2)) {
                this.errorMsg = 'Esta tarea no tiene puntos GPS suficientes para dibujar la ruta.';
                this.loading = false;
                return;
            }
            if (this.mode === 'realtime' && (!this.shopId || !this.taskId)) {
                this.errorMsg = 'Falta información de la tarea para conectar en tiempo real.';
                this.loading = false;
                return;
            }

            try {
                // Centro inicial: primer punto histórico, o CDMX por defecto en realtime
                const center = this.mode === 'historic'
                    ? [parseFloat(this.routePoints[0].lng), parseFloat(this.routePoints[0].lat)]
                    : [-99.133209, 19.432608];

                this.map = new mapboxgl.Map({
                    container: this.$refs.mapContainer,
                    style: 'mapbox://styles/mapbox/streets-v12',
                    center,
                    zoom: this.mode === 'historic' ? 13 : 12,
                });

                this.map.addControl(new mapboxgl.NavigationControl(), 'top-right');

                this.map.on('load', () => {
                    this.map.resize();
                    this.addRouteLayer();

                    if (this.mode === 'historic') {
                        this.drawHistoricRoute();
                        this.loading = false;
                    } else {
                        this.subscribeRealtime();
                    }
                });

                if (typeof ResizeObserver !== 'undefined') {
                    this.resizeObserver = new ResizeObserver(() => {
                        if (this.map) this.map.resize();
                    });
                    this.resizeObserver.observe(this.$refs.mapContainer);
                }
            } catch (e) {
                console.error(e);
                this.errorMsg = 'No se pudo inicializar el mapa.';
                this.loading = false;
            }
        },

        addRouteLayer() {
            this.map.addSource('route', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: { type: 'LineString', coordinates: [] },
                },
            });
            this.map.addLayer({
                id: 'route-line',
                type: 'line',
                source: 'route',
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: { 'line-color': '#007cbf', 'line-width': 4 },
            });
        },

        updateRouteSource(coords) {
            const source = this.map.getSource('route');
            if (source) {
                source.setData({
                    type: 'Feature',
                    properties: {},
                    geometry: { type: 'LineString', coordinates: coords },
                });
            }
        },

        drawHistoricRoute() {
            const coords = this.routePoints.map(p => [parseFloat(p.lng), parseFloat(p.lat)]);
            this.updateRouteSource(coords);

            new mapboxgl.Marker({ color: '#28a745', scale: 1.2 })
                .setLngLat(coords[0])
                .setPopup(new mapboxgl.Popup({ offset: 25 }).setText('🟢 Inicio'))
                .addTo(this.map);

            new mapboxgl.Marker({ color: '#dc3545', scale: 1.2 })
                .setLngLat(coords[coords.length - 1])
                .setPopup(new mapboxgl.Popup({ offset: 25 }).setText('🔴 Fin'))
                .addTo(this.map);

            const bounds = new mapboxgl.LngLatBounds();
            coords.forEach(c => bounds.extend(c));
            this.map.fitBounds(bounds, { padding: 60, duration: 800 });
        },

        subscribeRealtime() {
            try {
                this.firebaseUnsubscribe = listenToTaskTracking(
                    Number(this.shopId),
                    Number(this.taskId),
                    (data) => this.onRealtimeData(data),
                    (err) => {
                        console.error('Firebase error:', err);
                        this.errorMsg = 'Error de conexión con el servidor en tiempo real.';
                        this.loading = false;
                    }
                );
            } catch (e) {
                console.error(e);
                this.errorMsg = e.message || 'No se pudo conectar a Firebase.';
                this.loading = false;
            }
        },

        onRealtimeData(data) {
            this.loading = false;

            const points = pointsObjectToArray(data.points);
            this.realtimePoints = points;
            this.realtimeLastPosition = data.last_position || null;

            if (points.length === 0 && !data.last_position) {
                // Sin datos aún
                return;
            }

            const coords = points.map(p => [parseFloat(p.lng), parseFloat(p.lat)]);

            // 1) Actualizar línea de ruta (sin recrear mapa)
            if (coords.length >= 2) {
                this.updateRouteSource(coords);
            }

            // 2) Marcador de inicio (solo una vez)
            if (!this.startMarker && coords.length > 0) {
                this.startMarker = new mapboxgl.Marker({ color: '#28a745', scale: 1.2 })
                    .setLngLat(coords[0])
                    .setPopup(new mapboxgl.Popup({ offset: 25 }).setText('🟢 Inicio'))
                    .addTo(this.map);
            }

            // 3) Marcador de posición actual (azul pulsante)
            const currentLngLat = data.last_position
                ? [parseFloat(data.last_position.lng), parseFloat(data.last_position.lat)]
                : (coords.length > 0 ? coords[coords.length - 1] : null);

            if (currentLngLat) {
                if (!this.currentPositionMarker) {
                    const el = document.createElement('div');
                    el.className = 'current-position-marker';
                    el.innerHTML = '<div class="marker-pulse"></div><div class="marker-dot"></div>';
                    this.currentPositionMarker = new mapboxgl.Marker({ element: el })
                        .setLngLat(currentLngLat)
                        .addTo(this.map);
                } else {
                    this.currentPositionMarker.setLngLat(currentLngLat);
                }
            }

            // 4) fitBounds en primer render y cada 5 puntos nuevos (eficiencia)
            if (this.fitBoundsCounter === 0 || (coords.length > 0 && coords.length % 5 === 0)) {
                if (coords.length > 1) {
                    const bounds = new mapboxgl.LngLatBounds();
                    coords.forEach(c => bounds.extend(c));
                    this.map.fitBounds(bounds, { padding: 80, duration: 600, maxZoom: 16 });
                } else if (currentLngLat) {
                    this.map.flyTo({ center: currentLngLat, zoom: 15, duration: 600 });
                }
                this.fitBoundsCounter++;
            } else {
                this.fitBoundsCounter++;
            }
        },

        destroyMap() {
            if (this.firebaseUnsubscribe) {
                this.firebaseUnsubscribe();
                this.firebaseUnsubscribe = null;
            }
            if (this.resizeObserver) {
                this.resizeObserver.disconnect();
                this.resizeObserver = null;
            }
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
            this.startMarker = null;
            this.currentPositionMarker = null;
            this.realtimePoints = [];
            this.realtimeLastPosition = null;
            this.errorMsg = '';
            this.loading = false;
        },

        cerrar() {
            this.$emit('close');
        },
    },
    beforeUnmount() {
        this.destroyMap();
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

.j2b-modal.map-modal {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 1100px;
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
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.map-container {
    width: 100%;
    height: 65vh;
    min-height: 400px;
    position: relative;
    overflow: hidden;
}
.map-container.hidden {
    display: none;
}

.map-loading,
.map-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    min-height: 400px;
}

.map-metrics {
    display: flex;
    justify-content: space-around;
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    flex-shrink: 0;
}
.metric-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.metric-item i {
    font-size: 1.1rem;
}
.metric-label {
    color: #6c757d;
    font-size: 0.85rem;
}
.metric-value {
    font-weight: 600;
    color: #212529;
}

/* Live badge */
.live-badge {
    font-size: 0.7rem;
    vertical-align: middle;
}
.live-badge i {
    font-size: 0.5rem;
    animation: blink 1.5s infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

@media (max-width: 768px) {
    .map-container {
        height: 55vh;
    }
    .map-metrics {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<style>
/* Marcador pulsante de posición actual (no scoped para que aplique al elemento de Mapbox) */
.current-position-marker {
    width: 24px;
    height: 24px;
    position: relative;
}
.current-position-marker .marker-dot {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 14px;
    height: 14px;
    background-color: #007AFF;
    border: 3px solid white;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    z-index: 2;
}
.current-position-marker .marker-pulse {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 14px;
    height: 14px;
    background-color: rgba(0, 122, 255, 0.4);
    border-radius: 50%;
    animation: marker-pulse 2s infinite;
    z-index: 1;
}
@keyframes marker-pulse {
    0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
    50% { transform: translate(-50%, -50%) scale(2); opacity: 0.5; }
    100% { transform: translate(-50%, -50%) scale(3); opacity: 0; }
}
</style>
