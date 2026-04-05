<template>
<div>
    <div class="row">
        <div class="col-12 mb-3">
            <h4 style="color: var(--j2b-dark); font-weight: 600;">
                <i class="fa fa-route" style="color: var(--j2b-primary);"></i>
                Seguimiento de Servicio
            </h4>
            <p class="text-muted mb-0">Define los pasos que seguira el servicio de tu negocio. Tus clientes podran consultar en que etapa se encuentra su servicio.</p>
        </div>
    </div>

    <!-- Preview del flujo -->
    <div class="row" v-if="steps.length > 0">
        <div class="col-12 mb-4">
            <div class="j2b-card">
                <div class="j2b-card-header">
                    <i class="fa fa-eye mr-2"></i>Vista previa del flujo
                </div>
                <div class="j2b-card-body">
                    <div class="flow-preview">
                        <div v-for="(step, index) in activeSteps" :key="'preview-'+step.id" class="flow-step">
                            <div class="flow-circle" :style="{ backgroundColor: step.color || '#6c757d' }">
                                <i :class="step.icon || 'fa fa-circle'" class="text-white"></i>
                            </div>
                            <div class="flow-label">{{ step.name }}</div>
                            <div v-if="step.is_initial" class="flow-tag flow-tag-initial">Inicial</div>
                            <div v-if="step.is_final" class="flow-tag flow-tag-final">Final</div>
                            <div v-if="index < activeSteps.length - 1" class="flow-line"></div>
                        </div>
                    </div>
                    <div v-if="activeSteps.length === 0" class="text-center text-muted py-3">
                        No hay pasos activos para mostrar
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna izquierda: Listado de pasos -->
        <div class="col-lg-7">
            <div class="j2b-card mb-3">
                <div class="j2b-card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-list-ol mr-2"></i>Pasos configurados ({{ steps.length }})</span>
                    <button class="j2b-btn j2b-btn-primary j2b-btn-sm" @click="abrirModalCrear()">
                        <i class="fa fa-plus"></i> Agregar paso
                    </button>
                </div>
                <div class="j2b-card-body p-0">
                    <div v-if="loading" class="text-center py-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                    </div>

                    <div v-else-if="steps.length === 0" class="text-center py-5">
                        <i class="fa fa-route fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No has configurado pasos de seguimiento.</p>
                        <p class="text-muted small">Agrega pasos para que tus clientes puedan ver el avance de su servicio.</p>
                        <button class="j2b-btn j2b-btn-primary" @click="abrirModalCrear()">
                            <i class="fa fa-plus"></i> Crear primer paso
                        </button>
                    </div>

                    <table v-else class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px;"></th>
                                <th>Orden</th>
                                <th>Paso</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(step, index) in steps" :key="step.id"
                                draggable="true"
                                @dragstart="onDragStart(index, $event)"
                                @dragover.prevent="onDragOver(index, $event)"
                                @drop="onDrop(index)"
                                @dragend="dragIndex = null"
                                :class="{ 'drag-over': dragOverIndex === index, 'opacity-50': !step.active }"
                                style="cursor: grab;">
                                <td class="text-center text-muted"><i class="fa fa-grip-vertical"></i></td>
                                <td>
                                    <span class="badge bg-secondary">{{ index + 1 }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="step-color-dot" :style="{ backgroundColor: step.color || '#6c757d' }">
                                            <i :class="step.icon || 'fa fa-circle'" class="text-white" style="font-size: 10px;"></i>
                                        </span>
                                        <div>
                                            <strong>{{ step.name }}</strong>
                                            <br v-if="step.description">
                                            <small v-if="step.description" class="text-muted">{{ step.description }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span v-if="step.is_initial" class="j2b-badge j2b-badge-success">Inicial</span>
                                    <span v-else-if="step.is_final" class="j2b-badge j2b-badge-primary">Final</span>
                                    <span v-else class="j2b-badge j2b-badge-outline">Intermedio</span>
                                </td>
                                <td>
                                    <span v-if="step.active" class="j2b-badge j2b-badge-success">Activo</span>
                                    <span v-else class="j2b-badge j2b-badge-danger">Inactivo</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="j2b-btn j2b-btn-sm j2b-btn-dark" @click="abrirModalEditar(step)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="j2b-btn j2b-btn-sm" :class="step.active ? 'j2b-btn-dark' : 'j2b-btn-primary'" @click="toggleActivo(step)" :title="step.active ? 'Desactivar' : 'Activar'">
                                            <i class="fa" :class="step.active ? 'fa-toggle-off' : 'fa-toggle-on'"></i>
                                        </button>
                                        <button class="j2b-btn j2b-btn-sm" style="background: var(--j2b-danger); color: #fff;" @click="eliminarPaso(step)" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Columna derecha: Ayuda -->
        <div class="col-lg-5">
            <div class="j2b-card mb-3">
                <div class="j2b-card-header">
                    <i class="fa fa-info-circle mr-2"></i>Como funciona
                </div>
                <div class="j2b-card-body">
                    <div class="mb-3">
                        <strong><i class="fa fa-play-circle text-success me-1"></i> Paso Inicial</strong>
                        <p class="small text-muted mb-2">Se asigna automaticamente cuando se crea una nueva tarea. Solo puede haber uno.</p>
                    </div>
                    <div class="mb-3">
                        <strong><i class="fa fa-flag-checkered text-primary me-1"></i> Paso Final</strong>
                        <p class="small text-muted mb-2">Indica que el servicio esta completo/entregado. Solo puede haber uno.</p>
                    </div>
                    <div class="mb-3">
                        <strong><i class="fa fa-arrows-alt text-info me-1"></i> Reordenar</strong>
                        <p class="small text-muted mb-2">Arrastra y suelta los pasos para cambiar el orden del flujo.</p>
                    </div>
                    <div class="mb-3">
                        <strong><i class="fa fa-qrcode text-dark me-1"></i> Codigo QR</strong>
                        <p class="small text-muted mb-0">Al crear una tarea se genera un codigo de seguimiento. El cliente puede escanear un QR para ver el avance de su servicio en una pagina publica.</p>
                    </div>
                </div>
            </div>

            <div class="j2b-card mb-3">
                <div class="j2b-card-header">
                    <i class="fa fa-lightbulb mr-2"></i>Ejemplos por giro
                </div>
                <div class="j2b-card-body">
                    <div class="mb-2">
                        <strong class="small">Reparacion de celulares:</strong>
                        <p class="small text-muted mb-1">Recepcion &rarr; Diagnostico &rarr; Cotizacion &rarr; Reparacion &rarr; Pruebas &rarr; Listo para entrega &rarr; Entregado</p>
                    </div>
                    <div class="mb-2">
                        <strong class="small">Taller mecanico:</strong>
                        <p class="small text-muted mb-1">Ingreso &rarr; Inspeccion &rarr; Presupuesto &rarr; En reparacion &rarr; Control de calidad &rarr; Entregado</p>
                    </div>
                    <div class="mb-0">
                        <strong class="small">Servicio tecnico (impresoras):</strong>
                        <p class="small text-muted mb-0">Solicitud recibida &rarr; Tecnico asignado &rarr; En camino &rarr; En sitio &rarr; Resuelto</p>
                    </div>
                </div>
            </div>

            <!-- Aviso legal para comprobante -->
            <div class="j2b-card">
                <div class="j2b-card-header">
                    <i class="fa fa-file-contract mr-2"></i>Aviso legal del comprobante
                </div>
                <div class="j2b-card-body">
                    <p class="small text-muted mb-2">Este texto aparecera en el comprobante de recepcion que se imprime para el cliente.</p>
                    <textarea class="form-control mb-2" v-model="receiptDisclaimer" rows="3" maxlength="1000" placeholder="Ej: No nos hacemos responsables por equipos no reclamados despues de 15 dias..."></textarea>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">{{ (receiptDisclaimer || '').length }}/1000</small>
                        <button class="j2b-btn j2b-btn-primary j2b-btn-sm" @click="guardarDisclaimer()" :disabled="savingDisclaimer">
                            <i class="fa" :class="savingDisclaimer ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                            {{ savingDisclaimer ? 'Guardando...' : 'Guardar aviso' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar paso -->
    <div class="j2b-modal-overlay" :class="{ 'mostrar': showModal }" @click.self="showModal = false">
        <div class="j2b-modal" style="max-width: 500px;">
            <div class="j2b-modal-header">
                <h5 class="mb-0">
                    <i class="fa" :class="editingStep ? 'fa-edit' : 'fa-plus'"></i>
                    {{ editingStep ? 'Editar paso' : 'Nuevo paso' }}
                </h5>
                <button class="j2b-modal-close" @click="showModal = false">&times;</button>
            </div>
            <div class="j2b-modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre del paso *</label>
                    <input type="text" class="form-control" v-model="form.name" maxlength="100" placeholder="Ej: Recepcion, Diagnostico, En reparacion...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Descripcion</label>
                    <input type="text" class="form-control" v-model="form.description" maxlength="255" placeholder="Descripcion breve del paso (opcional)">
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Color</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color" v-model="form.color" style="width: 50px; height: 38px;">
                            <span class="small text-muted">{{ form.color }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Icono</label>
                        <select class="form-select" v-model="form.icon">
                            <option value="">Sin icono</option>
                            <option v-for="ic in iconOptions" :key="ic.value" :value="ic.value">{{ ic.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" v-model="form.is_initial" @change="form.is_initial ? form.is_final = false : null">
                            <label class="form-check-label">Paso inicial</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" v-model="form.is_final" @change="form.is_final ? form.is_initial = false : null">
                            <label class="form-check-label">Paso final</label>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="border rounded p-3 text-center" style="background: #f8f9fa;">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <span class="step-color-dot" :style="{ backgroundColor: form.color || '#6c757d' }">
                            <i :class="form.icon || 'fa fa-circle'" class="text-white" style="font-size: 10px;"></i>
                        </span>
                        <strong>{{ form.name || 'Nombre del paso' }}</strong>
                    </div>
                    <small v-if="form.description" class="text-muted">{{ form.description }}</small>
                </div>
            </div>
            <div class="j2b-modal-footer">
                <button class="j2b-btn j2b-btn-dark" @click="showModal = false">Cancelar</button>
                <button class="j2b-btn j2b-btn-primary" @click="guardarPaso()" :disabled="!form.name || saving">
                    <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    {{ saving ? 'Guardando...' : 'Guardar' }}
                </button>
            </div>
        </div>
    </div>
</div>
</template>

<script>
export default {
    data() {
        return {
            steps: [],
            loading: true,
            saving: false,
            showModal: false,
            editingStep: null,
            form: {
                name: '',
                description: '',
                color: '#0d6efd',
                icon: '',
                is_initial: false,
                is_final: false,
            },
            dragIndex: null,
            dragOverIndex: null,
            iconOptions: [
                { value: 'fa fa-inbox', label: 'Recepcion' },
                { value: 'fa fa-search', label: 'Diagnostico / Busqueda' },
                { value: 'fa fa-dollar-sign', label: 'Cotizacion / Precio' },
                { value: 'fa fa-wrench', label: 'Reparacion / Herramienta' },
                { value: 'fa fa-cogs', label: 'En proceso / Engranajes' },
                { value: 'fa fa-check-circle', label: 'Verificado / Listo' },
                { value: 'fa fa-truck', label: 'En camino / Transporte' },
                { value: 'fa fa-map-marker-alt', label: 'En sitio / Ubicacion' },
                { value: 'fa fa-clipboard-check', label: 'Control de calidad' },
                { value: 'fa fa-box', label: 'Empaquetado / Caja' },
                { value: 'fa fa-handshake', label: 'Entregado' },
                { value: 'fa fa-flag-checkered', label: 'Finalizado' },
                { value: 'fa fa-phone', label: 'Contacto / Llamada' },
                { value: 'fa fa-camera', label: 'Foto / Evidencia' },
                { value: 'fa fa-tools', label: 'Herramientas' },
                { value: 'fa fa-car', label: 'Vehiculo' },
                { value: 'fa fa-mobile-alt', label: 'Celular' },
                { value: 'fa fa-print', label: 'Impresora' },
                { value: 'fa fa-laptop', label: 'Computadora' },
                { value: 'fa fa-star', label: 'Estrella' },
            ],
            receiptDisclaimer: '',
            savingDisclaimer: false,
        };
    },
    computed: {
        activeSteps() {
            return this.steps.filter(s => s.active);
        }
    },
    mounted() {
        this.loadSteps();
    },
    methods: {
        loadSteps() {
            this.loading = true;
            axios.get('/admin/configurations/service-tracking/get')
                .then(res => {
                    this.steps = res.data.steps;
                    this.receiptDisclaimer = res.data.receipt_disclaimer || '';
                })
                .catch(err => {
                    alert('Error al cargar los pasos');
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        abrirModalCrear() {
            this.editingStep = null;
            this.form = {
                name: '',
                description: '',
                color: '#0d6efd',
                icon: '',
                is_initial: this.steps.length === 0,
                is_final: false,
            };
            this.showModal = true;
        },

        abrirModalEditar(step) {
            this.editingStep = step;
            this.form = {
                name: step.name,
                description: step.description || '',
                color: step.color || '#0d6efd',
                icon: step.icon || '',
                is_initial: step.is_initial,
                is_final: step.is_final,
            };
            this.showModal = true;
        },

        guardarPaso() {
            if (!this.form.name) return;
            this.saving = true;

            const data = { ...this.form };
            let request;

            if (this.editingStep) {
                request = axios.put('/admin/configurations/service-tracking/' + this.editingStep.id, data);
            } else {
                request = axios.post('/admin/configurations/service-tracking/store', data);
            }

            request
                .then(res => {
                    this.showModal = false;
                    this.loadSteps();
                })
                .catch(err => {
                    alert(err.response?.data?.message || 'Error al guardar');
                })
                .finally(() => {
                    this.saving = false;
                });
        },

        toggleActivo(step) {
            axios.put('/admin/configurations/service-tracking/' + step.id + '/toggle')
                .then(res => {
                    this.loadSteps();
                })
                .catch(err => {
                    alert('Error al cambiar estado');
                });
        },

        eliminarPaso(step) {
            if (!confirm('¿Eliminar el paso "' + step.name + '"?')) return;

            axios.delete('/admin/configurations/service-tracking/' + step.id)
                .then(res => {
                    this.loadSteps();
                })
                .catch(err => {
                    alert(err.response?.data?.message || 'Error al eliminar');
                });
        },

        // Drag & drop
        onDragStart(index, event) {
            this.dragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
        },

        onDragOver(index, event) {
            this.dragOverIndex = index;
        },

        onDrop(index) {
            this.dragOverIndex = null;
            if (this.dragIndex === null || this.dragIndex === index) return;

            const moved = this.steps.splice(this.dragIndex, 1)[0];
            this.steps.splice(index, 0, moved);
            this.dragIndex = null;

            // Guardar nuevo orden
            const ids = this.steps.map(s => s.id);
            axios.put('/admin/configurations/service-tracking/reorder/steps', { ids })
                .catch(err => {
                    alert('Error al reordenar');
                    this.loadSteps();
                });
        },

        guardarDisclaimer() {
            this.savingDisclaimer = true;
            axios.put('/admin/configurations/service-tracking/disclaimer', {
                receipt_disclaimer: this.receiptDisclaimer || null,
            })
                .then(() => {
                    alert('Aviso legal guardado');
                })
                .catch(() => {
                    alert('Error al guardar');
                })
                .finally(() => {
                    this.savingDisclaimer = false;
                });
        },
    }
};
</script>

<style scoped>
/* Modal overlay */
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

.j2b-modal {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    margin: 1rem;
}

.j2b-modal-header {
    background: var(--j2b-gradient-dark, linear-gradient(135deg, #1a1a2e, #16213e));
    color: #fff;
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.j2b-modal-close {
    background: rgba(255,255,255,0.1);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.2rem;
    transition: background 0.2s;
}

.j2b-modal-close:hover {
    background: rgba(255,255,255,0.2);
}

.j2b-modal-body {
    padding: 1.5rem;
}

.j2b-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.step-color-dot {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.drag-over {
    background-color: #e3f2fd !important;
}

/* Flow preview */
.flow-preview {
    display: flex;
    align-items: flex-start;
    overflow-x: auto;
    padding: 15px 0;
    gap: 0;
}

.flow-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    min-width: 100px;
    flex-shrink: 0;
}

.flow-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.flow-label {
    margin-top: 8px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    max-width: 90px;
}

.flow-tag {
    font-size: 10px;
    padding: 1px 6px;
    border-radius: 10px;
    margin-top: 4px;
}

.flow-tag-initial {
    background: #d4edda;
    color: #155724;
}

.flow-tag-final {
    background: #cce5ff;
    color: #004085;
}

.flow-line {
    position: absolute;
    top: 20px;
    left: 70px;
    width: calc(100% - 40px);
    height: 2px;
    background: #dee2e6;
    z-index: 0;
}
</style>
