<template>
<div>
    <div class="container-fluid">
        <!-- Tabla de clientes -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fa fa-users"></i> Clientes De {{ shop.name }}
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
                    <button type="button" @click="abrirModal('client','registrar')" class="btn btn-primary">
                        <i class="icon-plus"></i>&nbsp;Nuevo Cliente
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-8">
                        <div class="input-group">
                            <select class="form-control col-md-2" v-model="criterio">
                                <option value="name">Nombre</option>
                                <option value="email">Email</option>
                                <option value="movil">Teléfono</option>
                                <option value="company">Empresa</option>
                            </select>
                            <input type="text" v-model="buscar" class="form-control" placeholder="Texto a buscar" @keyup.enter="loadClients(1,buscar,criterio,estatus)">
                            <select class="form-control col-md-2" v-model="estatus">
                                <option value="">TODOS</option>
                                <option value="active">ACTIVOS</option>
                                <option value="inactive">INACTIVOS</option>
                            </select>
                            <button type="submit" @click="loadClients(1,buscar,criterio,estatus)" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>

                <!-- VISTA CARDS -->
                <div class="row mt-3" v-if="vistaActual === 'cards'">
                    <div class="col-md-4 col-lg-3 mb-4" v-for="client in arrayClients" :key="client.id">
                        <div class="card client-card h-100" :class="{'inactive-card': !client.active}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="client-id">#{{ client.id }}</span>
                                    <span v-if="client.active" class="badge badge-success ml-2">Activo</span>
                                    <span v-else class="badge badge-danger ml-2">Inactivo</span>
                                    <span v-if="client.level" class="badge badge-info ml-1">Nv{{ client.level }}</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-dark dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModalDetalle(client)">
                                            <i class="fa fa-eye text-primary"></i> Ver Detalle
                                        </a></li>
                                        <li v-if="!isLimitedUser"><a class="dropdown-item" href="#" @click.prevent="abrirModal('client','actualizar_datos', client)">
                                            <i class="fa fa-edit text-info"></i> Editar Cliente
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModalDirecciones(client)">
                                            <i class="fa fa-map-marker text-warning"></i> Gestionar Direcciones
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" :href="`/admin/clients/${client.id}/contracts`">
                                            <i class="fa fa-folder-open text-primary"></i> Ver Contratos
                                        </a></li>
                                        <li><a class="dropdown-item" :href="`/admin/clients/${client.id}/assign-contract`">
                                            <i class="fa fa-plus text-success"></i> Crear Contrato
                                        </a></li>
                                        <li><a class="dropdown-item" :href="`/admin/clients/${client.id}/receipts`">
                                            <i class="fa fa-receipt text-info"></i> Ver Recibos
                                        </a></li>
                                        <li><a class="dropdown-item" :href="`/admin/clients/${client.id}/rentas`">
                                            <i class="fa fa-list text-secondary"></i> Ver Rentas
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <template v-if="!isLimitedUser">
                                            <template v-if="client.active">
                                                <li><a class="dropdown-item" href="#" @click.prevent="actualizarAInactivo(client.id)">
                                                    <i class="fa fa-toggle-off text-danger"></i> Desactivar Cliente
                                                </a></li>
                                            </template>
                                            <template v-else>
                                                <li><a class="dropdown-item" href="#" @click.prevent="actualizarAActivo(client.id)">
                                                    <i class="fa fa-toggle-on text-success"></i> Activar Cliente
                                                </a></li>
                                            </template>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body" @click="abrirModalDetalle(client)" style="cursor: pointer;">
                                <h5 class="card-title client-name">
                                    <i class="fa fa-user text-primary"></i>
                                    {{ client.name }}
                                </h5>
                                <div class="client-info">
                                    <div class="info-item" v-if="client.email">
                                        <i class="fa fa-envelope text-muted"></i>
                                        <span>{{ client.email }}</span>
                                    </div>
                                    <div class="info-item" v-if="client.company">
                                        <i class="fa fa-building text-muted"></i>
                                        <span>{{ client.company }}</span>
                                    </div>
                                    <div class="info-item" v-if="client.movil">
                                        <i class="fa fa-phone text-muted"></i>
                                        <span>{{ client.movil }}</span>
                                    </div>
                                    <div class="info-item" v-if="client.address">
                                        <i class="fa fa-map-marker text-muted"></i>
                                        <span>{{ truncateText(client.address, 50) }}</span>
                                    </div>
                                </div>
                                <!-- Indicadores visuales -->
                                <div class="client-indicators mt-2" v-if="client.location_latitude || client.user_id || client.location_image || client.rents_count > 0">
                                    <a v-if="client.rents_count > 0" :href="`/admin/clients/${client.id}/rentas`" class="badge bg-warning text-dark me-1 text-decoration-none" title="Ver rentas">
                                        <i class="fa fa-list"></i> {{ client.rents_count }}
                                    </a>
                                    <span v-if="client.location_latitude" class="badge bg-success me-1" title="Tiene ubicación GPS">
                                        <i class="fa fa-map-marker"></i>
                                    </span>
                                    <span v-if="client.user_id" class="badge bg-primary me-1" title="Tiene usuario APP">
                                        <i class="fa fa-mobile"></i>
                                    </span>
                                    <span v-if="client.location_image" class="badge bg-info me-1" title="Tiene imagen de referencia">
                                        <i class="fa fa-image"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <i class="fa fa-clock-o"></i>
                                    Cliente #{{ client.id }}
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
                                <th>Email</th>
                                <th>Empresa</th>
                                <th>Teléfono</th>
                                <th>Nivel</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="client in arrayClients" :key="client.id" :class="{'table-secondary': !client.active}">
                                <td><strong>{{ client.id }}</strong></td>
                                <td>
                                    {{ client.name }}
                                    <a v-if="client.rents_count > 0" :href="`/admin/clients/${client.id}/rentas`" class="badge bg-warning text-dark ms-1 text-decoration-none" :title="client.rents_count + ' rentas'"><i class="fa fa-list"></i> {{ client.rents_count }}</a>
                                    <span v-if="client.location_latitude" class="badge bg-success ms-1" title="GPS"><i class="fa fa-map-marker"></i></span>
                                    <span v-if="client.user_id" class="badge bg-primary ms-1" title="Usuario APP"><i class="fa fa-mobile"></i></span>
                                </td>
                                <td>{{ client.email || '-' }}</td>
                                <td>{{ client.company || '-' }}</td>
                                <td>{{ client.movil || '-' }}</td>
                                <td><span class="badge badge-info">Nv{{ client.level || 1 }}</span></td>
                                <td>
                                    <span v-if="client.active" class="badge badge-success">Activo</span>
                                    <span v-else class="badge badge-danger">Inactivo</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-primary btn-sm" @click="abrirModalDetalle(client)" title="Ver Detalle">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button v-if="!isLimitedUser" class="btn btn-info btn-sm" @click="abrirModal('client','actualizar_datos', client)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" @click="abrirModalDirecciones(client)" title="Direcciones">
                                            <i class="fa fa-map-marker"></i>
                                        </button>
                                        <a class="btn btn-secondary btn-sm" :href="`/admin/clients/${client.id}/contracts`" title="Contratos">
                                            <i class="fa fa-folder-open"></i>
                                        </a>
                                        <template v-if="!isLimitedUser">
                                            <button v-if="client.active" class="btn btn-danger btn-sm" @click="actualizarAInactivo(client.id)" title="Desactivar">
                                                <i class="fa fa-toggle-off"></i>
                                            </button>
                                            <button v-else class="btn btn-success btn-sm" @click="actualizarAActivo(client.id)" title="Activar">
                                                <i class="fa fa-toggle-on"></i>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mostrar mensaje si no hay clientes -->
                <div v-if="arrayClients.length === 0" class="text-center py-5">
                    <i class="fa fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron clientes con los criterios de búsqueda.</p>
                </div>
                <nav>
                    <ul class="pagination">
                        <li class="page-item" v-if="pagination.current_page > 1">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page-1,buscar,criterio)">Ant</a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page==isActived ? 'active':'']">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(page,buscar,criterio)" v-text="page"></a>
                        </li>
                        <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page+1,buscar,criterio)">Sig</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Componente de direcciones -->
    <client-addresses-component ref="clientAddresses"></client-addresses-component>

    <!-- Modal Ver Detalle Cliente -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalDetalle}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title"><i class="fa fa-user"></i> Detalle del Cliente</h4>
                    <button type="button" class="close text-white" @click="cerrarModalDetalle()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" v-if="clienteDetalle">
                    <div class="row">
                        <!-- Columna izquierda: Info básica -->
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
                                                <td>#{{ clienteDetalle.id }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Nombre:</strong></td>
                                                <td>{{ clienteDetalle.name }}</td>
                                            </tr>
                                            <tr v-if="clienteDetalle.company">
                                                <td><strong>Empresa:</strong></td>
                                                <td>{{ clienteDetalle.company }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Nivel:</strong></td>
                                                <td><span class="badge badge-info">Nivel {{ clienteDetalle.level || 1 }}</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Estado:</strong></td>
                                                <td>
                                                    <span v-if="clienteDetalle.active" class="badge badge-success">Activo</span>
                                                    <span v-else class="badge badge-danger">Inactivo</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Card Contacto -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fa fa-phone"></i> Contacto
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr v-if="clienteDetalle.email">
                                                <td><strong>Email:</strong></td>
                                                <td><a :href="'mailto:' + clienteDetalle.email">{{ clienteDetalle.email }}</a></td>
                                            </tr>
                                            <tr v-if="clienteDetalle.movil">
                                                <td><strong>Móvil:</strong></td>
                                                <td><a :href="'tel:' + clienteDetalle.movil">{{ clienteDetalle.movil }}</a></td>
                                            </tr>
                                            <tr v-if="clienteDetalle.phone">
                                                <td><strong>Teléfono:</strong></td>
                                                <td><a :href="'tel:' + clienteDetalle.phone">{{ clienteDetalle.phone }}</a></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p v-if="!clienteDetalle.email && !clienteDetalle.movil && !clienteDetalle.phone" class="text-muted mb-0">
                                        Sin información de contacto
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Columna derecha -->
                        <div class="col-md-6">
                            <!-- Card Dirección -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fa fa-map-marker"></i> Dirección Principal
                                </div>
                                <div class="card-body">
                                    <p v-if="clienteDetalle.address">{{ clienteDetalle.address }}</p>
                                    <p v-if="clienteDetalle.district || clienteDetalle.city || clienteDetalle.state">
                                        {{ [clienteDetalle.district, clienteDetalle.city, clienteDetalle.state].filter(Boolean).join(', ') }}
                                    </p>
                                    <p v-if="clienteDetalle.zip_code"><strong>C.P.:</strong> {{ clienteDetalle.zip_code }}</p>
                                    <p v-if="!clienteDetalle.address" class="text-muted mb-0">Sin dirección registrada</p>

                                    <!-- Ubicación GPS -->
                                    <div v-if="clienteDetalle.location_latitude && clienteDetalle.location_longitude" class="mt-2 pt-2 border-top">
                                        <strong><i class="fa fa-crosshairs text-success"></i> GPS:</strong>
                                        <span class="ms-2">{{ clienteDetalle.location_latitude }}, {{ clienteDetalle.location_longitude }}</span>
                                        <a :href="getGoogleMapsUrl(clienteDetalle.location_latitude, clienteDetalle.location_longitude)"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-primary ms-2">
                                            <i class="fa fa-external-link"></i> Ver en Mapa
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Imagen de Referencia -->
                            <div class="card mb-3" v-if="clienteDetalle.location_image">
                                <div class="card-header bg-light">
                                    <i class="fa fa-image"></i> Imagen de Referencia
                                </div>
                                <div class="card-body text-center">
                                    <img :src="getImageUrl(clienteDetalle.location_image)"
                                         alt="Imagen de referencia"
                                         class="img-fluid rounded"
                                         style="max-height: 200px; cursor: pointer;"
                                         @click="abrirImagenCompleta(clienteDetalle.location_image)">
                                </div>
                            </div>

                            <!-- Card Usuario APP -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fa fa-mobile"></i> Usuario APP
                                </div>
                                <div class="card-body">
                                    <div v-if="clienteDetalle.user_id">
                                        <span class="badge badge-success"><i class="fa fa-check"></i> Tiene usuario APP</span>
                                        <p class="mt-2 mb-0">ID Usuario: {{ clienteDetalle.user_id }}</p>
                                    </div>
                                    <div v-else>
                                        <span class="badge badge-secondary">Sin usuario APP</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Observaciones -->
                            <div class="card mb-3" v-if="clienteDetalle.observations || clienteDetalle.detail || clienteDetalle.reference">
                                <div class="card-header bg-light">
                                    <i class="fa fa-sticky-note"></i> Notas
                                </div>
                                <div class="card-body">
                                    <div v-if="clienteDetalle.reference">
                                        <strong>Referencia:</strong>
                                        <p>{{ clienteDetalle.reference }}</p>
                                    </div>
                                    <div v-if="clienteDetalle.detail">
                                        <strong>Detalle:</strong>
                                        <p>{{ clienteDetalle.detail }}</p>
                                    </div>
                                    <div v-if="clienteDetalle.observations">
                                        <strong>Observaciones:</strong>
                                        <p class="mb-0">{{ clienteDetalle.observations }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalDetalle()">Cerrar</button>
                    <button type="button" class="btn btn-success" @click="abrirModalUserApp(clienteDetalle)">
                        <i class="fa fa-mobile"></i> {{ clienteDetalle && clienteDetalle.user_id ? 'Editar' : 'Crear' }} Usuario APP
                    </button>
                    <button type="button" class="btn btn-warning" @click="abrirModalGPS(clienteDetalle)">
                        <i class="fa fa-map-marker"></i> GPS
                    </button>
                    <button type="button" class="btn btn-info" @click="abrirModalImagen(clienteDetalle)">
                        <i class="fa fa-image"></i> Imagen
                    </button>
                    <button v-if="!isLimitedUser && clienteDetalle" type="button" class="btn btn-primary" @click="cerrarModalDetalle(); abrirModal('client','actualizar_datos', clienteDetalle)">
                        <i class="fa fa-edit"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal agregar/actualizar -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modal}" role="dialog" aria-labelledby="myModalLabel" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-primary modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" v-text="tituloModal"></h4>
                    <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form v-on:submit.prevent action="" method="post" enctype="multipart/form-data" class="form-horizontal">
                        <div v-show="errorClient" class="form-group row div-error">
                            <div class="container-fluid">
                                <div class="alert alert-danger text-center">
                                    <div v-for="error in errorMostrarMsjClient" :key="error" v-text="error"></div>
                                </div>
                            </div>
                        </div>
                        <p><em><strong class="text text-danger">* Campos obligatorios</strong></em></p>

                        <div v-if="tipoAccion==1 || tipoAccion==2">
                            <div class="form-group row">
                                <label for="name" class="col-md-4 col-form-label text-md-right"><strong class="text text-danger">*</strong> Nombre</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="name" placeholder="Nombre completo" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="email" class="col-md-4 col-form-label text-md-right"><strong class="text text-danger">*</strong> Email</label>
                                <div class="col-md-6">
                                    <input type="email" class="form-control" v-model="email" placeholder="correo@ejemplo.com" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="company" class="col-md-4 col-form-label text-md-right">Empresa</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="company" placeholder="Nombre de la empresa">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="movil" class="col-md-4 col-form-label text-md-right">Teléfono Móvil</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="movil" placeholder="Número de teléfono móvil">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="phone" class="col-md-4 col-form-label text-md-right">Teléfono Fijo</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="phone" placeholder="Número de teléfono fijo">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="address" class="col-md-4 col-form-label text-md-right">Dirección</label>
                                <div class="col-md-6">
                                    <textarea class="form-control" v-model="address" placeholder="Dirección completa" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="city" class="col-md-4 col-form-label text-md-right">Ciudad</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="city" placeholder="Ciudad">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="state" class="col-md-4 col-form-label text-md-right">Estado</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" v-model="state" placeholder="Estado">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="level" class="col-md-4 col-form-label text-md-right">Nivel</label>
                                <div class="col-md-6">
                                    <select class="form-control" v-model="level">
                                        <option value="1">Nivel 1 - Básico</option>
                                        <option value="2">Nivel 2 - Premium</option>
                                        <option value="3">Nivel 3 - VIP</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="observations" class="col-md-4 col-form-label text-md-right">Observaciones</label>
                                <div class="col-md-6">
                                    <textarea class="form-control" v-model="observations" placeholder="Notas u observaciones" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cerrar</button>
                    <button type="button" v-if="tipoAccion==1" class="btn btn-primary" @click="registrar()">Guardar</button>
                    <button type="button" v-if="tipoAccion==2" class="btn btn-primary" @click="actualizarDatos()">Actualizar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Usuario APP -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalUserApp}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h4 class="modal-title"><i class="fa fa-mobile"></i> {{ userAppMode === 'create' ? 'Crear' : 'Editar' }} Usuario APP</h4>
                    <button type="button" class="close text-white" @click="cerrarModalUserApp()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" v-if="clienteUserApp">
                    <!-- Modo Crear -->
                    <div v-if="userAppMode === 'create'">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> Se creará un usuario para que el cliente acceda a la app móvil.
                        </div>
                        <div class="form-group">
                            <label><strong>Usuario</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" v-model="userAppUsername" placeholder="nombre_usuario" @input="userAppUsername = userAppUsername.toLowerCase().replace(/[^a-z0-9_]/g, '')">
                                <span class="input-group-text">@{{ shop.slug }}.app</span>
                            </div>
                            <small class="text-muted">Solo letras minúsculas, números y guión bajo</small>
                        </div>
                        <div class="form-group mt-3">
                            <label><strong>Contraseña</strong></label>
                            <input type="password" class="form-control" v-model="userAppPassword" placeholder="Mínimo 8 caracteres">
                            <small class="text-muted">Debe contener letras y números</small>
                        </div>
                        <div class="form-group mt-3">
                            <label><strong>Confirmar Contraseña</strong></label>
                            <input type="password" class="form-control" v-model="userAppPasswordConfirm" placeholder="Repetir contraseña">
                        </div>
                    </div>

                    <!-- Modo Editar -->
                    <div v-else>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <i class="fa fa-envelope"></i> Email Actual
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <code>{{ userAppCurrentEmail }}</code>
                                    <button class="btn btn-sm btn-outline-secondary" @click="copyToClipboard(userAppCurrentEmail)">
                                        <i class="fa fa-copy"></i> Copiar
                                    </button>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label>Nuevo Email (opcional)</label>
                                    <input type="email" class="form-control" v-model="userAppNewEmail" placeholder="nuevo@email.com">
                                </div>
                                <button class="btn btn-primary btn-sm mt-2" @click="actualizarEmailUserApp()" :disabled="!userAppNewEmail">
                                    <i class="fa fa-save"></i> Actualizar Email
                                </button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header bg-light">
                                <i class="fa fa-lock"></i> Cambiar Contraseña
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Nueva Contraseña</label>
                                    <input type="password" class="form-control" v-model="userAppPassword" placeholder="Mínimo 8 caracteres">
                                </div>
                                <div class="form-group mt-2">
                                    <label>Confirmar Contraseña</label>
                                    <input type="password" class="form-control" v-model="userAppPasswordConfirm" placeholder="Repetir contraseña">
                                </div>
                                <button class="btn btn-warning btn-sm mt-2" @click="resetearPasswordUserApp()" :disabled="!userAppPassword || !userAppPasswordConfirm">
                                    <i class="fa fa-refresh"></i> Resetear Contraseña
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalUserApp()">Cerrar</button>
                    <button v-if="userAppMode === 'create'" type="button" class="btn btn-success" @click="crearUserApp()" :disabled="!userAppUsername || !userAppPassword || !userAppPasswordConfirm">
                        <i class="fa fa-save"></i> Crear Usuario APP
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal GPS -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalGPS}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h4 class="modal-title"><i class="fa fa-map-marker"></i> Ubicación GPS</h4>
                    <button type="button" class="close" @click="cerrarModalGPS()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" v-if="clienteGPS">
                    <div v-if="clienteGPS.location_latitude && clienteGPS.location_longitude" class="alert alert-success">
                        <i class="fa fa-check-circle"></i> Este cliente tiene ubicación GPS guardada
                    </div>
                    <div v-else class="alert alert-secondary">
                        <i class="fa fa-info-circle"></i> Este cliente no tiene ubicación GPS
                    </div>

                    <div class="form-group">
                        <label><strong>Latitud</strong></label>
                        <input type="number" step="any" class="form-control" v-model="gpsLatitude" placeholder="Ej: 20.659698">
                    </div>
                    <div class="form-group mt-3">
                        <label><strong>Longitud</strong></label>
                        <input type="number" step="any" class="form-control" v-model="gpsLongitude" placeholder="Ej: -103.349609">
                    </div>

                    <div class="mt-3" v-if="clienteGPS.location_latitude && clienteGPS.location_longitude">
                        <a :href="getGoogleMapsUrl(clienteGPS.location_latitude, clienteGPS.location_longitude)"
                           target="_blank"
                           class="btn btn-outline-primary">
                            <i class="fa fa-external-link"></i> Ver en Google Maps
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalGPS()">Cerrar</button>
                    <button v-if="!isLimitedUser && clienteGPS && clienteGPS.location_latitude" type="button" class="btn btn-danger" @click="eliminarUbicacion()">
                        <i class="fa fa-trash"></i> Eliminar GPS
                    </button>
                    <button type="button" class="btn btn-success" @click="guardarUbicacion()" :disabled="!gpsLatitude || !gpsLongitude">
                        <i class="fa fa-save"></i> Guardar GPS
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Imagen -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalImagen}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h4 class="modal-title"><i class="fa fa-image"></i> Imagen de Referencia</h4>
                    <button type="button" class="close text-white" @click="cerrarModalImagen()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" v-if="clienteImagen">
                    <!-- Imagen actual -->
                    <div v-if="clienteImagen.location_image" class="text-center mb-3">
                        <img :src="getImageUrl(clienteImagen.location_image)"
                             alt="Imagen de referencia"
                             class="img-fluid rounded"
                             style="max-height: 300px; cursor: pointer;"
                             @click="abrirImagenCompleta(clienteImagen.location_image)">
                        <p class="text-muted mt-2"><small>Click para ver en tamaño completo</small></p>
                    </div>
                    <div v-else class="alert alert-secondary text-center">
                        <i class="fa fa-image fa-2x mb-2"></i>
                        <p class="mb-0">Este cliente no tiene imagen de referencia</p>
                    </div>

                    <!-- Subir nueva imagen -->
                    <div class="form-group mt-3">
                        <label><strong>{{ clienteImagen.location_image ? 'Cambiar imagen' : 'Subir imagen' }}</strong></label>
                        <input type="file" class="form-control" @change="onImagenSeleccionada" accept="image/*" ref="inputImagen">
                        <small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 5MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalImagen()">Cerrar</button>
                    <button v-if="!isLimitedUser && clienteImagen && clienteImagen.location_image" type="button" class="btn btn-danger" @click="eliminarImagen()">
                        <i class="fa fa-trash"></i> Eliminar
                    </button>
                    <button type="button" class="btn btn-success" @click="subirImagen()" :disabled="!imagenSeleccionada">
                        <i class="fa fa-upload"></i> Subir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Rentas del Cliente -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalRentas}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h4 class="modal-title">
                        <i class="fa fa-list"></i> Rentas: {{ clienteRentas ? clienteRentas.name : '' }}
                    </h4>
                    <button type="button" class="close text-white" @click="cerrarModalRentas()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <span class="text-muted">{{ arrayRentas.length }} rentas encontradas</span>
                        </div>
                        <button class="btn btn-success btn-sm" @click="abrirModalNuevaRenta()">
                            <i class="fa fa-plus"></i> Nueva Renta
                        </button>
                    </div>
                    <div v-if="cargandoRentas" class="text-center py-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando rentas...</p>
                    </div>
                    <div v-else-if="arrayRentas.length === 0" class="text-center py-4">
                        <i class="fa fa-folder-open fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Este cliente no tiene rentas</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Corte</th>
                                    <th>Ubicacion</th>
                                    <th>Equipos</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="renta in arrayRentas" :key="renta.id" :class="{'table-secondary': !renta.active}">
                                    <td><strong>#{{ renta.id }}</strong></td>
                                    <td>
                                        <span class="badge bg-info">{{ renta.cutoff || '-' }}</span>
                                    </td>
                                    <td>
                                        <span v-if="renta.location_descripcion" class="d-block fw-bold">{{ renta.location_descripcion }}</span>
                                        <small class="text-muted">{{ renta.location_address || '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ renta.rent_detail_count || (renta.rent_detail ? renta.rent_detail.length : 0) }}</span>
                                    </td>
                                    <td>
                                        <span v-if="renta.active" class="badge bg-success">Activa</span>
                                        <span v-else class="badge bg-danger">Inactiva</span>
                                    </td>
                                    <td><small>{{ formatDate(renta.created_at) }}</small></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-info btn-sm" @click="verDetalleRenta(renta)" title="Ver Detalle">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm" @click="editarRenta(renta)" title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button v-if="renta.active" class="btn btn-danger btn-sm" @click="darDeBajaRenta(renta)" title="Dar de Baja">
                                                <i class="fa fa-toggle-off"></i>
                                            </button>
                                            <button v-else class="btn btn-success btn-sm" @click="reactivarRenta(renta)" title="Reactivar">
                                                <i class="fa fa-toggle-on"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalRentas()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Renta con Equipos -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalDetalleRenta}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title" v-if="rentaSeleccionada">
                        <i class="fa fa-file-contract"></i> Renta #{{ rentaSeleccionada.id }}
                    </h4>
                    <button type="button" class="close text-white" @click="cerrarModalDetalleRenta()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" v-if="rentaSeleccionada">
                    <div v-if="cargandoDetalleRenta" class="text-center py-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando detalle...</p>
                    </div>
                    <template v-else>
                        <!-- Info de la Renta -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Dia de Corte:</strong>
                                        <span class="badge bg-info ms-2">{{ rentaSeleccionada.cutoff }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Descripcion:</strong>
                                        <span class="ms-2">{{ rentaSeleccionada.location_descripcion || '-' }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Direccion:</strong>
                                        <span class="ms-2">{{ rentaSeleccionada.location_address || '-' }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Contacto:</strong>
                                        <span class="ms-2">{{ rentaSeleccionada.location_phone || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Equipos -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fa fa-print"></i> Equipos
                                <span class="badge bg-primary">{{ rentaSeleccionada.rent_detail ? rentaSeleccionada.rent_detail.length : 0 }}</span>
                            </h5>
                            <div class="dropdown">
                                <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fa fa-plus"></i> Agregar Equipo
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" @click.prevent="abrirModalNuevoEquipo()">
                                        <i class="fa fa-plus-circle"></i> Crear Nuevo
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" @click.prevent="abrirModalSeleccionarEquipo()">
                                        <i class="fa fa-check-square"></i> Seleccionar del Inventario
                                    </a></li>
                                </ul>
                            </div>
                        </div>

                        <div v-if="!rentaSeleccionada.rent_detail || rentaSeleccionada.rent_detail.length === 0" class="text-center py-4 text-muted">
                            <i class="fa fa-print fa-2x"></i>
                            <p class="mt-2">No hay equipos asignados a esta renta</p>
                        </div>

                        <!-- Acordeon de Equipos -->
                        <div class="accordion" id="acordeonEquipos">
                            <div class="accordion-item" v-for="(equipo, index) in rentaSeleccionada.rent_detail" :key="equipo.id">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button"
                                            :data-bs-toggle="'collapse'"
                                            :data-bs-target="'#equipo' + equipo.id">
                                        <div class="d-flex justify-content-between w-100 me-3">
                                            <span>
                                                <strong>{{ equipo.trademark }}</strong> - {{ equipo.model }}
                                            </span>
                                            <span class="badge bg-success">${{ equipo.rent_price }}</span>
                                        </div>
                                    </button>
                                </h2>
                                <div :id="'equipo' + equipo.id" class="accordion-collapse collapse" data-bs-parent="#acordeonEquipos">
                                    <div class="accordion-body">
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <strong>No. Serie:</strong> {{ equipo.serial_number || '-' }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Descripcion:</strong> {{ equipo.description || '-' }}
                                            </div>
                                            <div class="col-md-4" v-if="equipo.url_web_monitor">
                                                <a :href="equipo.url_web_monitor" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="fa fa-external-link"></i> URL Monitor
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Config Blanco y Negro -->
                                        <div v-if="equipo.monochrome" class="card mb-2">
                                            <div class="card-header bg-secondary text-white py-1">
                                                <strong>Blanco y Negro</strong>
                                            </div>
                                            <div class="card-body py-2">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <small class="text-muted">Pag. Incluidas:</small>
                                                        <strong>{{ equipo.pages_included_mono }}</strong>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted">Costo Extra:</small>
                                                        <strong>${{ equipo.extra_page_cost_mono }}</strong>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted">Contador:</small>
                                                        <strong class="text-primary">{{ equipo.counter_mono }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Config Color -->
                                        <div v-if="equipo.color" class="card mb-2">
                                            <div class="card-header bg-info text-white py-1">
                                                <strong>Color</strong>
                                            </div>
                                            <div class="card-body py-2">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <small class="text-muted">Pag. Incluidas:</small>
                                                        <strong>{{ equipo.pages_included_color }}</strong>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted">Costo Extra:</small>
                                                        <strong>${{ equipo.extra_page_cost_color }}</strong>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <small class="text-muted">Contador:</small>
                                                        <strong class="text-info">{{ equipo.counter_color }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Botones de Acciones -->
                                        <div class="d-flex gap-2 mt-3">
                                            <button class="btn btn-outline-secondary btn-sm" @click="abrirModalConsumible(equipo)">
                                                <i class="fa fa-plus-circle"></i> Consumible
                                            </button>
                                            <button class="btn btn-outline-info btn-sm" @click="verHistorialConsumibles(equipo)">
                                                <i class="fa fa-history"></i> Historial
                                            </button>
                                            <button class="btn btn-outline-warning btn-sm" @click="editarEquipo(equipo)">
                                                <i class="fa fa-edit"></i> Editar
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" @click="liberarEquipo(equipo)">
                                                <i class="fa fa-unlink"></i> Liberar
                                            </button>
                                            <button class="btn btn-outline-primary btn-sm" @click="editarUrlMonitor(equipo)">
                                                <i class="fa fa-link"></i> URL Monitor
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalDetalleRenta()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar Renta -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalFormRenta}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h4 class="modal-title">
                        <i class="fa fa-file-contract"></i> {{ editandoRenta ? 'Editar Renta' : 'Nueva Renta' }}
                    </h4>
                    <button type="button" class="close text-white" @click="cerrarModalFormRenta()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="guardarRenta()">
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
                            <input type="text" class="form-control" v-model="formRenta.location_address" placeholder="Direccion de la renta">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefono</label>
                                <input type="text" class="form-control" v-model="formRenta.location_phone" placeholder="Telefono contacto">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" v-model="formRenta.location_email" placeholder="Email contacto">
                            </div>
                        </div>
                    </form>
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
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalFormEquipo}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h4 class="modal-title">
                        <i class="fa fa-print"></i> {{ editandoEquipo ? 'Editar Equipo' : 'Nuevo Equipo' }}
                    </h4>
                    <button type="button" class="close" @click="cerrarModalFormEquipo()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="guardarEquipo()">
                        <!-- Info Basica -->
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
                                <label class="form-label">Descripcion</label>
                                <input type="text" class="form-control" v-model="formEquipo.description">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL Web Monitor</label>
                            <input type="url" class="form-control" v-model="formEquipo.url_web_monitor" placeholder="https://...">
                        </div>

                        <!-- Config Blanco y Negro -->
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><strong>Blanco y Negro</strong></span>
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" v-model="formEquipo.monochrome">
                                </div>
                            </div>
                            <div class="card-body" v-if="formEquipo.monochrome">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Pag. Incluidas</label>
                                        <input type="number" class="form-control" v-model="formEquipo.pages_included_mono">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Costo Pag. Extra</label>
                                        <input type="number" step="0.01" class="form-control" v-model="formEquipo.extra_page_cost_mono">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Contador Actual</label>
                                        <input type="number" class="form-control" v-model="formEquipo.counter_mono">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Config Color -->
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><strong>Color</strong></span>
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" v-model="formEquipo.color">
                                </div>
                            </div>
                            <div class="card-body" v-if="formEquipo.color">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Pag. Incluidas</label>
                                        <input type="number" class="form-control" v-model="formEquipo.pages_included_color">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Costo Pag. Extra</label>
                                        <input type="number" step="0.01" class="form-control" v-model="formEquipo.extra_page_cost_color">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Contador Actual</label>
                                        <input type="number" class="form-control" v-model="formEquipo.counter_color">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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

    <!-- Modal Seleccionar Equipo del Inventario -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalSeleccionarEquipo}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h4 class="modal-title"><i class="fa fa-check-square"></i> Seleccionar Equipo del Inventario</h4>
                    <button type="button" class="close text-white" @click="cerrarModalSeleccionarEquipo()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="cargandoEquiposDisponibles" class="text-center py-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando equipos disponibles...</p>
                    </div>
                    <div v-else-if="equiposDisponibles.length === 0" class="text-center py-4 text-muted">
                        <i class="fa fa-box-open fa-2x"></i>
                        <p class="mt-2">No hay equipos disponibles en inventario</p>
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

    <!-- Modal Agregar Consumible -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalConsumible}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h4 class="modal-title"><i class="fa fa-plus-circle"></i> Agregar Consumible</h4>
                    <button type="button" class="close text-white" @click="cerrarModalConsumible()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="guardarConsumible()">
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalConsumible()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarConsumible()" :disabled="guardandoConsumible">
                        <i v-if="guardandoConsumible" class="fa fa-spinner fa-spin"></i>
                        <i v-else class="fa fa-save"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Historial Consumibles -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalHistorialConsumibles}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h4 class="modal-title"><i class="fa fa-history"></i> Historial de Consumibles</h4>
                    <button type="button" class="close text-white" @click="cerrarModalHistorialConsumibles()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
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
    <div class="modal fade" tabindex="-1" :class="{'mostrar':modalUrlMonitor}" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title"><i class="fa fa-link"></i> URL Web Monitor</h4>
                    <button type="button" class="close text-white" @click="cerrarModalUrlMonitor()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
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
                        <i v-else class="fa fa-save"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
</template>

<script>
import ClientAddressesComponent from './ClientAddressesComponent.vue';

export default {
        components: {
            ClientAddressesComponent
        },
        props: {
            shop: {
                type: Object,
                required: true
            },
            isLimitedUser: {
                type: Boolean,
                default: false
            }
        },
        data(){
            return {
                arrayClients:[],
                pagination:{
                    'total':0,
                    'current_page':0,
                    'per_page':0,
                    'last_page':0,
                    'from':0,
                    'to':0
                },
                offset:3,
                criterio:'name',
                buscar:'',
                estatus:'active',

                // Campos del cliente
                client_id:0,
                name:'',
                email:'',
                company:'',
                movil:'',
                phone:'',
                address:'',
                city:'',
                state:'',
                level:1,
                observations:'',

                errors:[],
                modal:0,
                tituloModal:'',
                tipoAccion:0,
                errorClient:0,
                errorMostrarMsjClient:[],

                // Modal detalle
                modalDetalle: 0,
                clienteDetalle: null,

                // Modal Usuario APP
                modalUserApp: 0,
                clienteUserApp: null,
                userAppMode: 'create',
                userAppUsername: '',
                userAppPassword: '',
                userAppPasswordConfirm: '',
                userAppCurrentEmail: '',
                userAppNewEmail: '',

                // Modal GPS
                modalGPS: 0,
                clienteGPS: null,
                gpsLatitude: '',
                gpsLongitude: '',

                // Modal Imagen
                modalImagen: 0,
                clienteImagen: null,
                imagenSeleccionada: null,

                // Modal Rentas
                modalRentas: 0,
                clienteRentas: null,
                arrayRentas: [],
                cargandoRentas: false,

                // Modal Detalle Renta
                modalDetalleRenta: 0,
                rentaSeleccionada: null,
                cargandoDetalleRenta: false,

                // Modal Form Renta (crear/editar)
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

                // Modal Form Equipo (crear/editar)
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
                    description: '',
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

                // Modal Seleccionar Equipo
                modalSeleccionarEquipo: 0,
                equiposDisponibles: [],
                cargandoEquiposDisponibles: false,

                // Modal Consumible
                modalConsumible: 0,
                equipoConsumible: null,
                guardandoConsumible: false,
                formConsumible: {
                    description: '',
                    qty: 1,
                    counter: 0,
                    observation: ''
                },

                // Modal Historial Consumibles
                modalHistorialConsumibles: 0,
                historialConsumibles: [],
                cargandoConsumibles: false,

                // Modal URL Monitor
                modalUrlMonitor: 0,
                equipoUrlMonitor: null,
                urlMonitorTemp: '',
                guardandoUrlMonitor: false,

                // UI Vista
                vistaActual: localStorage.getItem('admin_vista') || 'cards'
            }
        },
        watch: {
            vistaActual(newVal) {
                localStorage.setItem('admin_vista', newVal);
            }
        },
        computed:{
            isActived: function(){
                return this.pagination.current_page;
            },
            pagesNumber: function(){
                if(!this.pagination.to){
                    return [];
                }
                var from = this.pagination.current_page - this.offset;
                if(from <1){
                    from=1;
                }

                var to = from + (this.offset * 2);
                if(to >= this.pagination.last_page){
                    to = this.pagination.last_page;
                }

                var pagesArray = [];
                while(from <= to ){
                    pagesArray.push(from);
                    from++;
                }
                return pagesArray;
            }
        },
        methods : {
            loadClients(page,buscar,criterio,estatus){
                let me=this;
                var url = '/admin/clients/get?page='+page+'&buscar='+buscar+'&criterio='+criterio+'&estatus='+estatus;
                axios.get(url).then(function (response){
                    var respuesta = response.data;
                    me.arrayClients = respuesta.clients.data;
                    me.pagination = respuesta.pagination;
                })
                .catch(function (error) {
                    console.log(error);
                });
            },
            cambiarPagina(page,buscar,criterio,estatus){
                let me = this;
                me.pagination.current_page = page;
                me.loadClients(page,buscar,criterio,estatus);
            },
            actualizarAActivo(id){
                if(this.isLimitedUser) {
                    Swal.fire('Acceso Denegado', 'No tienes permisos para realizar esta acción.', 'warning');
                    return;
                }
                const swalWithBootstrapButtons = Swal.mixin({
                    customClass: {
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-danger'
                    },
                    buttonsStyling: false
                })

                swalWithBootstrapButtons.fire({
                    title: '¿Desea activar este cliente?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Aceptar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        let me=this;
                        axios.put('/admin/clients/active',{
                            'id': id
                        }).then(function (response){
                            me.loadClients(me.pagination.current_page,me.buscar,me.criterio,me.estatus);
                            swalWithBootstrapButtons.fire(
                                '¡Activado!',
                                'Cliente activado exitosamente.',
                                'success'
                            )
                        }).catch(function (error){
                            console.log(error);
                        });
                    }
                })
            },
            actualizarAInactivo(id){
                if(this.isLimitedUser) {
                    Swal.fire('Acceso Denegado', 'No tienes permisos para realizar esta acción.', 'warning');
                    return;
                }
                const swalWithBootstrapButtons = Swal.mixin({
                    customClass: {
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-danger'
                    },
                    buttonsStyling: false
                })

                swalWithBootstrapButtons.fire({
                    title: '¿Desea desactivar este cliente?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Aceptar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        let me=this;
                        axios.put('/admin/clients/inactive',{
                            'id': id
                        }).then(function (response){
                            me.loadClients(me.pagination.current_page,me.buscar,me.criterio,me.estatus);
                            swalWithBootstrapButtons.fire(
                                '¡Desactivado!',
                                'Cliente desactivado exitosamente.',
                                'success'
                            )
                        }).catch(function (error){
                            console.log(error);
                        });
                    }
                })
            },
            registrar(){
                if(this.validarDatos('registrar')){
                    return;
                }
                let me=this;
                axios.post('/admin/clients/store',{
                    'name':me.name,
                    'email':me.email,
                    'company':me.company,
                    'movil':me.movil,
                    'phone':me.phone,
                    'address':me.address,
                    'city':me.city,
                    'state':me.state,
                    'level':me.level,
                    'observations':me.observations,
                }).then(function (response){
                    me.cerrarModal();
                    me.loadClients(1,me.buscar,me.criterio,me.estatus)
                    Swal.fire(
                        'Éxito!',
                        'Cliente agregado correctamente.',
                        'success'
                    );
                }).catch(function (error){
                    console.log(error);
                    Swal.fire(
                        'Error!',
                        'Ocurrió un error al guardar, consulte al administrador del sistema.',
                        'error'
                    );
                });
            },
            actualizarDatos(){
                if(this.isLimitedUser) {
                    Swal.fire('Acceso Denegado', 'No tienes permisos para realizar esta acción.', 'warning');
                    return;
                }
                if(this.validarDatos('actualizar')){
                    return;
                }

                let me=this;
                axios.put('/admin/clients/update',{
                    'client_id':me.client_id,
                    'name':me.name,
                    'email':me.email,
                    'company':me.company,
                    'movil':me.movil,
                    'phone':me.phone,
                    'address':me.address,
                    'city':me.city,
                    'state':me.state,
                    'level':me.level,
                    'observations':me.observations,
                }).then(function (response){
                    me.cerrarModal();
                    me.loadClients(me.pagination.current_page,me.buscar,me.criterio,me.estatus)
                    Swal.fire(
                        'Éxito!',
                        'Cliente actualizado correctamente.',
                        'success'
                    );
                }).catch(function (error){
                    console.log(error);
                    Swal.fire(
                        'Error!',
                        'Ocurrió un error al actualizar, consulte al administrador del sistema.',
                        'error'
                    );
                });
            },
            validarDatos(accion){
                this.errorClient=0;
                this.errorMostrarMsjClient=[];
                if(accion=='registrar' || accion=='actualizar'){
                    if(!this.name) this.errorMostrarMsjClient.push('El nombre no puede estar vacío.');
                    if(!this.email) this.errorMostrarMsjClient.push('El email no puede estar vacío.');
                }
                if(this.errorMostrarMsjClient.length)
                {
                    this.errorClient=1;
                    Swal.fire({
                        title: 'Alerta',
                        text: 'Ingrese todos los campos requeridos',
                        icon: 'error',
                    });
                }
                return this.errorClient;
            },
            abrirModal(modelo, accion, data=[]){
                switch(modelo){
                    case "client":{
                        switch(accion){
                            case 'registrar':{
                                this.modal=1;
                                this.tipoAccion =1;
                                this.errorClient=0;
                                this.tituloModal='Agregar Cliente';
                                this.name='';
                                this.email ='';
                                this.company ='';
                                this.movil ='';
                                this.phone ='';
                                this.address ='';
                                this.city ='';
                                this.state ='';
                                this.level=1;
                                this.observations='';
                                break;
                            }
                            case 'actualizar_datos':{
                                if(this.isLimitedUser) {
                                    Swal.fire('Acceso Denegado', 'No tienes permisos para editar clientes.', 'warning');
                                    return;
                                }
                                this.modal=1;
                                this.tipoAccion =2;
                                this.errorClient=0;
                                this.tituloModal='Actualizar Cliente';

                                this.client_id = data['id'];
                                this.name = data['name'] || '';
                                this.email = data['email'] || '';
                                this.company = data['company'] || '';
                                this.movil = data['movil'] || '';
                                this.phone = data['phone'] || '';
                                this.address = data['address'] || '';
                                this.city = data['city'] || '';
                                this.state = data['state'] || '';
                                this.level = data['level'] || 1;
                                this.observations = data['observations'] || '';
                                break;
                            }
                        }
                    }
                }
            },
            cerrarModal(){
                this.modal=0;
                this.tituloModal='';
            },
            abrirModalDirecciones(client) {
                this.$refs.clientAddresses.abrirModal(client);
            },
            // Modal Detalle
            abrirModalDetalle(client) {
                this.clienteDetalle = client;
                this.modalDetalle = 1;
            },
            cerrarModalDetalle() {
                this.modalDetalle = 0;
                this.clienteDetalle = null;
            },
            // Utilidades
            truncateText(text, length) {
                if (!text) return '';
                return text.length > length ? text.substring(0, length) + '...' : text;
            },
            getGoogleMapsUrl(lat, lng) {
                return `https://www.google.com/maps?q=${lat},${lng}`;
            },
            getImageUrl(imagePath) {
                if (!imagePath) return '';
                if (imagePath.startsWith('http')) return imagePath;
                return `/storage/${imagePath}`;
            },
            abrirImagenCompleta(imagePath) {
                // Usar visor de imágenes global
                this.$viewImage(imagePath);
            },
            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
            },
            copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copiado',
                        text: 'Email copiado al portapapeles',
                        timer: 1500,
                        showConfirmButton: false
                    });
                });
            },

            // =====================================================
            // USUARIO APP
            // =====================================================
            abrirModalUserApp(client) {
                this.clienteUserApp = client;
                this.userAppUsername = '';
                this.userAppPassword = '';
                this.userAppPasswordConfirm = '';
                this.userAppNewEmail = '';

                if (client.user_id) {
                    this.userAppMode = 'edit';
                    // Cargar datos del usuario
                    axios.get(`/admin/clients/${client.id}/get-user-app`).then(response => {
                        if (response.data.ok && response.data.user) {
                            this.userAppCurrentEmail = response.data.user.email;
                        }
                    });
                } else {
                    this.userAppMode = 'create';
                }

                this.modalUserApp = 1;
            },
            cerrarModalUserApp() {
                this.modalUserApp = 0;
                this.clienteUserApp = null;
            },
            crearUserApp() {
                if (this.userAppPassword !== this.userAppPasswordConfirm) {
                    Swal.fire('Error', 'Las contraseñas no coinciden', 'error');
                    return;
                }
                if (this.userAppPassword.length < 8) {
                    Swal.fire('Error', 'La contraseña debe tener al menos 8 caracteres', 'error');
                    return;
                }
                if (!/[a-zA-Z]/.test(this.userAppPassword) || !/[0-9]/.test(this.userAppPassword)) {
                    Swal.fire('Error', 'La contraseña debe contener letras y números', 'error');
                    return;
                }

                let me = this;
                axios.post(`/admin/clients/${this.clienteUserApp.id}/store-user-app`, {
                    username: this.userAppUsername,
                    password: this.userAppPassword
                }).then(response => {
                    if (response.data.ok) {
                        Swal.fire('Éxito', response.data.message, 'success');
                        me.clienteUserApp.user_id = response.data.user.id;
                        me.cerrarModalUserApp();
                        me.loadClients(me.pagination.current_page, me.buscar, me.criterio, me.estatus);
                    }
                }).catch(error => {
                    Swal.fire('Error', error.response?.data?.message || 'Error al crear usuario', 'error');
                });
            },
            actualizarEmailUserApp() {
                let me = this;
                axios.put(`/admin/clients/${this.clienteUserApp.id}/update-user-app`, {
                    email: this.userAppNewEmail
                }).then(response => {
                    if (response.data.ok) {
                        Swal.fire('Éxito', response.data.message, 'success');
                        me.userAppCurrentEmail = me.userAppNewEmail;
                        me.userAppNewEmail = '';
                    }
                }).catch(error => {
                    Swal.fire('Error', error.response?.data?.message || 'Error al actualizar email', 'error');
                });
            },
            resetearPasswordUserApp() {
                if (this.userAppPassword !== this.userAppPasswordConfirm) {
                    Swal.fire('Error', 'Las contraseñas no coinciden', 'error');
                    return;
                }
                if (this.userAppPassword.length < 8) {
                    Swal.fire('Error', 'La contraseña debe tener al menos 8 caracteres', 'error');
                    return;
                }

                Swal.fire({
                    title: '¿Confirmar cambio de contraseña?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cambiar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let me = this;
                        axios.put(`/admin/clients/${this.clienteUserApp.id}/update-user-app`, {
                            password: this.userAppPassword
                        }).then(response => {
                            if (response.data.ok) {
                                Swal.fire('Éxito', response.data.message, 'success');
                                me.userAppPassword = '';
                                me.userAppPasswordConfirm = '';
                            }
                        }).catch(error => {
                            Swal.fire('Error', error.response?.data?.message || 'Error al cambiar contraseña', 'error');
                        });
                    }
                });
            },

            // =====================================================
            // GPS
            // =====================================================
            abrirModalGPS(client) {
                this.clienteGPS = client;
                this.gpsLatitude = client.location_latitude || '';
                this.gpsLongitude = client.location_longitude || '';
                this.modalGPS = 1;
            },
            cerrarModalGPS() {
                this.modalGPS = 0;
                this.clienteGPS = null;
            },
            guardarUbicacion() {
                let me = this;
                axios.put(`/admin/clients/${this.clienteGPS.id}/update-location`, {
                    latitude: this.gpsLatitude,
                    longitude: this.gpsLongitude
                }).then(response => {
                    if (response.data.ok) {
                        Swal.fire('Éxito', response.data.message, 'success');
                        me.clienteGPS.location_latitude = me.gpsLatitude;
                        me.clienteGPS.location_longitude = me.gpsLongitude;
                        me.cerrarModalGPS();
                        me.loadClients(me.pagination.current_page, me.buscar, me.criterio, me.estatus);
                    }
                }).catch(error => {
                    Swal.fire('Error', error.response?.data?.message || 'Error al guardar ubicación', 'error');
                });
            },
            eliminarUbicacion() {
                Swal.fire({
                    title: '¿Eliminar ubicación GPS?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let me = this;
                        axios.delete(`/admin/clients/${this.clienteGPS.id}/remove-location`).then(response => {
                            if (response.data.ok) {
                                Swal.fire('Éxito', response.data.message, 'success');
                                me.clienteGPS.location_latitude = null;
                                me.clienteGPS.location_longitude = null;
                                me.gpsLatitude = '';
                                me.gpsLongitude = '';
                                me.cerrarModalGPS();
                                me.loadClients(me.pagination.current_page, me.buscar, me.criterio, me.estatus);
                            }
                        }).catch(error => {
                            Swal.fire('Error', error.response?.data?.message || 'Error al eliminar ubicación', 'error');
                        });
                    }
                });
            },

            // =====================================================
            // IMAGEN
            // =====================================================
            abrirModalImagen(client) {
                this.clienteImagen = client;
                this.imagenSeleccionada = null;
                this.modalImagen = 1;
            },
            cerrarModalImagen() {
                this.modalImagen = 0;
                this.clienteImagen = null;
                this.imagenSeleccionada = null;
            },
            onImagenSeleccionada(event) {
                const file = event.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        Swal.fire('Error', 'La imagen no debe superar los 5MB', 'error');
                        this.imagenSeleccionada = null;
                        return;
                    }
                    this.imagenSeleccionada = file;
                }
            },
            subirImagen() {
                if (!this.imagenSeleccionada) return;

                let formData = new FormData();
                formData.append('image', this.imagenSeleccionada);

                let me = this;
                axios.post(`/admin/clients/${this.clienteImagen.id}/upload-location-image`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                }).then(response => {
                    if (response.data.ok) {
                        Swal.fire('Éxito', response.data.message, 'success');
                        me.clienteImagen.location_image = response.data.client.location_image;
                        me.imagenSeleccionada = null;
                        if (me.$refs.inputImagen) me.$refs.inputImagen.value = '';
                        me.loadClients(me.pagination.current_page, me.buscar, me.criterio, me.estatus);
                    }
                }).catch(error => {
                    Swal.fire('Error', error.response?.data?.message || 'Error al subir imagen', 'error');
                });
            },
            eliminarImagen() {
                Swal.fire({
                    title: '¿Eliminar imagen?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let me = this;
                        axios.delete(`/admin/clients/${this.clienteImagen.id}/delete-location-image`).then(response => {
                            if (response.data.ok) {
                                Swal.fire('Éxito', response.data.message, 'success');
                                me.clienteImagen.location_image = null;
                                me.cerrarModalImagen();
                                me.loadClients(me.pagination.current_page, me.buscar, me.criterio, me.estatus);
                            }
                        }).catch(error => {
                            Swal.fire('Error', error.response?.data?.message || 'Error al eliminar imagen', 'error');
                        });
                    }
                });
            },

            // =====================================================
            // RENTAS
            // =====================================================
            abrirModalRentas(client) {
                this.clienteRentas = client;
                this.arrayRentas = [];
                this.cargandoRentas = true;
                this.modalRentas = 1;

                axios.get(`/admin/clients/${client.id}/rents`).then(response => {
                    if (response.data.ok) {
                        this.arrayRentas = response.data.rents;
                    }
                    this.cargandoRentas = false;
                }).catch(() => {
                    this.cargandoRentas = false;
                });
            },
            cerrarModalRentas() {
                this.modalRentas = 0;
                this.clienteRentas = null;
                this.arrayRentas = [];
            },
            recargarRentas() {
                if (this.clienteRentas) {
                    this.cargandoRentas = true;
                    axios.get(`/admin/clients/${this.clienteRentas.id}/rents`).then(response => {
                        if (response.data.ok) {
                            this.arrayRentas = response.data.rents;
                        }
                        this.cargandoRentas = false;
                    }).catch(() => {
                        this.cargandoRentas = false;
                    });
                }
            },

            // Ver detalle de renta
            verDetalleRenta(renta) {
                this.rentaSeleccionada = renta;
                this.cargandoDetalleRenta = true;
                this.modalDetalleRenta = 1;

                axios.get(`/admin/rents/${renta.id}/details`).then(response => {
                    if (response.data.ok) {
                        this.rentaSeleccionada = response.data.rent;
                    }
                    this.cargandoDetalleRenta = false;
                }).catch(() => {
                    this.cargandoDetalleRenta = false;
                });
            },
            cerrarModalDetalleRenta() {
                this.modalDetalleRenta = 0;
                this.rentaSeleccionada = null;
            },

            // Crear/Editar Renta
            abrirModalNuevaRenta() {
                this.editandoRenta = false;
                this.formRenta = {
                    id: null,
                    client_id: this.clienteRentas.id,
                    cutoff: '',
                    location_descripcion: '',
                    location_address: '',
                    location_phone: '',
                    location_email: ''
                };
                this.modalFormRenta = 1;
            },
            editarRenta(renta) {
                this.editandoRenta = true;
                this.formRenta = {
                    id: renta.id,
                    client_id: renta.client_id,
                    cutoff: renta.cutoff,
                    location_descripcion: renta.location_descripcion || '',
                    location_address: renta.location_address || '',
                    location_phone: renta.location_phone || '',
                    location_email: renta.location_email || ''
                };
                this.modalFormRenta = 1;
            },
            cerrarModalFormRenta() {
                this.modalFormRenta = 0;
                this.editandoRenta = false;
            },
            guardarRenta() {
                this.guardandoRenta = true;
                let url = this.editandoRenta ? '/admin/rents/update' : '/admin/rents/store';
                let method = this.editandoRenta ? 'put' : 'post';

                axios[method](url, this.formRenta).then(response => {
                    if (response.data.ok) {
                        Swal.fire('Exito', response.data.message, 'success');
                        this.cerrarModalFormRenta();
                        this.recargarRentas();
                    }
                    this.guardandoRenta = false;
                }).catch(error => {
                    Swal.fire('Error', error.response?.data?.message || 'Error al guardar', 'error');
                    this.guardandoRenta = false;
                });
            },

            // Dar de baja / Reactivar renta
            darDeBajaRenta(renta) {
                Swal.fire({
                    title: 'Dar de Baja',
                    text: '¿Esta seguro de dar de baja esta renta?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Si, dar de baja'
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.put(`/admin/rents/${renta.id}/inactive`).then(response => {
                            if (response.data.ok) {
                                Swal.fire('Exito', response.data.message, 'success');
                                this.recargarRentas();
                            }
                        }).catch(error => {
                            Swal.fire('Error', error.response?.data?.message || 'Error', 'error');
                        });
                    }
                });
            },
            reactivarRenta(renta) {
                axios.put(`/admin/rents/${renta.id}/active`).then(response => {
                    if (response.data.ok) {
                        Swal.fire('Exito', response.data.message, 'success');
                        this.recargarRentas();
                    }
                }).catch(error => {
                    Swal.fire('Error', error.response?.data?.message || 'Error', 'error');
                });
            },

            // =====================================================
            // EQUIPOS
            // =====================================================
            abrirModalNuevoEquipo() {
                this.editandoEquipo = false;
                this.formEquipo = {
                    id: null,
                    rent_id: this.rentaSeleccionada.id,
                    trademark: '',
                    model: '',
                    serial_number: '',
                    rent_price: 0,
                    description: '',
                    url_web_monitor: '',
                    monochrome: false,
                    pages_included_mono: 0,
                    extra_page_cost_mono: 0,
                    counter_mono: 0,
                    color: false,
                    pages_included_color: 0,
                    extra_page_cost_color: 0,
                    counter_color: 0
                };
                this.modalFormEquipo = 1;
            },
            editarEquipo(equipo) {
                this.editandoEquipo = true;
                this.formEquipo = {
                    id: equipo.id,
                    rent_id: equipo.rent_id,
                    trademark: equipo.trademark || '',
                    model: equipo.model || '',
                    serial_number: equipo.serial_number || '',
                    rent_price: equipo.rent_price || 0,
                    description: equipo.description || '',
                    url_web_monitor: equipo.url_web_monitor || '',
                    monochrome: equipo.monochrome ? true : false,
                    pages_included_mono: equipo.pages_included_mono || 0,
                    extra_page_cost_mono: equipo.extra_page_cost_mono || 0,
                    counter_mono: equipo.counter_mono || 0,
                    color: equipo.color ? true : false,
                    pages_included_color: equipo.pages_included_color || 0,
                    extra_page_cost_color: equipo.extra_page_cost_color || 0,
                    counter_color: equipo.counter_color || 0
                };
                this.modalFormEquipo = 1;
            },
            cerrarModalFormEquipo() {
                this.modalFormEquipo = 0;
                this.editandoEquipo = false;
            },
            guardarEquipo() {
                this.guardandoEquipo = true;
                let url = this.editandoEquipo ? '/admin/rents/details/update' : '/admin/rents/details/store';
                let method = this.editandoEquipo ? 'put' : 'post';

                axios[method](url, this.formEquipo).then(response => {
                    if (response.data.ok) {
                        Swal.fire('Exito', response.data.message, 'success');
                        this.cerrarModalFormEquipo();
                        // Recargar detalle de renta
                        this.verDetalleRenta(this.rentaSeleccionada);
                    }
                    this.guardandoEquipo = false;
                }).catch(error => {
                    Swal.fire('Error', error.response?.data?.message || 'Error al guardar', 'error');
                    this.guardandoEquipo = false;
                });
            },

            // Liberar equipo
            liberarEquipo(equipo) {
                Swal.fire({
                    title: 'Liberar Equipo',
                    text: '¿Esta seguro de liberar este equipo? Volvera al inventario.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Si, liberar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.put(`/admin/rents/details/${equipo.id}/liberar`).then(response => {
                            if (response.data.ok) {
                                Swal.fire('Exito', response.data.message, 'success');
                                this.verDetalleRenta(this.rentaSeleccionada);
                                this.recargarRentas();
                            }
                        }).catch(error => {
                            Swal.fire('Error', error.response?.data?.message || 'Error', 'error');
                        });
                    }
                });
            },

            // Seleccionar equipo del inventario
            abrirModalSeleccionarEquipo() {
                this.equiposDisponibles = [];
                this.cargandoEquiposDisponibles = true;
                this.modalSeleccionarEquipo = 1;

                axios.get('/admin/rents/equipments/available').then(response => {
                    if (response.data.ok) {
                        this.equiposDisponibles = response.data.equipments;
                    }
                    this.cargandoEquiposDisponibles = false;
                }).catch(() => {
                    this.cargandoEquiposDisponibles = false;
                });
            },
            cerrarModalSeleccionarEquipo() {
                this.modalSeleccionarEquipo = 0;
                this.equiposDisponibles = [];
            },
            asignarEquipo(equipo) {
                Swal.fire({
                    title: 'Asignar Equipo',
                    text: `¿Asignar ${equipo.trademark} ${equipo.model} a esta renta?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Si, asignar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('/admin/rents/details/assign', {
                            equipment_id: equipo.id,
                            rent_id: this.rentaSeleccionada.id
                        }).then(response => {
                            if (response.data.ok) {
                                Swal.fire('Exito', response.data.message, 'success');
                                this.cerrarModalSeleccionarEquipo();
                                this.verDetalleRenta(this.rentaSeleccionada);
                                this.recargarRentas();
                            }
                        }).catch(error => {
                            Swal.fire('Error', error.response?.data?.message || 'Error', 'error');
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
            cerrarModalUrlMonitor() {
                this.modalUrlMonitor = 0;
                this.equipoUrlMonitor = null;
                this.urlMonitorTemp = '';
            },
            guardarUrlMonitor() {
                this.guardandoUrlMonitor = true;
                axios.put(`/admin/rents/details/${this.equipoUrlMonitor.id}/url-monitor`, {
                    url_web_monitor: this.urlMonitorTemp
                }).then(response => {
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

            // =====================================================
            // CONSUMIBLES
            // =====================================================
            abrirModalConsumible(equipo) {
                this.equipoConsumible = equipo;
                this.formConsumible = {
                    description: '',
                    qty: 1,
                    counter: equipo.counter_mono || equipo.counter_color || 0,
                    observation: ''
                };
                this.modalConsumible = 1;
            },
            cerrarModalConsumible() {
                this.modalConsumible = 0;
                this.equipoConsumible = null;
            },
            guardarConsumible() {
                this.guardandoConsumible = true;
                axios.post(`/admin/rents/details/${this.equipoConsumible.id}/consumables/store`, this.formConsumible)
                    .then(response => {
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

            // Historial de consumibles
            verHistorialConsumibles(equipo) {
                this.historialConsumibles = [];
                this.cargandoConsumibles = true;
                this.modalHistorialConsumibles = 1;

                axios.get(`/admin/rents/details/${equipo.id}/consumables`).then(response => {
                    if (response.data.ok) {
                        this.historialConsumibles = response.data.consumables;
                    }
                    this.cargandoConsumibles = false;
                }).catch(() => {
                    this.cargandoConsumibles = false;
                });
            },
            cerrarModalHistorialConsumibles() {
                this.modalHistorialConsumibles = 0;
                this.historialConsumibles = [];
            }
        },
        mounted() {
            this.loadClients(1,'','name','active');
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

    .text-error{
        color: red !important;
        font-weight: bold;
    }

    /* Estilos para Cards de Clientes */
    .client-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .client-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    .client-card.inactive-card {
        opacity: 0.7;
        background-color: #f8f9fa;
    }

    .client-card .card-header {
        background: linear-gradient(135deg, #00F5A0 0%, #00D9F5 100%);
        color: #0D1117;
        border: none;
        padding: 1rem;
    }

    .client-card.inactive-card .card-header {
        background: linear-gradient(135deg, #868e96 0%, #6c757d 100%);
        color: white;
    }

    .client-id {
        font-weight: bold;
        font-size: 0.9rem;
    }

    .client-name {
        color: #2c3e50;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .client-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #6c757d;
    }

    .info-item i {
        width: 16px;
        text-align: center;
    }

    .info-item span {
        flex: 1;
        word-break: break-word;
    }

    .client-indicators {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    .client-card .card-footer {
        border-top: 1px solid #e9ecef;
        padding: 0.75rem 1rem;
    }

    .client-card .btn-group .btn {
        border-radius: 6px;
        font-size: 0.85rem;
        padding: 0.375rem 0.5rem;
    }

    .client-card .btn-group .btn:not(:last-child) {
        margin-right: 0.25rem;
    }

    .client-card .dropdown-menu {
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
    }

    .client-card .dropdown-item {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }

    .client-card .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .col-md-4 {
            margin-bottom: 1rem;
        }

        .client-card .btn-group .btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.4rem;
        }
    }

    /* Paginación moderna */
    .pagination {
        justify-content: center;
        margin-top: 2rem;
    }

    .pagination .page-link {
        border: none;
        color: #667eea;
        font-weight: 500;
        padding: 0.5rem 1rem;
        margin: 0 0.125rem;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .pagination .page-link:hover {
        background-color: #667eea;
        color: white;
        transform: translateY(-1px);
    }

    .pagination .page-item.active .page-link {
        background-color: #667eea;
        border-color: #667eea;
        color: white;
    }
</style>
