<template>
<div>
    <!-- Header -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fa fa-list"></i> Rentas de {{ client.name }}
            </h4>
            <div>
                <button class="btn btn-success" @click="abrirModalNuevaRenta()">
                    <i class="fa fa-plus"></i> Nueva Renta
                </button>
                <a :href="'/admin/clients'" class="btn btn-secondary ms-2">
                    <i class="fa fa-arrow-left"></i> Volver a Clientes
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Info del Cliente -->
            <div class="row">
                <div class="col-md-3">
                    <strong><i class="fa fa-user"></i> Cliente:</strong>
                    <span class="ms-2">{{ client.name }}</span>
                </div>
                <div class="col-md-3">
                    <strong><i class="fa fa-envelope"></i> Email:</strong>
                    <span class="ms-2">{{ client.email || '-' }}</span>
                </div>
                <div class="col-md-3">
                    <strong><i class="fa fa-building"></i> Empresa:</strong>
                    <span class="ms-2">{{ client.company || '-' }}</span>
                </div>
                <div class="col-md-3">
                    <strong><i class="fa fa-phone"></i> Telefono:</strong>
                    <span class="ms-2">{{ client.movil || '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="row">
        <!-- Lista de Rentas (izquierda) -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-file-contract"></i> Rentas
                        <span class="badge bg-light text-dark ms-2">{{ rentas.length }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div v-if="cargando" class="text-center py-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div v-else-if="rentas.length === 0" class="text-center py-4 text-muted">
                        <i class="fa fa-folder-open fa-2x"></i>
                        <p class="mt-2">No hay rentas</p>
                    </div>
                    <div v-else class="list-group list-group-flush">
                        <a href="#" v-for="renta in rentas" :key="renta.id"
                           class="list-group-item list-group-item-action"
                           :class="{'active': rentaSeleccionada && rentaSeleccionada.id === renta.id, 'list-group-item-secondary': !renta.active}"
                           @click.prevent="seleccionarRenta(renta)">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>#{{ renta.id }}</strong>
                                    <span v-if="renta.location_descripcion" class="ms-2">{{ renta.location_descripcion }}</span>
                                </div>
                                <div>
                                    <span class="badge bg-info me-1">Corte: {{ renta.cutoff }}</span>
                                    <span class="badge bg-primary">{{ renta.rent_detail_count || 0 }} eq.</span>
                                </div>
                            </div>
                            <small class="text-muted d-block">{{ renta.location_address || 'Sin direccion' }}</small>
                            <div class="mt-1">
                                <span v-if="renta.active" class="badge bg-success">Activa</span>
                                <span v-else class="badge bg-danger">Inactiva</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle de Renta (derecha) -->
        <div class="col-lg-8">
            <div v-if="!rentaSeleccionada" class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="fa fa-hand-pointer fa-3x"></i>
                    <h5 class="mt-3">Selecciona una renta</h5>
                    <p>Haz clic en una renta de la lista para ver sus detalles y equipos</p>
                </div>
            </div>

            <div v-else>
                <!-- Info de la Renta -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fa fa-file-contract"></i> Renta #{{ rentaSeleccionada.id }}
                            <span v-if="rentaSeleccionada.active" class="badge bg-success ms-2">Activa</span>
                            <span v-else class="badge bg-danger ms-2">Inactiva</span>
                        </h5>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm" @click="editarRenta(rentaSeleccionada)">
                                <i class="fa fa-edit"></i> Editar
                            </button>
                            <button v-if="rentaSeleccionada.active" class="btn btn-danger btn-sm" @click="darDeBajaRenta(rentaSeleccionada)">
                                <i class="fa fa-toggle-off"></i> Dar de Baja
                            </button>
                            <button v-else class="btn btn-success btn-sm" @click="reactivarRenta(rentaSeleccionada)">
                                <i class="fa fa-toggle-on"></i> Reactivar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="text-muted small">Dia de Corte</label>
                                <p class="mb-0"><span class="badge bg-info fs-6">{{ rentaSeleccionada.cutoff }}</span></p>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted small">Descripcion</label>
                                <p class="mb-0">{{ rentaSeleccionada.location_descripcion || '-' }}</p>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted small">Direccion</label>
                                <p class="mb-0">{{ rentaSeleccionada.location_address || '-' }}</p>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted small">Contacto</label>
                                <p class="mb-0">
                                    {{ rentaSeleccionada.location_phone || '-' }}
                                    <br><small class="text-muted">{{ rentaSeleccionada.location_email || '' }}</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Equipos -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fa fa-print"></i> Equipos
                            <span class="badge bg-primary ms-2">{{ rentaSeleccionada.rent_detail ? rentaSeleccionada.rent_detail.length : 0 }}</span>
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fa fa-plus"></i> Agregar Equipo
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" @click.prevent="abrirModalNuevoEquipo()">
                                    <i class="fa fa-plus-circle"></i> Crear Nuevo
                                </a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="abrirModalSeleccionarEquipo()">
                                    <i class="fa fa-check-square"></i> Seleccionar del Inventario
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div v-if="cargandoDetalle" class="text-center py-4">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                        </div>
                        <div v-else-if="!rentaSeleccionada.rent_detail || rentaSeleccionada.rent_detail.length === 0" class="text-center py-4 text-muted">
                            <i class="fa fa-print fa-2x"></i>
                            <p class="mt-2">No hay equipos asignados</p>
                        </div>
                        <div v-else>
                            <!-- Cards de Equipos -->
                            <div class="row">
                                <div class="col-md-6 mb-3" v-for="equipo in rentaSeleccionada.rent_detail" :key="equipo.id">
                                    <div class="card h-100 border-primary">
                                        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                                            <strong>{{ equipo.trademark }} - {{ equipo.model }}</strong>
                                            <span class="badge bg-success">${{ equipo.rent_price }}</span>
                                        </div>
                                        <div class="card-body py-2">
                                            <div class="row small">
                                                <div class="col-6">
                                                    <strong>Serie:</strong> {{ equipo.serial_number || '-' }}
                                                </div>
                                                <div class="col-6" v-if="equipo.url_web_monitor">
                                                    <a :href="equipo.url_web_monitor" target="_blank" class="btn btn-outline-info btn-sm">
                                                        <i class="fa fa-external-link"></i> Monitor
                                                    </a>
                                                </div>
                                            </div>

                                            <!-- B/N -->
                                            <div v-if="equipo.monochrome" class="mt-2 p-2 bg-light rounded">
                                                <strong class="small">Blanco y Negro</strong>
                                                <div class="row small">
                                                    <div class="col-4">Incluidas: <strong>{{ equipo.pages_included_mono }}</strong></div>
                                                    <div class="col-4">Extra: <strong>${{ equipo.extra_page_cost_mono }}</strong></div>
                                                    <div class="col-4">Contador: <strong class="text-primary">{{ equipo.counter_mono }}</strong></div>
                                                </div>
                                            </div>

                                            <!-- Color -->
                                            <div v-if="equipo.color" class="mt-2 p-2 bg-info bg-opacity-10 rounded">
                                                <strong class="small">Color</strong>
                                                <div class="row small">
                                                    <div class="col-4">Incluidas: <strong>{{ equipo.pages_included_color }}</strong></div>
                                                    <div class="col-4">Extra: <strong>${{ equipo.extra_page_cost_color }}</strong></div>
                                                    <div class="col-4">Contador: <strong class="text-info">{{ equipo.counter_color }}</strong></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer py-2">
                                            <div class="btn-group btn-group-sm w-100">
                                                <button class="btn btn-outline-secondary" @click="abrirModalConsumible(equipo)" title="Agregar Consumible">
                                                    <i class="fa fa-plus-circle"></i>
                                                </button>
                                                <button class="btn btn-outline-info" @click="verHistorialConsumibles(equipo)" title="Historial">
                                                    <i class="fa fa-history"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" @click="editarEquipo(equipo)" title="Editar">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-primary" @click="editarUrlMonitor(equipo)" title="URL Monitor">
                                                    <i class="fa fa-link"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" @click="liberarEquipo(equipo)" title="Liberar">
                                                    <i class="fa fa-unlink"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar Renta -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalFormRenta}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-file-contract"></i> {{ editandoRenta ? 'Editar Renta' : 'Nueva Renta' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="cerrarModalFormRenta()"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Dia de Corte *</label>
                        <select class="form-select" v-model="formRenta.cutoff" required>
                            <option value="">Seleccione...</option>
                            <option v-for="dia in 31" :key="dia" :value="dia">{{ dia }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripcion/Nombre Ubicacion</label>
                        <input type="text" class="form-control" v-model="formRenta.location_descripcion" placeholder="Ej: Oficina Principal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Direccion</label>
                        <input type="text" class="form-control" v-model="formRenta.location_address">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefono</label>
                            <input type="text" class="form-control" v-model="formRenta.location_phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" v-model="formRenta.location_email">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalFormRenta()">Cancelar</button>
                    <button type="button" class="btn btn-success" @click="guardarRenta()" :disabled="guardandoRenta">
                        <i v-if="guardandoRenta" class="fa fa-spinner fa-spin"></i>
                        <i v-else class="fa fa-save"></i>
                        {{ editandoRenta ? 'Actualizar' : 'Crear' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar Equipo -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalFormEquipo}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fa fa-print"></i> {{ editandoEquipo ? 'Editar Equipo' : 'Nuevo Equipo' }}
                    </h5>
                    <button type="button" class="btn-close" @click="cerrarModalFormEquipo()"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Marca *</label>
                            <input type="text" class="form-control" v-model="formEquipo.trademark" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Modelo *</label>
                            <input type="text" class="form-control" v-model="formEquipo.model" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No. Serie</label>
                            <input type="text" class="form-control" v-model="formEquipo.serial_number">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Precio Renta *</label>
                            <input type="number" step="0.01" class="form-control" v-model="formEquipo.rent_price" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">URL Web Monitor</label>
                            <input type="url" class="form-control" v-model="formEquipo.url_web_monitor" placeholder="https://...">
                        </div>
                    </div>

                    <!-- Config B/N -->
                    <div class="card mb-3">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <strong>Blanco y Negro</strong>
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" class="form-check-input" v-model="formEquipo.monochrome">
                            </div>
                        </div>
                        <div class="card-body py-2" v-if="formEquipo.monochrome">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label small">Pag. Incluidas</label>
                                    <input type="number" class="form-control form-control-sm" v-model="formEquipo.pages_included_mono">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Costo Extra</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" v-model="formEquipo.extra_page_cost_mono">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Contador</label>
                                    <input type="number" class="form-control form-control-sm" v-model="formEquipo.counter_mono">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Config Color -->
                    <div class="card">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <strong>Color</strong>
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" class="form-check-input" v-model="formEquipo.color">
                            </div>
                        </div>
                        <div class="card-body py-2" v-if="formEquipo.color">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label small">Pag. Incluidas</label>
                                    <input type="number" class="form-control form-control-sm" v-model="formEquipo.pages_included_color">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Costo Extra</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" v-model="formEquipo.extra_page_cost_color">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Contador</label>
                                    <input type="number" class="form-control form-control-sm" v-model="formEquipo.counter_color">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalFormEquipo()">Cancelar</button>
                    <button type="button" class="btn btn-warning" @click="guardarEquipo()" :disabled="guardandoEquipo">
                        <i v-if="guardandoEquipo" class="fa fa-spinner fa-spin"></i>
                        <i v-else class="fa fa-save"></i>
                        {{ editandoEquipo ? 'Actualizar' : 'Crear' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Seleccionar Equipo -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalSeleccionarEquipo}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fa fa-check-square"></i> Seleccionar Equipo del Inventario</h5>
                    <button type="button" class="btn-close btn-close-white" @click="cerrarModalSeleccionarEquipo()"></button>
                </div>
                <div class="modal-body">
                    <div v-if="cargandoEquiposDisponibles" class="text-center py-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div v-else-if="equiposDisponibles.length === 0" class="text-center py-4 text-muted">
                        <i class="fa fa-box-open fa-2x"></i>
                        <p class="mt-2">No hay equipos disponibles</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Serie</th>
                                    <th>Precio</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="eq in equiposDisponibles" :key="eq.id">
                                    <td>{{ eq.trademark }}</td>
                                    <td>{{ eq.model }}</td>
                                    <td>{{ eq.serial_number || '-' }}</td>
                                    <td>${{ eq.rent_price }}</td>
                                    <td>
                                        <button class="btn btn-success btn-sm" @click="asignarEquipo(eq)">
                                            <i class="fa fa-check"></i> Asignar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalSeleccionarEquipo()">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Consumible -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalConsumible}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="fa fa-plus-circle"></i> Agregar Consumible</h5>
                    <button type="button" class="btn-close btn-close-white" @click="cerrarModalConsumible()"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Descripcion *</label>
                        <input type="text" class="form-control" v-model="formConsumible.description" required placeholder="Ej: Toner Negro">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" class="form-control" v-model="formConsumible.qty" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contador al Momento</label>
                            <input type="number" class="form-control" v-model="formConsumible.counter">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" v-model="formConsumible.observation" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalConsumible()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarConsumible()" :disabled="guardandoConsumible">
                        <i v-if="guardandoConsumible" class="fa fa-spinner fa-spin"></i>
                        <i v-else class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Historial Consumibles -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalHistorialConsumibles}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fa fa-history"></i> Historial de Consumibles</h5>
                    <button type="button" class="btn-close btn-close-white" @click="cerrarModalHistorialConsumibles()"></button>
                </div>
                <div class="modal-body">
                    <div v-if="cargandoConsumibles" class="text-center py-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div v-else-if="historialConsumibles.length === 0" class="text-center py-4 text-muted">
                        <i class="fa fa-inbox fa-2x"></i>
                        <p class="mt-2">No hay consumibles registrados</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Descripcion</th>
                                    <th>Cantidad</th>
                                    <th>Contador</th>
                                    <th>Observacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cons in historialConsumibles" :key="cons.id">
                                    <td><small>{{ formatDate(cons.created_at) }}</small></td>
                                    <td>{{ cons.description }}</td>
                                    <td><span class="badge bg-primary">{{ cons.qty }}</span></td>
                                    <td>{{ cons.counter || '-' }}</td>
                                    <td><small>{{ cons.observation || '-' }}</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalHistorialConsumibles()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal URL Monitor -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalUrlMonitor}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-link"></i> URL Web Monitor</h5>
                    <button type="button" class="btn-close btn-close-white" @click="cerrarModalUrlMonitor()"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">URL del Monitor Web</label>
                        <input type="url" class="form-control" v-model="urlMonitorTemp" placeholder="https://ejemplo.com/monitor">
                    </div>
                    <div v-if="urlMonitorTemp" class="mt-2">
                        <a :href="urlMonitorTemp" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-external-link"></i> Abrir URL
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalUrlMonitor()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarUrlMonitor()" :disabled="guardandoUrlMonitor">
                        <i v-if="guardandoUrlMonitor" class="fa fa-spinner fa-spin"></i>
                        <i v-else class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
export default {
    props: {
        client: { type: Object, required: true },
        shop: { type: Object, required: true },
        isLimitedUser: { type: Boolean, default: false }
    },
    data() {
        return {
            rentas: [],
            cargando: true,
            rentaSeleccionada: null,
            cargandoDetalle: false,

            // Form Renta
            modalFormRenta: 0,
            editandoRenta: false,
            guardandoRenta: false,
            formRenta: {
                id: null,
                client_id: null,
                cutoff: '',
                location_descripcion: '',
                location_address: '',
                location_phone: '',
                location_email: ''
            },

            // Form Equipo
            modalFormEquipo: 0,
            editandoEquipo: false,
            guardandoEquipo: false,
            formEquipo: {
                id: null,
                rent_id: null,
                trademark: '',
                model: '',
                serial_number: '',
                rent_price: 0,
                url_web_monitor: '',
                monochrome: false,
                pages_included_mono: 0,
                extra_page_cost_mono: 0,
                counter_mono: 0,
                color: false,
                pages_included_color: 0,
                extra_page_cost_color: 0,
                counter_color: 0
            },

            // Seleccionar Equipo
            modalSeleccionarEquipo: 0,
            equiposDisponibles: [],
            cargandoEquiposDisponibles: false,

            // Consumibles
            modalConsumible: 0,
            equipoConsumible: null,
            guardandoConsumible: false,
            formConsumible: { description: '', qty: 1, counter: 0, observation: '' },

            // Historial
            modalHistorialConsumibles: 0,
            historialConsumibles: [],
            cargandoConsumibles: false,

            // URL Monitor
            modalUrlMonitor: 0,
            equipoUrlMonitor: null,
            urlMonitorTemp: '',
            guardandoUrlMonitor: false
        }
    },
    methods: {
        cargarRentas() {
            this.cargando = true;
            axios.get(`/admin/clients/${this.client.id}/rents`).then(response => {
                if (response.data.ok) {
                    this.rentas = response.data.rents;
                }
                this.cargando = false;
            }).catch(() => { this.cargando = false; });
        },

        seleccionarRenta(renta) {
            this.rentaSeleccionada = renta;
            this.cargandoDetalle = true;
            axios.get(`/admin/rents/${renta.id}/details`).then(response => {
                if (response.data.ok) {
                    this.rentaSeleccionada = response.data.rent;
                }
                this.cargandoDetalle = false;
            }).catch(() => { this.cargandoDetalle = false; });
        },

        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        // RENTAS
        abrirModalNuevaRenta() {
            this.editandoRenta = false;
            this.formRenta = { id: null, client_id: this.client.id, cutoff: '', location_descripcion: '', location_address: '', location_phone: '', location_email: '' };
            this.modalFormRenta = 1;
        },
        editarRenta(renta) {
            this.editandoRenta = true;
            this.formRenta = { id: renta.id, client_id: renta.client_id, cutoff: renta.cutoff, location_descripcion: renta.location_descripcion || '', location_address: renta.location_address || '', location_phone: renta.location_phone || '', location_email: renta.location_email || '' };
            this.modalFormRenta = 1;
        },
        cerrarModalFormRenta() { this.modalFormRenta = 0; },
        guardarRenta() {
            this.guardandoRenta = true;
            let url = this.editandoRenta ? '/admin/rents/update' : '/admin/rents/store';
            let method = this.editandoRenta ? 'put' : 'post';
            axios[method](url, this.formRenta).then(response => {
                if (response.data.ok) {
                    Swal.fire('Exito', response.data.message, 'success');
                    this.cerrarModalFormRenta();
                    this.cargarRentas();
                    if (this.editandoRenta && this.rentaSeleccionada) {
                        this.seleccionarRenta(response.data.rent);
                    }
                }
                this.guardandoRenta = false;
            }).catch(error => {
                Swal.fire('Error', error.response?.data?.message || 'Error', 'error');
                this.guardandoRenta = false;
            });
        },
        darDeBajaRenta(renta) {
            Swal.fire({ title: 'Dar de Baja', text: '¿Dar de baja esta renta?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Si' }).then(result => {
                if (result.isConfirmed) {
                    axios.put(`/admin/rents/${renta.id}/inactive`).then(response => {
                        if (response.data.ok) {
                            Swal.fire('Exito', response.data.message, 'success');
                            this.cargarRentas();
                            this.rentaSeleccionada = null;
                        }
                    });
                }
            });
        },
        reactivarRenta(renta) {
            axios.put(`/admin/rents/${renta.id}/active`).then(response => {
                if (response.data.ok) {
                    Swal.fire('Exito', response.data.message, 'success');
                    this.cargarRentas();
                    this.seleccionarRenta(renta);
                }
            });
        },

        // EQUIPOS
        abrirModalNuevoEquipo() {
            this.editandoEquipo = false;
            this.formEquipo = { id: null, rent_id: this.rentaSeleccionada.id, trademark: '', model: '', serial_number: '', rent_price: 0, url_web_monitor: '', monochrome: false, pages_included_mono: 0, extra_page_cost_mono: 0, counter_mono: 0, color: false, pages_included_color: 0, extra_page_cost_color: 0, counter_color: 0 };
            this.modalFormEquipo = 1;
        },
        editarEquipo(equipo) {
            this.editandoEquipo = true;
            this.formEquipo = { id: equipo.id, rent_id: equipo.rent_id, trademark: equipo.trademark || '', model: equipo.model || '', serial_number: equipo.serial_number || '', rent_price: equipo.rent_price || 0, url_web_monitor: equipo.url_web_monitor || '', monochrome: !!equipo.monochrome, pages_included_mono: equipo.pages_included_mono || 0, extra_page_cost_mono: equipo.extra_page_cost_mono || 0, counter_mono: equipo.counter_mono || 0, color: !!equipo.color, pages_included_color: equipo.pages_included_color || 0, extra_page_cost_color: equipo.extra_page_cost_color || 0, counter_color: equipo.counter_color || 0 };
            this.modalFormEquipo = 1;
        },
        cerrarModalFormEquipo() { this.modalFormEquipo = 0; },
        guardarEquipo() {
            this.guardandoEquipo = true;
            let url = this.editandoEquipo ? '/admin/rents/details/update' : '/admin/rents/details/store';
            let method = this.editandoEquipo ? 'put' : 'post';
            axios[method](url, this.formEquipo).then(response => {
                if (response.data.ok) {
                    Swal.fire('Exito', response.data.message, 'success');
                    this.cerrarModalFormEquipo();
                    this.seleccionarRenta(this.rentaSeleccionada);
                    this.cargarRentas();
                }
                this.guardandoEquipo = false;
            }).catch(error => {
                Swal.fire('Error', error.response?.data?.message || 'Error', 'error');
                this.guardandoEquipo = false;
            });
        },
        liberarEquipo(equipo) {
            Swal.fire({ title: 'Liberar Equipo', text: '¿Liberar este equipo?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Si' }).then(result => {
                if (result.isConfirmed) {
                    axios.put(`/admin/rents/details/${equipo.id}/liberar`).then(response => {
                        if (response.data.ok) {
                            Swal.fire('Exito', response.data.message, 'success');
                            this.seleccionarRenta(this.rentaSeleccionada);
                            this.cargarRentas();
                        }
                    });
                }
            });
        },

        // Seleccionar del inventario
        abrirModalSeleccionarEquipo() {
            this.equiposDisponibles = [];
            this.cargandoEquiposDisponibles = true;
            this.modalSeleccionarEquipo = 1;
            axios.get('/admin/rents/equipments/available').then(response => {
                if (response.data.ok) { this.equiposDisponibles = response.data.equipments; }
                this.cargandoEquiposDisponibles = false;
            }).catch(() => { this.cargandoEquiposDisponibles = false; });
        },
        cerrarModalSeleccionarEquipo() { this.modalSeleccionarEquipo = 0; },
        asignarEquipo(equipo) {
            Swal.fire({ title: 'Asignar', text: `¿Asignar ${equipo.trademark} ${equipo.model}?`, icon: 'question', showCancelButton: true, confirmButtonText: 'Si' }).then(result => {
                if (result.isConfirmed) {
                    axios.post('/admin/rents/details/assign', { equipment_id: equipo.id, rent_id: this.rentaSeleccionada.id }).then(response => {
                        if (response.data.ok) {
                            Swal.fire('Exito', response.data.message, 'success');
                            this.cerrarModalSeleccionarEquipo();
                            this.seleccionarRenta(this.rentaSeleccionada);
                            this.cargarRentas();
                        }
                    });
                }
            });
        },

        // URL Monitor
        editarUrlMonitor(equipo) {
            this.equipoUrlMonitor = equipo;
            this.urlMonitorTemp = equipo.url_web_monitor || '';
            this.modalUrlMonitor = 1;
        },
        cerrarModalUrlMonitor() { this.modalUrlMonitor = 0; this.equipoUrlMonitor = null; },
        guardarUrlMonitor() {
            this.guardandoUrlMonitor = true;
            axios.put(`/admin/rents/details/${this.equipoUrlMonitor.id}/url-monitor`, { url_web_monitor: this.urlMonitorTemp }).then(response => {
                if (response.data.ok) {
                    Swal.fire('Exito', response.data.message, 'success');
                    this.equipoUrlMonitor.url_web_monitor = this.urlMonitorTemp;
                    this.cerrarModalUrlMonitor();
                }
                this.guardandoUrlMonitor = false;
            }).catch(error => {
                Swal.fire('Error', error.response?.data?.message || 'Error', 'error');
                this.guardandoUrlMonitor = false;
            });
        },

        // Consumibles
        abrirModalConsumible(equipo) {
            this.equipoConsumible = equipo;
            this.formConsumible = { description: '', qty: 1, counter: equipo.counter_mono || equipo.counter_color || 0, observation: '' };
            this.modalConsumible = 1;
        },
        cerrarModalConsumible() { this.modalConsumible = 0; this.equipoConsumible = null; },
        guardarConsumible() {
            this.guardandoConsumible = true;
            axios.post(`/admin/rents/details/${this.equipoConsumible.id}/consumables/store`, this.formConsumible).then(response => {
                if (response.data.ok) {
                    Swal.fire('Exito', response.data.message, 'success');
                    this.cerrarModalConsumible();
                }
                this.guardandoConsumible = false;
            }).catch(error => {
                Swal.fire('Error', error.response?.data?.message || 'Error', 'error');
                this.guardandoConsumible = false;
            });
        },
        verHistorialConsumibles(equipo) {
            this.historialConsumibles = [];
            this.cargandoConsumibles = true;
            this.modalHistorialConsumibles = 1;
            axios.get(`/admin/rents/details/${equipo.id}/consumables`).then(response => {
                if (response.data.ok) { this.historialConsumibles = response.data.consumables; }
                this.cargandoConsumibles = false;
            }).catch(() => { this.cargandoConsumibles = false; });
        },
        cerrarModalHistorialConsumibles() { this.modalHistorialConsumibles = 0; }
    },
    mounted() {
        this.cargarRentas();
    }
}
</script>

<style scoped>
.mostrar {
    display: list-item !important;
    opacity: 1 !important;
    position: fixed !important;
    background-color: #3c29297a !important;
    overflow: scroll;
}
.list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>
