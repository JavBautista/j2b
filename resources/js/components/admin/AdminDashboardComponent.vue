<template>
    <div class="dashboard-summary">
        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-2">Cargando resumen...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="text-center py-5">
            <i class="fa fa-cloud fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted">No se pudo cargar el resumen</p>
            <button class="btn btn-outline-primary btn-sm" @click="cargar">
                <i class="fa fa-refresh me-1"></i> Reintentar
            </button>
        </div>

        <!-- Dashboard Content -->
        <div v-else>
            <!-- Fecha -->
            <p class="text-muted mb-3">
                <i class="fa fa-calendar me-1"></i> {{ fechaHoy }}
            </p>

            <!-- Ventas Hoy + Mes -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <div class="summary-card summary-card--success">
                        <div class="summary-card__header">
                            <i class="fa fa-calendar-check-o"></i>
                            <span>HOY</span>
                        </div>
                        <div class="summary-card__value">{{ currencySymbol }}{{ formatMoney(data.ventas_hoy.ingresos) }}</div>
                        <div class="summary-card__sub text-success">Cobrado ({{ data.ventas_hoy.num_pagos }} pagos)</div>
                        <div class="summary-card__sub">{{ data.ventas_hoy.notas_cantidad }} notas creadas</div>
                        <div class="summary-card__sub">Facturado: {{ currencySymbol }}{{ formatMoney(data.ventas_hoy.notas_total) }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="summary-card summary-card--success">
                        <div class="summary-card__header">
                            <i class="fa fa-calendar"></i>
                            <span>MES</span>
                        </div>
                        <div class="summary-card__value">{{ currencySymbol }}{{ formatMoney(data.ventas_mes.ingresos) }}</div>
                        <div class="summary-card__sub text-success">Cobrado ({{ data.ventas_mes.num_pagos }} pagos)</div>
                        <div class="summary-card__sub">{{ data.ventas_mes.notas_cantidad }} notas creadas</div>
                        <div class="summary-card__sub">Facturado: {{ currencySymbol }}{{ formatMoney(data.ventas_mes.notas_total) }}</div>
                    </div>
                </div>
            </div>

            <!-- Tareas -->
            <div class="summary-card summary-card--warning mb-3">
                <div class="summary-card__header">
                    <i class="fa fa-clipboard"></i>
                    <span>TAREAS DEL MES</span>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ data.tareas.mes.nuevo }} Nuevo</span>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">{{ data.tareas.mes.pendiente }} Pendiente</span>
                    <span class="badge bg-success rounded-pill px-3 py-2">{{ data.tareas.mes.atendido }} Atendido</span>
                </div>
                <div class="summary-card__header mt-3">
                    <i class="fa fa-calendar-o"></i>
                    <span>SEMANA ({{ data.tareas.semana.inicio }} - {{ data.tareas.semana.fin }})</span>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ data.tareas.semana.nuevo }} Nuevo</span>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">{{ data.tareas.semana.pendiente }} Pendiente</span>
                    <span class="badge bg-success rounded-pill px-3 py-2">{{ data.tareas.semana.atendido }} Atendido</span>
                </div>
                <div class="summary-card__header mt-3" v-if="data.tareas.anteriores">
                    <i class="fa fa-history"></i>
                    <span>ANTERIORES AL MES</span>
                </div>
                <div class="d-flex gap-2 mt-2" v-if="data.tareas.anteriores">
                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ data.tareas.anteriores.nuevo }} Nuevo</span>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">{{ data.tareas.anteriores.pendiente }} Pendiente</span>
                </div>
            </div>

            <!-- Usuarios -->
            <div class="summary-card summary-card--primary mb-3" v-if="data.usuarios">
                <div class="summary-card__header">
                    <i class="fa fa-users"></i>
                    <span>USUARIOS</span>
                </div>
                <div class="usuarios-grid mt-2">
                    <div class="usuarios-grid__item">
                        <div class="fw-bold fs-5">{{ data.usuarios.admins }}</div>
                        <small class="text-muted">Admins</small>
                    </div>
                    <div class="usuarios-grid__item">
                        <div class="fw-bold fs-5">{{ data.usuarios.admins_limited }}</div>
                        <small class="text-muted">Limitados</small>
                    </div>
                    <div class="usuarios-grid__item">
                        <div class="fw-bold fs-5">{{ data.usuarios.colaboradores }}</div>
                        <small class="text-muted">Colabs.</small>
                    </div>
                    <div class="usuarios-grid__item">
                        <div class="fw-bold fs-5">{{ data.usuarios.clientes_total }}</div>
                        <small class="text-muted">Clientes</small>
                    </div>
                    <div class="usuarios-grid__item">
                        <div class="fw-bold fs-5">{{ data.usuarios.clientes_con_app }}</div>
                        <small class="text-muted">Con App</small>
                    </div>
                </div>
            </div>

            <!-- Adeudos + Stock Bajo -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <div class="summary-card summary-card--danger">
                        <div class="summary-card__header">
                            <i class="fa fa-exclamation-circle"></i>
                            <span>ADEUDOS</span>
                        </div>
                        <div class="summary-card__value">{{ currencySymbol }}{{ formatMoney(data.adeudos.total) }}</div>
                        <div class="summary-card__sub">{{ data.adeudos.num_clientes }} clientes</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="summary-card summary-card--warning">
                        <div class="summary-card__header">
                            <i class="fa fa-warning"></i>
                            <span>STOCK BAJO</span>
                        </div>
                        <div class="summary-card__value">{{ data.stock_bajo.count }}</div>
                        <div class="summary-card__sub">productos</div>
                    </div>
                </div>
            </div>

            <!-- Rankings -->
            <div class="row g-3 mb-3">
                <!-- Tareas por Colaborador -->
                <div class="col-12" v-if="data.tareas_por_colaborador && data.tareas_por_colaborador.length">
                    <div class="summary-card summary-card--info">
                        <div class="summary-card__header">
                            <i class="fa fa-user"></i>
                            <span>TAREAS ATENDIDAS POR COLABORADOR</span>
                        </div>
                        <table class="table table-sm table-borderless mt-2 mb-0">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th class="text-center" style="width:50px">Mes</th>
                                    <th class="text-center" style="width:50px">Sem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(c, i) in data.tareas_por_colaborador" :key="i">
                                    <td style="width:30px">
                                        <span class="ranking-num">{{ i + 1 }}</span>
                                    </td>
                                    <td class="text-truncate" style="max-width:150px">{{ c.nombre }}</td>
                                    <td class="text-center fw-bold">{{ c.total_mes }}</td>
                                    <td class="text-center">{{ c.total_semana }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Clientes Top Incidencias -->
                <div class="col-md-6" v-if="data.cliente_top_incidencias && data.cliente_top_incidencias.length">
                    <div class="summary-card summary-card--danger">
                        <div class="summary-card__header">
                            <i class="fa fa-flag"></i>
                            <span>CLIENTES CON MAS REPORTES</span>
                        </div>
                        <table class="table table-sm table-borderless mt-2 mb-0">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th class="text-center" style="width:50px">Mes</th>
                                    <th class="text-center" style="width:50px">Sem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(c, i) in data.cliente_top_incidencias" :key="i">
                                    <td style="width:30px">
                                        <span class="ranking-num">{{ i + 1 }}</span>
                                    </td>
                                    <td class="text-truncate" style="max-width:150px">{{ c.nombre }}</td>
                                    <td class="text-center fw-bold">{{ c.total_mes }}</td>
                                    <td class="text-center">{{ c.total_semana }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Productos Mas Vendidos - Mes -->
                <div class="col-md-6" v-if="data.productos_mas_vendidos_mes && data.productos_mas_vendidos_mes.length">
                    <div class="summary-card summary-card--success">
                        <div class="summary-card__header">
                            <i class="fa fa-line-chart"></i>
                            <span>MAS VENDIDOS DEL MES</span>
                        </div>
                        <table class="table table-sm table-borderless mt-2 mb-0">
                            <tbody>
                                <tr v-for="(p, i) in data.productos_mas_vendidos_mes" :key="i">
                                    <td style="width:30px">
                                        <span class="ranking-num">{{ i + 1 }}</span>
                                    </td>
                                    <td class="text-truncate" style="max-width:150px">{{ p.nombre }}</td>
                                    <td class="text-end fw-bold">{{ p.cantidad }} uds</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Productos Mas Vendidos - Semana -->
                <div class="col-md-6" v-if="data.productos_mas_vendidos_semana && data.productos_mas_vendidos_semana.length">
                    <div class="summary-card summary-card--success">
                        <div class="summary-card__header">
                            <i class="fa fa-line-chart"></i>
                            <span>MAS VENDIDOS DE LA SEMANA</span>
                        </div>
                        <table class="table table-sm table-borderless mt-2 mb-0">
                            <tbody>
                                <tr v-for="(p, i) in data.productos_mas_vendidos_semana" :key="i">
                                    <td style="width:30px">
                                        <span class="ranking-num">{{ i + 1 }}</span>
                                    </td>
                                    <td class="text-truncate" style="max-width:150px">{{ p.nombre }}</td>
                                    <td class="text-end fw-bold">{{ p.cantidad }} uds</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Productos Mas Vendidos - General -->
                <div class="col-md-6" v-if="data.productos_mas_vendidos && data.productos_mas_vendidos.length">
                    <div class="summary-card summary-card--success">
                        <div class="summary-card__header">
                            <i class="fa fa-line-chart"></i>
                            <span>MAS VENDIDOS (GENERAL)</span>
                        </div>
                        <table class="table table-sm table-borderless mt-2 mb-0">
                            <tbody>
                                <tr v-for="(p, i) in data.productos_mas_vendidos" :key="i">
                                    <td style="width:30px">
                                        <span class="ranking-num">{{ i + 1 }}</span>
                                    </td>
                                    <td class="text-truncate" style="max-width:150px">{{ p.nombre }}</td>
                                    <td class="text-end fw-bold">{{ p.cantidad }} uds</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Acciones Rapidas -->
            <div class="summary-card mb-3">
                <div class="summary-card__header">
                    <i class="fa fa-bolt"></i>
                    <span>ACCIONES RAPIDAS</span>
                </div>
                <div class="d-flex justify-content-center gap-4 mt-3">
                    <a href="/admin/receipts" class="quick-btn text-center text-decoration-none">
                        <div class="quick-btn__icon bg-gradient-success">
                            <i class="fa fa-list-alt"></i>
                        </div>
                        <small class="d-block mt-1 text-muted">Ventas</small>
                    </a>
                    <a href="/admin/clients" class="quick-btn text-center text-decoration-none">
                        <div class="quick-btn__icon bg-gradient-info">
                            <i class="fa fa-users"></i>
                        </div>
                        <small class="d-block mt-1 text-muted">Clientes</small>
                    </a>
                    <a href="/admin/tasks" class="quick-btn text-center text-decoration-none">
                        <div class="quick-btn__icon bg-gradient-primary">
                            <i class="fa fa-tasks"></i>
                        </div>
                        <small class="d-block mt-1 text-muted">Tareas</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AdminDashboardComponent',
    props: {
        currencySymbol: {
            type: String,
            default: '$'
        }
    },
    data() {
        return {
            loading: true,
            error: false,
            data: null
        };
    },
    computed: {
        fechaHoy() {
            const d = new Date();
            return d.toLocaleDateString('es-MX', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    },
    mounted() {
        this.cargar();
    },
    methods: {
        async cargar() {
            this.loading = true;
            this.error = false;
            try {
                const resp = await axios.get('/admin/dashboard/summary');
                if (resp.data.ok) {
                    this.data = resp.data;
                } else {
                    this.error = true;
                }
            } catch (e) {
                console.error('Error cargando dashboard:', e);
                this.error = true;
            } finally {
                this.loading = false;
            }
        },
        formatMoney(num) {
            if (!num) return '0';
            return parseFloat(num).toLocaleString('es-MX', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }
    }
};
</script>

<style scoped>
.dashboard-summary {
    min-height: 200px;
}

/* Summary Cards */
.summary-card {
    background: white;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    border-left: 4px solid #dee2e6;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.summary-card--success { border-left-color: #198754; }
.summary-card--warning { border-left-color: #ffc107; }
.summary-card--danger  { border-left-color: #dc3545; }
.summary-card--primary { border-left-color: #667eea; }
.summary-card--info    { border-left-color: #0dcaf0; }

.summary-card__header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
}

.summary-card__header i {
    font-size: 0.85rem;
}

.summary-card__value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    margin-top: 0.25rem;
}

.summary-card__sub {
    font-size: 0.8rem;
    color: #6c757d;
}

/* Rankings */
.ranking-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e9ecef;
    font-size: 0.7rem;
    font-weight: 700;
    color: #495057;
}

/* Quick Buttons */
.quick-btn__icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    margin: 0 auto;
    transition: transform 0.2s;
}

.quick-btn:hover .quick-btn__icon {
    transform: scale(1.1);
}

/* Usuarios Grid - responsive */
.usuarios-grid {
    display: flex;
    justify-content: space-around;
    gap: 0.5rem;
}

.usuarios-grid__item {
    text-align: center;
    flex: 1;
    min-width: 0;
    padding: 0.5rem 0.25rem;
    background: #f8f9fa;
    border-radius: 8px;
}

/* Tablet: 3 arriba + 2 abajo */
@media (max-width: 991px) {
    .usuarios-grid {
        flex-wrap: wrap;
    }
    .usuarios-grid__item {
        flex: 0 0 calc(33.33% - 0.5rem);
    }
    .usuarios-grid__item:nth-child(n+4) {
        flex: 0 0 calc(50% - 0.5rem);
    }
}

/* Celular: 3 arriba + 2 abajo más compacto */
@media (max-width: 575px) {
    .usuarios-grid__item {
        padding: 0.35rem 0.15rem;
    }
    .usuarios-grid__item .fs-5 {
        font-size: 1.1rem !important;
    }
}

/* Table inside cards */
.summary-card .table {
    font-size: 0.85rem;
}

.summary-card .table td,
.summary-card .table th {
    padding: 0.3rem 0.5rem;
    vertical-align: middle;
}
</style>
