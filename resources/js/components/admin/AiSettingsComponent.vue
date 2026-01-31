<template>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="fa fa-bolt text-warning"></i> Configuraciones IA
            </h4>
            <p class="text-muted mb-0">Personaliza el asistente de inteligencia artificial de tu tienda</p>
        </div>
        <a href="/admin/configurations/ai-settings" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-2 text-muted">Cargando configuración...</p>
    </div>

    <!-- Contenido -->
    <div v-else>
        <!-- Mensaje de sugerencia para primer prompt -->
        <div v-if="!settingsId" class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="me-3">
                        <i class="fa fa-info-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-primary">Antes de guardar tu primer prompt</h6>
                        <p class="mb-2 small">
                            El prompt se genera automáticamente con la información de tu tienda (nombre, descripción, misión, visión, valores, contacto, etc.).
                            <strong>Te recomendamos completar primero toda la información de tu negocio</strong> para que el asistente IA tenga el mejor contexto posible.
                        </p>
                        <a href="/admin/shop/edit" class="btn btn-sm btn-primary">
                            <i class="fa fa-building"></i> Completar información de mi tienda
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" @click="loadSettings">
                            <i class="fa fa-refresh"></i> Regenerar prompt
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="me-3">
                        <i class="fa fa-lightbulb-o fa-2x text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Prompt del Sistema</h6>
                        <p class="mb-0 small">
                            El prompt del sistema define la personalidad y conocimientos de tu asistente IA.
                            Personalízalo con información sobre tu negocio, productos, servicios y cómo quieres que responda a tus clientes.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-file-text-o text-primary me-2"></i>
                        Prompt de {{ shopName }}
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="resetToDefault" title="Restaurar prompt por defecto">
                        <i class="fa fa-refresh"></i> Restaurar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Prompt del Sistema <span class="text-danger">*</span>
                    </label>
                    <textarea
                        v-model="systemPrompt"
                        class="form-control"
                        rows="15"
                        placeholder="Escribe aquí las instrucciones para tu asistente IA..."
                        style="font-family: monospace; font-size: 14px;"
                    ></textarea>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> Máximo 5000 caracteres
                        </small>
                        <small :class="systemPrompt.length > 5000 ? 'text-danger' : 'text-muted'">
                            {{ systemPrompt.length }} / 5000
                        </small>
                    </div>
                </div>

                <!-- Tips -->
                <div class="alert alert-light border mb-4">
                    <h6 class="alert-heading"><i class="fa fa-magic text-primary"></i> Tips para un buen prompt:</h6>
                    <ul class="mb-0 small">
                        <li>Describe claramente tu negocio y qué productos/servicios ofreces</li>
                        <li>Incluye información sobre precios, horarios y formas de contacto</li>
                        <li>Define el tono: formal, amigable, técnico, etc.</li>
                        <li>Indica qué NO debe hacer el asistente (ej: no dar descuentos sin autorización)</li>
                        <li>Puedes usar variables como información de productos que el sistema inyectará automáticamente</li>
                    </ul>
                </div>
            </div>
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/configurations/ai-settings" class="btn btn-secondary">
                        <i class="fa fa-times"></i> Cancelar
                    </a>
                    <button type="button" class="btn btn-primary" @click="saveSettings" :disabled="saving || systemPrompt.length > 5000">
                        <span v-if="saving">
                            <i class="fa fa-spinner fa-spin"></i> Guardando...
                        </span>
                        <span v-else>
                            <i class="fa fa-save"></i> Guardar Configuración
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
export default {
    data() {
        return {
            loading: true,
            saving: false,
            shopName: '',
            systemPrompt: '',
            settingsId: null,
        }
    },
    methods: {
        loadSettings() {
            this.loading = true;
            axios.get('/admin/configurations/ai-settings/prompt/get')
                .then(response => {
                    if (response.data.ok) {
                        this.shopName = response.data.shop_name;
                        this.systemPrompt = response.data.settings.system_prompt || '';
                        this.settingsId = response.data.settings.id;
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire('Error', 'No se pudo cargar la configuración', 'error');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        saveSettings() {
            if (!this.systemPrompt.trim()) {
                Swal.fire('Atención', 'El prompt no puede estar vacío', 'warning');
                return;
            }

            if (this.systemPrompt.length > 5000) {
                Swal.fire('Atención', 'El prompt excede el límite de 5000 caracteres', 'warning');
                return;
            }

            this.saving = true;
            axios.post('/admin/configurations/ai-settings/prompt/save', {
                system_prompt: this.systemPrompt
            })
            .then(response => {
                if (response.data.ok) {
                    this.settingsId = response.data.settings.id;
                    Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            })
            .catch(error => {
                console.error(error);
                let message = 'No se pudo guardar la configuración';
                if (error.response && error.response.data && error.response.data.message) {
                    message = error.response.data.message;
                }
                Swal.fire('Error', message, 'error');
            })
            .finally(() => {
                this.saving = false;
            });
        },
        resetToDefault() {
            Swal.fire({
                title: '¿Restaurar prompt por defecto?',
                text: 'Se reemplazará el contenido actual con el template por defecto',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, restaurar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post('/admin/configurations/ai-settings/prompt/reset')
                        .then(response => {
                            if (response.data.ok) {
                                this.systemPrompt = response.data.default_prompt;
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Restaurado',
                                    text: 'Prompt restaurado al valor por defecto. No olvides guardar los cambios.',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            Swal.fire('Error', 'No se pudo restaurar el prompt', 'error');
                        });
                }
            });
        }
    },
    mounted() {
        this.loadSettings();
    }
}
</script>
