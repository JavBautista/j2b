<template>
<div>
    <div class="container-fluid">
        <!-- Resumen de tareas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h3>{{ numStatus.numNuevo || 0 }}</h3>
                        <p class="mb-0">Nuevas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h3>{{ numStatus.numPendiente || 0 }}</h3>
                        <p class="mb-0">Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h3>{{ numStatus.numAtendido || 0 }}</h3>
                        <p class="mb-0">Atendidas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card principal de tareas -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-tasks"></i> Tareas de {{ shop.name }}</span>
                <div>
                    <!-- Toggle Vista -->
                    <div class="btn-group mr-2">
                        <button type="button" class="btn btn-sm" :class="vistaActual === 'cards' ? 'btn-secondary' : 'btn-outline-secondary'" @click="vistaActual = 'cards'" title="Vista Cards">
                            <i class="fa fa-th-large"></i>
                        </button>
                        <button type="button" class="btn btn-sm" :class="vistaActual === 'tabla' ? 'btn-secondary' : 'btn-outline-secondary'" @click="vistaActual = 'tabla'" title="Vista Tabla">
                            <i class="fa fa-list"></i>
                        </button>
                    </div>
                    <button type="button" @click="abrirModal('crear')" class="btn btn-primary">
                        <i class="fa fa-plus"></i>&nbsp;Nueva Tarea
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros y búsqueda -->
                <div class="form-group row mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" v-model="buscar" class="form-control" placeholder="Buscar por título, descripción o ID..." @keyup.enter="loadTasks(1)">
                            <select class="form-control col-md-2" v-model="filtroStatus">
                                <option value="TODOS">TODOS</option>
                                <option value="NUEVO">NUEVOS</option>
                                <option value="PENDIENTE">PENDIENTES</option>
                                <option value="ATENDIDO">ATENDIDOS</option>
                            </select>
                            <select class="form-control col-md-2" v-model="filtroOrdenar">
                                <option value="ID_DESC">Más recientes</option>
                                <option value="ID_ASC">Más antiguos</option>
                                <option value="PRD_DESC">Mayor prioridad</option>
                                <option value="PRD_ASC">Menor prioridad</option>
                            </select>
                            <button type="submit" @click="loadTasks(1)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>

                <!-- VISTA CARDS -->
                <div class="row" v-if="vistaActual === 'cards'">
                    <div class="col-md-4 col-lg-3 mb-4" v-for="task in arrayTasks" :key="task.id">
                        <div class="card task-card h-100" :class="{'inactive-card': !task.active}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="task-id">#{{ task.id }}</span>
                                    <span class="badge ml-2" :class="getBadgeClass(task.status)">{{ task.status }}</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-dark dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModal('ver', task)">
                                            <i class="fa fa-eye text-info"></i> Ver Detalle
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModal('editar', task)">
                                            <i class="fa fa-edit text-primary"></i> Editar
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
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
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" @click.prevent="confirmarEliminar(task)">
                                            <i class="fa fa-trash"></i> Eliminar
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body" @click="abrirModal('ver', task)" style="cursor: pointer;">
                                <h5 class="card-title task-name">
                                    <i class="fa fa-clipboard text-primary"></i>
                                    {{ task.title }}
                                </h5>
                                <div class="task-info">
                                    <div class="info-item">
                                        <span v-if="task.active" class="badge badge-success">Activo</span>
                                        <span v-else class="badge badge-danger">Inactivo</span>
                                        <span v-if="task.origin === 'client'" class="badge badge-warning ml-1">
                                            <i class="fa fa-user"></i> Cliente
                                        </span>
                                        <span class="badge badge-secondary ml-1">P{{ task.priority }}</span>
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
                                    Tarea #{{ task.id }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISTA TABLA -->
                <div class="table-responsive" v-if="vistaActual === 'tabla'">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
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
                                <td><strong>{{ task.id }}</strong></td>
                                <td>
                                    <a href="#" @click.prevent="abrirModal('ver', task)" class="text-dark">
                                        {{ task.title }}
                                    </a>
                                    <span v-if="task.origin === 'client'" class="badge badge-warning ml-1">
                                        <i class="fa fa-user"></i>
                                    </span>
                                </td>
                                <td><span class="badge" :class="getBadgeClass(task.status)">{{ task.status }}</span></td>
                                <td><span class="badge badge-secondary">P{{ task.priority }}</span></td>
                                <td>{{ task.assigned_user ? task.assigned_user.name : '-' }}</td>
                                <td>{{ task.client ? task.client.name : '-' }}</td>
                                <td>{{ formatDate(task.created_at) }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info btn-sm" @click="abrirModal('ver', task)" title="Ver">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm" @click="abrirModal('editar', task)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" @click="abrirModal('estatus', task)" title="Estatus">
                                            <i class="fa fa-exchange"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" @click="confirmarEliminar(task)" title="Eliminar">
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
                    <i class="fa fa-tasks fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron tareas con los criterios de búsqueda.</p>
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

    <!-- Modal Ver Detalle -->
    <div class="modal fade modal-ver-detalle" tabindex="-1" :class="{'mostrar': modalVer}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title">
                        <i class="fa fa-clipboard mr-2"></i>
                        Tarea #{{ taskSeleccionada.id }} - {{ taskSeleccionada.title }}
                    </h4>
                    <button type="button" class="close text-white" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" v-if="taskSeleccionada.id">
                    <div class="row">
                        <!-- Columna izquierda -->
                        <div class="col-lg-6">
                            <!-- Info básica -->
                            <div class="card">
                                <div class="card-header">
                                    <i class="fa fa-info-circle mr-2"></i>Información General
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="text-muted small mb-1">Estatus</label>
                                            <div>
                                                <span class="badge badge-lg" :class="getBadgeClass(taskSeleccionada.status)">{{ taskSeleccionada.status }}</span>
                                                <span v-if="taskSeleccionada.active" class="badge badge-success ml-1">Activo</span>
                                                <span v-else class="badge badge-danger ml-1">Inactivo</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="text-muted small mb-1">Prioridad</label>
                                            <div>
                                                <span class="badge badge-secondary badge-lg">P{{ taskSeleccionada.priority }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted small mb-1">Título</label>
                                        <p class="font-weight-bold mb-0">{{ taskSeleccionada.title }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted small mb-1">Descripción</label>
                                        <p class="mb-0">{{ taskSeleccionada.description || 'Sin descripción' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted small mb-1">Solución</label>
                                        <p class="mb-0">{{ taskSeleccionada.solution || 'Sin solución' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted small mb-1">Reseña</label>
                                        <p class="mb-0">{{ taskSeleccionada.review || 'Sin reseña' }}</p>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="text-muted small mb-1">Fecha expiración</label>
                                            <p class="mb-0">{{ taskSeleccionada.expiration || 'Sin fecha' }}</p>
                                        </div>
                                        <div class="col-6">
                                            <label class="text-muted small mb-1">Creada</label>
                                            <p class="mb-0">{{ formatDateTime(taskSeleccionada.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cliente -->
                            <div class="card" v-if="taskSeleccionada.client">
                                <div class="card-header">
                                    <i class="fa fa-user mr-2"></i>Cliente
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                            <label class="text-muted small mb-1">Nombre</label>
                                            <p class="font-weight-bold mb-0">{{ taskSeleccionada.client.name }}</p>
                                        </div>
                                        <div class="col-6" v-if="taskSeleccionada.client.email">
                                            <label class="text-muted small mb-1">Email</label>
                                            <p class="mb-0">{{ taskSeleccionada.client.email }}</p>
                                        </div>
                                        <div class="col-6" v-if="taskSeleccionada.client.movil">
                                            <label class="text-muted small mb-1">Teléfono</label>
                                            <p class="mb-0">{{ taskSeleccionada.client.movil }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna derecha -->
                        <div class="col-lg-6">
                            <!-- Asignación (Solo Admin) -->
                            <div class="card">
                                <div class="card-header">
                                    <i class="fa fa-user-plus mr-2"></i>Asignación
                                </div>
                                <div class="card-body">
                                    <div v-if="taskSeleccionada.assigned_user">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="text-muted small mb-1">Colaborador asignado</label>
                                                <p class="font-weight-bold mb-0">
                                                    <i class="fa fa-user-circle text-primary mr-1"></i>
                                                    {{ taskSeleccionada.assigned_user.name }}
                                                </p>
                                            </div>
                                            <button class="btn btn-outline-danger" @click="desasignarTarea(taskSeleccionada.id)">
                                                <i class="fa fa-user-times"></i> Desasignar
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <p class="text-muted mb-2">
                                            <i class="fa fa-exclamation-circle mr-1"></i>
                                            Sin asignar
                                        </p>
                                        <div class="input-group">
                                            <select class="form-control form-control-lg" v-model="colaboradorSeleccionado">
                                                <option value="">Seleccionar colaborador...</option>
                                                <option v-for="colab in colaboradores" :key="colab.id" :value="colab.id">
                                                    {{ colab.name }}
                                                </option>
                                            </select>
                                            <button class="btn btn-primary btn-lg" @click="asignarTarea(taskSeleccionada.id)" :disabled="!colaboradorSeleccionado">
                                                <i class="fa fa-user-plus"></i> Asignar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Productos/Refacciones Asignados -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-cubes mr-2"></i>Productos / Refacciones</span>
                                    <button class="btn btn-sm btn-success" @click="abrirModalProductos()">
                                        <i class="fa fa-plus mr-1"></i> Agregar
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div v-if="taskSeleccionada.products && taskSeleccionada.products.length > 0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th class="text-center">Entregados</th>
                                                        <th class="text-center">Usados</th>
                                                        <th class="text-center">Devueltos</th>
                                                        <th class="text-center">Pendientes</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="tp in taskSeleccionada.products" :key="tp.id">
                                                        <td>
                                                            <strong>{{ tp.product ? tp.product.name : 'Producto eliminado' }}</strong>
                                                            <br><small class="text-muted">{{ tp.product ? tp.product.key : '' }}</small>
                                                            <br><small class="text-info" v-if="tp.notes">{{ tp.notes }}</small>
                                                        </td>
                                                        <td class="text-center">{{ tp.qty_delivered }}</td>
                                                        <td class="text-center">
                                                            <span :class="tp.qty_used > 0 ? 'text-danger' : ''">{{ tp.qty_used }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span :class="tp.qty_returned > 0 ? 'text-success' : ''">{{ tp.qty_returned }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge" :class="getPendientesBadge(tp)">
                                                                {{ tp.qty_delivered - tp.qty_used - tp.qty_returned }}
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <button class="btn btn-sm btn-outline-secondary" @click="editarProductoTarea(tp)" title="Actualizar cantidades">
                                                                <i class="fa fa-cog"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-2 p-2 bg-light rounded">
                                            <small>
                                                <strong>Resumen:</strong>
                                                Total entregados: {{ getTotalEntregados() }} |
                                                Usados: {{ getTotalUsados() }} |
                                                Devueltos: {{ getTotalDevueltos() }} |
                                                <span :class="getTotalPendientes() > 0 ? 'text-warning' : 'text-success'">
                                                    Pendientes: {{ getTotalPendientes() }}
                                                </span>
                                            </small>
                                        </div>
                                    </div>
                                    <div v-else class="text-center text-muted py-3">
                                        <i class="fa fa-box-open fa-2x mb-2"></i>
                                        <p class="mb-0">No hay productos asignados a esta tarea</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Imágenes (Solo visualización) -->
                            <div class="card" v-if="(taskSeleccionada.images && taskSeleccionada.images.length > 0) || taskSeleccionada.image">
                                <div class="card-header">
                                    <i class="fa fa-image mr-2"></i>Imágenes
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-4 col-md-3 mb-3" v-if="taskSeleccionada.image">
                                            <img :src="getImageUrl(taskSeleccionada.image)" class="img-fluid img-thumbnail rounded" style="max-height: 120px; cursor: pointer; object-fit: cover;">
                                        </div>
                                        <div class="col-4 col-md-3 mb-3" v-for="img in taskSeleccionada.images" :key="img.id">
                                            <img :src="getImageUrl(img.image)" class="img-fluid img-thumbnail rounded" style="max-height: 120px; cursor: pointer; object-fit: cover;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Firma Digital (Solo visualización) -->
                            <div class="card" v-if="taskSeleccionada.signature_path">
                                <div class="card-header">
                                    <i class="fa fa-pencil-square-o mr-2"></i>Firma Digital
                                </div>
                                <div class="card-body text-center">
                                    <img :src="getImageUrl(taskSeleccionada.signature_path)" class="img-fluid rounded" style="max-height: 180px; border: 2px solid #dee2e6; padding: 15px; background: #fff;">
                                </div>
                            </div>

                            <!-- Logs / Historial -->
                            <div class="card" v-if="taskSeleccionada.logs && taskSeleccionada.logs.length > 0">
                                <div class="card-header">
                                    <i class="fa fa-history mr-2"></i>Historial de Cambios
                                </div>
                                <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                                    <ul class="list-unstyled mb-0">
                                        <li v-for="log in taskSeleccionada.logs" :key="log.id" class="mb-3 pb-2 border-bottom">
                                            <div class="d-flex justify-content-between">
                                                <strong class="text-primary">{{ log.user }}</strong>
                                                <small class="text-muted">{{ formatDateTime(log.created_at) }}</small>
                                            </div>
                                            <p class="mb-0 mt-1">{{ log.description }}</p>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-lg btn-secondary" @click="cerrarModal()">
                        <i class="fa fa-times mr-1"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-lg btn-warning" @click="abrirModal('estatus', taskSeleccionada)">
                        <i class="fa fa-exchange mr-1"></i> Cambiar Estatus
                    </button>
                    <button type="button" class="btn btn-lg btn-primary" @click="abrirModal('editar', taskSeleccionada)">
                        <i class="fa fa-edit mr-1"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalEditar}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ modoEdicion ? 'Editar Tarea #' + formTask.id : 'Nueva Tarea' }}</h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
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
                            <div class="col-md-9">
                                <select class="form-control" v-model="formTask.client_id">
                                    <option value="">Sin cliente</option>
                                    <option v-for="client in clientes" :key="client.id" :value="client.id">
                                        {{ client.name }} {{ client.company ? '(' + client.company + ')' : '' }}
                                    </option>
                                </select>
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarTarea" :disabled="guardando">
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
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Cambiar Estatus</h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Tarea: <strong>{{ taskSeleccionada.title }}</strong></p>
                    <p>Estatus actual: <span class="badge" :class="getBadgeClass(taskSeleccionada.status)">{{ taskSeleccionada.status }}</span></p>
                    <div class="form-group">
                        <label>Nuevo estatus:</label>
                        <select class="form-control" v-model="nuevoEstatus">
                            <option value="NUEVO">NUEVO</option>
                            <option value="PENDIENTE">PENDIENTE</option>
                            <option value="ATENDIDO">ATENDIDO</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="cambiarEstatus">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reseña -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalResena}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Agregar Reseña</h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Tarea: <strong>{{ taskSeleccionada.title }}</strong></p>
                    <div class="form-group">
                        <label>Reseña:</label>
                        <textarea class="form-control" v-model="nuevaResena" rows="4" placeholder="Escribe la reseña..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarResena">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Producto -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalProductos}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Agregar Producto/Refacción</h4>
                    <button type="button" class="close" @click="cerrarModalProductos()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Tarea: <strong>#{{ taskSeleccionada.id }} - {{ taskSeleccionada.title }}</strong></p>

                    <!-- Buscador de productos -->
                    <div class="form-group">
                        <label>Buscar producto:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" v-model="buscarProducto"
                                   placeholder="Nombre, código o código de barras..."
                                   @input="buscarProductosDebounce">
                        </div>
                    </div>

                    <!-- Lista de productos encontrados -->
                    <div v-if="productosDisponibles.length > 0 && !productoSeleccionado" class="list-group mb-3" style="max-height: 200px; overflow-y: auto;">
                        <a href="#" class="list-group-item list-group-item-action"
                           v-for="prod in productosDisponibles" :key="prod.id"
                           @click.prevent="seleccionarProducto(prod)">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ prod.name }}</strong>
                                    <br><small class="text-muted">{{ prod.key }}</small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-info">Stock: {{ prod.stock }}</span>
                                    <br><small>${{ prod.retail }}</small>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Producto seleccionado -->
                    <div v-if="productoSeleccionado" class="card mb-3 border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1">{{ productoSeleccionado.name }}</h5>
                                    <small class="text-muted">Código: {{ productoSeleccionado.key }}</small>
                                    <br><small>Stock disponible: <strong class="text-success">{{ productoSeleccionado.stock }}</strong></small>
                                    <br><small>Costo: ${{ productoSeleccionado.cost }} | Precio: ${{ productoSeleccionado.retail }}</small>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary" @click="productoSeleccionado = null">
                                    <i class="fa fa-times"></i> Cambiar
                                </button>
                            </div>

                            <hr>

                            <div class="form-group row">
                                <label class="col-md-4 col-form-label">Cantidad a entregar:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" v-model.number="formProducto.qty_delivered"
                                           min="1" :max="productoSeleccionado.stock">
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Máx: {{ productoSeleccionado.stock }}</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Notas (opcional):</label>
                                <input type="text" class="form-control" v-model="formProducto.notes"
                                       placeholder="Ej: Para reparación de cabezal">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalProductos()">Cancelar</button>
                    <button type="button" class="btn btn-success" @click="agregarProductoTarea"
                            :disabled="!productoSeleccionado || formProducto.qty_delivered < 1 || guardando">
                        <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                        <i class="fa fa-plus" v-else></i>
                        Agregar Producto
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Actualizar Cantidades de Producto -->
    <div class="modal fade modal-cantidades" tabindex="-1" :class="{'mostrar': editandoProducto !== null}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-cog mr-2"></i>Actualizar Cantidades
                    </h5>
                    <button type="button" class="close text-white" @click="editandoProducto = null" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" v-if="editandoProducto">
                    <!-- Info del producto -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-1">{{ editandoProducto.product ? editandoProducto.product.name : 'N/A' }}</h6>
                        <small class="text-muted">{{ editandoProducto.product ? editandoProducto.product.key : '' }}</small>
                        <div class="mt-2">
                            <span class="badge badge-primary badge-lg">
                                Entregados: {{ editandoProducto.qty_delivered }}
                            </span>
                        </div>
                    </div>

                    <!-- Slider visual de distribución -->
                    <div class="mb-4">
                        <label class="font-weight-bold">¿Cuántos se devuelven al inventario?</label>
                        <input type="range" class="form-control-range slider-devolucion"
                               v-model.number="formEditProducto.qty_returned"
                               min="0" :max="editandoProducto.qty_delivered"
                               @input="syncUsadosDesdeDevueltos">

                        <!-- Indicadores visuales -->
                        <div class="d-flex justify-content-between mt-3">
                            <div class="text-center flex-fill">
                                <div class="h3 mb-0 text-danger">{{ formEditProducto.qty_used }}</div>
                                <small class="text-muted">Usados</small>
                                <br><small class="text-danger">(No regresan)</small>
                            </div>
                            <div class="text-center flex-fill">
                                <div class="h3 mb-0 text-success">{{ formEditProducto.qty_returned }}</div>
                                <small class="text-muted">Devueltos</small>
                                <br><small class="text-success">(Regresan al stock)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen -->
                    <div class="alert alert-light border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fa fa-check-circle text-success mr-1"></i>
                                <strong>Total:</strong> {{ formEditProducto.qty_used }} usados + {{ formEditProducto.qty_returned }} devueltos = {{ formEditProducto.qty_used + formEditProducto.qty_returned }}
                            </span>
                            <span class="badge badge-success" v-if="formEditProducto.qty_used + formEditProducto.qty_returned === editandoProducto.qty_delivered">
                                <i class="fa fa-check"></i> OK
                            </span>
                        </div>
                    </div>

                    <!-- Notas opcionales -->
                    <div class="form-group mb-0">
                        <label>Notas (opcional):</label>
                        <input type="text" class="form-control" v-model="formEditProducto.notes" placeholder="Ej: 2 fusibles quemados, 1 sobrante...">
                    </div>
                </div>
                <div class="modal-footer" v-if="editandoProducto">
                    <button type="button" class="btn btn-secondary" @click="editandoProducto = null">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="actualizarProductoTarea"
                            :disabled="formEditProducto.qty_used + formEditProducto.qty_returned !== editandoProducto.qty_delivered || guardando">
                        <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                        <i class="fa fa-save" v-else></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
</template>

<script>
export default {
    props: ['shop'],
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
            modalVer: false,
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
            colaboradores: [],
            clientes: [],
            colaboradorSeleccionado: '',

            // Estados
            nuevoEstatus: '',
            nuevaResena: '',

            // UI
            guardando: false,
            errorForm: false,
            erroresForm: [],
            vistaActual: 'cards', // 'cards' o 'tabla'

            // Productos de tarea
            modalProductos: false,
            productosDisponibles: [],
            buscarProducto: '',
            formProducto: {
                product_id: '',
                qty_delivered: 1,
                notes: ''
            },
            productoSeleccionado: null,
            editandoProducto: null,
            formEditProducto: {
                qty_used: 0,
                qty_returned: 0,
                notes: ''
            }
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
        this.loadColaboradores();
        this.loadClientes();
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

        loadColaboradores() {
            let me = this;
            axios.get('/admin/tasks/collaborators').then(function(response) {
                if (response.data.ok) {
                    me.colaboradores = response.data.collaborators;
                }
            }).catch(function(error) {
                console.log(error);
            });
        },

        loadClientes() {
            let me = this;
            axios.get('/admin/tasks/clients').then(function(response) {
                if (response.data.ok) {
                    me.clientes = response.data.clients;
                }
            }).catch(function(error) {
                console.log(error);
            });
        },

        cambiarPagina(page) {
            let me = this;
            me.pagination.current_page = page;
            me.loadTasks(page);
        },

        // Modales
        abrirModal(tipo, task = null) {
            this.cerrarModal();

            if (tipo === 'ver' && task) {
                this.taskSeleccionada = { ...task };
                this.colaboradorSeleccionado = '';
                this.modalVer = true;
            } else if (tipo === 'crear') {
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
            this.modalVer = false;
            this.modalEditar = false;
            this.modalEstatus = false;
            this.modalResena = false;
        },

        // CRUD
        guardarTarea() {
            let me = this;
            me.guardando = true;
            me.errorForm = false;
            me.erroresForm = [];

            let url = me.modoEdicion ? '/admin/tasks/update' : '/admin/tasks/store';
            let method = me.modoEdicion ? axios.put : axios.post;

            method(url, me.formTask).then(function(response) {
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
                text: `#${task.id} - ${task.title}`,
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

        asignarTarea(taskId) {
            let me = this;
            if (!me.colaboradorSeleccionado) return;

            axios.post(`/admin/tasks/${taskId}/assign`, {
                user_id: me.colaboradorSeleccionado
            }).then(function(response) {
                if (response.data.ok) {
                    me.taskSeleccionada = response.data.task;
                    me.colaboradorSeleccionado = '';
                    me.loadTasks(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al asignar tarea', 'error');
            });
        },

        desasignarTarea(taskId) {
            let me = this;
            axios.post(`/admin/tasks/${taskId}/unassign`).then(function(response) {
                if (response.data.ok) {
                    me.taskSeleccionada = response.data.task;
                    me.loadTasks(me.pagination.current_page || 1);
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                Swal.fire('Error', 'Error al desasignar tarea', 'error');
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
                case 'NUEVO': return 'badge-success';
                case 'PENDIENTE': return 'badge-warning';
                case 'ATENDIDO': return 'badge-info';
                default: return 'badge-secondary';
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

        // ==========================================
        // MÉTODOS PARA PRODUCTOS DE TAREA
        // ==========================================

        abrirModalProductos() {
            this.modalProductos = true;
            this.buscarProducto = '';
            this.productosDisponibles = [];
            this.productoSeleccionado = null;
            this.formProducto = {
                product_id: '',
                qty_delivered: 1,
                notes: ''
            };
        },

        cerrarModalProductos() {
            this.modalProductos = false;
            this.buscarProducto = '';
            this.productosDisponibles = [];
            this.productoSeleccionado = null;
        },

        buscarProductosDebounce() {
            let me = this;
            clearTimeout(me.searchTimeout);
            me.searchTimeout = setTimeout(() => {
                me.buscarProductos();
            }, 300);
        },

        buscarProductos() {
            let me = this;
            if (me.buscarProducto.length < 2) {
                me.productosDisponibles = [];
                return;
            }
            axios.get(`/admin/tasks/products?q=${me.buscarProducto}`).then(function(response) {
                if (response.data.ok) {
                    me.productosDisponibles = response.data.products;
                }
            }).catch(function(error) {
                console.log(error);
            });
        },

        seleccionarProducto(producto) {
            this.productoSeleccionado = producto;
            this.formProducto.product_id = producto.id;
            this.formProducto.qty_delivered = 1;
            this.productosDisponibles = [];
        },

        agregarProductoTarea() {
            let me = this;
            me.guardando = true;

            axios.post(`/admin/tasks/${me.taskSeleccionada.id}/products`, {
                product_id: me.formProducto.product_id,
                qty_delivered: me.formProducto.qty_delivered,
                notes: me.formProducto.notes
            }).then(function(response) {
                if (response.data.ok) {
                    // Agregar producto a la lista local
                    if (!me.taskSeleccionada.products) {
                        me.taskSeleccionada.products = [];
                    }
                    me.taskSeleccionada.products.push(response.data.taskProduct);
                    me.cerrarModalProductos();
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                let msg = 'Error al agregar producto';
                if (error.response && error.response.data && error.response.data.message) {
                    msg = error.response.data.message;
                }
                Swal.fire('Error', msg, 'error');
            }).finally(function() {
                me.guardando = false;
            });
        },

        editarProductoTarea(taskProduct) {
            this.editandoProducto = taskProduct;

            // Si nunca se ha actualizado (ambos en 0), por defecto usados = entregados
            let usados = taskProduct.qty_used;
            let devueltos = taskProduct.qty_returned;

            if (usados === 0 && devueltos === 0) {
                usados = taskProduct.qty_delivered;
                devueltos = 0;
            }

            this.formEditProducto = {
                qty_used: usados,
                qty_returned: devueltos,
                notes: taskProduct.notes || ''
            };
        },

        // Sincroniza usados cuando cambia devueltos (slider)
        syncUsadosDesdeDevueltos() {
            if (this.editandoProducto) {
                this.formEditProducto.qty_used = this.editandoProducto.qty_delivered - this.formEditProducto.qty_returned;
            }
        },

        actualizarProductoTarea() {
            let me = this;
            me.guardando = true;

            axios.put(`/admin/tasks/${me.taskSeleccionada.id}/products/${me.editandoProducto.id}`, {
                qty_used: me.formEditProducto.qty_used,
                qty_returned: me.formEditProducto.qty_returned,
                notes: me.formEditProducto.notes
            }).then(function(response) {
                if (response.data.ok) {
                    // Actualizar en la lista local
                    let idx = me.taskSeleccionada.products.findIndex(p => p.id === me.editandoProducto.id);
                    if (idx !== -1) {
                        me.taskSeleccionada.products[idx] = response.data.taskProduct;
                    }
                    me.editandoProducto = null;
                    Swal.fire('Éxito', response.data.message, 'success');
                }
            }).catch(function(error) {
                let msg = 'Error al actualizar producto';
                if (error.response && error.response.data && error.response.data.message) {
                    msg = error.response.data.message;
                }
                Swal.fire('Error', msg, 'error');
            }).finally(function() {
                me.guardando = false;
            });
        },

        // Helpers para productos
        getPendientesBadge(tp) {
            let pendientes = tp.qty_delivered - tp.qty_used - tp.qty_returned;
            if (pendientes === 0) return 'badge-success';
            if (pendientes === tp.qty_delivered) return 'badge-secondary';
            return 'badge-warning';
        },

        getTotalEntregados() {
            if (!this.taskSeleccionada.products) return 0;
            return this.taskSeleccionada.products.reduce((sum, p) => sum + p.qty_delivered, 0);
        },

        getTotalUsados() {
            if (!this.taskSeleccionada.products) return 0;
            return this.taskSeleccionada.products.reduce((sum, p) => sum + p.qty_used, 0);
        },

        getTotalDevueltos() {
            if (!this.taskSeleccionada.products) return 0;
            return this.taskSeleccionada.products.reduce((sum, p) => sum + p.qty_returned, 0);
        },

        getTotalPendientes() {
            return this.getTotalEntregados() - this.getTotalUsados() - this.getTotalDevueltos();
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

    .div-error{
        display: flex;
        justify-content: center;
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

    /* Modal Ver Detalle - Grande y espacioso */
    .modal-ver-detalle .modal-dialog {
        max-width: 1400px;
        margin: 1rem auto;
    }

    .modal-ver-detalle .modal-content {
        min-height: 85vh;
    }

    .modal-ver-detalle .modal-body {
        padding: 1.5rem;
    }

    /* Cards dentro del modal con mejor espaciado */
    .modal-ver-detalle .card {
        margin-bottom: 1.25rem;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .modal-ver-detalle .card-header {
        padding: 0.75rem 1rem;
        font-size: 1rem;
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .modal-ver-detalle .card-body {
        padding: 1rem 1.25rem;
        font-size: 0.95rem;
    }

    .modal-ver-detalle .card-body p {
        margin-bottom: 0.6rem;
        line-height: 1.5;
    }

    /* Tabla de productos legible */
    .modal-ver-detalle .table-sm td,
    .modal-ver-detalle .table-sm th {
        padding: 0.5rem 0.6rem;
        font-size: 0.9rem;
        vertical-align: middle;
    }

    /* Botón extra pequeño */
    .btn-xs {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }

    /* Badge más grande */
    .badge-lg {
        font-size: 0.95rem;
        padding: 0.5rem 0.75rem;
    }

    /* Modal header mejorado */
    .modal-ver-detalle .modal-header {
        padding: 1rem 1.5rem;
    }

    .modal-ver-detalle .modal-header .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
    }

    .modal-ver-detalle .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 2px solid #dee2e6;
    }

    /* Labels en el modal */
    .modal-ver-detalle label.small {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Resumen de productos */
    .modal-ver-detalle .bg-light.rounded {
        border: 1px solid #dee2e6;
    }

    /* Modal de cantidades sobre el modal principal */
    .modal-cantidades.mostrar {
        z-index: 1060 !important;
    }

    .modal-cantidades .modal-dialog {
        margin-top: 10vh;
    }

    /* Slider de devolución */
    .slider-devolucion {
        width: 100%;
        height: 12px;
        -webkit-appearance: none;
        background: linear-gradient(to right, #dc3545 0%, #28a745 100%);
        border-radius: 6px;
        outline: none;
    }

    .slider-devolucion::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 28px;
        height: 28px;
        background: #fff;
        border: 3px solid #007bff;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    .slider-devolucion::-moz-range-thumb {
        width: 28px;
        height: 28px;
        background: #fff;
        border: 3px solid #007bff;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }
</style>
