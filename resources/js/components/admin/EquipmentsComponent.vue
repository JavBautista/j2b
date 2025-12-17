<template>
<div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-print"></i> Equipos de {{ shop.name }}</span>
                <button v-if="!userLimited" type="button" @click="abrirModal('crear')" class="btn btn-primary">
                    <i class="fa fa-plus"></i>&nbsp;Nuevo Equipo
                </button>
            </div>
            <div class="card-body">
                <!-- Filtros y búsqueda -->
                <div class="form-group row mb-3">
                    <div class="col-md-10">
                        <div class="input-group">
                            <input type="text" v-model="buscar" class="form-control" placeholder="Buscar por marca, modelo o número de serie..." @keyup.enter="loadEquipments(1)">
                            <select class="form-control col-md-2" v-model="filtroActivo">
                                <option value="TODOS">Todos</option>
                                <option value="ACTIVOS">Activos</option>
                                <option value="INACTIVOS">Inactivos</option>
                            </select>
                            <button type="submit" @click="loadEquipments(1)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de equipos -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>N° Serie</th>
                                <th class="text-right">Renta</th>
                                <th class="text-center">Mono</th>
                                <th class="text-center">Color</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="equipment in arrayEquipments" :key="equipment.id" :class="{'table-secondary': !equipment.active}">
                                <td><strong>{{ equipment.id }}</strong></td>
                                <td>{{ equipment.trademark }}</td>
                                <td>{{ equipment.model }}</td>
                                <td>{{ equipment.serial_number || '-' }}</td>
                                <td class="text-right"><strong>${{ formatMoney(equipment.rent_price) }}</strong></td>
                                <td class="text-center">
                                    <span v-if="equipment.monochrome" class="badge badge-info">Sí</span>
                                    <span v-else class="badge badge-secondary">No</span>
                                </td>
                                <td class="text-center">
                                    <span v-if="equipment.color" class="badge badge-success">Sí</span>
                                    <span v-else class="badge badge-secondary">No</span>
                                </td>
                                <td>
                                    <span v-if="equipment.active" class="badge badge-success">Activo</span>
                                    <span v-else class="badge badge-danger">Inactivo</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button v-if="!userLimited" class="btn btn-primary btn-sm" @click="abrirModal('editar', equipment)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button v-if="!userLimited" class="btn btn-success btn-sm" @click="abrirModalImagenes(equipment)" title="Imágenes">
                                            <i class="fa fa-image"></i>
                                        </button>
                                        <button v-if="!userLimited && equipment.active" class="btn btn-warning btn-sm" @click="desactivarEquipo(equipment.id)" title="Desactivar">
                                            <i class="fa fa-toggle-off"></i>
                                        </button>
                                        <button v-if="!userLimited && !equipment.active" class="btn btn-success btn-sm" @click="activarEquipo(equipment.id)" title="Activar">
                                            <i class="fa fa-toggle-on"></i>
                                        </button>
                                        <button v-if="!userLimited" class="btn btn-danger btn-sm" @click="confirmarEliminar(equipment)" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <span v-if="userLimited" class="text-muted small">Solo lectura</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mensaje si no hay equipos -->
                <div v-if="arrayEquipments.length === 0 && !loading" class="text-center py-5">
                    <i class="fa fa-print fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron equipos.</p>
                </div>

                <!-- Paginación -->
                <nav>
                    <ul class="pagination">
                        <li class="page-item" v-if="pagination.current_page > 1">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page-1)">Ant</a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page==isActived ? 'active':'']">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(page)" v-text="page"></a>
                        </li>
                        <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page+1)">Sig</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalEditar}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ modoEdicion ? 'Editar Equipo' : 'Nuevo Equipo' }}</h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="errorForm" class="alert alert-danger">
                        <div v-for="error in erroresForm" :key="error">{{ error }}</div>
                    </div>
                    <form @submit.prevent="guardarEquipo">
                        <p><em><strong class="text text-danger">* Campos obligatorios</strong></em></p>

                        <!-- Información básica -->
                        <h6 class="text-muted mb-3">Información del Equipo</h6>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label"><strong class="text text-danger">*</strong> Marca</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" v-model="formEquipment.trademark" required>
                            </div>
                            <label class="col-md-2 col-form-label"><strong class="text text-danger">*</strong> Modelo</label>
                            <div class="col-md-3">
                                <input type="text" class="form-control" v-model="formEquipment.model" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">N° Serie</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" v-model="formEquipment.serial_number">
                            </div>
                            <label class="col-md-2 col-form-label">Precio Renta</label>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formEquipment.rent_price" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">Descripción</label>
                            <div class="col-md-9">
                                <textarea class="form-control" v-model="formEquipment.description" rows="2"></textarea>
                            </div>
                        </div>

                        <hr>
                        <!-- Contadores Monocromático -->
                        <h6 class="text-muted mb-3">Contador Monocromático</h6>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" v-model="formEquipment.monochrome" id="checkMono">
                                    <label class="form-check-label" for="checkMono">Tiene monocromático</label>
                                </div>
                            </div>
                            <label class="col-md-2 col-form-label">Págs incluidas</label>
                            <div class="col-md-2">
                                <input type="number" class="form-control" v-model="formEquipment.pages_included_mono" min="0" :disabled="!formEquipment.monochrome">
                            </div>
                            <label class="col-md-2 col-form-label">Costo extra</label>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formEquipment.extra_page_cost_mono" min="0" :disabled="!formEquipment.monochrome">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row" v-if="formEquipment.monochrome">
                            <label class="col-md-3 col-form-label">Contador actual</label>
                            <div class="col-md-3">
                                <input type="number" class="form-control" v-model="formEquipment.counter_mono" min="0">
                            </div>
                        </div>

                        <hr>
                        <!-- Contadores Color -->
                        <h6 class="text-muted mb-3">Contador Color</h6>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" v-model="formEquipment.color" id="checkColor">
                                    <label class="form-check-label" for="checkColor">Tiene color</label>
                                </div>
                            </div>
                            <label class="col-md-2 col-form-label">Págs incluidas</label>
                            <div class="col-md-2">
                                <input type="number" class="form-control" v-model="formEquipment.pages_included_color" min="0" :disabled="!formEquipment.color">
                            </div>
                            <label class="col-md-2 col-form-label">Costo extra</label>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formEquipment.extra_page_cost_color" min="0" :disabled="!formEquipment.color">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row" v-if="formEquipment.color">
                            <label class="col-md-3 col-form-label">Contador actual</label>
                            <div class="col-md-3">
                                <input type="number" class="form-control" v-model="formEquipment.counter_color" min="0">
                            </div>
                        </div>

                        <hr>
                        <!-- Precios de Venta -->
                        <h6 class="text-muted mb-3">Precios de Venta (Opcional)</h6>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">Costo</label>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formEquipment.cost" min="0">
                                </div>
                            </div>
                            <label class="col-md-2 col-form-label">Precio Venta</label>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formEquipment.retail" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">Mayoreo</label>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" v-model="formEquipment.wholesale" min="0">
                                </div>
                            </div>
                            <label class="col-md-2 col-form-label">Tipo Venta</label>
                            <div class="col-md-4">
                                <select class="form-control" v-model="formEquipment.type_sale">
                                    <option value="">Sin especificar</option>
                                    <option value="nuevo">Nuevo</option>
                                    <option value="usado">Usado</option>
                                    <option value="remanufacturado">Remanufacturado</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarEquipo" :disabled="guardando">
                        <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                        <i class="fa fa-save" v-else></i>
                        {{ guardando ? 'Guardando...' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestionar Imágenes -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalImagenes}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Imágenes del Equipo</h4>
                    <button type="button" class="close" @click="modalImagenes = false" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body" v-if="equipoImagenes">
                    <p><strong>Equipo:</strong> {{ equipoImagenes.trademark }} {{ equipoImagenes.model }}</p>
                    <hr>

                    <!-- Subir nueva imagen -->
                    <div class="mb-4">
                        <h6>Subir nueva imagen</h6>
                        <div class="input-group">
                            <input type="file" class="form-control" @change="seleccionarImagen" accept="image/*" ref="inputImagen">
                            <button class="btn btn-primary" @click="subirImagen" :disabled="!imagenSeleccionada || subiendoImagen">
                                <i class="fa fa-spinner fa-spin" v-if="subiendoImagen"></i>
                                <i class="fa fa-upload" v-else></i>
                                {{ subiendoImagen ? 'Subiendo...' : 'Subir' }}
                            </button>
                        </div>
                        <small class="text-muted">Formatos: JPG, PNG, GIF, WebP. Máximo 2MB.</small>
                    </div>

                    <!-- Imágenes -->
                    <div>
                        <h6>Imágenes ({{ equipoImagenes.images ? equipoImagenes.images.length : 0 }})</h6>
                        <div class="row" v-if="equipoImagenes.images && equipoImagenes.images.length > 0">
                            <div class="col-md-3 mb-3" v-for="(img, index) in equipoImagenes.images" :key="img.id">
                                <div class="position-relative">
                                    <img :src="getImageUrl(img.image)" class="img-thumbnail" style="width: 100%; height: 120px; object-fit: cover; cursor: pointer;" @click="verGaleriaEquipo(index)">
                                    <button class="btn btn-danger btn-sm position-absolute" style="top: 5px; right: 5px;" @click="eliminarImagen(img.id)" :disabled="eliminandoImagen">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted">
                            <p>Sin imágenes</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="modalImagenes = false">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
export default {
    props: ['shop', 'userLimited'],
    data() {
        return {
            arrayEquipments: [],
            pagination: {
                'total': 0,
                'current_page': 0,
                'per_page': 0,
                'last_page': 0,
                'from': 0,
                'to': 0
            },
            offset: 3,
            loading: false,
            buscar: '',
            filtroActivo: 'TODOS',

            // Modal
            modalEditar: false,
            modalImagenes: false,
            modoEdicion: false,
            formEquipment: {
                id: null,
                trademark: '',
                model: '',
                serial_number: '',
                rent_price: 0,
                monochrome: false,
                pages_included_mono: null,
                extra_page_cost_mono: null,
                counter_mono: null,
                color: false,
                pages_included_color: null,
                extra_page_cost_color: null,
                counter_color: null,
                description: '',
                cost: 0,
                retail: 0,
                wholesale: 0,
                type_sale: ''
            },

            // Imágenes
            equipoImagenes: null,
            imagenSeleccionada: null,
            subiendoImagen: false,
            eliminandoImagen: false,

            // UI
            guardando: false,
            errorForm: false,
            erroresForm: []
        }
    },
    computed: {
        isActived() {
            return this.pagination.current_page;
        },
        pagesNumber() {
            if (!this.pagination.to) {
                return [];
            }
            var from = this.pagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }
            var to = from + (this.offset * 2);
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
            }
            var pagesArray = [];
            while (from <= to) {
                pagesArray.push(from);
                from++;
            }
            return pagesArray;
        }
    },
    mounted() {
        this.loadEquipments(1);
    },
    methods: {
        loadEquipments(page) {
            let me = this;
            me.loading = true;
            var url = `/admin/equipments/get?page=${page}&buscar=${me.buscar}&filtro_activo=${me.filtroActivo}`;
            axios.get(url).then(function(response) {
                var respuesta = response.data;
                me.arrayEquipments = respuesta.data;
                me.pagination = {
                    total: respuesta.total,
                    current_page: respuesta.current_page,
                    per_page: respuesta.per_page,
                    last_page: respuesta.last_page,
                    from: respuesta.from,
                    to: respuesta.to
                };
            }).catch(function(error) {
                console.log(error);
            }).finally(function() {
                me.loading = false;
            });
        },

        cambiarPagina(page) {
            let me = this;
            me.pagination.current_page = page;
            me.loadEquipments(page);
        },

        formatMoney(value) {
            if (!value) return '0.00';
            return parseFloat(value).toFixed(2);
        },

        getImageUrl(path) {
            if (!path) return '';
            return `/storage/${path}`;
        },

        abrirModal(tipo, equipment = null) {
            this.cerrarModal();

            if (tipo === 'crear') {
                this.modoEdicion = false;
                this.formEquipment = {
                    id: null,
                    trademark: '',
                    model: '',
                    serial_number: '',
                    rent_price: 0,
                    monochrome: false,
                    pages_included_mono: null,
                    extra_page_cost_mono: null,
                    counter_mono: null,
                    color: false,
                    pages_included_color: null,
                    extra_page_cost_color: null,
                    counter_color: null,
                    description: '',
                    cost: 0,
                    retail: 0,
                    wholesale: 0,
                    type_sale: ''
                };
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            } else if (tipo === 'editar' && equipment) {
                this.modoEdicion = true;
                this.formEquipment = {
                    id: equipment.id,
                    trademark: equipment.trademark || '',
                    model: equipment.model || '',
                    serial_number: equipment.serial_number || '',
                    rent_price: equipment.rent_price || 0,
                    monochrome: equipment.monochrome == 1,
                    pages_included_mono: equipment.pages_included_mono,
                    extra_page_cost_mono: equipment.extra_page_cost_mono,
                    counter_mono: equipment.counter_mono,
                    color: equipment.color == 1,
                    pages_included_color: equipment.pages_included_color,
                    extra_page_cost_color: equipment.extra_page_cost_color,
                    counter_color: equipment.counter_color,
                    description: equipment.description || '',
                    cost: equipment.cost || 0,
                    retail: equipment.retail || 0,
                    wholesale: equipment.wholesale || 0,
                    type_sale: equipment.type_sale || ''
                };
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            }
        },

        abrirModalImagenes(equipment) {
            this.equipoImagenes = equipment;
            this.imagenSeleccionada = null;
            this.modalImagenes = true;
        },

        cerrarModal() {
            this.modalEditar = false;
            this.modalImagenes = false;
        },

        guardarEquipo() {
            let me = this;
            me.guardando = true;
            me.errorForm = false;
            me.erroresForm = [];

            let url = me.modoEdicion ? '/admin/equipments/update' : '/admin/equipments/store';
            let method = me.modoEdicion ? axios.put : axios.post;

            method(url, me.formEquipment).then(function(response) {
                if (response.data.ok) {
                    me.cerrarModal();
                    me.loadEquipments(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                me.errorForm = true;
                if (error.response && error.response.data.errors) {
                    me.erroresForm = Object.values(error.response.data.errors).flat();
                } else {
                    me.erroresForm = ['Error al guardar el equipo'];
                }
            }).finally(function() {
                me.guardando = false;
            });
        },

        // Imágenes
        seleccionarImagen(event) {
            this.imagenSeleccionada = event.target.files[0];
        },

        subirImagen() {
            let me = this;
            if (!me.imagenSeleccionada) return;

            me.subiendoImagen = true;

            let formData = new FormData();
            formData.append('image', me.imagenSeleccionada);

            axios.post(`/admin/equipments/${me.equipoImagenes.id}/upload-image`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(function(response) {
                if (response.data.ok) {
                    me.equipoImagenes = response.data.equipment;
                    me.imagenSeleccionada = null;
                    me.$refs.inputImagen.value = '';
                    me.loadEquipments(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                let msg = 'Error al subir imagen';
                if (error.response && error.response.data.message) {
                    msg = error.response.data.message;
                }
                Swal.fire('Error', msg, 'error');
            }).finally(function() {
                me.subiendoImagen = false;
            });
        },

        eliminarImagen(imageId) {
            let me = this;
            Swal.fire({
                title: '¿Eliminar esta imagen?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    me.eliminandoImagen = true;
                    axios.delete(`/admin/equipments/delete-image/${imageId}`).then(function(response) {
                        if (response.data.ok) {
                            me.equipoImagenes = response.data.equipment;
                            me.loadEquipments(me.pagination.current_page || 1);
                            Swal.fire('Eliminada', response.data.message, 'success');
                        }
                    }).catch(function(error) {
                        Swal.fire('Error', 'Error al eliminar imagen', 'error');
                    }).finally(function() {
                        me.eliminandoImagen = false;
                    });
                }
            });
        },

        // Visor de imágenes
        verGaleriaEquipo(index) {
            if (this.equipoImagenes.images && this.equipoImagenes.images.length > 0) {
                let imagenes = this.equipoImagenes.images.map(img => img.image);
                this.$viewImages(imagenes, index);
            }
        },

        confirmarEliminar(equipment) {
            let me = this;
            Swal.fire({
                title: '¿Eliminar equipo?',
                text: `${equipment.trademark} ${equipment.model}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    me.eliminarEquipo(equipment.id);
                }
            });
        },

        eliminarEquipo(id) {
            let me = this;
            axios.delete(`/admin/equipments/${id}`).then(function(response) {
                if (response.data.ok) {
                    me.loadEquipments(me.pagination.current_page || 1);
                    Swal.fire('Eliminado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al eliminar equipo', 'error');
            });
        },

        activarEquipo(id) {
            let me = this;
            axios.put(`/admin/equipments/${id}/activate`).then(function(response) {
                if (response.data.ok) {
                    me.loadEquipments(me.pagination.current_page || 1);
                    Swal.fire('Activado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al activar equipo', 'error');
            });
        },

        desactivarEquipo(id) {
            let me = this;
            axios.put(`/admin/equipments/${id}/deactivate`).then(function(response) {
                if (response.data.ok) {
                    me.loadEquipments(me.pagination.current_page || 1);
                    Swal.fire('Desactivado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al desactivar equipo', 'error');
            });
        }
    }
}
</script>

<style>
    .modal-content{
        width: 100% !important;
        position: absolute !important;
    }
    .mostrar{
        display: list-item !important;
        opacity: 1 !important;
        position: fixed !important;
        background-color: #3c29297a !important;
        overflow: scroll;
    }
</style>
