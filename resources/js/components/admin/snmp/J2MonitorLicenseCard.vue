<template>
    <div class="card mb-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <i class="fa fa-id-card"></i>
                <strong class="ms-1">J2 Monitor — Licencias</strong>
                <small class="ms-2 d-none d-md-inline">Control de equipos en monitoreo</small>
            </div>
            <button class="btn btn-sm btn-outline-light" @click="abierto = !abierto">
                <i class="fa" :class="abierto ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                {{ abierto ? 'Ocultar' : 'Mostrar' }}
            </button>
        </div>

        <div v-show="abierto" class="card-body">
            <div v-if="cargando" class="text-center py-3">
                <i class="fa fa-spinner fa-spin"></i> Cargando...
            </div>

            <div v-else>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge" :class="resumen.monitor_active ? 'bg-success' : 'bg-secondary'">
                        <i class="fa" :class="resumen.monitor_active ? 'fa-check-circle' : 'fa-pause-circle'"></i>
                        Servicio {{ resumen.monitor_active ? 'activo' : 'inactivo' }}
                    </span>
                    <button class="btn btn-sm btn-outline-primary" @click="abrirEditar()">
                        <i class="fa fa-cog"></i> Editar configuración
                    </button>
                </div>

                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">
                            <strong>{{ resumen.used }}</strong> de <strong>{{ resumen.total }}</strong> licencias asignadas
                        </span>
                        <span class="small text-muted">
                            {{ resumen.available }} disponibles
                        </span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div
                            class="progress-bar"
                            :class="barClass"
                            :style="{ width: porcentaje + '%' }"
                            role="progressbar">
                        </div>
                    </div>
                </div>

                <div v-if="resumen.total === 0" class="alert alert-warning small mb-0 mt-2">
                    <i class="fa fa-info-circle"></i>
                    Aún no hay licencias contratadas. Edita la configuración para asignar el cupo.
                </div>
                <div v-else-if="!resumen.monitor_active" class="alert alert-secondary small mb-0 mt-2">
                    <i class="fa fa-info-circle"></i>
                    Servicio en pausa. El agente recibirá lista vacía hasta reactivar.
                </div>
            </div>
        </div>

        <!-- Modal Editar Configuración -->
        <div class="modal fade" tabindex="-1" :class="{ mostrar: modalEditar }" role="dialog" style="display: none;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fa fa-cog"></i> Configuración J2 Monitor</h5>
                        <button type="button" class="btn-close btn-close-white" @click="cerrarEditar()"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="monActiveSwitch" v-model="formEditar.monitor_active">
                            <label class="form-check-label" for="monActiveSwitch">
                                <strong>Servicio activo</strong>
                                <div class="small text-muted">
                                    Si está inactivo, el agente Python recibe lista vacía y deja de monitorear.
                                </div>
                            </label>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Licencias contratadas (cupo total)</label>
                            <input type="number" min="0" max="10000" class="form-control" v-model.number="formEditar.monitor_licenses_total">
                            <div class="form-text">
                                Máximo de equipos que pueden tener licencia asignada simultáneamente.
                                Actualmente {{ resumen.used }} equipo(s) ya tienen licencia.
                            </div>
                        </div>

                        <div v-if="formEditar.monitor_licenses_total < resumen.used" class="alert alert-danger small mb-0">
                            <i class="fa fa-exclamation-triangle"></i>
                            No puedes reducir a {{ formEditar.monitor_licenses_total }} licencias porque ya hay
                            {{ resumen.used }} equipos con licencia asignada. Desactiva primero los sobrantes.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarEditar()">Cancelar</button>
                        <button type="button" class="btn btn-primary" @click="guardar()" :disabled="guardando || formEditar.monitor_licenses_total < resumen.used">
                            <i v-if="guardando" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-save"></i>
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalEditar" class="modal-backdrop fade show"></div>
    </div>
</template>

<script>
export default {
    name: 'J2MonitorLicenseCard',

    props: {
        clientId: { type: [Number, String], required: true },
    },

    data() {
        return {
            cargando: true,
            abierto: true,
            resumen: { monitor_active: false, total: 0, used: 0, available: 0 },
            modalEditar: 0,
            guardando: false,
            formEditar: { monitor_active: false, monitor_licenses_total: 0 },
        };
    },

    computed: {
        porcentaje() {
            if (!this.resumen.total) return 0;
            return Math.min(100, Math.round((this.resumen.used / this.resumen.total) * 100));
        },
        barClass() {
            if (this.porcentaje >= 100) return 'bg-danger';
            if (this.porcentaje >= 80) return 'bg-warning';
            return 'bg-success';
        },
    },

    mounted() {
        this.cargar();
        this._refrescarHandler = () => this.cargar();
        window.addEventListener('monitor:license-changed', this._refrescarHandler);
    },

    unmounted() {
        if (this._refrescarHandler) {
            window.removeEventListener('monitor:license-changed', this._refrescarHandler);
        }
    },

    methods: {
        async cargar() {
            this.cargando = true;
            try {
                const res = await axios.get(`/admin/clients/${this.clientId}/monitor-config`);
                this.resumen = res.data.monitor;
            } catch (err) {
                Swal.fire('Error', err.response?.data?.message || 'No se pudo cargar la configuración J2 Monitor.', 'error');
            } finally {
                this.cargando = false;
            }
        },

        abrirEditar() {
            this.formEditar.monitor_active = !!this.resumen.monitor_active;
            this.formEditar.monitor_licenses_total = this.resumen.total || 0;
            this.modalEditar = 1;
        },

        cerrarEditar() {
            this.modalEditar = 0;
        },

        async guardar() {
            if (this.formEditar.monitor_licenses_total < this.resumen.used) return;
            this.guardando = true;
            try {
                const res = await axios.put(`/admin/clients/${this.clientId}/monitor-config`, {
                    monitor_active: !!this.formEditar.monitor_active,
                    monitor_licenses_total: this.formEditar.monitor_licenses_total,
                });
                this.resumen = res.data.monitor;
                this.cerrarEditar();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.data.message, showConfirmButton: false, timer: 1800 });
                window.dispatchEvent(new CustomEvent('monitor:license-changed'));
            } catch (err) {
                Swal.fire('Error', err.response?.data?.message || 'No se pudo guardar la configuración.', 'error');
            } finally {
                this.guardando = false;
            }
        },
    },
};
</script>
