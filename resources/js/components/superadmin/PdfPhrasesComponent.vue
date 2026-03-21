<template>
  <div>
    <div class="container-fluid" style="padding: 1.5rem;">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                    <i class="fa fa-quote-left" style="color: var(--j2b-primary);"></i> Frases PDF
                </h4>
                <p class="mb-0" style="color: var(--j2b-gray-500);">Frases de pie de pagina para los PDFs generados (growth hacking)</p>
            </div>
            <div class="d-flex gap-2">
                <button class="j2b-btn j2b-btn-secondary" @click="abrirModalBulk()">
                    <i class="fa fa-upload"></i> Importar Masivo
                </button>
                <button class="j2b-btn j2b-btn-primary" @click="abrirModal('crear')">
                    <i class="fa fa-plus"></i> Nueva Frase
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="j2b-card">
                    <div class="j2b-card-body text-center">
                        <h3 style="color: var(--j2b-primary);">{{ phrases.length }}</h3>
                        <small style="color: var(--j2b-gray-500);">Total frases</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="j2b-card">
                    <div class="j2b-card-body text-center">
                        <h3 style="color: var(--j2b-success);">{{ activePhrases }}</h3>
                        <small style="color: var(--j2b-gray-500);">Activas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="j2b-card">
                    <div class="j2b-card-body text-center">
                        <h3 style="color: var(--j2b-gray-400);">{{ phrases.length - activePhrases }}</h3>
                        <small style="color: var(--j2b-gray-500);">Inactivas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="j2b-card mb-4">
            <div class="j2b-card-body">
                <div class="d-flex align-items-start gap-3">
                    <i class="fa fa-info-circle fa-2x" style="color: var(--j2b-info);"></i>
                    <div>
                        <h6 style="color: var(--j2b-dark);">Como funciona</h6>
                        <p class="mb-0" style="color: var(--j2b-gray-500);">
                            Cada vez que se genera un PDF, se selecciona <strong>una frase al azar</strong> de las activas
                            y se muestra en el pie de pagina. Cada frase puede tener su propio <strong>link destino</strong> (por defecto j2biznes.com).
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="j2b-card">
            <div class="j2b-card-body" style="padding: 0;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr style="background: var(--j2b-gray-100);">
                            <th style="width: 50px;">#</th>
                            <th>Frase</th>
                            <th style="width: 200px;">Link destino</th>
                            <th style="width: 120px;" class="text-center">Estado</th>
                            <th style="width: 180px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, index) in phrases" :key="p.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ p.phrase }}</td>
                            <td><small style="color: var(--j2b-gray-500);">{{ p.link_url }}</small></td>
                            <td class="text-center">
                                <span v-if="p.is_active" class="j2b-badge j2b-badge-success" style="cursor: pointer;" @click="toggleActive(p)">
                                    <i class="fa fa-check"></i> Activa
                                </span>
                                <span v-else class="j2b-badge j2b-badge-secondary" style="cursor: pointer;" @click="toggleActive(p)">
                                    <i class="fa fa-ban"></i> Inactiva
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="j2b-btn j2b-btn-sm j2b-btn-outline" @click="abrirModal('editar', p)" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="j2b-btn j2b-btn-sm j2b-btn-danger" @click="eliminar(p)" title="Eliminar" style="margin-left: 4px;">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="phrases.length === 0">
                            <td colspan="5" class="text-center py-4" style="color: var(--j2b-gray-400);">
                                <i class="fa fa-quote-left fa-2x mb-2 d-block"></i>
                                No hay frases registradas. Crea la primera o importa varias a la vez.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Crear/Editar -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modal}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-quote-left" style="color: var(--j2b-primary);"></i>
                        {{ tituloModal }}
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <div class="mb-3">
                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Frase</label>
                        <textarea class="j2b-input" v-model="form.phrase" rows="3"
                            placeholder="Ej: Menos Excel. Mas control"></textarea>
                        <small class="text-muted">Max 500 caracteres.</small>
                    </div>
                    <div class="mb-3">
                        <label class="j2b-label">Link destino</label>
                        <input type="url" class="j2b-input" v-model="form.link_url"
                            placeholder="https://j2biznes.com">
                        <small class="text-muted">URL a donde redirige "J2Biznes.com" en el PDF. Dejar vacio = j2biznes.com</small>
                    </div>
                    <!-- Preview -->
                    <div v-if="form.phrase" class="p-3" style="background: var(--j2b-gray-100); border-radius: var(--j2b-radius-md); text-align: center;">
                        <small style="color: var(--j2b-gray-500);">Vista previa:</small>
                        <p style="font-size: 9px; color: #999; margin: 0; margin-top: 4px;">
                            De
                            <img src="/images/heart-j2b.png" style="width: 12px; height: 12px; vertical-align: middle;">
                            <strong style="color: #555;">J2Biznes.com</strong> - {{ form.phrase }}
                        </p>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="guardar()" :disabled="saving">
                        <i class="fa fa-save"></i> {{ saving ? 'Guardando...' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Importar Masivo -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalBulk}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-upload" style="color: var(--j2b-primary);"></i>
                        Importar Frases Masivo
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="modalBulk = 0">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <div class="mb-3">
                        <label class="j2b-label"><span style="color: var(--j2b-danger);">*</span> Frases (una por linea)</label>
                        <textarea class="j2b-input" v-model="bulkForm.phrases" rows="12"
                            placeholder="Menos Excel. Mas control&#10;Tu negocio en piloto automatico&#10;Este PDF se genero solo. Literal"></textarea>
                        <small class="text-muted">
                            {{ bulkLineCount }} frases detectadas. Cada linea = una frase. Las lineas vacias se ignoran.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="j2b-label">Link destino (para todas las frases importadas)</label>
                        <input type="url" class="j2b-input" v-model="bulkForm.link_url"
                            placeholder="https://j2biznes.com">
                        <small class="text-muted">Dejar vacio = j2biznes.com. Se puede cambiar individualmente despues.</small>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="modalBulk = 0">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="importarMasivo()" :disabled="saving || bulkLineCount === 0">
                        <i class="fa fa-upload"></i> {{ saving ? 'Importando...' : `Importar ${bulkLineCount} frases` }}
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
            phrases: [],
            modal: 0,
            modalBulk: 0,
            tituloModal: '',
            tipoAccion: '',
            saving: false,
            form: {
                id: null,
                phrase: '',
                link_url: ''
            },
            bulkForm: {
                phrases: '',
                link_url: ''
            }
        }
    },
    computed: {
        activePhrases() {
            return this.phrases.filter(p => p.is_active).length;
        },
        bulkLineCount() {
            if (!this.bulkForm.phrases) return 0;
            return this.bulkForm.phrases.split('\n').filter(l => l.trim() !== '').length;
        }
    },
    methods: {
        loadPhrases() {
            axios.get('/superadmin/pdf-phrases/get')
                .then(response => {
                    if (response.data.ok) {
                        this.phrases = response.data.phrases;
                    }
                })
                .catch(error => console.error(error));
        },
        abrirModal(accion, phrase = null) {
            this.tipoAccion = accion;
            this.modal = 1;

            if (accion === 'crear') {
                this.tituloModal = 'Nueva Frase';
                this.form = { id: null, phrase: '', link_url: '' };
            } else {
                this.tituloModal = 'Editar Frase';
                this.form = { id: phrase.id, phrase: phrase.phrase, link_url: phrase.link_url };
            }
        },
        abrirModalBulk() {
            this.modalBulk = 1;
            this.bulkForm = { phrases: '', link_url: '' };
        },
        cerrarModal() {
            this.modal = 0;
            this.tipoAccion = '';
            this.tituloModal = '';
        },
        guardar() {
            if (!this.form.phrase) {
                Swal.fire('Error', 'La frase es obligatoria', 'error');
                return;
            }

            this.saving = true;

            if (this.tipoAccion === 'crear') {
                axios.post('/superadmin/pdf-phrases/store', this.form)
                    .then(response => {
                        if (response.data.ok) {
                            Swal.fire('Exito', response.data.message, 'success');
                            this.cerrarModal();
                            this.loadPhrases();
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire('Error', 'Ocurrio un error al guardar', 'error');
                    })
                    .finally(() => { this.saving = false; });
            } else {
                axios.put('/superadmin/pdf-phrases/update', this.form)
                    .then(response => {
                        if (response.data.ok) {
                            Swal.fire('Exito', response.data.message, 'success');
                            this.cerrarModal();
                            this.loadPhrases();
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire('Error', 'Ocurrio un error al actualizar', 'error');
                    })
                    .finally(() => { this.saving = false; });
            }
        },
        importarMasivo() {
            if (this.bulkLineCount === 0) return;

            this.saving = true;
            axios.post('/superadmin/pdf-phrases/bulk-import', this.bulkForm)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire('Exito', response.data.message, 'success');
                        this.modalBulk = 0;
                        this.loadPhrases();
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire('Error', 'Ocurrio un error al importar', 'error');
                })
                .finally(() => { this.saving = false; });
        },
        toggleActive(phrase) {
            axios.put('/superadmin/pdf-phrases/toggle-active', { id: phrase.id })
                .then(response => {
                    if (response.data.ok) {
                        phrase.is_active = response.data.phrase.is_active;
                    }
                })
                .catch(error => console.error(error));
        },
        eliminar(phrase) {
            Swal.fire({
                title: 'Eliminar frase?',
                text: 'Esta accion no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/superadmin/pdf-phrases/${phrase.id}`)
                        .then(response => {
                            if (response.data.ok) {
                                Swal.fire('Eliminado', response.data.message, 'success');
                                this.loadPhrases();
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
        this.loadPhrases();
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

.j2b-btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.j2b-badge-secondary {
    background: var(--j2b-gray-400);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: var(--j2b-radius-full);
    font-size: 0.75rem;
}
</style>
