<template>
<div>
    <div class="row">
        <!-- Header -->
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                        <i class="fa fa-clipboard" style="color: var(--j2b-primary);"></i>
                        Tarea #{{ task.folio || task.id }} - {{ task.title }}
                    </h4>
                    <p class="mb-0" style="color: var(--j2b-gray-500);">Detalle de la tarea</p>
                </div>
                <div class="task-actions">
                    <div class="task-actions-main">
                        <button type="button" class="j2b-btn j2b-btn-dark" @click="abrirModalEstatus()">
                            <i class="fa fa-exchange"></i> Estatus
                        </button>
                        <button type="button" class="j2b-btn j2b-btn-dark" @click="abrirModalResena()">
                            <i class="fa fa-comment"></i> Resena
                        </button>
                        <a :href="'/admin/tasks/' + task.id + '/reception-pdf'" target="_blank" class="j2b-btn j2b-btn-dark">
                            <i class="fa fa-print"></i> Comprobante
                        </a>
                        <button v-if="!userLimited" type="button" class="j2b-btn j2b-btn-primary" @click="abrirModalEditar()">
                            <i class="fa fa-edit"></i> Editar
                        </button>
                    </div>
                    <div v-if="!userLimited" class="task-actions-secondary">
                        <button type="button" class="j2b-btn j2b-btn-sm j2b-btn-dark" @click="task.active ? desactivarTarea() : activarTarea()" :title="task.active ? 'Desactivar' : 'Activar'">
                            <i class="fa" :class="task.active ? 'fa-toggle-off' : 'fa-toggle-on'"></i>
                        </button>
                        <button type="button" class="j2b-btn j2b-btn-sm" style="background: var(--j2b-danger); color: #fff;" @click="confirmarEliminar()" title="Eliminar">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Tracking -->
        <div class="col-12 mb-3" v-if="serviceSteps.length > 0">
            <div class="j2b-card">
                <div class="j2b-card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-route mr-2"></i>Seguimiento de Servicio</span>
                    <span v-if="task.tracking_code" class="small" style="opacity: 0.8;">
                        <i class="fa fa-qrcode mr-1"></i>{{ task.tracking_code }}
                    </span>
                </div>
                <div class="j2b-card-body">
                    <!-- Barra de progreso visual -->
                    <div class="tracking-flow">
                        <div v-for="(step, index) in serviceSteps" :key="'st-'+step.id" class="tracking-step"
                            :class="{
                                'tracking-step-completed': isStepCompleted(step),
                                'tracking-step-current': task.current_service_step_id === step.id,
                                'tracking-step-pending': !isStepCompleted(step) && task.current_service_step_id !== step.id,
                                'tracking-step-selected': trackingStepDetalle && trackingStepDetalle.id === step.id
                            }">
                            <div class="tracking-circle"
                                :style="getStepCircleStyle(step)"
                                @click="onStepClick(step)"
                                :title="step.name + (task.current_service_step_id === step.id ? ' (actual)' : '')">
                                <i v-if="isStepCompleted(step) && task.current_service_step_id !== step.id" class="fa fa-check"></i>
                                <i v-else :class="step.icon || 'fa fa-circle'" style="font-size: 12px;"></i>
                            </div>
                            <!-- Badge de fotos -->
                            <span v-if="getStepEvidenceCount(step) > 0" class="tracking-photo-badge">
                                <i class="fa fa-camera" style="font-size: 8px;"></i> {{ getStepEvidenceCount(step) }}
                            </span>
                            <div class="tracking-label" :class="{ 'fw-bold': task.current_service_step_id === step.id }">
                                {{ step.name }}
                            </div>
                            <div v-if="task.current_service_step_id === step.id" class="tracking-badge">Actual</div>
                            <div v-if="index < serviceSteps.length - 1" class="tracking-line"
                                :class="{ 'tracking-line-completed': isStepCompleted(step) }"></div>
                        </div>
                    </div>

                    <!-- Panel de detalle del paso seleccionado -->
                    <div v-if="trackingStepDetalle && trackingEntryDetalle" class="tracking-detail-panel mt-3 pt-3 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="tracking-history-dot" :style="{ backgroundColor: trackingStepDetalle.color || '#6c757d' }"></span>
                                <strong class="small">{{ trackingStepDetalle.name }}</strong>
                                <span class="text-muted small">{{ formatDateTime(trackingEntryDetalle.created_at) }}</span>
                                <span v-if="trackingEntryDetalle.changed_by" class="text-muted small">— {{ trackingEntryDetalle.changed_by.name }}</span>
                            </div>
                            <button class="btn btn-sm p-0 text-muted" @click="trackingStepDetalle = null" title="Cerrar">&times;</button>
                        </div>
                        <div v-if="trackingEntryDetalle.notes" class="text-muted fst-italic small mb-2">{{ trackingEntryDetalle.notes }}</div>
                        <!-- Evidencia -->
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div v-for="(ev, evIdx) in trackingEntryDetalle.evidence" :key="ev.id" class="position-relative evidence-thumb-wrapper">
                                <img :src="'/storage/' + ev.image" class="evidence-thumb" @click="verEvidencia(trackingEntryDetalle, evIdx)" :title="ev.caption || 'Ver imagen'">
                                <button v-if="!userLimited" class="evidence-delete-btn" @click="eliminarEvidencia(ev.id)" title="Eliminar">&times;</button>
                            </div>
                            <!-- Botón agregar -->
                            <label v-if="!userLimited" :for="'evidence-panel-' + trackingEntryDetalle.id" class="evidence-add-btn" title="Agregar evidencia">
                                <i class="fa fa-plus"></i>
                                <input type="file" :id="'evidence-panel-' + trackingEntryDetalle.id" accept="image/*" style="display:none;" @change="subirEvidenciaDirecta(trackingEntryDetalle, $event)">
                            </label>
                        </div>
                        <div v-if="!trackingEntryDetalle.evidence || trackingEntryDetalle.evidence.length === 0" class="small text-muted">
                            Sin evidencia fotográfica
                            <label v-if="!userLimited" :for="'evidence-panel-empty-' + trackingEntryDetalle.id" class="text-primary ms-1" style="cursor:pointer; text-decoration: underline;">
                                agregar
                                <input type="file" :id="'evidence-panel-empty-' + trackingEntryDetalle.id" accept="image/*" style="display:none;" @change="subirEvidenciaDirecta(trackingEntryDetalle, $event)">
                            </label>
                        </div>
                    </div>

                    <!-- Mensaje cuando no hay paso seleccionado pero hay historial -->
                    <div v-else-if="task.service_tracking_history && task.service_tracking_history.length > 0" class="mt-3 pt-3 border-top">
                        <small class="text-muted"><i class="fa fa-info-circle mr-1"></i>Click en un paso para ver su detalle y evidencia</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna izquierda -->
        <div class="col-lg-6">
            <!-- Info básica -->
            <div class="j2b-card mb-3">
                <div class="j2b-card-header">
                    <i class="fa fa-info-circle mr-2"></i>Informacion General
                </div>
                <div class="j2b-card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="text-muted small mb-1">Estatus</label>
                            <div>
                                <span class="j2b-badge" :class="getBadgeClass(task.status)">{{ task.status }}</span>
                                <span v-if="task.active" class="j2b-badge j2b-badge-success ml-1">Activo</span>
                                <span v-else class="j2b-badge j2b-badge-danger ml-1">Inactivo</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small mb-1">Prioridad</label>
                            <div>
                                <span class="j2b-badge j2b-badge-outline badge-lg">P{{ task.priority }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Titulo</label>
                        <p class="font-weight-bold mb-0">{{ task.title }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Descripcion</label>
                        <p class="mb-0">{{ task.description || 'Sin descripcion' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Solucion</label>
                        <p class="mb-0">{{ task.solution || 'Sin solucion' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Resena</label>
                        <p class="mb-0">{{ task.review || 'Sin resena' }}</p>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="text-muted small mb-1">Fecha expiracion</label>
                            <p class="mb-0">{{ task.expiration || 'Sin fecha' }}</p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small mb-1">Creada</label>
                            <p class="mb-0">{{ formatDateTime(task.created_at) }}</p>
                        </div>
                    </div>
                    <!-- Campos extra dinámicos -->
                    <div v-if="task.info_extra && task.info_extra.length > 0" class="mt-3 pt-3" style="border-top: 1px solid var(--j2b-gray-300);">
                        <label class="text-muted small mb-2"><i class="fa fa-plus-square me-1"></i> Informacion Adicional</label>
                        <div class="row">
                            <div class="col-6 mb-2" v-for="extra in task.info_extra" :key="extra.id">
                                <label class="text-muted small mb-0">{{ extra.field_name }}</label>
                                <p class="font-weight-bold mb-0">{{ extra.value }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cliente -->
            <div class="j2b-card mb-3" v-if="task.client">
                <div class="j2b-card-header">
                    <i class="fa fa-user mr-2"></i>Cliente
                </div>
                <div class="j2b-card-body">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <label class="text-muted small mb-1">Nombre</label>
                            <p class="font-weight-bold mb-0">{{ task.client.name }}</p>
                        </div>
                        <div class="col-6" v-if="task.client.email">
                            <label class="text-muted small mb-1">Email</label>
                            <p class="mb-0">{{ task.client.email }}</p>
                        </div>
                        <div class="col-6" v-if="task.client.movil">
                            <label class="text-muted small mb-1">Telefono</label>
                            <p class="mb-0">{{ task.client.movil }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Firma Digital -->
            <div class="j2b-card mb-3" v-if="task.signature_path">
                <div class="j2b-card-header">
                    <i class="fa fa-pencil-square-o mr-2"></i>Firma Digital
                </div>
                <div class="j2b-card-body text-center">
                    <img :src="getImageUrl(task.signature_path)" class="img-fluid rounded" style="max-height: 180px; border: 2px solid #dee2e6; padding: 15px; background: #fff; cursor: pointer;" @click="$viewImage(task.signature_path)">
                </div>
            </div>

            <!-- Logs / Historial -->
            <div class="j2b-card mb-3" v-if="task.logs && task.logs.length > 0">
                <div class="j2b-card-header">
                    <i class="fa fa-history mr-2"></i>Historial de Cambios
                </div>
                <div class="j2b-card-body" style="max-height: 350px; overflow-y: auto;">
                    <ul class="list-unstyled mb-0">
                        <li v-for="log in task.logs" :key="log.id" class="mb-3 pb-2 border-bottom">
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

        <!-- Columna derecha -->
        <div class="col-lg-6">
            <!-- Asignacion -->
            <div class="j2b-card mb-3">
                <div class="j2b-card-header">
                    <i class="fa fa-user-plus mr-2"></i>Asignacion
                </div>
                <div class="j2b-card-body">
                    <div v-if="task.assigned_user">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <label class="text-muted small mb-1">Asignado a</label>
                                <p class="font-weight-bold mb-0">
                                    <i class="fa fa-user-circle text-primary mr-1"></i>
                                    {{ task.assigned_user.name }}
                                </p>
                            </div>
                            <button class="btn btn-outline-danger" @click="desasignarTarea()">
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
                                <option value="">Seleccionar usuario...</option>
                                <option v-for="colab in colaboradores" :key="colab.id" :value="colab.id">
                                    {{ colab.name }}
                                </option>
                            </select>
                            <button class="btn btn-primary btn-lg" @click="asignarTarea()" :disabled="!colaboradorSeleccionado">
                                <i class="fa fa-user-plus"></i> Asignar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos/Refacciones -->
            <div class="j2b-card mb-3">
                <div class="j2b-card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-cubes mr-2"></i>Productos / Refacciones</span>
                    <button class="btn btn-sm btn-success" @click="abrirModalProductos()">
                        <i class="fa fa-plus mr-1"></i> Agregar
                    </button>
                </div>
                <div class="j2b-card-body">
                    <div v-if="task.products && task.products.length > 0">
                        <div class="j2b-table-responsive">
                            <table class="j2b-table">
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
                                    <tr v-for="tp in task.products" :key="tp.id">
                                        <td>
                                            <strong>{{ tp.product ? tp.product.name : 'Producto eliminado' }}</strong>
                                            <span v-if="tp.receipt_id" class="j2b-badge j2b-badge-success ml-2">
                                                <i class="fa fa-file-invoice-dollar"></i> Facturado
                                            </span>
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
                                            <button class="btn btn-sm btn-outline-secondary"
                                                    @click="editarProductoTarea(tp)"
                                                    :disabled="tp.receipt_id"
                                                    :title="tp.receipt_id ? 'Producto ya facturado' : 'Actualizar cantidades'">
                                                <i class="fa fa-cog"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 p-2 bg-light rounded border">
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
                        <div class="mt-3" v-if="getProductosSinFacturar() > 0">
                            <button class="btn btn-success btn-block" @click="generarNotaDesdeProductos()">
                                <i class="fa fa-file-invoice-dollar mr-2"></i>
                                Generar Nota de Venta ({{ getProductosSinFacturar() }} productos)
                            </button>
                        </div>
                        <div class="mt-3" v-else-if="getTotalUsados() > 0">
                            <span class="text-success">
                                <i class="fa fa-check-circle mr-1"></i>
                                Todos los productos usados ya fueron facturados
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-center text-muted py-3">
                        <i class="fa fa-box-open fa-2x mb-2"></i>
                        <p class="mb-0">No hay productos asignados a esta tarea</p>
                    </div>
                </div>
            </div>

            <!-- Checklist -->
            <div class="j2b-card mb-3">
                <div class="j2b-card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fa fa-check-square-o mr-2"></i>Checklist
                        <span class="j2b-badge j2b-badge-outline ml-1" v-if="task.checklist_items && task.checklist_items.length > 0">
                            {{ getChecklistCompleted() }}/{{ task.checklist_items.length }}
                        </span>
                    </span>
                    <a v-if="task.checklist_items && task.checklist_items.length > 0"
                       :href="'/admin/tasks/' + task.id + '/checklist-pdf'"
                       target="_blank"
                       class="j2b-btn j2b-btn-sm j2b-btn-dark">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </a>
                </div>
                <div class="j2b-card-body">
                    <div v-if="task.checklist_items && task.checklist_items.length > 0">
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-success" :style="{ width: getChecklistPercent() + '%' }"></div>
                        </div>

                        <div v-for="(item, index) in task.checklist_items" :key="item.id"
                             class="checklist-item d-flex align-items-center py-1 px-2 mb-1 rounded"
                             :class="{ 'bg-light': dragOverIndex === index }"
                             draggable="true"
                             @dragstart="onDragStart(index, $event)"
                             @dragover.prevent="onDragOver(index)"
                             @dragleave="dragOverIndex = null"
                             @drop.prevent="onDrop(index)"
                             @dragend="dragOverIndex = null">
                            <span class="text-muted mr-2" style="cursor: grab;"><i class="fa fa-bars"></i></span>
                            <input type="checkbox" class="mr-2" :checked="item.is_completed"
                                   @change="toggleChecklistItem(item)" style="cursor: pointer; width: 16px; height: 16px;">
                            <span v-if="editandoChecklistId !== item.id"
                                  class="flex-grow-1"
                                  :class="{ 'text-decoration-line-through text-muted': item.is_completed }"
                                  @dblclick="iniciarEdicionChecklist(item)"
                                  style="cursor: text;">
                                {{ item.text }}
                            </span>
                            <input v-else type="text" class="form-control form-control-sm flex-grow-1"
                                   v-model="editandoChecklistTexto"
                                   @keyup.enter="guardarEdicionChecklist(item)"
                                   @keyup.esc="editandoChecklistId = null"
                                   @blur="guardarEdicionChecklist(item)"
                                   ref="inputEditChecklist">
                            <button class="btn btn-sm text-danger ml-2 p-0" @click="eliminarChecklistItem(item)"
                                    style="line-height: 1;" title="Eliminar">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-center text-muted py-2 mb-2">
                        <small>Sin items en el checklist</small>
                    </div>

                    <div class="position-relative mt-2">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" v-model="nuevoChecklistTexto"
                                   placeholder="Escribir o buscar producto/servicio..."
                                   @input="buscarCatalogoChecklist"
                                   @keyup.enter="agregarChecklistItem"
                                   @focus="mostrarDropdownChecklist = true">
                            <button class="btn btn-success btn-sm" @click="agregarChecklistItem"
                                    :disabled="!nuevoChecklistTexto.trim()" title="Agregar texto libre">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        <div v-if="mostrarDropdownChecklist && catalogoChecklistResultados.length > 0"
                             class="dropdown-menu show w-100" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                            <a class="dropdown-item" href="#"
                               v-for="item in catalogoChecklistResultados" :key="item.type + '-' + item.id"
                               @click.prevent="seleccionarCatalogoChecklist(item)">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ item.name }}</strong>
                                        <br><small class="text-muted">
                                            <span class="badge" :class="item.type === 'producto' ? 'badge-primary' : 'badge-info'">
                                                {{ item.type === 'producto' ? 'Producto' : 'Servicio' }}
                                            </span>
                                            <span v-if="item.code" class="ml-1">{{ item.code }}</span>
                                        </small>
                                    </div>
                                    <small v-if="item.price">${{ item.price }}</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Imagenes -->
            <div class="j2b-card mb-3" v-if="(task.images && task.images.length > 0) || task.image">
                <div class="j2b-card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-image mr-2"></i>Imagenes</span>
                    <button v-if="!userLimited" class="btn btn-sm btn-outline-primary" @click="abrirModalImagenes()">
                        <i class="fa fa-cog"></i> Gestionar
                    </button>
                </div>
                <div class="j2b-card-body">
                    <div class="row">
                        <div class="col-4 col-md-3 mb-3" v-if="task.image">
                            <img :src="getImageUrl(task.image)" class="img-fluid img-thumbnail rounded" style="max-height: 120px; cursor: pointer; object-fit: cover;" @click="verGaleriaTarea(0)">
                        </div>
                        <div class="col-4 col-md-3 mb-3" v-for="(img, index) in task.images" :key="img.id">
                            <img :src="getImageUrl(img.image)" class="img-fluid img-thumbnail rounded" style="max-height: 120px; cursor: pointer; object-fit: cover;" @click="verGaleriaTarea(task.image ? index + 1 : index)">
                        </div>
                    </div>
                </div>
            </div>

            <div class="j2b-card mb-3" v-else-if="!userLimited">
                <div class="j2b-card-header">
                    <i class="fa fa-image mr-2"></i>Imagenes
                </div>
                <div class="j2b-card-body text-center">
                    <p class="text-muted mb-2">Sin imagenes</p>
                    <button class="btn btn-outline-primary btn-sm" @click="abrirModalImagenes()">
                        <i class="fa fa-plus"></i> Agregar Imagenes
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
                    <button type="button" class="j2b-modal-close" @click="modalEstatus = false"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <p>Tarea: <strong>{{ task.title }}</strong></p>
                    <p>Estatus actual: <span class="j2b-badge" :class="getBadgeClass(task.status)">{{ task.status }}</span></p>
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
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="modalEstatus = false">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="cambiarEstatus">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalEditar}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title"><i class="fa fa-edit" style="color: var(--j2b-primary);"></i> Editar Tarea #{{ task.folio || task.id }}</h5>
                    <button type="button" class="j2b-modal-close" @click="modalEditar = false"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <div v-if="errorForm" class="alert alert-danger">
                        <div v-for="error in erroresForm" :key="error">{{ error }}</div>
                    </div>
                    <form @submit.prevent="guardarTarea">
                        <p><em><strong style="color: var(--j2b-danger);">* Campos obligatorios</strong></em></p>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right"><strong style="color: var(--j2b-danger);">*</strong> Titulo</label>
                            <div class="col-md-9">
                                <input type="text" class="j2b-input" v-model="formTask.title" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right"><strong style="color: var(--j2b-danger);">*</strong> Prioridad</label>
                            <div class="col-md-3">
                                <select class="j2b-select" v-model="formTask.priority" required>
                                    <option value="1">1 - Muy baja</option>
                                    <option value="2">2 - Baja</option>
                                    <option value="3">3 - Media</option>
                                    <option value="4">4 - Alta</option>
                                    <option value="5">5 - Muy alta</option>
                                </select>
                            </div>
                            <label class="col-md-2 col-form-label text-md-right">Expiracion</label>
                            <div class="col-md-4">
                                <input type="date" class="j2b-input" v-model="formTask.expiration">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Descripcion</label>
                            <div class="col-md-9">
                                <textarea class="j2b-input" v-model="formTask.description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label text-md-right">Solucion</label>
                            <div class="col-md-9">
                                <textarea class="j2b-input" v-model="formTask.solution" rows="2"></textarea>
                            </div>
                        </div>
                        <!-- Campos extra dinámicos -->
                        <div v-if="extraFields.length > 0" class="mt-3 pt-3" style="border-top: 1px solid #eee;">
                            <p class="text-muted mb-2"><i class="fa fa-plus-square me-1"></i> <strong>Informacion Adicional</strong></p>
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
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="modalEditar = false">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="guardarTarea" :disabled="guardando">
                        <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                        <i class="fa fa-save" v-else></i>
                        {{ guardando ? 'Guardando...' : 'Guardar' }}
                    </button>
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
                    <button type="button" class="j2b-modal-close" @click="modalResena = false"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <p>Tarea: <strong>{{ task.title }}</strong></p>
                    <div class="j2b-form-group">
                        <label class="j2b-label">Resena:</label>
                        <textarea class="j2b-input" v-model="nuevaResena" rows="4" placeholder="Escribe la resena..."></textarea>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="modalResena = false">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="guardarResena">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Producto -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalProductos}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title"><i class="fa fa-cubes" style="color: var(--j2b-primary);"></i> Agregar Producto/Refaccion</h5>
                    <button type="button" class="j2b-modal-close" @click="modalProductos = false"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <p>Tarea: <strong>#{{ task.folio || task.id }} - {{ task.title }}</strong></p>
                    <div class="form-group">
                        <label>Buscar producto:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" v-model="buscarProducto"
                                   placeholder="Nombre, codigo o codigo de barras..."
                                   @input="buscarProductosDebounce">
                        </div>
                    </div>
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
                                    <span class="j2b-badge j2b-badge-info">Stock: {{ prod.stock }}</span>
                                    <br><small>${{ prod.retail }}</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div v-if="productoSeleccionado" class="card mb-3 border-success">
                        <div class="j2b-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1">{{ productoSeleccionado.name }}</h5>
                                    <small class="text-muted">Codigo: {{ productoSeleccionado.key }}</small>
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
                                    <small class="text-muted">Max: {{ productoSeleccionado.stock }}</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Notas (opcional):</label>
                                <input type="text" class="form-control" v-model="formProducto.notes"
                                       placeholder="Ej: Para reparacion de cabezal">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="modalProductos = false">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="agregarProductoTarea"
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
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-cog" style="color: var(--j2b-primary);"></i> Actualizar Cantidades
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="editandoProducto = null"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body" v-if="editandoProducto">
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-1">{{ editandoProducto.product ? editandoProducto.product.name : 'N/A' }}</h6>
                        <small class="text-muted">{{ editandoProducto.product ? editandoProducto.product.key : '' }}</small>
                        <div class="mt-2">
                            <span class="j2b-badge j2b-badge-primary badge-lg">
                                Entregados: {{ editandoProducto.qty_delivered }}
                            </span>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="font-weight-bold">Cuantos se devuelven al inventario?</label>
                        <input type="range" class="form-control-range slider-devolucion"
                               v-model.number="formEditProducto.qty_returned"
                               min="0" :max="editandoProducto.qty_delivered"
                               @input="syncUsadosDesdeDevueltos">
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
                    <div class="alert alert-light border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fa fa-check-circle text-success mr-1"></i>
                                <strong>Total:</strong> {{ formEditProducto.qty_used }} usados + {{ formEditProducto.qty_returned }} devueltos = {{ formEditProducto.qty_used + formEditProducto.qty_returned }}
                            </span>
                            <span class="j2b-badge j2b-badge-success" v-if="formEditProducto.qty_used + formEditProducto.qty_returned === editandoProducto.qty_delivered">
                                <i class="fa fa-check"></i> OK
                            </span>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Notas (opcional):</label>
                        <input type="text" class="form-control" v-model="formEditProducto.notes" placeholder="Ej: 2 fusibles quemados, 1 sobrante...">
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer" v-if="editandoProducto">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="editandoProducto = null">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="actualizarProductoTarea"
                            :disabled="formEditProducto.qty_used + formEditProducto.qty_returned !== editandoProducto.qty_delivered || guardando">
                        <i class="fa fa-spinner fa-spin" v-if="guardando"></i>
                        <i class="fa fa-save" v-else></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestion de Imagenes -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalImagenes}" role="dialog" style="display: none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-image" style="color: var(--j2b-primary);"></i> Gestionar Imagenes - #{{ task.folio || task.id }}
                    </h5>
                    <button type="button" class="j2b-modal-close" @click="modalImagenes = false"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6>Subir Nueva Imagen</h6>
                        <div class="input-group">
                            <input type="file" class="form-control" @change="seleccionarImagenTarea" accept="image/*" ref="inputImagenTarea">
                            <button class="btn btn-primary" @click="subirImagenTarea" :disabled="!imagenTareaSeleccionada || subiendoImagenTarea">
                                <i class="fa fa-spinner fa-spin" v-if="subiendoImagenTarea"></i>
                                <i class="fa fa-upload" v-else></i>
                                Subir
                            </button>
                        </div>
                        <small class="text-muted">Formatos: JPG, PNG, GIF, WebP. Maximo 2MB.</small>
                    </div>
                    <div class="mb-4">
                        <h6>Imagen Principal</h6>
                        <div v-if="task.image" class="position-relative d-inline-block">
                            <img :src="getImageUrl(task.image)" class="img-thumbnail" style="max-width: 200px; max-height: 200px; cursor: pointer;" @click="$viewImage(task.image)">
                            <button class="btn btn-danger btn-sm position-absolute" style="top: 5px; right: 5px;" @click="eliminarImagenPrincipalTarea" :disabled="eliminandoImagenTarea">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                        <div v-else class="text-muted">
                            <i class="fa fa-image fa-3x"></i>
                            <p>Sin imagen principal</p>
                        </div>
                    </div>
                    <div>
                        <h6>Imagenes Alternativas ({{ task.images ? task.images.length : 0 }})</h6>
                        <div class="row" v-if="task.images && task.images.length > 0">
                            <div class="col-md-3 mb-3" v-for="(img, index) in task.images" :key="img.id">
                                <div class="position-relative">
                                    <img :src="getImageUrl(img.image)" class="img-thumbnail" style="width: 100%; height: 120px; object-fit: cover; cursor: pointer;" @click="verGaleriaImagenes(index)">
                                    <button class="btn btn-danger btn-sm position-absolute" style="top: 5px; right: 5px;" @click="eliminarImagenAlternativaTarea(img.id)" :disabled="eliminandoImagenTarea">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted">
                            <p>Sin imagenes alternativas</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-secondary" @click="modalImagenes = false">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Paso Tracking -->
    <div class="modal fade" tabindex="-1" :class="{'mostrar': modalTracking}" role="dialog" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content j2b-modal-content">
                <div class="modal-header j2b-modal-header">
                    <h5 class="modal-title"><i class="fa fa-route" style="color: var(--j2b-primary);"></i> Cambiar Paso de Seguimiento</h5>
                    <button type="button" class="j2b-modal-close" @click="modalTracking = false"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body j2b-modal-body">
                    <div class="mb-3" v-if="trackingStepSeleccionado">
                        <label class="form-label text-muted small">Cambiar a:</label>
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background: #f8f9fa;">
                            <span class="step-color-dot-sm" :style="{ backgroundColor: trackingStepSeleccionado.color || '#6c757d' }">
                                <i :class="trackingStepSeleccionado.icon || 'fa fa-circle'" class="text-white" style="font-size: 10px;"></i>
                            </span>
                            <strong>{{ trackingStepSeleccionado.name }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Nota (opcional)</label>
                        <textarea class="form-control" v-model="trackingNota" rows="2" maxlength="500" placeholder="Agregar nota sobre este cambio..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Evidencia fotográfica (opcional, máx. 5)</label>
                        <input type="file" class="form-control form-control-sm" ref="trackingEvidenceInput" accept="image/*" multiple @change="onTrackingEvidenceSelected">
                        <div v-if="trackingEvidencePreview.length > 0" class="d-flex flex-wrap gap-2 mt-2">
                            <div v-for="(preview, idx) in trackingEvidencePreview" :key="idx" class="position-relative evidence-thumb-wrapper">
                                <img :src="preview" class="evidence-thumb">
                                <button class="evidence-delete-btn" @click="quitarEvidenciaPreview(idx)" title="Quitar">&times;</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer j2b-modal-footer">
                    <button type="button" class="j2b-btn j2b-btn-dark" @click="modalTracking = false">Cancelar</button>
                    <button type="button" class="j2b-btn j2b-btn-primary" @click="guardarCambioTracking()" :disabled="guardandoTracking">
                        <i class="fa" :class="guardandoTracking ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                        {{ guardandoTracking ? 'Guardando...' : 'Confirmar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
</template>

<script>
export default {
    props: ['taskInitial', 'shop', 'userLimited', 'serviceStepsInitial'],
    data() {
        return {
            task: JSON.parse(JSON.stringify(this.taskInitial)),

            // Modales
            modalEstatus: false,
            modalEditar: false,
            modalResena: false,
            modalProductos: false,
            modalImagenes: false,

            // Asignacion
            colaboradores: [],
            colaboradorSeleccionado: '',

            // Estatus
            nuevoEstatus: '',
            nuevaResena: '',

            // Editar
            formTask: {},
            extraFields: [],
            fieldValueMap: {},
            guardando: false,
            errorForm: false,
            erroresForm: [],

            // Productos
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
            },

            // Checklist
            nuevoChecklistTexto: '',
            editandoChecklistId: null,
            editandoChecklistTexto: '',
            dragIndex: null,
            dragOverIndex: null,
            catalogoChecklistResultados: [],
            mostrarDropdownChecklist: false,
            buscarCatalogoTimeout: null,

            // Imagenes
            imagenTareaSeleccionada: null,
            subiendoImagenTarea: false,
            eliminandoImagenTarea: false,

            // Service Tracking
            serviceSteps: this.serviceStepsInitial || [],
            modalTracking: false,
            trackingStepSeleccionado: null,
            trackingStepDetalle: null,
            trackingNota: '',
            guardandoTracking: false,
            trackingEvidenceFiles: [],
            trackingEvidencePreview: [],
            evidenciaGrandeUrl: null
        }
    },
    computed: {
        trackingEntryDetalle() {
            if (!this.trackingStepDetalle || !this.task.service_tracking_history) return null;
            // Buscar la última entrada del historial para este paso
            const entries = this.task.service_tracking_history.filter(e => e.step_id === this.trackingStepDetalle.id);
            return entries.length > 0 ? entries[entries.length - 1] : null;
        }
    },
    mounted() {
        this.loadColaboradores();
        this.loadExtraFields();
    },
    methods: {
        loadExtraFields() {
            axios.get('/admin/tasks/extra-fields').then(response => {
                if (response.data.ok) {
                    this.extraFields = response.data.extra_fields;
                }
            }).catch(() => {});
        },

        loadColaboradores() {
            axios.get('/admin/tasks/collaborators').then(response => {
                if (response.data.ok) {
                    this.colaboradores = response.data.collaborators;
                }
            }).catch(error => console.log(error));
        },

        reloadTask() {
            axios.get(`/admin/tasks/get?buscar=${this.task.id}&filtro_status=TODOS&filtro_ordenar=ID_DESC`).then(response => {
                let tasks = response.data.data;
                if (tasks && tasks.length > 0) {
                    this.task = tasks[0];
                }
            }).catch(error => console.log(error));
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

        formatDateTime(date) {
            if (!date) return '';
            return new Date(date).toLocaleString('es-MX');
        },

        // Estatus
        abrirModalEstatus() {
            this.nuevoEstatus = this.task.status;
            this.modalEstatus = true;
        },

        cambiarEstatus() {
            axios.put('/admin/tasks/update-status', {
                id: this.task.id,
                status: this.nuevoEstatus
            }).then(response => {
                if (response.data.ok) {
                    this.modalEstatus = false;
                    this.reloadTask();
                    Swal.fire('Exito', response.data.message, 'success');
                }
            }).catch(() => {
                Swal.fire('Error', 'Error al cambiar estatus', 'error');
            });
        },

        // Editar
        abrirModalEditar() {
            this.formTask = {
                id: this.task.id,
                title: this.task.title,
                description: this.task.description || '',
                solution: this.task.solution || '',
                priority: this.task.priority,
                expiration: this.task.expiration || '',
                client_id: this.task.client_id || ''
            };
            // Cargar valores de campos extra
            this.fieldValueMap = {};
            if (this.task.info_extra && this.task.info_extra.length > 0) {
                this.task.info_extra.forEach(ie => {
                    this.fieldValueMap[ie.field_name] = ie.value;
                });
            }
            this.errorForm = false;
            this.erroresForm = [];
            this.modalEditar = true;
        },

        guardarTarea() {
            this.guardando = true;
            this.errorForm = false;
            this.erroresForm = [];

            let payload = { ...this.formTask };
            if (this.extraFields.length > 0) {
                payload.info_extra = JSON.stringify(this.fieldValueMap);
            }

            axios.put('/admin/tasks/update', payload).then(response => {
                if (response.data.ok) {
                    this.modalEditar = false;
                    this.reloadTask();
                    Swal.fire('Exito', response.data.message, 'success');
                }
            }).catch(error => {
                this.errorForm = true;
                if (error.response && error.response.data.errors) {
                    this.erroresForm = Object.values(error.response.data.errors).flat();
                } else {
                    this.erroresForm = ['Error al guardar la tarea'];
                }
            }).finally(() => {
                this.guardando = false;
            });
        },

        // Asignacion
        asignarTarea() {
            if (!this.colaboradorSeleccionado) return;
            axios.post(`/admin/tasks/${this.task.id}/assign`, {
                user_id: this.colaboradorSeleccionado
            }).then(response => {
                if (response.data.ok) {
                    this.task = response.data.task;
                    this.colaboradorSeleccionado = '';
                    Swal.fire('Exito', response.data.message, 'success');
                }
            }).catch(() => {
                Swal.fire('Error', 'Error al asignar tarea', 'error');
            });
        },

        desasignarTarea() {
            axios.post(`/admin/tasks/${this.task.id}/unassign`).then(response => {
                if (response.data.ok) {
                    this.task = response.data.task;
                    Swal.fire('Exito', response.data.message, 'success');
                }
            }).catch(() => {
                Swal.fire('Error', 'Error al desasignar tarea', 'error');
            });
        },

        // Resena
        abrirModalResena() {
            this.nuevaResena = this.task.review || '';
            this.modalResena = true;
        },

        guardarResena() {
            axios.put('/admin/tasks/update-review', {
                id: this.task.id,
                review: this.nuevaResena
            }).then(response => {
                if (response.data.ok) {
                    this.modalResena = false;
                    this.reloadTask();
                    Swal.fire('Exito', response.data.message, 'success');
                }
            }).catch(() => {
                Swal.fire('Error', 'Error al guardar resena', 'error');
            });
        },

        // Activar/Desactivar
        activarTarea() {
            Swal.fire({
                title: 'Activar tarea?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Si, activar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.put(`/admin/tasks/${this.task.id}/activate`).then(response => {
                        if (response.data.ok) {
                            this.reloadTask();
                            Swal.fire('Activada', 'Tarea activada correctamente', 'success');
                        }
                    }).catch(() => {
                        Swal.fire('Error', 'Error al activar tarea', 'error');
                    });
                }
            });
        },

        desactivarTarea() {
            Swal.fire({
                title: 'Desactivar tarea?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Si, desactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.put(`/admin/tasks/${this.task.id}/deactivate`).then(response => {
                        if (response.data.ok) {
                            this.reloadTask();
                            Swal.fire('Desactivada', 'Tarea desactivada correctamente', 'success');
                        }
                    }).catch(() => {
                        Swal.fire('Error', 'Error al desactivar tarea', 'error');
                    });
                }
            });
        },

        // Eliminar
        confirmarEliminar() {
            Swal.fire({
                title: 'Eliminar tarea?',
                text: `#${this.task.folio || this.task.id} - ${this.task.title}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4757',
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/admin/tasks/${this.task.id}`).then(response => {
                        if (response.data.ok) {
                            Swal.fire('Eliminado', response.data.message, 'success').then(() => {
                                window.location.href = '/admin/tasks';
                            });
                        }
                    }).catch(() => {
                        Swal.fire('Error', 'Error al eliminar tarea', 'error');
                    });
                }
            });
        },

        // Productos
        abrirModalProductos() {
            this.modalProductos = true;
            this.buscarProducto = '';
            this.productosDisponibles = [];
            this.productoSeleccionado = null;
            this.formProducto = { product_id: '', qty_delivered: 1, notes: '' };
        },

        buscarProductosDebounce() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.buscarProductos();
            }, 300);
        },

        buscarProductos() {
            if (this.buscarProducto.length < 2) {
                this.productosDisponibles = [];
                return;
            }
            axios.get(`/admin/tasks/products?q=${this.buscarProducto}`).then(response => {
                if (response.data.ok) {
                    this.productosDisponibles = response.data.products;
                }
            }).catch(error => console.log(error));
        },

        seleccionarProducto(producto) {
            this.productoSeleccionado = producto;
            this.formProducto.product_id = producto.id;
            this.formProducto.qty_delivered = 1;
            this.productosDisponibles = [];
        },

        agregarProductoTarea() {
            this.guardando = true;
            axios.post(`/admin/tasks/${this.task.id}/products`, {
                product_id: this.formProducto.product_id,
                qty_delivered: this.formProducto.qty_delivered,
                notes: this.formProducto.notes
            }).then(response => {
                if (response.data.ok) {
                    if (!this.task.products) this.task.products = [];
                    this.task.products.push(response.data.taskProduct);
                    this.modalProductos = false;
                    Swal.fire('Exito', response.data.message, 'success');
                }
            }).catch(error => {
                let msg = error.response?.data?.message || 'Error al agregar producto';
                Swal.fire('Error', msg, 'error');
            }).finally(() => { this.guardando = false; });
        },

        editarProductoTarea(taskProduct) {
            this.editandoProducto = taskProduct;
            let usados = taskProduct.qty_used;
            let devueltos = taskProduct.qty_returned;
            if (usados === 0 && devueltos === 0) {
                usados = taskProduct.qty_delivered;
                devueltos = 0;
            }
            this.formEditProducto = { qty_used: usados, qty_returned: devueltos, notes: taskProduct.notes || '' };
        },

        syncUsadosDesdeDevueltos() {
            if (this.editandoProducto) {
                this.formEditProducto.qty_used = this.editandoProducto.qty_delivered - this.formEditProducto.qty_returned;
            }
        },

        actualizarProductoTarea() {
            this.guardando = true;
            axios.put(`/admin/tasks/${this.task.id}/products/${this.editandoProducto.id}`, {
                qty_used: this.formEditProducto.qty_used,
                qty_returned: this.formEditProducto.qty_returned,
                notes: this.formEditProducto.notes
            }).then(response => {
                if (response.data.ok) {
                    let idx = this.task.products.findIndex(p => p.id === this.editandoProducto.id);
                    if (idx !== -1) this.task.products[idx] = response.data.taskProduct;
                    this.editandoProducto = null;
                    Swal.fire('Exito', response.data.message, 'success');
                }
            }).catch(error => {
                let msg = error.response?.data?.message || 'Error al actualizar producto';
                Swal.fire('Error', msg, 'error');
            }).finally(() => { this.guardando = false; });
        },

        getPendientesBadge(tp) {
            let pendientes = tp.qty_delivered - tp.qty_used - tp.qty_returned;
            if (pendientes === 0) return 'badge-success';
            if (pendientes === tp.qty_delivered) return 'badge-secondary';
            return 'badge-warning';
        },

        getTotalEntregados() {
            if (!this.task.products) return 0;
            return this.task.products.reduce((sum, p) => sum + p.qty_delivered, 0);
        },

        getTotalUsados() {
            if (!this.task.products) return 0;
            return this.task.products.reduce((sum, p) => sum + p.qty_used, 0);
        },

        getTotalDevueltos() {
            if (!this.task.products) return 0;
            return this.task.products.reduce((sum, p) => sum + p.qty_returned, 0);
        },

        getTotalPendientes() {
            return this.getTotalEntregados() - this.getTotalUsados() - this.getTotalDevueltos();
        },

        getProductosSinFacturar() {
            if (!this.task.products) return 0;
            return this.task.products.filter(p => p.qty_used > 0 && !p.receipt_id).length;
        },

        generarNotaDesdeProductos() {
            window.location.href = `/admin/receipts/create?from_task=${this.task.id}`;
        },

        // Checklist
        agregarChecklistItem() {
            let text = this.nuevoChecklistTexto.trim();
            if (!text) return;
            this.catalogoChecklistResultados = [];
            this.mostrarDropdownChecklist = false;

            axios.post(`/admin/tasks/${this.task.id}/checklist`, { text: text }).then(response => {
                if (response.data.ok) {
                    if (!this.task.checklist_items) this.task.checklist_items = [];
                    this.task.checklist_items.push(response.data.item);
                    this.nuevoChecklistTexto = '';
                }
            }).catch(() => {
                Swal.fire('Error', 'Error al agregar item', 'error');
            });
        },

        toggleChecklistItem(item) {
            axios.put(`/admin/tasks/${this.task.id}/checklist/${item.id}/toggle`).then(response => {
                if (response.data.ok) {
                    item.is_completed = response.data.item.is_completed;
                }
            }).catch(error => console.error(error));
        },

        iniciarEdicionChecklist(item) {
            this.editandoChecklistId = item.id;
            this.editandoChecklistTexto = item.text;
            this.$nextTick(() => {
                if (this.$refs.inputEditChecklist) {
                    let inputs = this.$refs.inputEditChecklist;
                    let input = Array.isArray(inputs) ? inputs[0] : inputs;
                    if (input) input.focus();
                }
            });
        },

        guardarEdicionChecklist(item) {
            let text = this.editandoChecklistTexto.trim();
            if (!text || text === item.text) {
                this.editandoChecklistId = null;
                return;
            }
            axios.put(`/admin/tasks/${this.task.id}/checklist/${item.id}`, { text: text }).then(response => {
                if (response.data.ok) {
                    item.text = response.data.item.text;
                }
            }).catch(() => {
                Swal.fire('Error', 'Error al actualizar item', 'error');
            }).finally(() => { this.editandoChecklistId = null; });
        },

        eliminarChecklistItem(item) {
            axios.delete(`/admin/tasks/${this.task.id}/checklist/${item.id}`).then(response => {
                if (response.data.ok) {
                    let idx = this.task.checklist_items.findIndex(i => i.id === item.id);
                    if (idx !== -1) this.task.checklist_items.splice(idx, 1);
                }
            }).catch(() => {
                Swal.fire('Error', 'Error al eliminar item', 'error');
            });
        },

        onDragStart(index, event) {
            this.dragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
        },

        onDragOver(index) {
            this.dragOverIndex = index;
        },

        onDrop(index) {
            let items = this.task.checklist_items;
            if (this.dragIndex === null || this.dragIndex === index) return;
            let moved = items.splice(this.dragIndex, 1)[0];
            items.splice(index, 0, moved);
            this.dragIndex = null;
            this.dragOverIndex = null;
            let orderedIds = items.map(i => i.id);
            axios.put(`/admin/tasks/${this.task.id}/checklist/reorder`, { items: orderedIds })
                .catch(error => console.error('Error al reordenar:', error));
        },

        buscarCatalogoChecklist() {
            clearTimeout(this.buscarCatalogoTimeout);
            if (this.nuevoChecklistTexto.length < 2) {
                this.catalogoChecklistResultados = [];
                return;
            }
            this.buscarCatalogoTimeout = setTimeout(() => {
                axios.get('/admin/tasks/checklist/search-catalog', { params: { q: this.nuevoChecklistTexto } }).then(response => {
                    if (response.data.ok) {
                        this.catalogoChecklistResultados = response.data.results;
                        this.mostrarDropdownChecklist = true;
                    }
                }).catch(error => console.error(error));
            }, 300);
        },

        seleccionarCatalogoChecklist(item) {
            this.nuevoChecklistTexto = item.name;
            this.catalogoChecklistResultados = [];
            this.mostrarDropdownChecklist = false;
            this.agregarChecklistItem();
        },

        getChecklistCompleted() {
            if (!this.task.checklist_items) return 0;
            return this.task.checklist_items.filter(i => i.is_completed).length;
        },

        getChecklistPercent() {
            if (!this.task.checklist_items || this.task.checklist_items.length === 0) return 0;
            return Math.round((this.getChecklistCompleted() / this.task.checklist_items.length) * 100);
        },

        // Imagenes
        abrirModalImagenes() {
            this.imagenTareaSeleccionada = null;
            if (this.$refs.inputImagenTarea) {
                this.$refs.inputImagenTarea.value = '';
            }
            this.modalImagenes = true;
        },

        verGaleriaTarea(index) {
            let imagenes = [];
            if (this.task.image) imagenes.push(this.task.image);
            if (this.task.images && this.task.images.length > 0) {
                this.task.images.forEach(img => imagenes.push(img.image));
            }
            this.$viewImages(imagenes, index);
        },

        verGaleriaImagenes(indexAlternativa) {
            let imagenes = [];
            if (this.task.image) imagenes.push(this.task.image);
            if (this.task.images && this.task.images.length > 0) {
                this.task.images.forEach(img => imagenes.push(img.image));
            }
            let startIndex = this.task.image ? indexAlternativa + 1 : indexAlternativa;
            this.$viewImages(imagenes, startIndex);
        },

        seleccionarImagenTarea(event) {
            const file = event.target.files[0];
            if (file) this.imagenTareaSeleccionada = file;
        },

        subirImagenTarea() {
            if (!this.imagenTareaSeleccionada) return;
            this.subiendoImagenTarea = true;
            const formData = new FormData();
            formData.append('image', this.imagenTareaSeleccionada);

            axios.post(`/admin/tasks/${this.task.id}/upload-image`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(response => {
                if (response.data.ok) {
                    this.task = response.data.task;
                    this.imagenTareaSeleccionada = null;
                    if (this.$refs.inputImagenTarea) this.$refs.inputImagenTarea.value = '';
                    Swal.fire('Subida', response.data.message, 'success');
                }
            }).catch(() => {
                Swal.fire('Error', 'Error al subir la imagen', 'error');
            }).finally(() => { this.subiendoImagenTarea = false; });
        },

        eliminarImagenPrincipalTarea() {
            Swal.fire({
                title: 'Eliminar imagen principal?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.eliminandoImagenTarea = true;
                    axios.delete(`/admin/tasks/${this.task.id}/delete-main-image`).then(response => {
                        if (response.data.ok) {
                            this.task = response.data.task;
                            Swal.fire('Eliminada', response.data.message, 'success');
                        }
                    }).catch(() => {
                        Swal.fire('Error', 'Error al eliminar imagen', 'error');
                    }).finally(() => { this.eliminandoImagenTarea = false; });
                }
            });
        },

        // Service Tracking
        onStepClick(step) {
            const isCompleted = this.isStepCompleted(step);
            const isCurrent = this.task.current_service_step_id === step.id;

            if (isCompleted || isCurrent) {
                // Mostrar/ocultar detalle del paso
                if (this.trackingStepDetalle && this.trackingStepDetalle.id === step.id) {
                    this.trackingStepDetalle = null;
                } else {
                    this.trackingStepDetalle = step;
                }
            } else if (!this.userLimited) {
                // Paso pendiente → abrir modal para cambiar
                this.abrirModalTracking(step);
            }
        },

        getStepEvidenceCount(step) {
            if (!this.task.service_tracking_history) return 0;
            let count = 0;
            this.task.service_tracking_history.forEach(entry => {
                if (entry.step_id === step.id && entry.evidence) {
                    count += entry.evidence.length;
                }
            });
            return count;
        },

        isStepCompleted(step) {
            if (!this.task.service_tracking_history || !this.task.current_service_step_id) return false;
            const currentStepIndex = this.serviceSteps.findIndex(s => s.id === this.task.current_service_step_id);
            const stepIndex = this.serviceSteps.findIndex(s => s.id === step.id);
            return stepIndex <= currentStepIndex;
        },

        getStepCircleStyle(step) {
            const isCurrent = this.task.current_service_step_id === step.id;
            const isCompleted = this.isStepCompleted(step);
            if (isCurrent) {
                return { backgroundColor: step.color || '#0d6efd', boxShadow: '0 0 0 4px ' + (step.color || '#0d6efd') + '40' };
            }
            if (isCompleted) {
                return { backgroundColor: step.color || '#28a745' };
            }
            return { backgroundColor: '#dee2e6' };
        },

        abrirModalTracking(step) {
            if (step.id === this.task.current_service_step_id) return;
            this.trackingStepSeleccionado = step;
            this.trackingNota = '';
            this.trackingEvidenceFiles = [];
            this.trackingEvidencePreview = [];
            if (this.$refs.trackingEvidenceInput) this.$refs.trackingEvidenceInput.value = '';
            this.modalTracking = true;
        },

        onTrackingEvidenceSelected(event) {
            const files = Array.from(event.target.files).slice(0, 5);
            this.trackingEvidenceFiles = files;
            this.trackingEvidencePreview = [];
            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => this.trackingEvidencePreview.push(e.target.result);
                reader.readAsDataURL(file);
            });
        },

        quitarEvidenciaPreview(idx) {
            this.trackingEvidenceFiles.splice(idx, 1);
            this.trackingEvidencePreview.splice(idx, 1);
        },

        guardarCambioTracking() {
            if (!this.trackingStepSeleccionado) return;
            this.guardandoTracking = true;

            const formData = new FormData();
            formData.append('step_id', this.trackingStepSeleccionado.id);
            formData.append('_method', 'PUT');
            if (this.trackingNota) formData.append('notes', this.trackingNota);
            this.trackingEvidenceFiles.forEach(file => {
                formData.append('images[]', file);
            });

            axios.post(`/admin/tasks/${this.task.id}/service-tracking`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(response => {
                if (response.data.ok) {
                    this.modalTracking = false;
                    this.reloadTask();
                }
            }).catch(() => {
                Swal.fire('Error', 'Error al actualizar el seguimiento', 'error');
            }).finally(() => {
                this.guardandoTracking = false;
            });
        },

        subirEvidenciaDirecta(entry, event) {
            const file = event.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('image', file);

            axios.post(`/admin/tasks/${this.task.id}/tracking/${entry.id}/evidence`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(response => {
                if (response.data.ok) {
                    this.reloadTask();
                    Swal.fire('Subida', response.data.message, 'success');
                }
            }).catch(err => {
                const msg = err.response && err.response.data ? err.response.data.message : 'Error al subir evidencia';
                Swal.fire('Error', msg, 'error');
            });
            event.target.value = '';
        },

        verEvidencia(entry, evIndex) {
            const imagenes = entry.evidence.map(e => e.image);
            this.$viewImages(imagenes, evIndex);
        },

        eliminarEvidencia(evidenceId) {
            Swal.fire({
                title: 'Eliminar esta evidencia?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/admin/tasks/${this.task.id}/tracking-evidence/${evidenceId}`).then(response => {
                        if (response.data.ok) {
                            this.reloadTask();
                            Swal.fire('Eliminada', response.data.message, 'success');
                        }
                    }).catch(() => {
                        Swal.fire('Error', 'Error al eliminar evidencia', 'error');
                    });
                }
            });
        },

        eliminarImagenAlternativaTarea(imageId) {
            Swal.fire({
                title: 'Eliminar esta imagen?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.eliminandoImagenTarea = true;
                    axios.delete(`/admin/tasks/delete-alt-image/${imageId}`).then(response => {
                        if (response.data.ok) {
                            this.task = response.data.task;
                            Swal.fire('Eliminada', response.data.message, 'success');
                        }
                    }).catch(() => {
                        Swal.fire('Error', 'Error al eliminar imagen', 'error');
                    }).finally(() => { this.eliminandoImagenTarea = false; });
                }
            });
        }
    }
}
</script>

<style scoped>
    /* Header actions */
    .task-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .task-actions-main {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .task-actions-secondary {
        display: flex;
        gap: 4px;
        border-left: 1px solid #dee2e6;
        padding-left: 8px;
        margin-left: 2px;
    }
    @media (max-width: 768px) {
        .task-actions {
            width: 100%;
            flex-wrap: wrap;
        }
        .task-actions-main {
            width: 100%;
        }
        .task-actions-secondary {
            border-left: none;
            padding-left: 0;
            margin-left: 0;
        }
    }

    .modal-content {
        width: 100% !important;
        position: absolute !important;
    }
    .mostrar {
        display: block !important;
        opacity: 1 !important;
        position: fixed !important;
        background-color: rgba(26, 26, 46, 0.8) !important;
        overflow-y: auto;
        z-index: 1050;
    }
    .modal-cantidades.mostrar {
        z-index: 1060 !important;
    }
    .modal-cantidades .modal-dialog {
        margin-top: 10vh;
    }
    .slider-devolucion {
        width: 100%;
        height: 12px;
        -webkit-appearance: none;
        appearance: none;
        background: linear-gradient(to right, var(--j2b-danger) 0%, var(--j2b-success) 100%);
        border-radius: var(--j2b-radius-md);
        outline: none;
    }
    .slider-devolucion::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 28px;
        height: 28px;
        background: #fff;
        border: 3px solid var(--j2b-primary);
        border-radius: 50%;
        cursor: pointer;
        box-shadow: var(--j2b-shadow-sm);
    }
    .slider-devolucion::-moz-range-thumb {
        width: 28px;
        height: 28px;
        background: #fff;
        border: 3px solid var(--j2b-primary);
        border-radius: 50%;
        cursor: pointer;
        box-shadow: var(--j2b-shadow-sm);
    }

    /* Service Tracking */
    .tracking-flow {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        overflow-x: auto;
        padding: 10px 0;
    }
    .tracking-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        min-width: 90px;
        flex-shrink: 0;
    }
    .tracking-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        z-index: 1;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .tracking-circle:hover {
        transform: scale(1.15);
    }
    .tracking-step-current .tracking-circle {
        animation: pulse-tracking 2s infinite;
    }
    @keyframes pulse-tracking {
        0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(13, 110, 253, 0); }
    }
    .tracking-label {
        margin-top: 6px;
        font-size: 11px;
        text-align: center;
        max-width: 85px;
        color: #495057;
    }
    .tracking-badge {
        font-size: 9px;
        padding: 1px 6px;
        border-radius: 10px;
        background: var(--j2b-primary, #0d6efd);
        color: #fff;
        margin-top: 3px;
    }
    .tracking-line {
        position: absolute;
        top: 18px;
        left: 63px;
        width: calc(100% - 36px);
        height: 3px;
        background: #dee2e6;
        z-index: 0;
    }
    .tracking-line-completed {
        background: #28a745;
    }
    .tracking-history-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 4px;
        margin-right: 8px;
    }
    .step-color-dot-sm {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    /* Paso seleccionado */
    .tracking-step-selected .tracking-circle {
        outline: 3px solid rgba(13, 110, 253, 0.4);
        outline-offset: 2px;
    }
    .tracking-photo-badge {
        font-size: 9px;
        color: #6c757d;
        margin-top: 2px;
    }
    .tracking-detail-panel {
        animation: fadeIn 0.2s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .evidence-add-btn {
        width: 60px;
        height: 60px;
        border: 2px dashed #dee2e6;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #adb5bd;
        transition: all 0.2s;
    }
    .evidence-add-btn:hover {
        border-color: #0d6efd;
        color: #0d6efd;
    }
    /* Evidencia thumbnails */
    .evidence-thumb-wrapper {
        position: relative;
        display: inline-block;
    }
    .evidence-thumb {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
        cursor: pointer;
        border: 1px solid #dee2e6;
        transition: transform 0.2s;
    }
    .evidence-thumb:hover {
        transform: scale(1.1);
    }
    .evidence-delete-btn {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        border: none;
        font-size: 12px;
        line-height: 18px;
        padding: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
