<template>
<div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fa fa-handshake-o"></i> Proveedores de {{ shop.name }}
                    <span class="badge bg-secondary ms-2">{{ pagination.total || 0 }} encontrados</span>
                </span>
                <div>
                    <!-- Toggle Vista -->
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm" :class="vistaActual === 'cards' ? 'btn-secondary' : 'btn-outline-secondary'" @click="vistaActual = 'cards'" title="Vista Cards">
                            <i class="fa fa-th-large"></i>
                        </button>
                        <button type="button" class="btn btn-sm" :class="vistaActual === 'tabla' ? 'btn-secondary' : 'btn-outline-secondary'" @click="vistaActual = 'tabla'" title="Vista Tabla">
                            <i class="fa fa-list"></i>
                        </button>
                    </div>
                    <button v-if="!isLimitedUser" type="button" @click="abrirModalCrear()" class="btn btn-primary">
                        <i class="fa fa-plus"></i>&nbsp;Nuevo Proveedor
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="form-group row">
                    <div class="col-md-8">
                        <div class="input-group">
                            <select class="form-control col-md-2" v-model="criterio">
                                <option value="name">Nombre</option>
                                <option value="email">Email</option>
                                <option value="movil">Teléfono</option>
                                <option value="company">Empresa</option>
                            </select>
                            <input type="text" v-model="buscar" class="form-control" placeholder="Texto a buscar" @keyup.enter="loadSuppliers(1)">
                            <select class="form-control col-md-2" v-model="estatus">
                                <option value="">TODOS</option>
                                <option value="active">ACTIVOS</option>
                                <option value="inactive">INACTIVOS</option>
                            </select>
                            <button type="submit" @click="loadSuppliers(1)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>

                <!-- VISTA CARDS -->
                <div class="row mt-3" v-if="vistaActual === 'cards'">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4" v-for="supplier in arraySuppliers" :key="supplier.id">
                        <div class="card supplier-card h-100" :class="{'inactive-card': !supplier.active}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="supplier-id">#{{ supplier.id }}</span>
                                    <span v-if="supplier.active" class="badge badge-success ml-2">Activo</span>
                                    <span v-else class="badge badge-danger ml-2">Inactivo</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-dark dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModalDetalle(supplier)">
                                            <i class="fa fa-eye text-primary"></i> Ver Detalle
                                        </a></li>
                                        <li v-if="!isLimitedUser"><a class="dropdown-item" href="#" @click.prevent="abrirModalEditar(supplier)">
                                            <i class="fa fa-edit text-info"></i> Editar Proveedor
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <template v-if="!isLimitedUser">
                                            <li v-if="supplier.active"><a class="dropdown-item" href="#" @click.prevent="actualizarAInactivo(supplier.id)">
                                                <i class="fa fa-toggle-off text-danger"></i> Desactivar
                                            </a></li>
                                            <li v-else><a class="dropdown-item" href="#" @click.prevent="actualizarAActivo(supplier.id)">
                                                <i class="fa fa-toggle-on text-success"></i> Activar
                                            </a></li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body" @click="abrirModalDetalle(supplier)" style="cursor: pointer;">
                                <h5 class="card-title supplier-name">
                                    <i class="fa fa-handshake-o text-primary"></i>
                                    {{ supplier.name }}
                                </h5>
                                <div class="supplier-info">
                                    <div class="info-item" v-if="supplier.company">
                                        <i class="fa fa-building text-muted"></i>
                                        <span>{{ supplier.company }}</span>
                                    </div>
                                    <div class="info-item" v-if="supplier.email">
                                        <i class="fa fa-envelope text-muted"></i>
                                        <span>{{ supplier.email }}</span>
                                    </div>
                                    <div class="info-item" v-if="supplier.movil">
                                        <i class="fa fa-phone text-muted"></i>
                                        <span>{{ supplier.movil }}</span>
                                    </div>
                                    <div class="info-item" v-if="supplier.address">
                                        <i class="fa fa-map-marker text-muted"></i>
                                        <span>{{ truncateText(supplier.address, 50) }}</span>
                                    </div>
                                    <div class="info-item" v-if="supplier.bank_number_main">
                                        <i class="fa fa-credit-card text-muted"></i>
                                        <span>{{ supplier.bank_number_main }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <i class="fa fa-clock-o"></i>
                                    Proveedor #{{ supplier.id }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA TABLA -->
                <div class="table-responsive mt-3" v-if="vistaActual === 'tabla'">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Empresa</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="supplier in arraySuppliers" :key="supplier.id" :class="{'table-secondary': !supplier.active}">
                                <td><strong>{{ supplier.id }}</strong></td>
                                <td>{{ supplier.name }}</td>
                                <td>{{ supplier.company || '-' }}</td>
                                <td>{{ supplier.email || '-' }}</td>
                                <td>{{ supplier.movil || '-' }}</td>
                                <td>
                                    <span v-if="supplier.active" class="badge badge-success">Activo</span>
                                    <span v-else class="badge badge-danger">Inactivo</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-primary btn-sm" @click="abrirModalDetalle(supplier)" title="Ver Detalle">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button v-if="!isLimitedUser" class="btn btn-info btn-sm" @click="abrirModalEditar(supplier)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <template v-if="!isLimitedUser">
                                            <button v-if="supplier.active" class="btn btn-danger btn-sm" @click="actualizarAInactivo(supplier.id)" title="Desactivar">
                                                <i class="fa fa-toggle-off"></i>
                                            </button>
                                            <button v-else class="btn btn-success btn-sm" @click="actualizarAActivo(supplier.id)" title="Activar">
                                                <i class="fa fa-toggle-on"></i>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-if="arraySuppliers.length === 0" class="text-center py-5">
                    <i class="fa fa-handshake-o fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron proveedores con los criterios de búsqueda.</p>
                </div>

                <!-- Paginación -->
                <nav v-if="pagination.last_page > 1">
                    <ul class="pagination">
                        <li class="page-item" v-if="pagination.current_page > 1">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page-1)">Ant</a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page == pagination.current_page ? 'active' : '']">
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

    <!-- Modal Ver Detalle -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalDetalle}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title"><i class="fa fa-handshake-o"></i> Detalle del Proveedor</h4>
                    <button type="button" class="close text-white" @click="cerrarModalDetalle()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" v-if="supplierDetalle">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fa fa-info-circle"></i> Información Básica
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <td><strong>ID:</strong></td>
                                                <td>#{{ supplierDetalle.id }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Nombre:</strong></td>
                                                <td>{{ supplierDetalle.name }}</td>
                                            </tr>
                                            <tr v-if="supplierDetalle.company">
                                                <td><strong>Empresa:</strong></td>
                                                <td>{{ supplierDetalle.company }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Estado:</strong></td>
                                                <td>
                                                    <span v-if="supplierDetalle.active" class="badge badge-success">Activo</span>
                                                    <span v-else class="badge badge-danger">Inactivo</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fa fa-phone"></i> Contacto
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr v-if="supplierDetalle.email">
                                                <td><strong>Email:</strong></td>
                                                <td><a :href="'mailto:' + supplierDetalle.email">{{ supplierDetalle.email }}</a></td>
                                            </tr>
                                            <tr v-if="supplierDetalle.movil">
                                                <td><strong>Móvil:</strong></td>
                                                <td><a :href="'tel:' + supplierDetalle.movil">{{ supplierDetalle.movil }}</a></td>
                                            </tr>
                                            <tr v-if="supplierDetalle.phone">
                                                <td><strong>Teléfono:</strong></td>
                                                <td><a :href="'tel:' + supplierDetalle.phone">{{ supplierDetalle.phone }}</a></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p v-if="!supplierDetalle.email && !supplierDetalle.movil && !supplierDetalle.phone" class="text-muted mb-0">
                                        Sin información de contacto
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fa fa-map-marker"></i> Dirección
                                </div>
                                <div class="card-body">
                                    <p v-if="supplierDetalle.address">{{ supplierDetalle.address }}</p>
                                    <p v-if="supplierDetalle.number_out || supplierDetalle.number_int">
                                        <strong>Núm:</strong>
                                        {{ supplierDetalle.number_out || '' }}
                                        <span v-if="supplierDetalle.number_int"> Int. {{ supplierDetalle.number_int }}</span>
                                    </p>
                                    <p v-if="supplierDetalle.district || supplierDetalle.city || supplierDetalle.state">
                                        {{ [supplierDetalle.district, supplierDetalle.city, supplierDetalle.state].filter(Boolean).join(', ') }}
                                    </p>
                                    <p v-if="supplierDetalle.zip_code"><strong>C.P.:</strong> {{ supplierDetalle.zip_code }}</p>
                                    <p v-if="!supplierDetalle.address && !supplierDetalle.city && !supplierDetalle.state" class="text-muted mb-0">Sin dirección registrada</p>
                                </div>
                            </div>

                            <div class="card mb-3" v-if="supplierDetalle.bank_number_main || supplierDetalle.bank_number_secondary">
                                <div class="card-header bg-light">
                                    <i class="fa fa-credit-card"></i> Datos Bancarios
                                </div>
                                <div class="card-body">
                                    <p v-if="supplierDetalle.bank_number_main"><strong>Principal:</strong> {{ supplierDetalle.bank_number_main }}</p>
                                    <p v-if="supplierDetalle.bank_number_secondary" class="mb-0"><strong>Secundaria:</strong> {{ supplierDetalle.bank_number_secondary }}</p>
                                </div>
                            </div>

                            <div class="card mb-3" v-if="supplierDetalle.observations || supplierDetalle.detail || supplierDetalle.reference">
                                <div class="card-header bg-light">
                                    <i class="fa fa-sticky-note"></i> Notas
                                </div>
                                <div class="card-body">
                                    <div v-if="supplierDetalle.reference">
                                        <strong>Referencia:</strong>
                                        <p>{{ supplierDetalle.reference }}</p>
                                    </div>
                                    <div v-if="supplierDetalle.detail">
                                        <strong>Detalle:</strong>
                                        <p>{{ supplierDetalle.detail }}</p>
                                    </div>
                                    <div v-if="supplierDetalle.observations">
                                        <strong>Observaciones:</strong>
                                        <p class="mb-0">{{ supplierDetalle.observations }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalDetalle()">Cerrar</button>
                    <button v-if="!isLimitedUser && supplierDetalle" type="button" class="btn btn-primary" @click="cerrarModalDetalle(); abrirModalEditar(supplierDetalle)">
                        <i class="fa fa-edit"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modal}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-primary modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <i class="fa fa-handshake-o"></i>
                        {{ modoEdicion ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
                    </h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form v-on:submit.prevent class="form-horizontal">
                        <div v-show="errorForm" class="form-group row div-error">
                            <div class="container-fluid">
                                <div class="alert alert-danger">
                                    <div v-for="error in erroresForm" :key="error">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                        <p><em><strong class="text text-danger">* Campos obligatorios</strong></em></p>

                        <div class="form-group row">
                            <label class="col-md-4 col-form-label text-md-right"><strong class="text text-danger">*</strong> Nombre</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" v-model="form.name" placeholder="Nombre del contacto" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-4 col-form-label text-md-right">Empresa/Negocio</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" v-model="form.company" placeholder="Razón social o alias">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-4 col-form-label text-md-right">Email</label>
                            <div class="col-md-6">
                                <input type="email" class="form-control" v-model="form.email" placeholder="correo@ejemplo.com">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-4 col-form-label text-md-right">Teléfono Móvil</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" v-model="form.movil" placeholder="Número móvil">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-4 col-form-label text-md-right">Dirección</label>
                            <div class="col-md-6">
                                <textarea class="form-control" v-model="form.address" placeholder="Calle y referencias" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-4 col-form-label text-md-right">No. Cuenta/Banco</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" v-model="form.bank_number_main" placeholder="Cuenta principal">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-4 col-form-label text-md-right">Observaciones</label>
                            <div class="col-md-6">
                                <textarea class="form-control" v-model="form.observations" placeholder="Notas u observaciones" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Panel colapsable: Más datos -->
                        <div class="form-group row mt-3">
                            <div class="col-md-10 offset-md-1">
                                <button type="button" class="btn btn-link btn-sm text-decoration-none" @click="mostrarMasDatos = !mostrarMasDatos">
                                    <i class="fa" :class="mostrarMasDatos ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                    {{ mostrarMasDatos ? 'Ocultar datos adicionales' : 'Mostrar datos adicionales (dirección detallada, teléfono fijo, etc.)' }}
                                </button>
                            </div>
                        </div>

                        <div v-show="mostrarMasDatos">
                            <hr>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Teléfono Fijo</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.phone" placeholder="Teléfono fijo">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Núm. Exterior</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.number_out" placeholder="Ej: 123">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Núm. Interior</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.number_int" placeholder="Ej: A">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Colonia</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.district" placeholder="Colonia">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Ciudad</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.city" placeholder="Ciudad">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Estado</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.state" placeholder="Estado">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Código Postal</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.zip_code" placeholder="C.P.">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Referencia</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.reference" placeholder="Entre calles, puntos de referencia...">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Detalle</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.detail" placeholder="Detalle adicional">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-4 col-form-label text-md-right">Cuenta Secundaria</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="form.bank_number_secondary" placeholder="Cuenta secundaria">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cerrar</button>
                    <button type="button" v-if="!modoEdicion" class="btn btn-primary" @click="registrar()" :disabled="guardando">
                        <i class="fa" :class="guardando ? 'fa-spinner fa-spin' : 'fa-save'"></i> Guardar
                    </button>
                    <button type="button" v-if="modoEdicion" class="btn btn-primary" @click="actualizar()" :disabled="guardando">
                        <i class="fa" :class="guardando ? 'fa-spinner fa-spin' : 'fa-save'"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
const FORM_INICIAL = () => ({
    id: null,
    name: '',
    company: '',
    email: '',
    movil: '',
    phone: '',
    address: '',
    number_out: '',
    number_int: '',
    district: '',
    city: '',
    state: '',
    zip_code: '',
    reference: '',
    detail: '',
    observations: '',
    bank_number_main: '',
    bank_number_secondary: '',
});

export default {
    name: 'SuppliersComponent',
    props: {
        shop: { type: Object, required: true },
        isLimitedUser: { type: Boolean, default: false },
    },
    data() {
        return {
            arraySuppliers: [],
            pagination: { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 },
            buscar: '',
            criterio: 'name',
            estatus: 'active',
            vistaActual: 'cards',
            modal: false,
            modoEdicion: false,
            modalDetalle: false,
            supplierDetalle: null,
            mostrarMasDatos: false,
            guardando: false,
            errorForm: false,
            erroresForm: [],
            form: FORM_INICIAL(),
        };
    },
    computed: {
        pagesNumber() {
            if (!this.pagination.to) return [];
            let from = this.pagination.current_page - 3;
            if (from < 1) from = 1;
            let to = from + 6;
            if (to > this.pagination.last_page) to = this.pagination.last_page;
            const pages = [];
            while (from <= to) { pages.push(from); from++; }
            return pages;
        },
    },
    mounted() {
        this.loadSuppliers(1);
    },
    methods: {
        async loadSuppliers(page = 1) {
            try {
                const { data } = await axios.get('/admin/suppliers/get', {
                    params: {
                        page,
                        buscar: this.buscar,
                        criterio: this.criterio,
                        estatus: this.estatus,
                    },
                });
                this.arraySuppliers = data.suppliers.data || [];
                this.pagination = data.pagination;
            } catch (error) {
                console.error('Error al cargar proveedores:', error);
                Swal.fire('Error', 'No se pudieron cargar los proveedores.', 'error');
            }
        },
        cambiarPagina(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.loadSuppliers(page);
        },
        abrirModalCrear() {
            this.modoEdicion = false;
            this.form = FORM_INICIAL();
            this.errorForm = false;
            this.erroresForm = [];
            this.mostrarMasDatos = false;
            this.modal = true;
        },
        abrirModalEditar(supplier) {
            this.modoEdicion = true;
            this.form = { ...FORM_INICIAL(), ...supplier };
            this.errorForm = false;
            this.erroresForm = [];
            this.mostrarMasDatos = this.tieneDatosExtendidos(supplier);
            this.modal = true;
        },
        cerrarModal() {
            this.modal = false;
            this.modoEdicion = false;
            this.form = FORM_INICIAL();
        },
        abrirModalDetalle(supplier) {
            this.supplierDetalle = supplier;
            this.modalDetalle = true;
        },
        cerrarModalDetalle() {
            this.modalDetalle = false;
            this.supplierDetalle = null;
        },
        tieneDatosExtendidos(supplier) {
            const campos = ['phone', 'number_out', 'number_int', 'district', 'city', 'state', 'zip_code', 'reference', 'detail', 'bank_number_secondary'];
            return campos.some(c => supplier[c]);
        },
        async registrar() {
            if (!this.form.name || this.form.name.trim() === '') {
                this.mostrarErrores(['El nombre es obligatorio.']);
                return;
            }
            this.guardando = true;
            try {
                const { data } = await axios.post('/admin/suppliers/store', this.form);
                if (data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Creado',
                        text: data.message || 'Proveedor creado.',
                        timer: 1800,
                        showConfirmButton: false,
                    });
                    this.cerrarModal();
                    this.loadSuppliers(1);
                }
            } catch (error) {
                this.manejarError(error);
            } finally {
                this.guardando = false;
            }
        },
        async actualizar() {
            if (!this.form.name || this.form.name.trim() === '') {
                this.mostrarErrores(['El nombre es obligatorio.']);
                return;
            }
            this.guardando = true;
            try {
                const { data } = await axios.put('/admin/suppliers/update', this.form);
                if (data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: data.message || 'Proveedor actualizado.',
                        timer: 1800,
                        showConfirmButton: false,
                    });
                    this.cerrarModal();
                    this.loadSuppliers(this.pagination.current_page);
                }
            } catch (error) {
                this.manejarError(error);
            } finally {
                this.guardando = false;
            }
        },
        async actualizarAInactivo(id) {
            const ok = await this.confirmar('¿Desactivar este proveedor?', 'Podrás reactivarlo más tarde.');
            if (!ok) return;
            try {
                const { data } = await axios.put('/admin/suppliers/inactive', { id });
                if (data.ok) {
                    this.toastOk(data.message || 'Proveedor desactivado.');
                    this.loadSuppliers(this.pagination.current_page);
                }
            } catch (error) {
                this.manejarError(error);
            }
        },
        async actualizarAActivo(id) {
            try {
                const { data } = await axios.put('/admin/suppliers/active', { id });
                if (data.ok) {
                    this.toastOk(data.message || 'Proveedor activado.');
                    this.loadSuppliers(this.pagination.current_page);
                }
            } catch (error) {
                this.manejarError(error);
            }
        },
        async confirmar(title, text) {
            const result = await Swal.fire({
                icon: 'warning',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
            });
            return result.isConfirmed;
        },
        toastOk(message) {
            Swal.fire({
                icon: 'success',
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
            });
        },
        mostrarErrores(lista) {
            this.errorForm = true;
            this.erroresForm = lista;
        },
        manejarError(error) {
            const resp = error.response;
            if (resp && resp.status === 422 && resp.data.errors) {
                const msgs = [];
                Object.values(resp.data.errors).forEach(arr => arr.forEach(m => msgs.push(m)));
                this.mostrarErrores(msgs);
            } else if (resp && resp.status === 403) {
                Swal.fire('Acceso denegado', resp.data.message || 'No tienes permisos.', 'warning');
            } else {
                console.error(error);
                Swal.fire('Error', 'Ocurrió un error al procesar la solicitud.', 'error');
            }
        },
        truncateText(text, max) {
            if (!text) return '';
            return text.length > max ? text.substring(0, max) + '…' : text;
        },
    },
};
</script>

<style scoped>
.supplier-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    cursor: default;
}
.supplier-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.inactive-card {
    opacity: 0.6;
}
.supplier-id {
    font-weight: bold;
    color: #6c757d;
}
.supplier-name {
    font-size: 1.05rem;
    margin-bottom: 0.5rem;
}
.supplier-info .info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #495057;
    margin-bottom: 0.25rem;
    word-break: break-word;
}
.mostrar {
    display: block !important;
    opacity: 1 !important;
    position: fixed !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
    overflow-y: auto;
    z-index: 1050;
}
</style>
