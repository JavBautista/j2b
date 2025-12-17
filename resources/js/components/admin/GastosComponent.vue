<template>
<div>
    <div class="container-fluid">
        <!-- Contadores -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h3>{{ counters.total }}</h3>
                        <small>Total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h3>{{ counters.nuevos }}</h3>
                        <small>Nuevos</small>
                        <div class="small">${{ formatMoney(counters.suma_nuevos) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h3>{{ counters.pagados }}</h3>
                        <small>Pagados</small>
                        <div class="small">${{ formatMoney(counters.suma_pagados) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h3>{{ counters.activos }}</h3>
                        <small>Activos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-white bg-secondary">
                    <div class="card-body text-center">
                        <h3>{{ counters.inactivos }}</h3>
                        <small>Inactivos</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-money"></i> Gastos de {{ shop.name }}</span>
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
                    <button type="button" @click="abrirModalCrear()" class="btn btn-success">
                        <i class="fa fa-plus"></i> Nuevo Gasto
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" v-model="buscar" class="form-control" placeholder="Buscar por nombre..." @keyup.enter="loadExpenses(1)">
                            <button type="button" @click="loadExpenses(1)" class="btn btn-primary">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" v-model="filtroStatus" @change="loadExpenses(1)">
                            <option value="TODOS">Todos los status</option>
                            <option value="NUEVO">Nuevos</option>
                            <option value="PAGADO">Pagados</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" v-model="filtroActivo" @change="loadExpenses(1)">
                            <option value="TODOS">Todos</option>
                            <option value="ACTIVOS">Activos</option>
                            <option value="INACTIVOS">Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" v-model="filtroOrdenar" @change="loadExpenses(1)">
                            <option value="ID_DESC">Más recientes</option>
                            <option value="ID_ASC">Más antiguos</option>
                            <option value="FECHA_DESC">Fecha (desc)</option>
                            <option value="FECHA_ASC">Fecha (asc)</option>
                            <option value="TOTAL_DESC">Mayor total</option>
                            <option value="TOTAL_ASC">Menor total</option>
                        </select>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Cargando gastos...</p>
                </div>

                <!-- VISTA CARDS -->
                <div class="row" v-if="!loading && vistaActual === 'cards'">
                    <div class="col-md-4 col-lg-3 mb-4" v-for="expense in expenses" :key="expense.id">
                        <div class="card expense-card h-100" :class="{'inactive-card': !expense.active}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge" :class="getStatusBadge(expense.status)">{{ expense.status }}</span>
                                    <span v-if="expense.is_tax_invoiced" class="badge bg-info ms-1">Facturado</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModalVer(expense)">
                                            <i class="fa fa-eye text-info"></i> Ver Detalle
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="abrirModalEditar(expense)">
                                            <i class="fa fa-edit text-primary"></i> Editar
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="cambiarStatus(expense)">
                                            <i class="fa fa-refresh text-warning"></i> Cambiar Status
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="cambiarTotal(expense)">
                                            <i class="fa fa-money text-success"></i> Cambiar Total
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="cambiarFecha(expense)">
                                            <i class="fa fa-calendar text-info"></i> Cambiar Fecha
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="toggleFacturado(expense)">
                                            <i class="fa fa-file-text-o" :class="expense.is_tax_invoiced ? 'text-secondary' : 'text-info'"></i>
                                            {{ expense.is_tax_invoiced ? 'Marcar NO facturado' : 'Marcar Facturado' }}
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="toggleActive(expense)">
                                            <i class="fa" :class="expense.active ? 'fa-toggle-off text-danger' : 'fa-toggle-on text-success'"></i>
                                            {{ expense.active ? 'Desactivar' : 'Activar' }}
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body" @click="abrirModalVer(expense)" style="cursor: pointer;">
                                <h6 class="card-title">#{{ expense.id }} - {{ expense.name }}</h6>
                                <div class="expense-info">
                                    <div class="info-item">
                                        <i class="fa fa-money text-success"></i>
                                        <strong>${{ formatMoney(expense.total) }}</strong>
                                    </div>
                                    <div class="info-item">
                                        <i class="fa fa-calendar text-muted"></i>
                                        <span>{{ formatDate(expense.date) }}</span>
                                    </div>
                                    <div class="info-item" v-if="expense.description">
                                        <i class="fa fa-align-left text-muted"></i>
                                        <span class="text-truncate">{{ expense.description }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <span v-if="!expense.active" class="badge bg-danger me-1">Inactivo</span>
                                    Creado: {{ formatDateTime(expense.created_at) }}
                                </small>
                            </div>
                        </div>
                    </div>
                    <div v-if="expenses.length === 0" class="col-12 text-center py-5">
                        <i class="fa fa-money fa-3x text-muted"></i>
                        <p class="mt-2 text-muted">No hay gastos registrados</p>
                    </div>
                </div>

                <!-- VISTA TABLA -->
                <div class="table-responsive" v-if="!loading && vistaActual === 'tabla'">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Fecha</th>
                                <th class="text-right">Total</th>
                                <th>Status</th>
                                <th>Facturado</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="expense in expenses" :key="expense.id" :class="{'table-secondary': !expense.active}">
                                <td><strong>#{{ expense.id }}</strong></td>
                                <td>{{ expense.name }}</td>
                                <td>{{ formatDate(expense.date) }}</td>
                                <td class="text-right"><strong>${{ formatMoney(expense.total) }}</strong></td>
                                <td><span class="badge" :class="getStatusBadge(expense.status)">{{ expense.status }}</span></td>
                                <td>
                                    <span v-if="expense.is_tax_invoiced" class="badge bg-info">Sí</span>
                                    <span v-else class="badge bg-secondary">No</span>
                                </td>
                                <td>
                                    <span v-if="expense.active" class="badge bg-success">Activo</span>
                                    <span v-else class="badge bg-danger">Inactivo</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info btn-sm" @click="abrirModalVer(expense)" title="Ver">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm" @click="abrirModalEditar(expense)" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm" :class="expense.active ? 'btn-danger' : 'btn-success'" @click="toggleActive(expense)" :title="expense.active ? 'Desactivar' : 'Activar'">
                                            <i class="fa" :class="expense.active ? 'fa-toggle-off' : 'fa-toggle-on'"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="expenses.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">No hay gastos registrados</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <nav v-if="pagination.last_page > 1">
                    <ul class="pagination justify-content-center">
                        <li class="page-item" :class="{disabled: pagination.current_page === 1}">
                            <a class="page-link" href="#" @click.prevent="loadExpenses(pagination.current_page - 1)">Ant</a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page" :class="{active: page === pagination.current_page}">
                            <a class="page-link" href="#" @click.prevent="loadExpenses(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{disabled: pagination.current_page === pagination.last_page}">
                            <a class="page-link" href="#" @click.prevent="loadExpenses(pagination.current_page + 1)">Sig</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalle -->
    <div class="modal fade" :class="{'mostrar': modalVer}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" v-if="expenseSeleccionado">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fa fa-money"></i> Gasto #{{ expenseSeleccionado.id }}</h5>
                    <button type="button" class="btn-close btn-close-white" @click="cerrarModalVer()"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Información General</h6>
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th width="40%">Nombre:</th>
                                        <td>{{ expenseSeleccionado.name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Descripción:</th>
                                        <td>{{ expenseSeleccionado.description || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Fecha:</th>
                                        <td>{{ formatDate(expenseSeleccionado.date) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total:</th>
                                        <td><strong class="text-success">${{ formatMoney(expenseSeleccionado.total) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td><span class="badge" :class="getStatusBadge(expenseSeleccionado.status)">{{ expenseSeleccionado.status }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>Facturado:</th>
                                        <td>
                                            <span v-if="expenseSeleccionado.is_tax_invoiced" class="badge bg-info">Sí</span>
                                            <span v-else class="badge bg-secondary">No</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Estado:</th>
                                        <td>
                                            <span v-if="expenseSeleccionado.active" class="badge bg-success">Activo</span>
                                            <span v-else class="badge bg-danger">Inactivo</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <!-- Adjuntos -->
                            <h6>Adjuntos <button class="btn btn-sm btn-outline-primary ms-2" @click="$refs.fileInput.click()"><i class="fa fa-plus"></i></button></h6>
                            <input type="file" ref="fileInput" style="display:none" @change="uploadAttachment" accept="image/*,.pdf">
                            <div v-if="attachments.length === 0" class="text-muted small">Sin adjuntos</div>
                            <div class="row">
                                <div class="col-4 mb-2" v-for="att in attachments" :key="att.id">
                                    <div class="position-relative">
                                        <img v-if="isImage(att.file_type)" :src="'/storage/' + att.file_path" class="img-thumbnail" style="max-height: 80px; cursor: pointer;" @click="openImage('/storage/' + att.file_path)">
                                        <a v-else :href="'/storage/' + att.file_path" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa fa-file"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger position-absolute top-0 end-0" style="padding: 0 5px;" @click="deleteAttachment(att.id)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Historial -->
                            <h6 class="mt-3">Historial</h6>
                            <div style="max-height: 200px; overflow-y: auto;">
                                <div v-if="logs.length === 0" class="text-muted small">Sin historial</div>
                                <div v-for="log in logs" :key="log.id" class="small border-bottom py-1">
                                    <strong>{{ log.action }}</strong> - {{ log.description }}
                                    <br><span class="text-muted">{{ log.user }} - {{ formatDateTime(log.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalVer()">Cerrar</button>
                    <button type="button" class="btn btn-primary" @click="abrirModalEditar(expenseSeleccionado); cerrarModalVer();">Editar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear -->
    <div class="modal fade" :class="{'mostrar': modalCrear}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fa fa-plus"></i> Nuevo Gasto</h5>
                    <button type="button" class="btn-close btn-close-white" @click="cerrarModalCrear()"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" v-model="formCrear.name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" v-model="formCrear.description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha *</label>
                            <input type="date" class="form-control" v-model="formCrear.date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total *</label>
                            <input type="number" class="form-control" v-model="formCrear.total" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalCrear()">Cancelar</button>
                    <button type="button" class="btn btn-success" @click="guardarGasto()" :disabled="guardando">
                        <span v-if="guardando"><i class="fa fa-spinner fa-spin"></i> Guardando...</span>
                        <span v-else><i class="fa fa-save"></i> Guardar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div class="modal fade" :class="{'mostrar': modalEditar}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-edit"></i> Editar Gasto</h5>
                    <button type="button" class="btn-close btn-close-white" @click="cerrarModalEditar()"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" v-model="formEditar.name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" v-model="formEditar.description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModalEditar()">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="actualizarGasto()" :disabled="guardando">
                        <span v-if="guardando"><i class="fa fa-spinner fa-spin"></i> Guardando...</span>
                        <span v-else><i class="fa fa-save"></i> Actualizar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
export default {
    name: 'GastosComponent',
    props: {
        shop: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            expenses: [],
            pagination: { total: 0, current_page: 1, per_page: 12, last_page: 1 },
            offset: 3,

            buscar: '',
            filtroStatus: 'TODOS',
            filtroActivo: 'ACTIVOS',
            filtroOrdenar: 'ID_DESC',

            counters: { total: 0, nuevos: 0, pagados: 0, activos: 0, inactivos: 0, suma_nuevos: 0, suma_pagados: 0 },

            loading: false,
            guardando: false,
            vistaActual: localStorage.getItem('admin_vista') || 'cards',

            modalVer: false,
            modalCrear: false,
            modalEditar: false,

            expenseSeleccionado: null,
            attachments: [],
            logs: [],

            formCrear: {
                name: '',
                description: '',
                date: new Date().toISOString().substr(0, 10),
                total: 0
            },
            formEditar: {
                id: null,
                name: '',
                description: ''
            }
        }
    },
    watch: {
        vistaActual(newVal) {
            localStorage.setItem('admin_vista', newVal);
        }
    },
    computed: {
        pagesNumber() {
            if (!this.pagination.last_page) return [];
            let from = Math.max(1, this.pagination.current_page - this.offset);
            let to = Math.min(this.pagination.last_page, this.pagination.current_page + this.offset);
            let pages = [];
            for (let i = from; i <= to; i++) pages.push(i);
            return pages;
        }
    },
    mounted() {
        this.loadExpenses(1);
        this.loadCounters();
    },
    methods: {
        async loadExpenses(page) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    buscar: this.buscar,
                    filtro_status: this.filtroStatus,
                    filtro_activo: this.filtroActivo,
                    filtro_ordenar: this.filtroOrdenar
                });
                const response = await fetch(`/admin/gastos/get?${params}`);
                const data = await response.json();
                this.expenses = data.data;
                this.pagination = {
                    total: data.total,
                    current_page: data.current_page,
                    per_page: data.per_page,
                    last_page: data.last_page
                };
            } catch (error) {
                console.error('Error cargando gastos:', error);
            }
            this.loading = false;
        },

        async loadCounters() {
            try {
                const response = await fetch('/admin/gastos/counters');
                this.counters = await response.json();
            } catch (error) {
                console.error('Error cargando contadores:', error);
            }
        },

        formatMoney(value) {
            return parseFloat(value || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        },

        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('es-MX');
        },

        formatDateTime(date) {
            if (!date) return '-';
            return new Date(date).toLocaleString('es-MX');
        },

        getStatusBadge(status) {
            return status === 'PAGADO' ? 'bg-success' : 'bg-warning';
        },

        isImage(fileType) {
            return fileType && fileType.startsWith('image/');
        },

        openImage(url) {
            // Usar el visor de imágenes global
            this.$viewImage(url);
        },

        // Modal Ver
        async abrirModalVer(expense) {
            this.expenseSeleccionado = expense;
            this.modalVer = true;
            await this.loadAttachments(expense.id);
            await this.loadLogs(expense.id);
        },

        cerrarModalVer() {
            this.modalVer = false;
            this.expenseSeleccionado = null;
            this.attachments = [];
            this.logs = [];
        },

        async loadAttachments(id) {
            try {
                const response = await fetch(`/admin/gastos/${id}/attachments`);
                const data = await response.json();
                this.attachments = data.attachments || [];
            } catch (error) {
                console.error('Error cargando adjuntos:', error);
            }
        },

        async loadLogs(id) {
            try {
                const response = await fetch(`/admin/gastos/${id}/logs`);
                const data = await response.json();
                this.logs = data.logs || [];
            } catch (error) {
                console.error('Error cargando historial:', error);
            }
        },

        async uploadAttachment(event) {
            const file = event.target.files[0];
            if (!file || !this.expenseSeleccionado) return;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch(`/admin/gastos/${this.expenseSeleccionado.id}/attachments`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: formData
                });
                const data = await response.json();
                if (data.ok) {
                    await this.loadAttachments(this.expenseSeleccionado.id);
                    await this.loadLogs(this.expenseSeleccionado.id);
                }
            } catch (error) {
                console.error('Error subiendo archivo:', error);
            }
            event.target.value = '';
        },

        async deleteAttachment(id) {
            if (!confirm('¿Eliminar este adjunto?')) return;
            try {
                const response = await fetch(`/admin/gastos/attachments/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await response.json();
                if (data.ok) {
                    await this.loadAttachments(this.expenseSeleccionado.id);
                    await this.loadLogs(this.expenseSeleccionado.id);
                }
            } catch (error) {
                console.error('Error eliminando adjunto:', error);
            }
        },

        // Modal Crear
        abrirModalCrear() {
            this.formCrear = {
                name: '',
                description: '',
                date: new Date().toISOString().substr(0, 10),
                total: 0
            };
            this.modalCrear = true;
        },

        cerrarModalCrear() {
            this.modalCrear = false;
        },

        async guardarGasto() {
            if (!this.formCrear.name || !this.formCrear.date || this.formCrear.total < 0) {
                alert('Complete los campos requeridos');
                return;
            }

            this.guardando = true;
            try {
                const response = await fetch('/admin/gastos/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.formCrear)
                });
                const data = await response.json();
                if (data.ok) {
                    this.cerrarModalCrear();
                    this.loadExpenses(1);
                    this.loadCounters();
                }
            } catch (error) {
                console.error('Error creando gasto:', error);
            }
            this.guardando = false;
        },

        // Modal Editar
        abrirModalEditar(expense) {
            this.formEditar = {
                id: expense.id,
                name: expense.name,
                description: expense.description || ''
            };
            this.modalEditar = true;
        },

        cerrarModalEditar() {
            this.modalEditar = false;
        },

        async actualizarGasto() {
            if (!this.formEditar.name) {
                alert('El nombre es requerido');
                return;
            }

            this.guardando = true;
            try {
                const response = await fetch(`/admin/gastos/${this.formEditar.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.formEditar)
                });
                const data = await response.json();
                if (data.ok) {
                    this.cerrarModalEditar();
                    this.loadExpenses(this.pagination.current_page);
                }
            } catch (error) {
                console.error('Error actualizando gasto:', error);
            }
            this.guardando = false;
        },

        // Acciones rápidas
        async cambiarStatus(expense) {
            const nuevoStatus = expense.status === 'NUEVO' ? 'PAGADO' : 'NUEVO';
            if (!confirm(`¿Cambiar status a ${nuevoStatus}?`)) return;

            try {
                const response = await fetch(`/admin/gastos/${expense.id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: nuevoStatus })
                });
                const data = await response.json();
                if (data.ok) {
                    expense.status = nuevoStatus;
                    this.loadCounters();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async cambiarTotal(expense) {
            const nuevoTotal = prompt('Nuevo total:', expense.total);
            if (nuevoTotal === null) return;

            try {
                const response = await fetch(`/admin/gastos/${expense.id}/total`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ total: parseFloat(nuevoTotal) })
                });
                const data = await response.json();
                if (data.ok) {
                    expense.total = parseFloat(nuevoTotal);
                    this.loadCounters();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async cambiarFecha(expense) {
            const nuevaFecha = prompt('Nueva fecha (YYYY-MM-DD):', expense.date);
            if (nuevaFecha === null) return;

            try {
                const response = await fetch(`/admin/gastos/${expense.id}/fecha`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ date: nuevaFecha })
                });
                const data = await response.json();
                if (data.ok) {
                    expense.date = data.expense.date;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async toggleActive(expense) {
            const accion = expense.active ? 'desactivar' : 'activar';
            if (!confirm(`¿${accion} este gasto?`)) return;

            try {
                const response = await fetch(`/admin/gastos/${expense.id}/toggle-active`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.ok) {
                    expense.active = data.expense.active;
                    this.loadCounters();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async toggleFacturado(expense) {
            const accion = expense.is_tax_invoiced ? 'NO facturado' : 'facturado';
            if (!confirm(`¿Marcar como ${accion}?`)) return;

            try {
                const response = await fetch(`/admin/gastos/${expense.id}/toggle-facturado`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.ok) {
                    expense.is_tax_invoiced = data.expense.is_tax_invoiced;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    }
}
</script>

<style scoped>
.modal-content {
    width: 100% !important;
    position: absolute !important;
}
.mostrar {
    display: block !important;
    opacity: 1 !important;
    position: fixed !important;
    background-color: #3c29297a !important;
    overflow: scroll;
}

.expense-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.expense-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.expense-card .card-header {
    background: linear-gradient(135deg, #00F5A0 0%, #00D9F5 100%);
    color: #0D1117;
    border: none;
    padding: 0.75rem 1rem;
}

.expense-card.inactive-card {
    opacity: 0.7;
}

.expense-card.inactive-card .card-header {
    background: linear-gradient(135deg, #868e96 0%, #6c757d 100%);
    color: white;
}

.expense-info {
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

.expense-card .card-footer {
    background: transparent;
    border-top: 1px solid #eee;
    padding: 0.75rem 1rem;
}
</style>
