<template>
<div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-tags"></i> Categorías de {{ shop.name }}</span>
                <button v-if="!userLimited" type="button" @click="abrirModal('crear')" class="btn btn-primary">
                    <i class="fa fa-plus"></i>&nbsp;Nueva Categoría
                </button>
            </div>
            <div class="card-body">
                <!-- Filtros y búsqueda -->
                <div class="form-group row mb-3">
                    <div class="col-md-10">
                        <div class="input-group">
                            <input type="text" v-model="buscar" class="form-control" placeholder="Buscar por nombre..." @keyup.enter="loadCategories(1)">
                            <select class="form-control col-md-2" v-model="filtroActivo">
                                <option value="TODOS">Todos</option>
                                <option value="ACTIVOS">Activos</option>
                                <option value="INACTIVOS">Inactivos</option>
                            </select>
                            <button type="submit" @click="loadCategories(1)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de categorías -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="category in arrayCategories" :key="category.id" :class="{'table-secondary': !category.active}">
                                <td><strong>{{ category.id }}</strong></td>
                                <td>{{ category.name }}</td>
                                <td>{{ category.description || '-' }}</td>
                                <td>
                                    <span v-if="category.active" class="badge badge-success">Activo</span>
                                    <span v-else class="badge badge-danger">Inactivo</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button v-if="!userLimited" class="btn btn-primary btn-sm" @click="abrirModal('editar', category)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button v-if="!userLimited && category.active" class="btn btn-warning btn-sm" @click="desactivarCategoria(category.id)" title="Desactivar">
                                            <i class="fa fa-toggle-off"></i>
                                        </button>
                                        <button v-if="!userLimited && !category.active" class="btn btn-success btn-sm" @click="activarCategoria(category.id)" title="Activar">
                                            <i class="fa fa-toggle-on"></i>
                                        </button>
                                        <button v-if="!userLimited" class="btn btn-danger btn-sm" @click="confirmarEliminar(category)" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <span v-if="userLimited" class="text-muted small">Solo lectura</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mensaje si no hay categorías -->
                <div v-if="arrayCategories.length === 0 && !loading" class="text-center py-5">
                    <i class="fa fa-tags fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron categorías.</p>
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
                    <h4 class="modal-title">{{ modoEdicion ? 'Editar Categoría' : 'Nueva Categoría' }}</h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="errorForm" class="alert alert-danger">
                        <div v-for="error in erroresForm" :key="error">{{ error }}</div>
                    </div>
                    <form @submit.prevent="guardarCategoria">
                        <div class="form-group">
                            <label><strong class="text text-danger">*</strong> Nombre</label>
                            <input type="text" class="form-control" v-model="formCategory.name" required>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" v-model="formCategory.description" rows="3" placeholder="Descripción de la categoría..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarCategoria" :disabled="guardando">
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
            arrayCategories: [],
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
            formCategory: {
                id: null,
                name: '',
                description: ''
            },

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
        this.loadCategories(1);
    },
    methods: {
        loadCategories(page) {
            let me = this;
            me.loading = true;
            var url = `/admin/categories/get?page=${page}&buscar=${me.buscar}&filtro_activo=${me.filtroActivo}`;
            axios.get(url).then(function(response) {
                var respuesta = response.data;
                me.arrayCategories = respuesta.data;
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
            me.loadCategories(page);
        },

        abrirModal(tipo, category = null) {
            this.cerrarModal();

            if (tipo === 'crear') {
                this.modoEdicion = false;
                this.formCategory = {
                    id: null,
                    name: '',
                    description: ''
                };
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            } else if (tipo === 'editar' && category) {
                this.modoEdicion = true;
                this.formCategory = {
                    id: category.id,
                    name: category.name,
                    description: category.description || ''
                };
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            }
        },

        cerrarModal() {
            this.modalEditar = false;
        },

        guardarCategoria() {
            let me = this;
            me.guardando = true;
            me.errorForm = false;
            me.erroresForm = [];

            let url = me.modoEdicion ? '/admin/categories/update' : '/admin/categories/store';
            let method = me.modoEdicion ? axios.put : axios.post;

            method(url, me.formCategory).then(function(response) {
                if (response.data.ok) {
                    me.cerrarModal();
                    me.loadCategories(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                me.errorForm = true;
                if (error.response && error.response.data.errors) {
                    me.erroresForm = Object.values(error.response.data.errors).flat();
                } else {
                    me.erroresForm = ['Error al guardar la categoría'];
                }
            }).finally(function() {
                me.guardando = false;
            });
        },

        confirmarEliminar(category) {
            let me = this;
            Swal.fire({
                title: '¿Eliminar categoría?',
                text: category.name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    me.eliminarCategoria(category.id);
                }
            });
        },

        eliminarCategoria(id) {
            let me = this;
            axios.delete(`/admin/categories/${id}`).then(function(response) {
                if (response.data.ok) {
                    me.loadCategories(me.pagination.current_page || 1);
                    Swal.fire('Eliminada', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al eliminar categoría', 'error');
            });
        },

        activarCategoria(id) {
            let me = this;
            axios.put(`/admin/categories/${id}/activate`).then(function(response) {
                if (response.data.ok) {
                    me.loadCategories(me.pagination.current_page || 1);
                    Swal.fire('Activada', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al activar categoría', 'error');
            });
        },

        desactivarCategoria(id) {
            let me = this;
            axios.put(`/admin/categories/${id}/deactivate`).then(function(response) {
                if (response.data.ok) {
                    me.loadCategories(me.pagination.current_page || 1);
                    Swal.fire('Desactivada', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al desactivar categoría', 'error');
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
