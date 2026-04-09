<template>
<div>
    <div class="container-fluid">
        <!-- Resumen de tareas -->
        <div class="j2b-stat-grid mb-4">
            <div class="j2b-stat j2b-stat-success">
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">{{ numStatus.numNuevo || 0 }}</div>
                    <div class="j2b-stat-label">Nuevas</div>
                </div>
            </div>
            <div class="j2b-stat j2b-stat-warning">
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">{{ numStatus.numPendiente || 0 }}</div>
                    <div class="j2b-stat-label">Pendientes</div>
                </div>
            </div>
            <div class="j2b-stat j2b-stat-primary">
                <div class="j2b-stat-content">
                    <div class="j2b-stat-value">{{ numStatus.numAtendido || 0 }}</div>
                    <div class="j2b-stat-label">Atendidas</div>
                </div>
            </div>
        </div>

        <!-- Card principal de tareas -->
        <div class="j2b-card">
            <div class="j2b-card-header d-flex justify-content-between align-items-center">
                <h5 class="j2b-card-title mb-0"><i class="fa fa-tasks" style="color: var(--j2b-primary);"></i> Tareas de {{ shop.name }}</h5>
                <div class="d-flex gap-2">
                    <!-- Toggle Vista -->
                    <div class="btn-group">
                        <button type="button" class="j2b-btn j2b-btn-sm" :class="vistaActual === 'cards' ? 'j2b-btn-primary' : 'j2b-btn-outline'" @click="vistaActual = 'cards'" title="Vista Cards">
                            <i class="fa fa-th-large"></i>
                        </button>
                        <button type="button" class="j2b-btn j2b-btn-sm" :class="vistaActual === 'tabla' ? 'j2b-btn-primary' : 'j2b-btn-outline'" @click="vistaActual = 'tabla'" title="Vista Tabla">
                            <i class="fa fa-list"></i>
                        </button>
                    </div>
                    <button v-if="!userLimited" type="button" @click="abrirModal('crear')" class="j2b-btn j2b-btn-primary">
                        <i class="fa fa-plus"></i> Nueva Tarea
                    </button>
                </div>
            </div>
            <div class="j2b-card-body">
                <!-- Filtros y búsqueda -->
                <div class="row mb-4">
                    <div class="col-md-10">
                        <div class="d-flex gap-2 flex-wrap">
                            <select class="j2b-select" v-model="filtroStatus" style="width: 140px;">
                                <option value="TODOS">TODOS</option>
                                <option value="NUEVO">NUEVOS</option>
                                <option value="PENDIENTE">PENDIENTES</option>
                                <option value="ATENDIDO">ATENDIDOS</option>
                            </select>
                            <select class="j2b-select" v-model="filtroOrdenar" style="width: 150px;">
                                <option value="ID_DESC">Mas recientes</option>
                                <option value="ID_ASC">Mas antiguos</option>
                                <option value="PRD_DESC">Mayor prioridad</option>
                                <option value="PRD_ASC">Menor prioridad</option>
                            </select>
                            <div class="d-flex" style="flex: 1; min-width: 200px;">
                                <input type="text" v-model="buscar" class="j2b-input" placeholder="Buscar por titulo, descripcion o ID..." @keyup.enter="loadTasks(1)" style="border-radius: var(--j2b-radius-md) 0 0 var(--j2b-radius-md);">
                                <button @click="loadTasks(1)" class="j2b-btn j2b-btn-primary" style="border-radius: 0 var(--j2b-radius-md) var(--j2b-radius-md) 0;">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA CARDS -->
                <div class="row" v-if="vistaActual === 'cards'">
                    <div class="col-md-4 col-lg-3 mb-4" v-for="task in arrayTasks" :key="task.id">
                        <div class="card task-card h-100" :class="{'inactive-card': !task.active}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="task-id">#{{ task.folio || task.id }}</span>
                                    <span class="task-status-badge ml-2">{{ task.status }}</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-dark dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" :href="'/admin/tasks/detail/' + task.id">
                                            <i class="fa fa-eye text-info"></i> Ver Detalle
                                        </a></li>
                                        <li v-if="!userLimited"><a class="dropdown-item" href="#" @click.prevent="abrirModal('editar', task)">
                                            <i class="fa fa-edit text-primary"></i> Editar
                                        </a></li>
                                        <li v-if="!userLimited"><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModal('estatus', task)">
                                            <i class="fa fa-exchange text-warning"></i> Cambiar Estatus
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModal('resena', task)">
                                            <i class="fa fa-comment text-success"></i> Agregar Reseña
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <template v-if="task.active">
                                            <li><a class="dropdown-item" href="#" @click.prevent="desactivarTarea(task.id)">
                                                <i class="fa fa-toggle-off text-danger"></i> Desactivar
                                            </a></li>
                                        </template>
                                        <template v-else>
                                            <li><a class="dropdown-item" href="#" @click.prevent="activarTarea(task.id)">
                                                <i class="fa fa-toggle-on text-success"></i> Activar
                                            </a></li>
                                        </template>
                                        <li v-if="!userLimited"><hr class="dropdown-divider"></li>
                                        <li v-if="!userLimited"><a class="dropdown-item text-danger" href="#" @click.prevent="confirmarEliminar(task)">
                                            <i class="fa fa-trash"></i> Eliminar
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body" @click="irAlDetalle(task.id)" style="cursor: pointer;">
                                <h5 class="card-title task-name">
                                    <i class="fa fa-clipboard text-primary"></i>
                                    {{ task.title }}
                                </h5>
                                <div class="task-info">
                                    <div class="info-item">
                                        <span v-if="task.active" class="j2b-badge j2b-badge-success">Activo</span>
                                        <span v-else class="j2b-badge j2b-badge-danger">Inactivo</span>
                                        <span v-if="task.origin === 'client'" class="j2b-badge j2b-badge-warning ml-1">
                                            <i class="fa fa-user"></i> Cliente
                                        </span>
                                        <span class="j2b-badge j2b-badge-outline ml-1">P{{ task.priority }}</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fa fa-user-circle text-muted"></i>
                                        <span>{{ task.assigned_user ? task.assigned_user.name : 'Sin asignar' }}</span>
                                    </div>
                                    <div class="info-item" v-if="task.client">
                                        <i class="fa fa-building text-muted"></i>
                                        <span>{{ task.client.name }}</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fa fa-calendar text-muted"></i>
                                        <span>{{ formatDate(task.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <i class="fa fa-hashtag"></i>
                                    Tarea #{{ task.folio || task.id }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA TABLA -->
                <div class="j2b-table-responsive" v-if="vistaActual === 'tabla'">
                    <table class="j2b-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Título</th>
                                <th>Estatus</th>
                                <th>Prioridad</th>
                                <th>Asignado</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="task in arrayTasks" :key="task.id" :class="{'table-secondary': !task.active}">
                                <td><strong>{{ task.folio || task.id }}</strong></td>
                                <td>
                                    <a :href="'/admin/tasks/detail/' + task.id" class="text-dark">
                                        {{ task.title }}
                                    </a>
                                    <span v-if="task.origin === 'client'" class="j2b-badge j2b-badge-warning ml-1">
                                        <i class="fa fa-user"></i>
                                    </span>
                                </td>
                                <td><span class="badge" :class="getBadgeClass(task.status)">{{ task.status }}</span></td>
                                <td><span class="j2b-badge j2b-badge-outline">P{{ task.priority }}</span></td>
                                <td>{{ task.assigned_user ? task.assigned_user.name : '-' }}</td>
                                <td>{{ task.client ? task.client.name : '-' }}</td>
                                <td>{{ formatDate(task.created_at) }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a class="btn btn-info btn-sm" :href="'/admin/tasks/detail/' + task.id" title="Ver">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <button v-if="!userLimited" class="btn btn-primary btn-sm" @click="abrirModal('editar', task)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" @click="abrirModal('estatus', task)" title="Estatus">
                                            <i class="fa fa-exchange"></i>
                                        </button>
                                        <button v-if="!userLimited" class="btn btn-danger btn-sm" @click="confirmarEliminar(task)" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mensaje si no hay tareas -->
                <div v-if="arrayTasks.length === 0 && !loading" class="text-center py-5">
                    <i class="fa fa-tasks fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                    <p style="color: var(--j2b-gray-500);">No se encontraron tareas con los criterios de busqueda.</p>
                </div>

                <!-- Paginacion -->
                <div v-if="pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        <small style="color: var(--j2b-gray-500);">
                            Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} registros
                        </small>
                    </div>
                    <nav>
                        <ul class="pagination mb-0" style="gap: 4px; list-style: none; display: flex;">
                            <li>
                                <a class="j2b-btn j2b-btn-sm j2b-btn-outline" href="#"
                                   @click.prevent="cambiarPagina(pagination.current_page - 1)"
                                   :style="pagination.current_page <= 1 ? 'opacity: 0.5; pointer-events: none;' : ''">
                                    <i class="fa fa-chevron-left"></i>
                                </a>
                            </li>
                            <li v-for="page in pagesNumber" :key="page">
                                <a class="j2b-btn j2b-btn-sm"
                                   :class="page == pagination.current_page ? 'j2b-btn-primary' : 'j2b-btn-outline'"
                                   href="#" @click.prevent="cambiarPagina(page)">
                                    {{ page }}
                                </a>
                            </li>
                            <li>
                                <a class="j2b-btn j2b-btn-sm j2b-btn-outline" href="#"
                                   @click.prevent="cambiarPagina(pagination.current_page + 1)"
                                   :style="pagination.current_page >= pagination.last_page ? 'opacity: 0.5; pointer-events: none;' : ''">
                                    <i class="fa fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalEditar}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title"><i class="fa fa-clipboard" style="color: var(--j2b-primary);"></i> {{ modoEdicion ? 'Editar Tarea #' + formTask.id : 'Nueva Tarea' }}</h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <div v-if="errorForm" class="alert alert-danger">
                        <div v-for="error in erroresForm" :key="error">{{ error }}</div>
                    </div>
                    <form @submit.prevent="guardarTarea">
                        <p><em><strong class="text text-danger">* Campos obligatorios</strong></em></p>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right"><strong class="text text-danger">*</strong> Título</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" v-model="formTask.title" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right"><strong class="text text-danger">*</strong> Prioridad</label>
                            <div class="col-md-3">
                                <select class="form-control" v-model="formTask.priority" required>
                                    <option value="1">1 - Muy baja</option>
                                    <option value="2">2 - Baja</option>
                                    <option value="3">3 - Media</option>
                                    <option value="4">4 - Alta</option>
                                    <option value="5">5 - Muy alta</option>
                                </select>
                            </div>
                            <label class="col-md-2 col-form-label text-md-right">Expiración</label>
                            <div class="col-md-4">
                                <input type="date" class="form-control" v-model="formTask.expiration">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Cliente</label>
                            <div class="col-md-9 position-relative">
                                <!-- Cliente seleccionado -->
                                <div v-if="clienteSeleccionadoNombre" class="input-group">
                                    <input type="text" class="form-control bg-light" :value="clienteSeleccionadoNombre" readonly>
                                    <button class="btn btn-outline-secondary" type="button" @click="limpiarCliente">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <!-- Buscador -->
                                <div v-else>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="buscarCliente"
                                        @input="buscarClientes"
                                        @focus="mostrarDropdownClientes = true"
                                        placeholder="Buscar cliente por nombre, email o empresa..."
                                    >
                                    <div v-if="mostrarDropdownClientes && (clientes.length > 0 || buscarCliente)" class="dropdown-menu show w-100" style="max-height: 200px; overflow-y: auto;">
                                        <a class="dropdown-item" href="#" @click.prevent="seleccionarCliente(null)">
                                            <em class="text-muted">Sin cliente</em>
                                        </a>
                                        <a
                                            v-for="client in clientes"
                                            :key="client.id"
                                            class="dropdown-item"
                                            href="#"
                                            @click.prevent="seleccionarCliente(client)"
                                        >
                                            {{ client.name }} <small class="text-muted" v-if="client.company">({{ client.company }})</small>
                                        </a>
                                        <div v-if="buscandoClientes" class="dropdown-item text-center text-muted">
                                            <i class="fa fa-spinner fa-spin me-1"></i>Buscando...
                                        </div>
                                        <div v-if="!buscandoClientes && clientes.length === 0 && buscarCliente.length >= 2" class="dropdown-item text-muted">
                                            Sin resultados
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Descripción</label>
                            <div class="col-md-9">
                                <textarea class="form-control" v-model="formTask.description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Solución</label>
                            <div class="col-md-9">
                                <textarea class="form-control" v-model="formTask.solution" rows="2"></textarea>
                            </div>
                        </div>
                        <!-- Campos extra dinámicos -->
                        <div v-if="extraFields.length > 0" class="mt-3 pt-3" style="border-top: 1px solid #eee;">
                            <p class="text-muted mb-2"><i class="fa fa-plus-square me-1"></i> <strong>Información Adicional</strong></p>
                            <div class="row">
                                <div class="col-md-6 mb-2" v-for="field in extraFields" :key="field.id">
                                    <label class="form-label small text-muted mb-0">{{ field.field_name }}</label>
                                    <input type="text" class="form-control form-control-sm" v-model="fieldValueMap[field.field_name]" :placeholder="field.field_name">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="guardarTarea" :disabled="guardando">
                        <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                        <i class="fa fa-save" v-else></i>
                        {{ guardando ? 'Guardando...' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Estatus -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalEstatus}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title"><i class="fa fa-exchange" style="color: var(--j2b-primary);"></i> Cambiar Estatus</h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <p>Tarea: <strong>{{ taskSeleccionada.title }}</strong></p>
                    <p>Estatus actual: <span class="j2b-badge" :class="getBadgeClass(taskSeleccionada.status)">{{ taskSeleccionada.status }}</span></p>
                    <div class="j2b-form-group">
                        <label class="j2b-label">Nuevo estatus:</label>
                        <select class="j2b-select" v-model="nuevoEstatus">
                            <option value="NUEVO">NUEVO</option>
                            <option value="PENDIENTE">PENDIENTE</option>
                            <option value="ATENDIDO">ATENDIDO</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="cambiarEstatus">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Resena -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalResena}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title"><i class="fa fa-comment" style="color: var(--j2b-primary);"></i> Agregar Resena</h5>
                    <button type="button" class="j2b-modal-close" @click="cerrarModal()"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <p>Tarea: <strong>{{ taskSeleccionada.title }}</strong></p>
                    <div class="j2b-form-group">
                        <label class="j2b-label">Resena:</label>
                        <textarea class="j2b-input" v-model="nuevaResena" rows="4" placeholder="Escribe la resena..."></textarea>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="guardarResena">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales de Producto, Cantidades e Imágenes movidos a TaskDetailComponent -->

</div>
</template>

<script>
export default {
    props: ['shop', 'userLimited'],
    data() {
        return {
            arrayTasks: [],
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
            filtroStatus: 'TODOS',
            filtroOrdenar: 'ID_DESC',
            numStatus: {},

            // Modales
            modalEditar: false,
            modalEstatus: false,
            modalResena: false,

            taskSeleccionada: {},
            modoEdicion: false,
            formTask: {
                id: null,
                title: '',
                description: '',
                solution: '',
                priority: 3,
                expiration: '',
                client_id: ''
            },

            // Listas
            clientes: [],

            // Búsqueda de clientes
            buscarCliente: '',
            buscandoClientes: false,
            mostrarDropdownClientes: false,
            clienteSeleccionadoNombre: '',
            buscarClienteTimeout: null,

            // Estados
            nuevoEstatus: '',
            nuevaResena: '',

            // Campos extra
            extraFields: [],
            fieldValueMap: {},

            // UI
            guardando: false,
            errorForm: false,
            erroresForm: [],
            vistaActual: localStorage.getItem('admin_vista') || 'cards', // 'cards' o 'tabla'

        }
    },
    watch: {
        vistaActual(newVal) {
            localStorage.setItem('admin_vista', newVal);
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
        this.loadTasks(1);
        this.loadNumStatus();
        this.loadClientes();
        this.loadExtraFields();
    },
    methods: {
        loadTasks(page) {
            let me = this;
            me.loading = true;
            var url = `/admin/tasks/get?page=${page}&buscar=${me.buscar}&filtro_status=${me.filtroStatus}&filtro_ordenar=${me.filtroOrdenar}`;
            axios.get(url).then(function(response) {
                var respuesta = response.data;
                me.arrayTasks = respuesta.data;
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

        loadNumStatus() {
            let me = this;
            axios.get('/admin/tasks/get-num-status').then(function(response) {
                me.numStatus = response.data;
            }).catch(function(error) {
                console.log(error);
            });
        },

        loadClientes() {
            // Se mantiene para carga inicial vacía
            // Los clientes se buscan dinámicamente con buscarClientes()
        },

        loadExtraFields() {
            axios.get('/admin/tasks/extra-fields').then(response => {
                if (response.data.ok) {
                    this.extraFields = response.data.extra_fields;
                }
            }).catch(() => {});
        },

        buscarClientes() {
            clearTimeout(this.buscarClienteTimeout);
            if (this.buscarCliente.length < 2) {
                this.clientes = [];
                return;
            }
            this.buscandoClientes = true;
            this.buscarClienteTimeout = setTimeout(() => {
                axios.get('/admin/tasks/clients', { params: { q: this.buscarCliente } })
                    .then(response => {
                        if (response.data.ok) {
                            this.clientes = response.data.clients;
                        }
                    })
                    .catch(error => console.error(error))
                    .finally(() => { this.buscandoClientes = false; });
            }, 300);
        },

        seleccionarCliente(client) {
            if (client) {
                this.formTask.client_id = client.id;
                this.clienteSeleccionadoNombre = client.name + (client.company ? ' (' + client.company + ')' : '');
            } else {
                this.formTask.client_id = '';
                this.clienteSeleccionadoNombre = '';
            }
            this.mostrarDropdownClientes = false;
            this.buscarCliente = '';
            this.clientes = [];
        },

        limpiarCliente() {
            this.formTask.client_id = '';
            this.clienteSeleccionadoNombre = '';
            this.buscarCliente = '';
            this.clientes = [];
        },

        cambiarPagina(page) {
            let me = this;
            me.pagination.current_page = page;
            me.loadTasks(page);
        },

        irAlDetalle(taskId) {
            window.location.href = '/admin/tasks/detail/' + taskId;
        },

        // Modales
        abrirModal(tipo, task = null) {
            this.cerrarModal();

            if (tipo === 'crear') {
                this.modoEdicion = false;
                this.formTask = {
                    id: null,
                    title: '',
                    description: '',
                    solution: '',
                    priority: 3,
                    expiration: '',
                    client_id: ''
                };
                this.limpiarCliente();
                this.fieldValueMap = {};
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            } else if (tipo === 'editar' && task) {
                this.modoEdicion = true;
                this.formTask = {
                    id: task.id,
                    title: task.title,
                    description: task.description || '',
                    solution: task.solution || '',
                    priority: task.priority,
                    expiration: task.expiration || '',
                    client_id: task.client_id || ''
                };
                // Pre-cargar nombre del cliente si existe
                if (task.client) {
                    this.clienteSeleccionadoNombre = task.client.name + (task.client.company ? ' (' + task.client.company + ')' : '');
                } else {
                    this.clienteSeleccionadoNombre = '';
                }
                this.buscarCliente = '';
                this.clientes = [];
                // Cargar valores de campos extra existentes
                this.fieldValueMap = {};
                if (task.info_extra && task.info_extra.length > 0) {
                    task.info_extra.forEach(ie => {
                        this.fieldValueMap[ie.field_name] = ie.value;
                    });
                }
                this.errorForm = false;
                this.erroresForm = [];
                this.modalEditar = true;
            } else if (tipo === 'estatus' && task) {
                this.taskSeleccionada = { ...task };
                this.nuevoEstatus = task.status;
                this.modalEstatus = true;
            } else if (tipo === 'resena' && task) {
                this.taskSeleccionada = { ...task };
                this.nuevaResena = task.review || '';
                this.modalResena = true;
            }
        },

        cerrarModal() {
            this.modalEditar = false;
            this.modalEstatus = false;
            this.modalResena = false;
            this.mostrarDropdownClientes = false;
        },

        // CRUD
        guardarTarea() {
            let me = this;
            me.guardando = true;
            me.errorForm = false;
            me.erroresForm = [];

            let url = me.modoEdicion ? '/admin/tasks/update' : '/admin/tasks/store';
            let method = me.modoEdicion ? axios.put : axios.post;

            let payload = { ...me.formTask };
            if (me.extraFields.length > 0) {
                payload.info_extra = JSON.stringify(me.fieldValueMap);
            }

            method(url, payload).then(function(response) {
                if (response.data.ok) {
                    me.cerrarModal();
                    me.loadTasks(me.pagination.current_page || 1);
                    me.loadNumStatus();
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                me.errorForm = true;
                if (error.response && error.response.data.errors) {
                    me.erroresForm = Object.values(error.response.data.errors).flat();
                } else {
                    me.erroresForm = ['Error al guardar la tarea'];
                }
            }).finally(function() {
                me.guardando = false;
            });
        },

        cambiarEstatus() {
            let me = this;
            axios.put('/admin/tasks/update-status', {
                id: me.taskSeleccionada.id,
                status: me.nuevoEstatus
            }).then(function(response) {
                if (response.data.ok) {
                    me.cerrarModal();
                    me.loadTasks(me.pagination.current_page || 1);
                    me.loadNumStatus();
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al cambiar estatus', 'error');
            });
        },

        guardarResena() {
            let me = this;
            axios.put('/admin/tasks/update-review', {
                id: me.taskSeleccionada.id,
                review: me.nuevaResena
            }).then(function(response) {
                if (response.data.ok) {
                    me.cerrarModal();
                    me.loadTasks(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al guardar reseña', 'error');
            });
        },

        confirmarEliminar(task) {
            let me = this;
            Swal.fire({
                title: '¿Eliminar tarea?',
                text: `#${task.folio || task.id} - ${task.title}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    me.eliminarTarea(task.id);
                }
            });
        },

        eliminarTarea(id) {
            let me = this;
            axios.delete(`/admin/tasks/${id}`).then(function(response) {
                if (response.data.ok) {
                    me.loadTasks(me.pagination.current_page || 1);
                    me.loadNumStatus();
                    Swal.fire('Eliminado', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al eliminar tarea', 'error');
            });
        },

        activarTarea(taskId) {
            let me = this;
            Swal.fire({
                title: '¿Activar tarea?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, activar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.put(`/admin/tasks/${taskId}/activate`).then(function(response) {
                        if (response.data.ok) {
                            me.loadTasks(me.pagination.current_page || 1);
                            Swal.fire('Activada', 'Tarea activada correctamente', 'success');
                        }
                    }).catch(function(error) {
                        Swal.fire('Error', 'Error al activar tarea', 'error');
                    });
                }
            });
        },

        desactivarTarea(taskId) {
            let me = this;
            Swal.fire({
                title: '¿Desactivar tarea?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.put(`/admin/tasks/${taskId}/deactivate`).then(function(response) {
                        if (response.data.ok) {
                            me.loadTasks(me.pagination.current_page || 1);
                            Swal.fire('Desactivada', 'Tarea desactivada correctamente', 'success');
                        }
                    }).catch(function(error) {
                        Swal.fire('Error', 'Error al desactivar tarea', 'error');
                    });
                }
            });
        },

        // Helpers
        getBadgeClass(status) {
            switch (status) {
                case 'NUEVO': return 'j2b-badge-success';
                case 'PENDIENTE': return 'j2b-badge-warning';
                case 'ATENDIDO': return 'j2b-badge-info';
                default: return 'j2b-badge-outline';
            }
        },

        getImageUrl(path) {
            return `/storage/${path}`;
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('es-MX');
        },

        formatDateTime(date) {
            if (!date) return '';
            return new Date(date).toLocaleString('es-MX');
        },

    }
}
</script>

<style>
    .modal-content{
        width: 100% !important;
        position: absolute !important;
    }
    .mostrar{
        display: block !important;
        opacity: 1 !important;
        position: fixed !important;
        background-color: rgba(26, 26, 46, 0.8) !important;
        overflow-y: auto;
        z-index: 1050;
    }

    .div-error{
        display: flex;
        justify-content: center;
    }

    /* Badge de status dentro del header del card (fondo blanco para contraste) */
    .task-status-badge {
        background: rgba(255, 255, 255, 0.9);
        color: var(--j2b-dark);
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: var(--j2b-radius-sm);
    }

    /* Estilos para Cards de Tareas - igual que Clientes */
    .task-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .task-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    .task-card.inactive-card {
        opacity: 0.7;
        background-color: #f8f9fa;
    }

    .task-card .card-header {
        background: linear-gradient(135deg, #00F5A0 0%, #00D9F5 100%);
        color: #0D1117;
        border: none;
        padding: 1rem;
    }

    .task-card.inactive-card .card-header {
        background: linear-gradient(135deg, #868e96 0%, #6c757d 100%);
        color: white;
    }

    .task-id {
        font-weight: bold;
        font-size: 0.9rem;
    }

    .task-name {
        color: #2c3e50;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .task-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #666;
    }

    .info-item i {
        width: 20px;
        text-align: center;
    }

    .task-card .card-footer {
        background: transparent;
        border-top: 1px solid #eee;
        padding: 0.75rem 1rem;
    }

</style>
