<template>
    <div class="product-import">
        <!-- Paso 1: Subir archivo -->
        <div class="card mb-4" v-if="step === 1">
            <div class="card-header bg-primary text-white">
                <i class="fa fa-upload"></i> Paso 1: Seleccionar archivo
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Instrucciones:</h5>
                        <ol>
                            <li>Descarga la plantilla de ejemplo</li>
                            <li>Llena los datos de tus productos</li>
                            <li>Guarda el archivo como CSV o Excel (.xlsx)</li>
                            <li>Sube el archivo aquí</li>
                        </ol>
                        <a href="/admin/products/import/template" class="btn btn-outline-primary">
                            <i class="fa fa-download"></i> Descargar Plantilla
                        </a>
                    </div>
                    <div class="col-md-6">
                        <h5>Campos del archivo:</h5>
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Columna</th>
                                    <th>Obligatorio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Nombre</td><td><span class="badge badge-danger">Si</span></td></tr>
                                <tr><td>Codigo (SKU)</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Codigo Barras</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Descripcion</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Costo</td><td><span class="badge badge-danger">Si</span></td></tr>
                                <tr><td>Precio Nv1</td><td><span class="badge badge-danger">Si</span></td></tr>
                                <tr><td>Precio Nv2</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Precio Nv3</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Stock</td><td><span class="badge badge-secondary">No</span></td></tr>
                                <tr><td>Reserva</td><td><span class="badge badge-secondary">No</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr>

                <!-- Seleccionar Categoría -->
                <div class="form-group row mb-4">
                    <label class="col-md-3 col-form-label"><strong>Categoria para los productos:</strong></label>
                    <div class="col-md-5">
                        <select class="form-control" v-model="selectedCategoryId" required>
                            <option value="">-- Selecciona una categoria --</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <small class="text-muted">Todos los productos importados se asignaran a esta categoria</small>
                    </div>
                </div>

                <hr>

                <!-- Zona de carga -->
                <div
                    class="upload-zone"
                    :class="{ 'drag-over': isDragging }"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                    @click="$refs.fileInput.click()"
                >
                    <input
                        type="file"
                        ref="fileInput"
                        @change="handleFileSelect"
                        accept=".csv,.xlsx,.xls"
                        style="display: none"
                    >
                    <div v-if="!uploading">
                        <i class="fa fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <p class="mb-0">Arrastra tu archivo aquí o haz clic para seleccionar</p>
                        <small class="text-muted">Formatos: CSV, Excel (.xlsx, .xls) - Max 5MB</small>
                    </div>
                    <div v-else>
                        <i class="fa fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                        <p class="mb-0">Procesando archivo...</p>
                    </div>
                </div>

                <div v-if="errorMessage" class="alert alert-danger mt-3">
                    <i class="fa fa-exclamation-circle"></i> {{ errorMessage }}
                </div>
            </div>
        </div>

        <!-- Paso 2: Preview -->
        <div class="card mb-4" v-if="step === 2">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <span><i class="fa fa-eye"></i> Paso 2: Revisar datos</span>
                <button class="btn btn-sm btn-light" @click="reset">
                    <i class="fa fa-arrow-left"></i> Volver
                </button>
            </div>
            <div class="card-body">
                <!-- Resumen -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ previewData.length }}</h3>
                                <small>Total filas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ validCount }}</h3>
                                <small>Validos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ errorCount }}</h3>
                                <small>Con errores</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla preview -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="thead-dark" style="position: sticky; top: 0;">
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Codigo</th>
                                <th>Categoria</th>
                                <th>Costo</th>
                                <th>Precio Nv1</th>
                                <th>Stock</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in previewData" :key="index" :class="{ 'table-danger': !row.valid }">
                                <td>{{ row.row_number }}</td>
                                <td>{{ row.name || '-' }}</td>
                                <td>{{ row.key || '-' }}</td>
                                <td>
                                    <span v-if="row.category_id">{{ row.category_name }}</span>
                                    <span v-else class="text-muted">(General)</span>
                                </td>
                                <td>{{ formatMoney(row.cost) }}</td>
                                <td>{{ formatMoney(row.retail) }}</td>
                                <td>{{ row.stock }}</td>
                                <td>
                                    <span v-if="row.valid" class="badge badge-success">OK</span>
                                    <span v-else class="badge badge-danger" :title="row.errors.join(', ')">
                                        <i class="fa fa-exclamation-triangle"></i> {{ row.errors.length }} error(es)
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="errorCount > 0" class="alert alert-warning mt-3">
                    <i class="fa fa-info-circle"></i>
                    Las filas con errores no seran importadas. Corrigelas en el archivo y vuelve a subirlo, o continua para importar solo las validas.
                </div>

                <div class="mt-4 text-right">
                    <button class="btn btn-secondary mr-2" @click="reset">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button
                        class="btn btn-success"
                        @click="executeImport"
                        :disabled="importing || validCount === 0"
                    >
                        <i class="fa fa-spinner fa-spin" v-if="importing"></i>
                        <i class="fa fa-check" v-else></i>
                        Importar {{ validCount }} productos
                    </button>
                </div>
            </div>
        </div>

        <!-- Paso 3: Resultado -->
        <div class="card mb-4" v-if="step === 3">
            <div class="card-header bg-success text-white">
                <i class="fa fa-check-circle"></i> Importacion Completada
            </div>
            <div class="card-body text-center">
                <i class="fa fa-check-circle fa-5x text-success mb-4"></i>
                <h3>{{ importResult.created }} productos importados correctamente</h3>

                <div v-if="importResult.errors && importResult.errors.length > 0" class="alert alert-warning mt-3 text-left">
                    <h5><i class="fa fa-exclamation-triangle"></i> Errores durante la importacion:</h5>
                    <ul class="mb-0">
                        <li v-for="(err, i) in importResult.errors" :key="i">
                            Fila {{ err.row }}: {{ err.name }} - {{ err.error }}
                        </li>
                    </ul>
                </div>

                <div class="mt-4">
                    <a href="/admin/products" class="btn btn-primary">
                        <i class="fa fa-list"></i> Ver productos
                    </a>
                    <button class="btn btn-outline-secondary ml-2" @click="reset">
                        <i class="fa fa-upload"></i> Importar mas
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        shop: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            step: 1,
            isDragging: false,
            uploading: false,
            importing: false,
            errorMessage: '',
            previewData: [],
            importResult: {
                created: 0,
                errors: []
            },
            categories: [],
            selectedCategoryId: ''
        }
    },
    mounted() {
        this.loadCategories();
    },
    computed: {
        validCount() {
            return this.previewData.filter(r => r.valid).length;
        },
        errorCount() {
            return this.previewData.filter(r => !r.valid).length;
        }
    },
    methods: {
        loadCategories() {
            axios.get('/admin/products/categories').then(response => {
                if (response.data.ok) {
                    this.categories = response.data.categories;
                    // Si solo hay una categoría, seleccionarla automáticamente
                    if (this.categories.length === 1) {
                        this.selectedCategoryId = this.categories[0].id;
                    }
                }
            }).catch(error => {
                console.log('Error cargando categorías:', error);
            });
        },
        handleDrop(e) {
            this.isDragging = false;
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.uploadFile(files[0]);
            }
        },
        handleFileSelect(e) {
            const files = e.target.files;
            if (files.length > 0) {
                this.uploadFile(files[0]);
            }
        },
        uploadFile(file) {
            // Validar que se haya seleccionado una categoría
            if (!this.selectedCategoryId) {
                this.errorMessage = 'Primero selecciona una categoria para los productos';
                return;
            }
            // Validar tipo
            const validTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            const validExtensions = ['.csv', '.xls', '.xlsx'];
            const extension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();

            if (!validExtensions.includes(extension)) {
                this.errorMessage = 'Formato no valido. Usa CSV o Excel (.xlsx, .xls)';
                return;
            }

            // Validar tamaño (5MB)
            if (file.size > 5 * 1024 * 1024) {
                this.errorMessage = 'El archivo es muy grande. Maximo 5MB';
                return;
            }

            this.errorMessage = '';
            this.uploading = true;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('category_id', this.selectedCategoryId);

            axios.post('/admin/products/import/preview', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(response => {
                if (response.data.ok) {
                    this.previewData = response.data.preview;
                    this.step = 2;
                } else {
                    this.errorMessage = response.data.message || 'Error al procesar el archivo';
                }
            }).catch(error => {
                if (error.response && error.response.data.message) {
                    this.errorMessage = error.response.data.message;
                } else {
                    this.errorMessage = 'Error al subir el archivo. Intenta de nuevo.';
                }
            }).finally(() => {
                this.uploading = false;
                this.$refs.fileInput.value = '';
            });
        },
        executeImport() {
            this.importing = true;

            // Solo enviar los válidos
            const validProducts = this.previewData.filter(r => r.valid);

            axios.post('/admin/products/import/execute', {
                products: validProducts,
                category_id: this.selectedCategoryId
            }).then(response => {
                if (response.data.ok) {
                    this.importResult = {
                        created: response.data.created,
                        errors: response.data.errors || []
                    };
                    this.step = 3;
                } else {
                    Swal.fire('Error', response.data.message, 'error');
                }
            }).catch(error => {
                let msg = 'Error durante la importacion';
                if (error.response && error.response.data.message) {
                    msg = error.response.data.message;
                }
                Swal.fire('Error', msg, 'error');
            }).finally(() => {
                this.importing = false;
            });
        },
        reset() {
            this.step = 1;
            this.previewData = [];
            this.errorMessage = '';
            this.importResult = { created: 0, errors: [] };
        },
        formatMoney(value) {
            return '$' + parseFloat(value || 0).toFixed(2);
        }
    }
}
</script>

<style scoped>
.upload-zone {
    border: 3px dashed #ccc;
    border-radius: 10px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}
.upload-zone:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}
.upload-zone.drag-over {
    border-color: #28a745;
    background-color: #e8f5e9;
}
.table-danger {
    background-color: #f8d7da !important;
}
</style>
