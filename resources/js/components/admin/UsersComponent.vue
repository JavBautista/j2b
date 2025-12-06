<template>
<div>
    <div class="container-fluid">
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" :class="{active: tabActivo === 'admins'}" href="#" @click.prevent="cambiarTab('admins')">
                    <i class="fa fa-user-shield"></i> Administradores
                    <span class="badge bg-secondary ms-1">{{ numAdmins.total }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{active: tabActivo === 'colaboradores'}" href="#" @click.prevent="cambiarTab('colaboradores')">
                    <i class="fa fa-hard-hat"></i> Colaboradores
                    <span class="badge bg-secondary ms-1">{{ numCollabs.total }}</span>
                </a>
            </li>
        </ul>

        <!-- Resumen contadores -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h3>{{ currentCounters.total }}</h3>
                        <p class="mb-0">Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h3>{{ currentCounters.activos }}</h3>
                        <p class="mb-0">Activos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-secondary">
                    <div class="card-body text-center">
                        <h3>{{ currentCounters.bajas }}</h3>
                        <p class="mb-0">Dados de baja</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card principal -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fa fa-users"></i>
                    {{ tabActivo === 'admins' ? 'Administradores' : 'Colaboradores' }} de {{ shop.name }}
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
                    <button type="button" @click="abrirModalCrear()" class="btn btn-primary">
                        <i class="fa fa-plus"></i>&nbsp;Nuevo {{ tabActivo === 'admins' ? 'Admin' : 'Colaborador' }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros y búsqueda -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" v-model="buscar" class="form-control" placeholder="Buscar por nombre, email, teléfono..." @keyup.enter="buscarUsuarios()">
                            <button type="button" @click="buscarUsuarios()" class="btn btn-primary">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" v-model="filtroEstado" @change="buscarUsuarios()">
                            <option value="TODOS">Todos los estados</option>
                            <option value="ACTIVO">Solo activos</option>
                            <option value="BAJA">Solo dados de baja</option>
                        </select>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="text-center py-5">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p class="mt-2">Cargando...</p>
                </div>

                <!-- VISTA CARDS -->
                <div class="row" v-if="!loading && vistaActual === 'cards'">
                    <div class="col-md-4 col-lg-3 mb-4" v-for="user in currentList" :key="user.id">
                        <div class="card user-card h-100" :class="{'inactive-card': !user.active}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge" :class="user.active ? 'bg-success' : 'bg-danger'">
                                        {{ user.active ? 'ACTIVO' : 'BAJA' }}
                                    </span>
                                    <span v-if="tabActivo === 'admins' && user.user && user.user.limited" class="badge bg-warning ms-1">
                                        LIMITADO
                                    </span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-dark dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModalVer(user)">
                                            <i class="fa fa-eye text-info"></i> Ver Detalle
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModalEditar(user)">
                                            <i class="fa fa-edit text-primary"></i> Editar
                                        </a></li>
                                        <li v-if="tabActivo === 'admins'"><a class="dropdown-item" href="#" @click.prevent="confirmarToggleLimited(user)">
                                            <i class="fa fa-lock text-warning"></i>
                                            {{ user.user && user.user.limited ? 'Quitar límite' : 'Limitar privilegios' }}
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li v-if="user.active"><a class="dropdown-item" href="#" @click.prevent="confirmarDesactivar(user)">
                                            <i class="fa fa-ban text-danger"></i> Dar de baja
                                        </a></li>
                                        <li v-else><a class="dropdown-item" href="#" @click.prevent="confirmarActivar(user)">
                                            <i class="fa fa-check text-success"></i> Dar de alta
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body" @click="abrirModalVer(user)" style="cursor: pointer;">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar-circle me-3">
                                        {{ getInitials(user.name) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ user.name }}</h6>
                                        <small class="text-muted">{{ user.email }}</small>
                                    </div>
                                </div>
                                <div class="user-info">
                                    <div class="info-item" v-if="user.phone">
                                        <i class="fa fa-phone text-muted"></i>
                                        <span>{{ user.phone }}</span>
                                    </div>
                                    <div class="info-item" v-if="user.movil">
                                        <i class="fa fa-mobile text-muted"></i>
                                        <span>{{ user.movil }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <i class="fa fa-hashtag"></i>
                                    {{ tabActivo === 'admins' ? 'Admin' : 'Colaborador' }} #{{ user.id }}
                                </small>
                            </div>
                        </div>
                    </div>
                    <div v-if="currentList.length === 0" class="col-12 text-center py-5">
                        <i class="fa fa-users fa-3x text-muted"></i>
                        <p class="mt-2 text-muted">No hay {{ tabActivo === 'admins' ? 'administradores' : 'colaboradores' }} registrados</p>
                    </div>
                </div>

                <!-- VISTA TABLA -->
                <div class="table-responsive" v-if="!loading && vistaActual === 'tabla'">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Móvil</th>
                                <th>Estado</th>
                                <th v-if="tabActivo === 'admins'">Privilegios</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in currentList" :key="user.id">
                                <td>{{ user.id }}</td>
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>{{ user.movil || '-' }}</td>
                                <td>
                                    <span class="badge" :class="user.active ? 'bg-success' : 'bg-danger'">
                                        {{ user.active ? 'ACTIVO' : 'BAJA' }}
                                    </span>
                                </td>
                                <td v-if="tabActivo === 'admins'">
                                    <span v-if="user.user && user.user.limited" class="badge bg-warning">LIMITADO</span>
                                    <span v-else class="badge bg-info">COMPLETO</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" @click="abrirModalVer(user)" title="Ver">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" @click="abrirModalEditar(user)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button v-if="user.active" class="btn btn-outline-danger" @click="confirmarDesactivar(user)" title="Dar de baja">
                                            <i class="fa fa-ban"></i>
                                        </button>
                                        <button v-else class="btn btn-outline-success" @click="confirmarActivar(user)" title="Dar de alta">
                                            <i class="fa fa-check"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="currentList.length === 0">
                                <td :colspan="tabActivo === 'admins' ? 7 : 6" class="text-center py-4">
                                    No hay {{ tabActivo === 'admins' ? 'administradores' : 'colaboradores' }} registrados
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <nav v-if="currentPagination.last_page > 1" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item" :class="{disabled: currentPagination.current_page === 1}">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(currentPagination.current_page - 1)">Anterior</a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page" :class="{active: page === currentPagination.current_page}">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{disabled: currentPagination.current_page === currentPagination.last_page}">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(currentPagination.current_page + 1)">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- MODAL VER DETALLE -->
    <div class="modal fade" :class="{show: modalVer}" :style="{display: modalVer ? 'block' : 'none'}" tabindex="-1" v-if="modalVer">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-user"></i> Detalle de {{ tabActivo === 'admins' ? 'Administrador' : 'Colaborador' }}
                    </h5>
                    <button type="button" class="btn-close" @click="cerrarModales()"></button>
                </div>
                <div class="modal-body" v-if="usuarioSeleccionado">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Información Personal</h6>
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th width="40%">Nombre:</th>
                                        <td>{{ usuarioSeleccionado.name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td>
                                            {{ usuarioSeleccionado.email }}
                                            <button class="btn btn-sm btn-link" @click="copiarEmail(usuarioSeleccionado.email)" title="Copiar">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Teléfono:</th>
                                        <td>{{ usuarioSeleccionado.phone || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Móvil:</th>
                                        <td>{{ usuarioSeleccionado.movil || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Estado:</th>
                                        <td>
                                            <span class="badge" :class="usuarioSeleccionado.active ? 'bg-success' : 'bg-danger'">
                                                {{ usuarioSeleccionado.active ? 'ACTIVO' : 'BAJA' }}
                                            </span>
                                            <span v-if="tabActivo === 'admins' && usuarioSeleccionado.user && usuarioSeleccionado.user.limited" class="badge bg-warning ms-1">
                                                LIMITADO
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Dirección</h6>
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th width="40%">Calle:</th>
                                        <td>{{ usuarioSeleccionado.address || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Núm. Ext:</th>
                                        <td>{{ usuarioSeleccionado.number_out || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Núm. Int:</th>
                                        <td>{{ usuarioSeleccionado.number_int || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Colonia:</th>
                                        <td>{{ usuarioSeleccionado.district || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>CP:</th>
                                        <td>{{ usuarioSeleccionado.zip_code || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Ciudad:</th>
                                        <td>{{ usuarioSeleccionado.city || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Estado:</th>
                                        <td>{{ usuarioSeleccionado.state || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3" v-if="usuarioSeleccionado.reference || usuarioSeleccionado.detail || usuarioSeleccionado.observations">
                        <div class="col-12">
                            <h6 class="text-muted mb-3">Información Adicional</h6>
                            <table class="table table-sm">
                                <tbody>
                                    <tr v-if="usuarioSeleccionado.reference">
                                        <th width="20%">Referencia:</th>
                                        <td>{{ usuarioSeleccionado.reference }}</td>
                                    </tr>
                                    <tr v-if="usuarioSeleccionado.detail">
                                        <th>Detalle:</th>
                                        <td>{{ usuarioSeleccionado.detail }}</td>
                                    </tr>
                                    <tr v-if="usuarioSeleccionado.observations">
                                        <th>Observaciones:</th>
                                        <td>{{ usuarioSeleccionado.observations }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModales()">Cerrar</button>
                    <button type="button" class="btn btn-primary" @click="abrirModalEditar(usuarioSeleccionado)">
                        <i class="fa fa-edit"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" v-if="modalVer" @click="cerrarModales()"></div>

    <!-- MODAL CREAR -->
    <div class="modal fade" :class="{show: modalCrear}" :style="{display: modalCrear ? 'block' : 'none'}" tabindex="-1" v-if="modalCrear">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-user-plus"></i> Nuevo {{ tabActivo === 'admins' ? 'Administrador' : 'Colaborador' }}
                    </h5>
                    <button type="button" class="btn-close" @click="cerrarModales()"></button>
                </div>
                <div class="modal-body">
                    <!-- Errores -->
                    <div class="alert alert-danger" v-if="erroresForm.length > 0">
                        <ul class="mb-0">
                            <li v-for="error in erroresForm" :key="error">{{ error }}</li>
                        </ul>
                    </div>

                    <div class="row">
                        <!-- Credenciales -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Credenciales de Acceso</h6>
                            <div class="mb-3">
                                <label class="form-label">Usuario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="formCrear.username" @input="generarEmail()" placeholder="Ej: juan.perez">
                                <small class="text-muted">Se generará el email: {{ emailGenerado }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input :type="showPassword ? 'text' : 'password'" class="form-control" v-model="formCrear.password" placeholder="Mínimo 8 caracteres">
                                    <button class="btn btn-outline-secondary" type="button" @click="showPassword = !showPassword">
                                        <i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input :type="showConfirmPassword ? 'text' : 'password'" class="form-control" v-model="formCrear.confirmPassword" placeholder="Repetir contraseña">
                                    <button class="btn btn-outline-secondary" type="button" @click="showConfirmPassword = !showConfirmPassword">
                                        <i :class="showConfirmPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Datos personales -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Datos Personales</h6>
                            <div class="mb-3">
                                <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="formCrear.name" placeholder="Nombre completo">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" v-model="formCrear.phone" placeholder="Teléfono fijo">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Móvil</label>
                                <input type="text" class="form-control" v-model="formCrear.movil" placeholder="Teléfono móvil">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <!-- Dirección -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3">Dirección (Opcional)</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Calle</label>
                                <input type="text" class="form-control" v-model="formCrear.address" placeholder="Calle">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Núm. Exterior</label>
                                <input type="text" class="form-control" v-model="formCrear.number_out" placeholder="Núm. Ext">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Núm. Interior</label>
                                <input type="text" class="form-control" v-model="formCrear.number_int" placeholder="Núm. Int">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Colonia</label>
                                <input type="text" class="form-control" v-model="formCrear.district" placeholder="Colonia">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">CP</label>
                                <input type="text" class="form-control" v-model="formCrear.zip_code" placeholder="CP">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control" v-model="formCrear.city" placeholder="Ciudad">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <input type="text" class="form-control" v-model="formCrear.state" placeholder="Estado">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Referencia</label>
                                <input type="text" class="form-control" v-model="formCrear.reference" placeholder="Referencias para ubicar">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" v-model="formCrear.observations" rows="2" placeholder="Observaciones adicionales"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModales()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="crearUsuario()" :disabled="guardando">
                        <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                        <i class="fa fa-save" v-else></i>
                        Crear {{ tabActivo === 'admins' ? 'Administrador' : 'Colaborador' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" v-if="modalCrear" @click="cerrarModales()"></div>

    <!-- MODAL EDITAR -->
    <div class="modal fade" :class="{show: modalEditar}" :style="{display: modalEditar ? 'block' : 'none'}" tabindex="-1" v-if="modalEditar">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-edit"></i> Editar {{ tabActivo === 'admins' ? 'Administrador' : 'Colaborador' }}
                    </h5>
                    <button type="button" class="btn-close" @click="cerrarModales()"></button>
                </div>
                <div class="modal-body">
                    <!-- Errores -->
                    <div class="alert alert-danger" v-if="erroresForm.length > 0">
                        <ul class="mb-0">
                            <li v-for="error in erroresForm" :key="error">{{ error }}</li>
                        </ul>
                    </div>

                    <div class="row">
                        <!-- Info no editable -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Información (No editable)</h6>
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" :value="formEditar.name" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" :value="formEditar.email" disabled>
                            </div>
                        </div>

                        <!-- Datos editables -->
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Datos de Contacto</h6>
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" v-model="formEditar.phone" placeholder="Teléfono fijo">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Móvil</label>
                                <input type="text" class="form-control" v-model="formEditar.movil" placeholder="Teléfono móvil">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <!-- Dirección -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3">Dirección</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Calle</label>
                                <input type="text" class="form-control" v-model="formEditar.address" placeholder="Calle">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Núm. Exterior</label>
                                <input type="text" class="form-control" v-model="formEditar.number_out" placeholder="Núm. Ext">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Núm. Interior</label>
                                <input type="text" class="form-control" v-model="formEditar.number_int" placeholder="Núm. Int">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Colonia</label>
                                <input type="text" class="form-control" v-model="formEditar.district" placeholder="Colonia">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">CP</label>
                                <input type="text" class="form-control" v-model="formEditar.zip_code" placeholder="CP">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control" v-model="formEditar.city" placeholder="Ciudad">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <input type="text" class="form-control" v-model="formEditar.state" placeholder="Estado">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Referencia</label>
                                <input type="text" class="form-control" v-model="formEditar.reference" placeholder="Referencias para ubicar">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Detalle</label>
                                <textarea class="form-control" v-model="formEditar.detail" rows="2" placeholder="Detalles"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" v-model="formEditar.observations" rows="2" placeholder="Observaciones"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModales()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="actualizarUsuario()" :disabled="guardando">
                        <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                        <i class="fa fa-save" v-else></i>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" v-if="modalEditar" @click="cerrarModales()"></div>

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
            // Tab activo
            tabActivo: 'admins',

            // Listas
            administrators: [],
            collaborators: [],

            // Paginación
            paginationAdmins: { total: 0, current_page: 1, per_page: 12, last_page: 1, from: 0, to: 0 },
            paginationCollabs: { total: 0, current_page: 1, per_page: 12, last_page: 1, from: 0, to: 0 },
            offset: 3,

            // Búsqueda y filtros
            buscar: '',
            filtroEstado: 'TODOS',

            // Contadores
            numAdmins: { total: 0, activos: 0, bajas: 0 },
            numCollabs: { total: 0, activos: 0, bajas: 0 },

            // UI
            loading: false,
            vistaActual: localStorage.getItem('admin_vista') || 'cards',

            // Modales
            modalVer: false,
            modalCrear: false,
            modalEditar: false,

            // Usuario seleccionado
            usuarioSeleccionado: null,

            // Formulario crear
            formCrear: {
                username: '',
                password: '',
                confirmPassword: '',
                name: '',
                phone: '',
                movil: '',
                zip_code: '',
                address: '',
                number_out: '',
                number_int: '',
                district: '',
                city: '',
                state: '',
                reference: '',
                detail: '',
                observations: ''
            },

            // Formulario editar
            formEditar: {
                id: null,
                name: '',
                email: '',
                phone: '',
                movil: '',
                zip_code: '',
                address: '',
                number_out: '',
                number_int: '',
                district: '',
                city: '',
                state: '',
                reference: '',
                detail: '',
                observations: ''
            },

            // Validación
            erroresForm: [],
            guardando: false,
            showPassword: false,
            showConfirmPassword: false,

            // Shop slug
            shopSlug: ''
        }
    },
    watch: {
        vistaActual(newVal) {
            localStorage.setItem('admin_vista', newVal);
        }
    },
    computed: {
        currentList() {
            return this.tabActivo === 'admins' ? this.administrators : this.collaborators;
        },
        currentPagination() {
            return this.tabActivo === 'admins' ? this.paginationAdmins : this.paginationCollabs;
        },
        currentCounters() {
            return this.tabActivo === 'admins' ? this.numAdmins : this.numCollabs;
        },
        emailGenerado() {
            if (!this.formCrear.username || !this.shopSlug) return '';
            return this.generarSlug(this.formCrear.username) + '@' + this.shopSlug + '.app';
        },
        pagesNumber() {
            if (!this.currentPagination.to) {
                return [];
            }
            let from = this.currentPagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }
            let to = from + (this.offset * 2);
            if (to >= this.currentPagination.last_page) {
                to = this.currentPagination.last_page;
            }
            let pagesArray = [];
            while (from <= to) {
                pagesArray.push(from);
                from++;
            }
            return pagesArray;
        }
    },
    mounted() {
        this.loadShopSlug();
        this.loadAdministrators(1);
        this.loadCollaborators(1);
        this.loadCounters();
    },
    methods: {
        // ========== CARGA DE DATOS ==========
        loadShopSlug() {
            axios.get('/admin/users/shop-slug')
                .then(response => {
                    this.shopSlug = response.data.slug;
                })
                .catch(error => {
                    console.error('Error cargando shop slug:', error);
                });
        },

        loadAdministrators(page) {
            this.loading = true;
            axios.get('/admin/users/administrators', {
                params: {
                    page: page,
                    buscar: this.buscar,
                    estado: this.filtroEstado
                }
            })
            .then(response => {
                this.administrators = response.data.data;
                this.paginationAdmins = {
                    total: response.data.total,
                    current_page: response.data.current_page,
                    per_page: response.data.per_page,
                    last_page: response.data.last_page,
                    from: response.data.from,
                    to: response.data.to
                };
            })
            .catch(error => {
                console.error('Error cargando administradores:', error);
            })
            .finally(() => {
                this.loading = false;
            });
        },

        loadCollaborators(page) {
            this.loading = true;
            axios.get('/admin/users/collaborators', {
                params: {
                    page: page,
                    buscar: this.buscar,
                    estado: this.filtroEstado
                }
            })
            .then(response => {
                this.collaborators = response.data.data;
                this.paginationCollabs = {
                    total: response.data.total,
                    current_page: response.data.current_page,
                    per_page: response.data.per_page,
                    last_page: response.data.last_page,
                    from: response.data.from,
                    to: response.data.to
                };
            })
            .catch(error => {
                console.error('Error cargando colaboradores:', error);
            })
            .finally(() => {
                this.loading = false;
            });
        },

        loadCounters() {
            axios.get('/admin/users/counters')
                .then(response => {
                    this.numAdmins = response.data.admins;
                    this.numCollabs = response.data.collabs;
                })
                .catch(error => {
                    console.error('Error cargando contadores:', error);
                });
        },

        // ========== NAVEGACIÓN ==========
        cambiarTab(tab) {
            this.tabActivo = tab;
            this.buscar = '';
            this.filtroEstado = 'TODOS';
        },

        buscarUsuarios() {
            if (this.tabActivo === 'admins') {
                this.loadAdministrators(1);
            } else {
                this.loadCollaborators(1);
            }
        },

        cambiarPagina(page) {
            if (page < 1 || page > this.currentPagination.last_page) return;
            if (this.tabActivo === 'admins') {
                this.loadAdministrators(page);
            } else {
                this.loadCollaborators(page);
            }
        },

        // ========== MODALES ==========
        abrirModalVer(user) {
            this.usuarioSeleccionado = user;
            this.modalVer = true;
        },

        abrirModalCrear() {
            this.resetFormCrear();
            this.erroresForm = [];
            this.modalCrear = true;
        },

        abrirModalEditar(user) {
            this.usuarioSeleccionado = user;
            this.formEditar = {
                id: user.id,
                name: user.name,
                email: user.email,
                phone: user.phone || '',
                movil: user.movil || '',
                zip_code: user.zip_code || '',
                address: user.address || '',
                number_out: user.number_out || '',
                number_int: user.number_int || '',
                district: user.district || '',
                city: user.city || '',
                state: user.state || '',
                reference: user.reference || '',
                detail: user.detail || '',
                observations: user.observations || ''
            };
            this.erroresForm = [];
            this.modalVer = false;
            this.modalEditar = true;
        },

        cerrarModales() {
            this.modalVer = false;
            this.modalCrear = false;
            this.modalEditar = false;
            this.usuarioSeleccionado = null;
        },

        resetFormCrear() {
            this.formCrear = {
                username: '',
                password: '',
                confirmPassword: '',
                name: '',
                phone: '',
                movil: '',
                zip_code: '',
                address: '',
                number_out: '',
                number_int: '',
                district: '',
                city: '',
                state: '',
                reference: '',
                detail: '',
                observations: ''
            };
            this.showPassword = false;
            this.showConfirmPassword = false;
        },

        // ========== CRUD ==========
        async crearUsuario() {
            this.erroresForm = [];

            // Validaciones
            if (!this.formCrear.username) {
                this.erroresForm.push('El usuario es obligatorio');
            }
            if (!this.formCrear.name) {
                this.erroresForm.push('El nombre es obligatorio');
            }
            if (!this.formCrear.password) {
                this.erroresForm.push('La contraseña es obligatoria');
            } else if (this.formCrear.password.length < 8) {
                this.erroresForm.push('La contraseña debe tener al menos 8 caracteres');
            } else if (!/[a-zA-Z]/.test(this.formCrear.password) || !/[0-9]/.test(this.formCrear.password)) {
                this.erroresForm.push('La contraseña debe contener letras y números');
            }
            if (this.formCrear.password !== this.formCrear.confirmPassword) {
                this.erroresForm.push('Las contraseñas no coinciden');
            }

            if (this.erroresForm.length > 0) return;

            // Verificar email
            const email = this.emailGenerado;
            try {
                const checkResponse = await axios.get('/admin/users/verify-email', { params: { email } });
                if (checkResponse.data.exists) {
                    this.erroresForm.push('Este email ya está registrado');
                    return;
                }
            } catch (error) {
                console.error('Error verificando email:', error);
                return;
            }

            // Crear usuario
            this.guardando = true;
            const endpoint = this.tabActivo === 'admins'
                ? '/admin/users/administrators/store'
                : '/admin/users/collaborators/store';

            const data = {
                email: email,
                password: this.formCrear.password,
                name: this.formCrear.name,
                phone: this.formCrear.phone,
                movil: this.formCrear.movil,
                zip_code: this.formCrear.zip_code,
                address: this.formCrear.address,
                number_out: this.formCrear.number_out,
                number_int: this.formCrear.number_int,
                district: this.formCrear.district,
                city: this.formCrear.city,
                state: this.formCrear.state,
                reference: this.formCrear.reference,
                detail: this.formCrear.detail,
                observations: this.formCrear.observations
            };

            axios.post(endpoint, data)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Creado',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        this.cerrarModales();
                        this.buscarUsuarios();
                        this.loadCounters();
                    }
                })
                .catch(error => {
                    console.error('Error creando usuario:', error);
                    if (error.response && error.response.data && error.response.data.errors) {
                        Object.values(error.response.data.errors).forEach(errs => {
                            this.erroresForm.push(...errs);
                        });
                    } else {
                        this.erroresForm.push('Error al crear el usuario');
                    }
                })
                .finally(() => {
                    this.guardando = false;
                });
        },

        actualizarUsuario() {
            this.erroresForm = [];
            this.guardando = true;

            const endpoint = this.tabActivo === 'admins'
                ? '/admin/users/administrators/update'
                : '/admin/users/collaborators/update';

            axios.put(endpoint, this.formEditar)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        this.cerrarModales();
                        this.buscarUsuarios();
                    }
                })
                .catch(error => {
                    console.error('Error actualizando usuario:', error);
                    if (error.response && error.response.data && error.response.data.errors) {
                        Object.values(error.response.data.errors).forEach(errs => {
                            this.erroresForm.push(...errs);
                        });
                    } else {
                        this.erroresForm.push('Error al actualizar el usuario');
                    }
                })
                .finally(() => {
                    this.guardando = false;
                });
        },

        confirmarActivar(user) {
            Swal.fire({
                title: '¿Dar de alta?',
                text: `¿Deseas activar a ${user.name}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, activar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.activarUsuario(user.id);
                }
            });
        },

        confirmarDesactivar(user) {
            Swal.fire({
                title: '¿Dar de baja?',
                text: `¿Deseas dar de baja a ${user.name}? No podrá acceder al sistema.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, dar de baja',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.desactivarUsuario(user.id);
                }
            });
        },

        activarUsuario(id) {
            const endpoint = this.tabActivo === 'admins'
                ? `/admin/users/administrators/${id}/activate`
                : `/admin/users/collaborators/${id}/activate`;

            axios.put(endpoint)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Activado',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        this.buscarUsuarios();
                        this.loadCounters();
                    }
                })
                .catch(error => {
                    console.error('Error activando usuario:', error);
                    Swal.fire('Error', 'No se pudo activar el usuario', 'error');
                });
        },

        desactivarUsuario(id) {
            const endpoint = this.tabActivo === 'admins'
                ? `/admin/users/administrators/${id}/deactivate`
                : `/admin/users/collaborators/${id}/deactivate`;

            axios.put(endpoint)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Dado de baja',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        this.buscarUsuarios();
                        this.loadCounters();
                    }
                })
                .catch(error => {
                    console.error('Error desactivando usuario:', error);
                    Swal.fire('Error', 'No se pudo dar de baja el usuario', 'error');
                });
        },

        confirmarToggleLimited(user) {
            const esLimitado = user.user && user.user.limited;
            const accion = esLimitado ? 'quitar el límite de privilegios a' : 'limitar los privilegios de';

            Swal.fire({
                title: '¿Cambiar privilegios?',
                text: `¿Deseas ${accion} ${user.name}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.toggleLimited(user.id);
                }
            });
        },

        toggleLimited(id) {
            axios.put(`/admin/users/administrators/${id}/toggle-limited`)
                .then(response => {
                    if (response.data.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Privilegios actualizados',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        this.buscarUsuarios();
                    }
                })
                .catch(error => {
                    console.error('Error cambiando privilegios:', error);
                    Swal.fire('Error', 'No se pudieron cambiar los privilegios', 'error');
                });
        },

        // ========== HELPERS ==========
        generarSlug(texto) {
            if (!texto) return '';
            return texto
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '.')
                .replace(/^\.+|\.+$/g, '');
        },

        generarEmail() {
            // Se genera automáticamente en computed
        },

        getInitials(name) {
            if (!name) return '?';
            const parts = name.split(' ');
            if (parts.length >= 2) {
                return (parts[0][0] + parts[1][0]).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
        },

        copiarEmail(email) {
            navigator.clipboard.writeText(email).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Copiado',
                    text: 'Email copiado al portapapeles',
                    timer: 1500,
                    showConfirmButton: false
                });
            }).catch(() => {
                Swal.fire('Error', 'No se pudo copiar el email', 'error');
            });
        }
    }
}
</script>

<style scoped>
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

/* Estilos para Cards de Usuarios - igual que Tasks */
.user-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.user-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.user-card.inactive-card {
    opacity: 0.7;
    background-color: #f8f9fa;
}

.user-card .card-header {
    background: linear-gradient(135deg, #00F5A0 0%, #00D9F5 100%);
    color: #0D1117;
    border: none;
    padding: 0.75rem 1rem;
}

.user-card.inactive-card .card-header {
    background: linear-gradient(135deg, #868e96 0%, #6c757d 100%);
    color: white;
}

.avatar-circle {
    width: 45px;
    height: 45px;
    background-color: #6c757d;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.user-info {
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

.user-card .card-footer {
    background: transparent;
    border-top: 1px solid #eee;
    padding: 0.75rem 1rem;
}

.nav-tabs .nav-link {
    cursor: pointer;
}

.nav-tabs .nav-link.active {
    font-weight: bold;
}
</style>
