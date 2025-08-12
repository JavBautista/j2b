<template>
    <div class="template-creator-container">
        <!-- Barra superior compacta -->
        <div class="top-bar">
            <div class="d-flex align-items-center gap-3">
                <div class="flex-grow-1">
                    <input 
                        v-model="templateName" 
                        class="form-control form-control-lg border-0 bg-transparent" 
                        placeholder="Escriba el nombre de la plantilla (ej: Contrato de Servicios)"
                        style="font-weight: 500; font-size: 16px;"
                    >
                </div>
                <div class="d-flex gap-2">
                    <button @click="saveTemplate" 
                            class="btn btn-success px-4"
                            :disabled="!templateName || !htmlContent">
                        <i class="fas fa-save me-2"></i>Guardar Plantilla
                    </button>
                    <button class="btn btn-outline-secondary" 
                            @click="goBack">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Área principal con 3 columnas -->
        <div class="main-area">
            <!-- Panel de Variables -->
            <div class="variables-panel">
                <div class="panel-header">
                    <h6><i class="fas fa-tags me-2"></i>Variables</h6>
                </div>
                <div class="panel-body">
                    <!-- Variables predefinidas -->
                    <div class="variables-grid">
                        <button 
                            v-for="variable in variables" 
                            :key="variable"
                            class="variable-btn"
                            @click="insertVariable(variable)"
                            :title="'Insertar {{' + variable + '}}'"
                            @mouseover="highlightVariable(variable)"
                            @mouseout="unhighlightVariable()"
                        >
                            <i class="fas fa-plus me-1" style="font-size: 8px;"></i>
                            {{ formatVariableName(variable) }}
                        </button>
                    </div>
                    
                    <!-- Variables personalizadas -->
                    <div class="mt-3">
                        <div class="custom-var-header">Personalizada</div>
                        <div class="input-group input-group-sm mb-2">
                            <input v-model="newVariable" 
                                   class="form-control" 
                                   placeholder="mi_variable"
                                   @keyup.enter="addCustomVariable">
                            <button class="btn btn-outline-success" 
                                    @click="addCustomVariable"
                                    :disabled="!newVariable">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <div v-if="customVariables.length > 0">
                            <div v-for="variable in customVariables" :key="variable" 
                                 class="d-flex align-items-center mb-1">
                                <button class="btn btn-outline-info btn-sm flex-grow-1 text-start me-1"
                                        @click="insertVariable(variable)"
                                        style="font-size: 10px;">
                                    {{ variable }}
                                </button>
                                <button class="btn btn-outline-danger btn-sm" 
                                        @click="removeCustomVariable(variable)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Editor Principal -->
            <div class="editor-panel">
                <div class="panel-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6><i class="fas fa-edit me-2"></i>Editor de Contrato</h6>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-secondary" 
                                    :class="{active: activeTab === 'editor'}"
                                    @click="activeTab = 'editor'">
                                <i class="fas fa-edit me-1"></i>Editor
                            </button>
                            <button class="btn btn-outline-secondary" 
                                    :class="{active: activeTab === 'css'}"
                                    @click="activeTab = 'css'">
                                <i class="fas fa-palette me-1"></i>CSS
                            </button>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <!-- Editor Quill -->
                    <div v-show="activeTab === 'editor'" class="editor-container">
                        <div id="editor" class="quill-editor"></div>
                    </div>
                    
                    <!-- CSS Editor -->
                    <div v-show="activeTab === 'css'" class="css-container">
                        <textarea 
                            v-model="customCss" 
                            class="css-editor"
                            placeholder="/* CSS Personalizado (opcional):
h1 { color: #0066cc; font-size: 18px; }
.firma { border-bottom: 2px solid #000; width: 200px; }
.centrado { text-align: center; }
*/"
                        ></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Vista Previa -->
            <div class="preview-panel">
                <div class="panel-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6><i class="fas fa-eye me-2"></i>Vista Previa</h6>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-secondary" 
                                    :class="{active: previewMode === 'desktop'}"
                                    @click="previewMode = 'desktop'">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button class="btn btn-outline-secondary" 
                                    :class="{active: previewMode === 'mobile'}"
                                    @click="previewMode = 'mobile'">
                                <i class="fas fa-mobile-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="preview-container" :class="{'mobile-preview': previewMode === 'mobile'}">
                        <div class="preview-content" 
                             v-html="previewHtml" 
                             :style="customCss">
                        </div>
                        
                        <div v-if="!htmlContent" class="preview-placeholder">
                            <i class="fas fa-file-alt fa-3x mb-3"></i>
                            <p>La vista previa aparecerá aquí conforme escribas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

export default {
    props: ['defaultVariables', 'saveUrl', 'templateData', 'isEditing'],
    
    data() {
        return {
            templateName: '',
            htmlContent: '',
            customCss: '',
            variables: this.defaultVariables || [],
            customVariables: [],
            newVariable: '',
            quillEditor: null,
            previewHtml: '',
            activeTab: 'editor',
            previewMode: 'desktop',
            lastCursorPosition: 0,
            editorHasFocus: false
        }
    },

    mounted() {
        this.initializeEditor();
    },

    methods: {
        initializeEditor() {
            // Intentar inicializar el editor con reintentos
            const tryInitialize = (attempt = 1) => {
                this.$nextTick(() => {
                    const editorElement = document.getElementById('editor');
                    if (editorElement && !this.quillEditor) {
                        try {
                            
                            this.quillEditor = new Quill('#editor', {
                                theme: 'snow',
                                placeholder: 'Comienza escribiendo tu contrato aquí...\n\nEjemplo:\nEste contrato se celebra entre {{cliente_nombre}} y nuestra empresa...\n\nFecha: {{fecha_contrato}}\nMonto: {{monto_total}}',
                                modules: {
                                    toolbar: {
                                        container: [
                                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                                            ['bold', 'italic', 'underline', 'strike'],
                                            [{ 'color': [] }, { 'background': [] }],
                                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                            [{ 'align': [] }],
                                            ['blockquote', 'code-block'],
                                            ['clean']
                                        ]
                                    }
                                }
                            });

                            // Listener para cambios de texto
                            this.quillEditor.on('text-change', (delta, oldDelta, source) => {
                                this.htmlContent = this.quillEditor.root.innerHTML;
                                this.updatePreview();
                            });
                            
                            // Cargar datos de template después de inicializar el editor
                            this.loadTemplateData();

                            // Trackear la posición del cursor
                            this.quillEditor.on('selection-change', (range, oldRange, source) => {
                                if (range) {
                                    this.lastCursorPosition = range.index;
                                    this.editorHasFocus = true;
                                } else {
                                    this.editorHasFocus = false;
                                }
                            });

                            console.log('Quill editor inicializado correctamente');
                            this.updatePreview();
                            
                        } catch (error) {
                            console.error('Error inicializando Quill:', error);
                            if (attempt < 3) {
                                setTimeout(() => tryInitialize(attempt + 1), 100);
                            }
                        }
                    } else if (!editorElement && attempt < 3) {
                        setTimeout(() => tryInitialize(attempt + 1), 100);
                    }
                });
            };
            
            tryInitialize();
        },

        insertVariable(variable) {
            // Verificar que el editor existe
            if (!this.quillEditor) {
                this.initializeEditor();
                setTimeout(() => {
                    if (this.quillEditor) {
                        this.insertVariable(variable);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Editor no disponible',
                            text: 'El editor no está listo. Por favor recarga la página.'
                        });
                    }
                }, 1000);
                return;
            }
            
            // Cambiar al tab del editor si no está activo
            if (this.activeTab !== 'editor') {
                this.activeTab = 'editor';
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.doSimpleInsert(variable);
                    }, 200);
                });
            } else {
                this.doSimpleInsert(variable);
            }
        },
        
        doSimpleInsert(variable) {
            try {
                if (!this.quillEditor) {
                    return;
                }
                this.lastResortInsert(variable);
            } catch (error) {
                console.error('Error insertando variable:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al insertar',
                    text: 'No se pudo insertar la variable. Por favor, intente de nuevo.'
                });
            }
        },


        lastResortInsert(variable) {
            try {
                const variableText = typeof variable === 'string' && variable.includes('{{') ? variable : `{{${variable}}}`;
                
                // Obtener el contenido actual como texto plano
                const currentText = this.quillEditor.getText();
                const insertPosition = this.lastCursorPosition || 0;
                
                // Construir el nuevo texto insertando la variable en la posición correcta
                const beforeText = currentText.substring(0, insertPosition);
                const afterText = currentText.substring(insertPosition);
                const newText = beforeText + variableText + afterText;
                
                // Reemplazar todo el contenido del editor con el nuevo texto
                this.quillEditor.root.innerHTML = `<p>${newText.replace(/\n/g, '</p><p>')}</p>`;
                
                // Actualizar contenido y vista previa
                this.htmlContent = this.quillEditor.root.innerHTML;
                this.updatePreview();
                
                // Actualizar la posición del cursor después de la inserción
                this.lastCursorPosition = insertPosition + variableText.length;
                
            } catch (error) {
                console.error('Error insertando variable:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al insertar',
                    text: 'No se pudo insertar la variable. Por favor, intente de nuevo.'
                });
            }
        },

        
        formatVariableName(variable) {
            return variable.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },
        
        addCustomVariable() {
            if (this.newVariable && !this.variables.includes(this.newVariable) && !this.customVariables.includes(this.newVariable)) {
                this.customVariables.push(this.newVariable);
                this.newVariable = '';
            }
        },
        
        removeCustomVariable(variable) {
            const index = this.customVariables.indexOf(variable);
            if (index > -1) {
                this.customVariables.splice(index, 1);
            }
        },

        updatePreview() {
            if (!this.htmlContent) {
                this.previewHtml = '';
                return;
            }
            
            let preview = this.htmlContent;
            const sampleData = {
                // Datos del cliente/comprador
                'cliente_nombre': 'Juan Pérez González',
                'cliente_email': 'juan.perez@ejemplo.com',
                'cliente_telefono': '+52 55 1234-5678',
                'cliente_direccion': 'Av. Reforma 123, CDMX',
                'comprador_representante': 'Empresa ABC S.A. de C.V.',
                
                // Datos del vendedor/empresa
                'vendedor_nombre': 'María González López',
                'vendedor_direccion': 'Calle Principal 456, Centro, Querétaro, Qro',
                'empresa_nombre': 'Mi Empresa S.A. de C.V.',
                
                // Datos del producto/servicio
                'producto_descripcion': 'Equipo multifuncional HP LaserJet Pro',
                'numero_serie': 'HP123456789',
                'contador_inicial_color': '1,250',
                'contador_inicial_negro': '5,680',
                'descripcion': 'Servicios profesionales de desarrollo',
                
                // Datos financieros
                'monto_total': '$15,500.00',
                'monto_letra': 'QUINCE MIL QUINIENTOS PESOS 00/100 MN',
                'dia_pago': '15',
                'cuenta_bancaria': '12345678901',
                'clabe_interbancaria': '012345678901234567',
                'banco': 'BBVA México',
                
                // Fechas
                'fecha_contrato': new Date().toLocaleDateString('es-MX'),
                'fecha_vencimiento': new Date(Date.now() + 30*24*60*60*1000).toLocaleDateString('es-MX'),
                'fecha_entrega': new Date(Date.now() + 7*24*60*60*1000).toLocaleDateString('es-MX'),
                'dia_firma': new Date().getDate().toString(),
                'mes_firma': new Date().toLocaleDateString('es-MX', { month: 'long' }).toUpperCase(),
                'año_firma': new Date().getFullYear().toString(),
                
                // Garantías y términos
                'garantia_copias': '10,000',
                'garantia_meses': '12',
                'plazo': '30 días hábiles',
                
                // Ubicación
                'ciudad_contrato': 'Querétaro',
                'estado_contrato': 'Querétaro'
            };

            // Reemplazar variables predefinidas y personalizadas
            [...this.variables, ...this.customVariables].forEach(variable => {
                const sampleValue = sampleData[variable] || `[${this.formatVariableName(variable)}]`;
                preview = preview.replace(new RegExp(`{{${variable}}}`, 'g'), sampleValue);
            });

            this.previewHtml = preview;
        },

        loadTemplateData() {
            console.log('loadTemplateData called');
            console.log('isEditing:', this.isEditing);
            console.log('templateData:', this.templateData);
            
            if (this.isEditing && this.templateData) {
                console.log('Loading template data...');
                console.log('html_content:', this.templateData.html_content);
                
                this.templateName = this.templateData.name;
                this.customCss = this.templateData.css_styles || '';
                this.variables = this.templateData.variables || this.defaultVariables;
                
                // Cargar contenido HTML en el editor
                if (this.quillEditor && this.templateData.html_content) {
                    console.log('Setting editor content');
                    this.quillEditor.root.innerHTML = this.templateData.html_content;
                    this.htmlContent = this.templateData.html_content;
                    this.updatePreview();
                } else {
                    console.log('Editor or content missing:', {
                        editorExists: !!this.quillEditor,
                        contentExists: !!this.templateData.html_content
                    });
                }
            } else {
                console.log('Not editing or no template data');
            }
        },

        async saveTemplate() {
            if (!this.templateName.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'Por favor escribe el nombre de la plantilla'
                });
                return;
            }
            
            if (!this.htmlContent.trim() || this.htmlContent === '<p><br></p>') {
                Swal.fire({
                    icon: 'warning', 
                    title: 'Contenido requerido',
                    text: 'Por favor escribe el contenido del contrato'
                });
                return;
            }

            const allVariables = [...this.variables, ...this.customVariables];
            
            const formData = new FormData();
            formData.append('name', this.templateName.trim());
            formData.append('html_content', this.htmlContent);
            formData.append('css_styles', this.customCss || '');
            formData.append('variables', JSON.stringify(allVariables));
            formData.append('is_active', '1');

            if (this.isEditing) {
                formData.append('_method', 'PUT');
            }

            console.log('Enviando datos:', {
                saveUrl: this.saveUrl,
                isEditing: this.isEditing,
                name: this.templateName,
                html_content: this.htmlContent,
                variables: allVariables
            });
            
            try {
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (response.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Plantilla guardada exitosamente'
                    }).then(() => {
                        this.goBack();
                    });
                } else {
                    const errorData = await response.json();
                    console.error('Error response:', errorData);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: errorData.message || 'Error desconocido'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'Error al guardar la plantilla. Verifica tu conexión.'
                });
            }
        },
        
        highlightVariable(variable) {
            // Mostrar tooltip o efecto visual
        },
        
        unhighlightVariable() {
            // Remover efectos visuales
        },
        
        goBack() {
            window.location.href = '/contract-templates';
        }
    }
}
</script>

<style scoped>
.template-creator-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background-color: #f8f9fa;
}

.top-bar {
    background: white;
    border-bottom: 1px solid #dee2e6;
    padding: 15px 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.main-area {
    display: flex;
    flex: 1;
    gap: 10px;
    padding: 10px;
    overflow: hidden;
}

/* Panels */
.variables-panel {
    width: 250px;
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.editor-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.preview-panel {
    width: 350px;
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.panel-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 10px 15px;
    border-radius: 8px 8px 0 0;
}

.panel-header h6 {
    margin: 0;
    font-size: 14px;
    color: #495057;
}

.panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
}

/* Variables Panel */
.variables-grid {
    display: grid;
    gap: 8px;
}

.variable-btn {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    color: #1976d2;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    display: flex;
    align-items: center;
}

.variable-btn:hover {
    background: #2196f3;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
}

.variable-btn:active {
    transform: translateY(0);
    background: #1976d2;
}

.custom-var-header {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 8px;
    font-weight: 500;
}

/* Editor Panel */
.editor-container {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.quill-editor {
    flex: 1;
    height: 100%;
}

.css-container {
    height: 100%;
}

.css-editor {
    width: 100%;
    height: 100%;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 12px;
    resize: none;
    outline: none;
}

/* Preview Panel */
.preview-container {
    height: 100%;
}

.preview-content {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 20px;
    font-size: 12px;
    line-height: 1.5;
    min-height: 100%;
    max-height: 100%;
    overflow-y: auto;
}

.mobile-preview .preview-content {
    max-width: 320px;
    margin: 0 auto;
    font-size: 10px;
}

.preview-placeholder {
    text-align: center;
    color: #6c757d;
    padding: 50px 20px;
}

/* Button States */
.btn-group .btn.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

/* Quill Editor Customization */
:deep(.ql-toolbar) {
    border: none;
    border-bottom: 1px solid #dee2e6;
    padding: 8px 12px;
}

:deep(.ql-container) {
    border: none;
    font-size: 14px;
    height: calc(100% - 42px);
}

:deep(.ql-editor) {
    line-height: 1.6;
    padding: 15px;
    height: 100%;
}

:deep(.ql-editor.ql-blank::before) {
    font-style: normal;
    color: #6c757d;
    left: 15px;
    right: 15px;
}
</style>