<template>
  <div>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-file-text" style="color: var(--j2b-primary);"></i> Documentos Legales
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Administra los Terminos y Condiciones y Aviso de Privacidad</p>
            </div>
        </div>

        <!-- Cards de documentos -->
        <div class="row">
            <!-- Términos y Condiciones -->
            <div class="col-md-6 mb-4">
                <div class="j2b-card h-100">
                    <div class="j2b-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fa fa-gavel" style="color: var(--j2b-info);"></i> Terminos y Condiciones
                            </h5>
                            <span v-if="termsDoc" class="j2b-badge j2b-badge-success">
                                <i class="fa fa-check"></i> Configurado
                            </span>
                            <span v-else class="j2b-badge j2b-badge-warning">
                                <i class="fa fa-exclamation"></i> Sin configurar
                            </span>
                        </div>
                    </div>
                    <div class="j2b-card-body">
                        <div v-if="termsDoc">
                            <p><strong>Titulo:</strong> {{ termsDoc.title }}</p>
                            <p><strong>Version:</strong> {{ termsDoc.version }}</p>
                            <p><strong>Fecha vigencia:</strong> {{ termsDoc.effective_date || 'No especificada' }}</p>
                            <p><strong>Ultima actualizacion:</strong> {{ formatDate(termsDoc.updated_at) }}</p>
                            <hr>
                            <div class="d-flex gap-2">
                                <button class="j2b-btn j2b-btn-primary" @click="abrirModal('editar', termsDoc)">
                                    <i class="fa fa-edit"></i> Editar
                                </button>
                                <button class="j2b-btn j2b-btn-outline" @click="abrirModal('ver', termsDoc)">
                                    <i class="fa fa-eye"></i> Ver
                                </button>
                                <button class="j2b-btn j2b-btn-danger" @click="eliminarDocumento(termsDoc)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-center py-4">
                            <i class="fa fa-file-text fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                            <p style="color: var(--j2b-gray-500);">No hay documento configurado</p>
                            <button class="j2b-btn j2b-btn-primary" @click="abrirModal('crear', {type: 'terms'})">
                                <i class="fa fa-plus"></i> Crear Terminos y Condiciones
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aviso de Privacidad -->
            <div class="col-md-6 mb-4">
                <div class="j2b-card h-100">
                    <div class="j2b-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fa fa-shield" style="color: var(--j2b-success);"></i> Aviso de Privacidad
                            </h5>
                            <span v-if="privacyDoc" class="j2b-badge j2b-badge-success">
                                <i class="fa fa-check"></i> Configurado
                            </span>
                            <span v-else class="j2b-badge j2b-badge-warning">
                                <i class="fa fa-exclamation"></i> Sin configurar
                            </span>
                        </div>
                    </div>
                    <div class="j2b-card-body">
                        <div v-if="privacyDoc">
                            <p><strong>Titulo:</strong> {{ privacyDoc.title }}</p>
                            <p><strong>Version:</strong> {{ privacyDoc.version }}</p>
                            <p><strong>Fecha vigencia:</strong> {{ privacyDoc.effective_date || 'No especificada' }}</p>
                            <p><strong>Ultima actualizacion:</strong> {{ formatDate(privacyDoc.updated_at) }}</p>
                            <hr>
                            <div class="d-flex gap-2">
                                <button class="j2b-btn j2b-btn-primary" @click="abrirModal('editar', privacyDoc)">
                                    <i class="fa fa-edit"></i> Editar
                                </button>
                                <button class="j2b-btn j2b-btn-outline" @click="abrirModal('ver', privacyDoc)">
                                    <i class="fa fa-eye"></i> Ver
                                </button>
                                <button class="j2b-btn j2b-btn-danger" @click="eliminarDocumento(privacyDoc)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-center py-4">
                            <i class="fa fa-shield fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                            <p style="color: var(--j2b-gray-500);">No hay documento configurado</p>
                            <button class="j2b-btn j2b-btn-primary" @click="abrirModal('crear', {type: 'privacy'})">
                                <i class="fa fa-plus"></i> Crear Aviso de Privacidad
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información -->
        <div class="j2b-card">
            <div class="j2b-card-body">
                <div class="d-flex align-items-start gap-3">
                    <i class="fa fa-info-circle fa-2x" style="color: var(--j2b-info);"></i>
                    <div>
                        <h6 style="color: var(--j2b-dark);">Endpoints API para la App</h6>
                        <p class="mb-2" style="color: var(--j2b-gray-500);">
                            Una vez configurados, estos documentos estaran disponibles en:
                        </p>
                        <code class="d-block mb-1">GET /api/legal/terms</code>
                        <code class="d-block">GET /api/legal/privacy</code>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Crear/Editar/Ver -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modal}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-file-text" style="color: var(--j2b-primary);"></i>
                        {{ tituloModal }}
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body">

                    <!-- Modo Ver -->
                    <div v-if="tipoAccion === 'ver'">
                        <div class="mb-3">
                            <strong>Titulo:</strong> {{ form.title }}<br>
                            <strong>Version:</strong> {{ form.version }}<br>
                            <strong>Fecha vigencia:</strong> {{ form.effective_date || 'No especificada' }}
                        </div>
                        <hr>
                        <div class="content-preview" v-html="form.content"></div>
                    </div>

                    <!-- Modo Crear/Editar -->
                    <div v-else>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Titulo</label>
                                <input type="text" class="j2b-input" v-model="form.title"
                                    :placeholder="form.type === 'terms' ? 'Terminos y Condiciones' : 'Aviso de Privacidad'">
                            </div>
                            <div class="col-md-3">
                                <label class="j2b-label">Version</label>
                                <input type="text" class="j2b-input" v-model="form.version" placeholder="1.0">
                            </div>
                            <div class="col-md-3">
                                <label class="j2b-label">Fecha Vigencia</label>
                                <input type="date" class="j2b-input" v-model="form.effective_date">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Contenido (HTML)</label>
                            <textarea class="j2b-input" v-model="form.content" rows="20"
                                placeholder="Escribe el contenido en HTML..."></textarea>
                            <small class="text-muted">
                                Puedes usar etiquetas HTML: &lt;h1&gt;, &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, etc.
                            </small>
                        </div>

                        <!-- Vista previa -->
                        <div class="mb-3" v-if="form.content">
                            <label class="j2b-label">Vista Previa:</label>
                            <div class="content-preview" v-html="form.content"></div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">Cerrar</button>
                    <button type="button" v-if="tipoAccion === 'crear'" class="j2b-btn j2b-btn-primary" @click="guardar()" :disabled="saving">
                        <i class="fa fa-save"></i> {{ saving ? 'Guardando...' : 'Guardar' }}
                    </button>
                    <button type="button" v-if="tipoAccion === 'editar'" class="j2b-btn j2b-btn-primary" @click="actualizar()" :disabled="saving">
                        <i class="fa fa-save"></i> {{ saving ? 'Actualizando...' : 'Actualizar' }}
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
            documents: [],
            termsDoc: null,
            privacyDoc: null,

            modal: 0,
            tituloModal: '',
            tipoAccion: '',
            saving: false,

            form: {
                id: null,
                type: '',
                title: '',
                content: '',
                version: '1.0',
                effective_date: ''
            }
        }
    },
    methods: {
        loadDocuments() {
            axios.get('/superadmin/legal-documents/get')
                .then(response => {
                    if (response.data.ok) {
                        this.documents = response.data.documents;
                        this.termsDoc = this.documents.find(d => d.type === 'terms') || null;
                        this.privacyDoc = this.documents.find(d => d.type === 'privacy') || null;
                    }
                })
                .catch(error => {
                    console.error(error);
                });
        },
        formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-MX', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        abrirModal(accion, doc) {
            this.tipoAccion = accion;
            this.modal = 1;

            if (accion === 'crear') {
                this.tituloModal = doc.type === 'terms' ? 'Crear Terminos y Condiciones' : 'Crear Aviso de Privacidad';
                this.form = {
                    id: null,
                    type: doc.type,
                    title: doc.type === 'terms' ? 'Terminos y Condiciones' : 'Aviso de Privacidad',
                    content: '',
                    version: '1.0',
                    effective_date: new Date().toISOString().split('T')[0]
                };
            } else if (accion === 'editar') {
                this.tituloModal = 'Editar: ' + doc.title;
                this.form = {
                    id: doc.id,
                    type: doc.type,
                    title: doc.title,
                    content: doc.content,
                    version: doc.version,
                    effective_date: doc.effective_date ? doc.effective_date.split('T')[0] : ''
                };
            } else if (accion === 'ver') {
                this.tituloModal = doc.title;
                this.form = { ...doc };
            }
        },
        cerrarModal() {
            this.modal = 0;
            this.tipoAccion = '';
            this.tituloModal = '';
        },
        guardar() {
            if (!this.form.title || !this.form.content) {
                Swal.fire('Error', 'El titulo y contenido son obligatorios', 'error');
                return;
            }

            this.saving = true;
            axios.post('/superadmin/legal-documents/store', this.form)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire('Exito', response.data.message, 'success');
                        this.cerrarModal();
                        this.loadDocuments();
                    } else {
                        Swal.fire('Error', response.data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire('Error', 'Ocurrio un error al guardar', 'error');
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        actualizar() {
            if (!this.form.title || !this.form.content) {
                Swal.fire('Error', 'El titulo y contenido son obligatorios', 'error');
                return;
            }

            this.saving = true;
            axios.put('/superadmin/legal-documents/update', this.form)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire('Exito', response.data.message, 'success');
                        this.cerrarModal();
                        this.loadDocuments();
                    } else {
                        Swal.fire('Error', response.data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire('Error', 'Ocurrio un error al actualizar', 'error');
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        eliminarDocumento(doc) {
            Swal.fire({
                title: '¿Eliminar documento?',
                text: `Se eliminara "${doc.title}". Esta accion no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/superadmin/legal-documents/${doc.id}`)
                        .then(response => {
                            if (response.data.ok) {
                                Swal.fire('Eliminado', response.data.message, 'success');
                                this.loadDocuments();
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            Swal.fire('Error', 'Ocurrio un error al eliminar', 'error');
                        });
                }
            });
        }
    },
    mounted() {
        this.loadDocuments();
    }
}
</script>

<style scoped>
.mostrar {
    display: block !important;
    opacity: 1 !important;
    position: fixed !important;
    background-color: rgba(26, 26, 46, 0.8) !important;
    overflow-y: auto;
    z-index: 1050;
}

.j2b-modal-content {
    border: none;
    border-radius: var(--j2b-radius-lg);
    box-shadow: var(--j2b-shadow-lg);
}

.j2b-modal-header {
    background: var(--j2b-gradient-dark);
    color: var(--j2b-white);
    border-radius: var(--j2b-radius-lg) var(--j2b-radius-lg) 0 0;
    padding: 1rem 1.5rem;
    border-bottom: none;
}

.j2b-modal-close {
    background: rgba(255,255,255,0.1);
    border: none;
    color: var(--j2b-white);
    width: 32px;
    height: 32px;
    border-radius: var(--j2b-radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.j2b-modal-close:hover {
    background: rgba(255,255,255,0.2);
}

.j2b-modal-body {
    padding: 1.5rem;
    max-height: 75vh;
    overflow-y: auto;
}

.j2b-modal-footer {
    padding: 1rem 1.5rem;
    background: var(--j2b-gray-100);
    border-top: 1px solid var(--j2b-gray-200);
    border-radius: 0 0 var(--j2b-radius-lg) var(--j2b-radius-lg);
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.content-preview {
    background: var(--j2b-gray-100);
    border: 1px solid var(--j2b-gray-200);
    border-radius: var(--j2b-radius-md);
    padding: 1.5rem;
    max-height: 400px;
    overflow-y: auto;
}

.content-preview h1 { font-size: 1.5rem; margin-bottom: 1rem; }
.content-preview h2 { font-size: 1.25rem; margin-top: 1.5rem; margin-bottom: 0.75rem; }
.content-preview p { margin-bottom: 0.75rem; }
.content-preview ul { padding-left: 1.5rem; margin-bottom: 0.75rem; }
.content-preview li { margin-bottom: 0.25rem; }

textarea.j2b-input {
    font-family: monospace;
    font-size: 0.9rem;
}
</style>
