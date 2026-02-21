<template>
<div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-wrench"></i> Servicios de {{ shop.name }}</span>
                <button v-if="!userLimited" type="button" @click="abrirModal('crear')" class="btn btn-primary">
                    <i class="fa fa-plus"></i>&nbsp;Nuevo Servicio
                </button>
            </div>
            <div class="card-body">
                <!-- Filtros y búsqueda -->
                <div class="form-group row mb-3">
                    <div class="col-md-10">
                        <div class="input-group">
                            <input type="text" v-model="buscar" class="form-control" placeholder="Buscar por nombre..." @keyup.enter="loadServices(1)">
                            <select class="form-control col-md-2" v-model="filtroActivo">
                                <option value="TODOS">Todos</option>
                                <option value="ACTIVOS">Activos</option>
                                <option value="INACTIVOS">Inactivos</option>
                            </select>
                            <button type="submit" @click="loadServices(1)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de servicios -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th class="text-right">Precio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="service in arrayServices" :key="service.id" :class="{'table-secondary': !service.active}">
                                <td><strong>{{ service.id }}</strong></td>
                                <td>{{ service.name }}</td>
                                <td>{{ service.description || '-' }}</td>
                                <td class="text-right"><strong>${{ formatMoney(service.price) }}</strong></td>
                                <td>
                                    <span v-if="service.active" class="badge badge-success">Activo</span>
                                    <span v-else class="badge badge-danger">Inactivo</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button v-if="!userLimited" class="btn btn-primary btn-sm" @click="abrirModal('editar', service)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button v-if="!userLimited && service.active" class="btn btn-warning btn-sm" @click="desactivarServicio(service.id)" title="Desactivar">
                                            <i class="fa fa-toggle-off"></i>
                                        </button>
                                        <button v-if="!userLimited && !service.active" class="btn btn-success btn-sm" @click="activarServicio(service.id)" title="Activar">
                                            <i class="fa fa-toggle-on"></i>
                                        </button>
                                        <button v-if="!userLimited" class="btn btn-danger btn-sm" @click="confirmarEliminar(service)" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <span v-if="userLimited" class="text-muted small">Solo lectura</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mensaje si no hay servicios -->
                <div v-if="arrayServices.length === 0 && !loading" class="text-center py-5">
                    <i class="fa fa-wrench fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron servicios.</p>
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
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ modoEdicion ? 'Editar Servicio' : 'Nuevo Servicio' }}</h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="errorForm" class="alert alert-danger">
                        <div v-for="error in erroresForm" :key="error">{{ error }}</div>
                    </div>
                    <form @submit.prevent="guardarServicio">
                        <div class="form-group">
                            <label><strong class="text text-danger">*</strong> Nombre</label>
                            <input type="text" class="form-control" v-model="formService.name" required>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" v-model="formService.description" rows="3" placeholder="Descripción del servicio..."></textarea>
                        </div>
                        <div class="form-group">
                            <label><strong class="text text-danger">*</strong> Precio</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" v-model="formService.price" required min="0">
                            </div>
                        </div>
                        <hr>
                        <h6 class="text-muted mb-3"><i class="fa fa-file-invoice"></i> Facturación SAT</h6>
                        <div class="form-group">
                            <label>Clave Prod/Serv</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" v-model="satProductSearch"
                                    placeholder="Buscar clave SAT... ej: servicio, 01010101"
                                    @input="buscarSatProduct" @focus="showSatProductResults = true">
                                <small v-if="formService.sat_product_code" class="text-success">
                                    <i class="fa fa-check"></i> {{ formService.sat_product_code }} — {{ formService.sat_product_desc }}
                                </small>
                                <small v-else class="text-muted">Opcional. Si no se asigna, se usa 01010101 (genérico).</small>
                                <ul v-if="showSatProductResults && satProductResults.length > 0" class="list-group position-absolute w-100" style="z-index: 1050; max-height: 200px; overflow-y: auto;">
                                    <li v-for="item in satProductResults" :key="item.code"
                                        class="list-group-item list-group-item-action py-1 px-2" style="cursor: pointer; font-size: 0.85rem;"
                                        @mousedown.prevent="selectSatProduct(item)">
                                        <strong>{{ item.code }}</strong> — {{ item.description }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Clave Unidad</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" v-model="satUnitSearch"
                                    placeholder="Buscar unidad... ej: servicio, pieza, E48"
                                    @input="buscarSatUnit" @focus="showSatUnitResults = true">
                                <small v-if="formService.sat_unit_code" class="text-success">
                                    <i class="fa fa-check"></i> {{ formService.sat_unit_code }} — {{ formService.sat_unit_name }}
                                </small>
                                <small v-else class="text-muted">Default: E48 (Unidad de servicio)</small>
                                <ul v-if="showSatUnitResults && satUnitResults.length > 0" class="list-group position-absolute w-100" style="z-index: 1050; max-height: 200px; overflow-y: auto;">
                                    <li v-for="item in satUnitResults" :key="item.code"
                                        class="list-group-item list-group-item-action py-1 px-2" style="cursor: pointer; font-size: 0.85rem;"
                                        @mousedown.prevent="selectSatUnit(item)">
                                        <strong>{{ item.code }}</strong> — {{ item.name }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarServicio" :disabled="guardando">
                        <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                        <i class="fa fa-save" v-else></i>
                        {{ guardando ? 'Guardando...' : 'Guardar' }}
                    </button>
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
            arrayServices: [],
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
            modoEdicion: false,
            formService: {
                id: null,
                name: '',
                description: '',
                price: 0,
                sat_product_code: null,
                sat_product_desc: '',
                sat_unit_code: 'E48',
                sat_unit_name: 'Unidad de servicio'
            },

            // SAT autocomplete
            satProductSearch: '',
            satProductResults: [],
            showSatProductResults: false,
            satUnitSearch: '',
            satUnitResults: [],
            showSatUnitResults: false,
            satSearchTimer: null,

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
        this.loadServices(1);
    },
    methods: {
        loadServices(page) {
            let me = this;
            me.loading = true;
            var url = `/admin/services/get?page=${page}&buscar=${me.buscar}&filtro_activo=${me.filtroActivo}`;
            axios.get(url).then(function(response) {
                var respuesta = response.data;
                me.arrayServices = respuesta.data;
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
            me.loadServices(page);
        },

        formatMoney(value) {
            if (!value) return '0.00';
            return parseFloat(value).toFixed(2);
        },

        abrirModal(tipo, service = null) {
            this.cerrarModal();

            if (tipo === 'crear') {
                this.modoEdicion = false;
                this.formService = {
                    id: null,
                    name: '',
                    description: '',
                    price: 0,
                    sat_product_code: null,
                    sat_product_desc: '',
                    sat_unit_code: 'E48',
                    sat_unit_name: 'Unidad de servicio'
                };
                this.satProductSearch = '';
                this.satUnitSearch = '';
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            } else if (tipo === 'editar' && service) {
                this.modoEdicion = true;
                this.formService = {
                    id: service.id,
                    name: service.name,
                    description: service.description || '',
                    price: service.price || 0,
                    sat_product_code: service.sat_product_code || null,
                    sat_product_desc: service.sat_product_desc || '',
                    sat_unit_code: service.sat_unit_code || 'E48',
                    sat_unit_name: service.sat_unit_name || ''
                };
                this.satProductSearch = '';
                this.satUnitSearch = '';
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            }
        },

        cerrarModal() {
            this.modalEditar = false;
        },

        guardarServicio() {
            let me = this;
            me.guardando = true;
            me.errorForm = false;
            me.erroresForm = [];

            let url = me.modoEdicion ? '/admin/services/update' : '/admin/services/store';
            let method = me.modoEdicion ? axios.put : axios.post;

            method(url, me.formService).then(function(response) {
                if (response.data.ok) {
                    me.cerrarModal();
                    me.loadServices(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                me.errorForm = true;
                if (error.response && error.response.data.errors) {
                    me.erroresForm = Object.values(error.response.data.errors).flat();
                } else {
                    me.erroresForm = ['Error al guardar el servicio'];
                }
            }).finally(function() {
                me.guardando = false;
            });
        },

        confirmarEliminar(service) {
            let me = this;
            Swal.fire({
                title: '¿Eliminar servicio?',
                text: service.name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    me.eliminarServicio(service.id);
                }
            });
        },

        eliminarServicio(id) {
            let me = this;
            axios.delete(`/admin/services/${id}`).then(function(response) {
                if (response.data.ok) {
                    me.loadServices(me.pagination.current_page || 1);
                    Swal.fire('Eliminado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al eliminar servicio', 'error');
            });
        },

        activarServicio(id) {
            let me = this;
            axios.put(`/admin/services/${id}/activate`).then(function(response) {
                if (response.data.ok) {
                    me.loadServices(me.pagination.current_page || 1);
                    Swal.fire('Activado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al activar servicio', 'error');
            });
        },

        // SAT Catalog search
        buscarSatProduct() {
            clearTimeout(this.satSearchTimer);
            let q = this.satProductSearch;
            if (q.length < 2) { this.satProductResults = []; return; }
            this.satSearchTimer = setTimeout(() => {
                axios.get('/admin/sat/product-codes', { params: { q } }).then(res => {
                    this.satProductResults = res.data;
                    this.showSatProductResults = true;
                });
            }, 300);
        },

        selectSatProduct(item) {
            this.formService.sat_product_code = item.code;
            this.formService.sat_product_desc = item.description;
            this.satProductSearch = '';
            this.satProductResults = [];
            this.showSatProductResults = false;
        },

        buscarSatUnit() {
            clearTimeout(this.satSearchTimer);
            let q = this.satUnitSearch;
            if (q.length < 1) { this.satUnitResults = []; return; }
            this.satSearchTimer = setTimeout(() => {
                axios.get('/admin/sat/unit-codes', { params: { q } }).then(res => {
                    this.satUnitResults = res.data;
                    this.showSatUnitResults = true;
                });
            }, 300);
        },

        selectSatUnit(item) {
            this.formService.sat_unit_code = item.code;
            this.formService.sat_unit_name = item.name;
            this.satUnitSearch = '';
            this.satUnitResults = [];
            this.showSatUnitResults = false;
        },

        desactivarServicio(id) {
            let me = this;
            axios.put(`/admin/services/${id}/deactivate`).then(function(response) {
                if (response.data.ok) {
                    me.loadServices(me.pagination.current_page || 1);
                    Swal.fire('Desactivado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al desactivar servicio', 'error');
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
