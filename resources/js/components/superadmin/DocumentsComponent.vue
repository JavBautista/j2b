<template>
  <div>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-file-contract" style="color: var(--j2b-primary);"></i> Documentos
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Contratos y plantillas en Markdown con generación de PDF</p>
            </div>
            <button type="button" @click="abrirModal('crear')" class="j2b-btn j2b-btn-primary">
                <i class="fa fa-plus"></i> Nuevo Documento
            </button>
        </div>

        <!-- Tabla -->
        <div class="j2b-card">
            <div class="j2b-card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-list" style="color: var(--j2b-primary);"></i> Listado</h5>
                    <span class="j2b-badge j2b-badge-info">{{ documents.length }} documento(s)</span>
                </div>
            </div>
            <div class="j2b-card-body p-0">
                <div class="j2b-table-responsive">
                    <table class="j2b-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Título</th>
                                <th style="width: 140px;">Categoría</th>
                                <th style="width: 90px;">Versión</th>
                                <th style="width: 160px;">Actualizado</th>
                                <th style="width: 220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in documents" :key="doc.id">
                                <td><span class="j2b-badge j2b-badge-dark">{{ doc.id }}</span></td>
                                <td><strong>{{ doc.title }}</strong></td>
                                <td>
                                    <span v-if="doc.category" class="j2b-badge j2b-badge-info">{{ doc.category }}</span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td>{{ doc.version }}</td>
                                <td>{{ formatDate(doc.updated_at) }}</td>
                                <td>
                                    <div class="doc-actions">
                                        <button class="j2b-btn j2b-btn-sm j2b-btn-primary" title="Editar" @click="abrirModal('editar', doc)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="j2b-btn j2b-btn-sm j2b-btn-outline" title="Ver" @click="abrirModal('ver', doc)">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <a :href="`/superadmin/documents/${doc.id}/pdf`" target="_blank" class="j2b-btn j2b-btn-sm j2b-btn-info" title="Ver PDF">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </a>
                                        <a :href="`/superadmin/documents/${doc.id}/pdf?download=1`" class="j2b-btn j2b-btn-sm j2b-btn-secondary" title="Descargar PDF">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <button class="j2b-btn j2b-btn-sm j2b-btn-danger" title="Eliminar" @click="eliminar(doc)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="documents.length === 0">
                                <td colspan="6" class="text-center py-4" style="color: var(--j2b-gray-500);">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                    No hay documentos. Crea el primero con "Nuevo Documento".
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Crear/Editar/Ver -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modal}" role="dialog" style="display: none;">
        <div class="modal-dialog doc-modal-dialog" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-file-contract"></i> {{ tituloModal }}
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body">

                    <!-- Modo Ver -->
                    <div v-if="tipoAccion === 'ver'">
                        <div class="mb-3">
                            <strong>Título:</strong> {{ form.title }}<br>
                            <strong>Categoría:</strong> {{ form.category || '—' }}<br>
                            <strong>Versión:</strong> {{ form.version }}
                        </div>
                        <hr>
                        <div class="md-preview" v-html="renderedContent"></div>
                    </div>

                    <!-- Modo Crear/Editar -->
                    <div v-else>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Título</label>
                                <input type="text" class="j2b-input" v-model="form.title" placeholder="Contrato SaaS Copigama">
                            </div>
                            <div class="col-md-3">
                                <label class="j2b-label">Categoría</label>
                                <input type="text" class="j2b-input" v-model="form.category" placeholder="contrato">
                            </div>
                            <div class="col-md-3">
                                <label class="j2b-label">Versión</label>
                                <input type="text" class="j2b-input" v-model="form.version" placeholder="1.0">
                            </div>
                        </div>

                        <!-- Editor 2 paneles: Markdown | Vista previa -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Contenido (Markdown)</label>
                                <textarea class="j2b-input md-editor" v-model="form.content" rows="22"
                                    placeholder="# Título&#10;&#10;Escribe en Markdown..."></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="j2b-label">Vista previa</label>
                                <div class="md-preview md-preview-scroll" v-html="renderedContent"></div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="j2b-label">Notas internas (no salen en el PDF)</label>
                            <textarea class="j2b-input" v-model="form.notes" rows="2" placeholder="Opcional..."></textarea>
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
import { marked } from 'marked';

marked.setOptions({ gfm: true, breaks: false });

export default {
    data() {
        return {
            documents: [],
            modal: 0,
            tituloModal: '',
            tipoAccion: '',
            saving: false,
            form: {
                id: null,
                title: '',
                category: '',
                content: '',
                version: '1.0',
                notes: ''
            }
        }
    },
    computed: {
        renderedContent() {
            return marked.parse(this.form.content || '');
        }
    },
    methods: {
        loadDocuments() {
            axios.get('/superadmin/documents/get')
                .then(response => {
                    if (response.data.ok) this.documents = response.data.documents;
                })
                .catch(error => console.error(error));
        },
        formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-MX', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },
        abrirModal(accion, doc) {
            this.tipoAccion = accion;
            this.modal = 1;

            if (accion === 'crear') {
                this.tituloModal = 'Nuevo Documento';
                this.form = { id: null, title: '', category: '', content: '', version: '1.0', notes: '' };
            } else if (accion === 'editar') {
                this.tituloModal = 'Editar: ' + doc.title;
                this.form = {
                    id: doc.id,
                    title: doc.title,
                    category: doc.category || '',
                    content: doc.content,
                    version: doc.version,
                    notes: doc.notes || ''
                };
            } else if (accion === 'ver') {
                this.tituloModal = doc.title;
                this.form = { ...doc, category: doc.category || '', notes: doc.notes || '' };
            }
        },
        cerrarModal() {
            this.modal = 0;
            this.tipoAccion = '';
            this.tituloModal = '';
        },
        guardar() {
            if (!this.form.title || !this.form.content) {
                Swal.fire('Error', 'El título y el contenido son obligatorios', 'error');
                return;
            }
            this.saving = true;
            axios.post('/superadmin/documents/store', this.form)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire('Éxito', response.data.message, 'success');
                        this.cerrarModal();
                        this.loadDocuments();
                    } else {
                        Swal.fire('Error', response.data.message, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Ocurrió un error al guardar', 'error'))
                .finally(() => { this.saving = false; });
        },
        actualizar() {
            if (!this.form.title || !this.form.content) {
                Swal.fire('Error', 'El título y el contenido son obligatorios', 'error');
                return;
            }
            this.saving = true;
            axios.put('/superadmin/documents/update', this.form)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire('Éxito', response.data.message, 'success');
                        this.cerrarModal();
                        this.loadDocuments();
                    } else {
                        Swal.fire('Error', response.data.message, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Ocurrió un error al actualizar', 'error'))
                .finally(() => { this.saving = false; });
        },
        eliminar(doc) {
            Swal.fire({
                title: '¿Eliminar documento?',
                text: `Se eliminará "${doc.title}". Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/superadmin/documents/${doc.id}`)
                        .then(response => {
                            if (response.data.ok) {
                                Swal.fire('Eliminado', response.data.message, 'success');
                                this.loadDocuments();
                            }
                        })
                        .catch(() => Swal.fire('Error', 'Ocurrió un error al eliminar', 'error'));
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
/* Modal ancho tipo editor (no dependemos de .modal-xl de Bootstrap) */
.modal-dialog.doc-modal-dialog {
    width: 95vw;
    max-width: 1280px;
    margin: 1.5rem auto;
}

.j2b-modal-content {
    border: none;
    border-radius: var(--j2b-radius-lg);
    box-shadow: var(--j2b-shadow-lg);
    display: flex;
    flex-direction: column;
    max-height: 92vh;
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
    width: 32px; height: 32px;
    border-radius: var(--j2b-radius-full);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
}
.j2b-modal-close:hover { background: rgba(255,255,255,0.2); }
.j2b-modal-body { padding: 1.5rem; overflow-y: auto; flex: 1 1 auto; }
.j2b-modal-footer {
    padding: 1rem 1.5rem;
    background: var(--j2b-gray-100);
    border-top: 1px solid var(--j2b-gray-200);
    border-radius: 0 0 var(--j2b-radius-lg) var(--j2b-radius-lg);
    display: flex; gap: 0.5rem; justify-content: flex-end;
}

/* Botones de acción de la tabla: tamaño uniforme (a y button por igual) */
.doc-actions {
    display: flex;
    gap: 0.35rem;
    flex-wrap: nowrap;
}
.doc-actions .j2b-btn-sm {
    width: 34px;
    height: 34px;
    padding: 0;
    line-height: 1;
    flex: 0 0 auto;
    box-sizing: border-box;
}
.doc-actions .j2b-btn-sm i { font-size: 0.9rem; }

.md-editor {
    font-family: monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    height: 58vh;
    resize: vertical;
    width: 100%;
}

.md-preview {
    background: var(--j2b-white);
    border: 1px solid var(--j2b-gray-200);
    border-radius: var(--j2b-radius-md);
    padding: 1.25rem 1.5rem;
}
.md-preview-scroll { height: 58vh; overflow-y: auto; }

.md-preview :deep(h1) { font-size: 1.4rem; margin: 0 0 0.75rem; }
.md-preview :deep(h2) { font-size: 1.15rem; margin: 1rem 0 0.5rem; border-bottom: 1px solid var(--j2b-gray-200); padding-bottom: 0.25rem; }
.md-preview :deep(h3) { font-size: 1rem; margin: 0.75rem 0 0.4rem; }
.md-preview :deep(p) { margin: 0 0 0.6rem; text-align: justify; }
.md-preview :deep(ul), .md-preview :deep(ol) { padding-left: 1.4rem; margin-bottom: 0.6rem; }
.md-preview :deep(li) { margin-bottom: 0.2rem; }
.md-preview :deep(blockquote) {
    margin: 0.6rem 0; padding: 0.5rem 1rem;
    background: var(--j2b-gray-100);
    border-left: 3px solid var(--j2b-gray-400);
    color: var(--j2b-gray-600);
    font-size: 0.9rem;
}
.md-preview :deep(code) { background: var(--j2b-gray-100); padding: 1px 4px; border-radius: 3px; font-size: 0.85rem; }
.md-preview :deep(hr) { border: none; border-top: 1px solid var(--j2b-gray-300); margin: 1rem 0; }
.md-preview :deep(table) { width: 100%; border-collapse: collapse; margin: 0.75rem 0; }
.md-preview :deep(th), .md-preview :deep(td) { border: 1px solid var(--j2b-gray-300); padding: 0.4rem 0.6rem; text-align: left; }
.md-preview :deep(th) { background: var(--j2b-gray-100); }
</style>
