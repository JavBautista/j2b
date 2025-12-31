<template>
<div>
    <div class="container-fluid">
        <!-- Tabs de navegación -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" :class="{ active: tabActivo === 'dashboard' }" href="#" @click.prevent="cambiarTab('dashboard')">
                    <i class="fa fa-dashboard"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: tabActivo === 'ventas' }" href="#" @click.prevent="cambiarTab('ventas')">
                    <i class="fa fa-line-chart"></i> Ventas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: tabActivo === 'utilidad' }" href="#" @click.prevent="cambiarTab('utilidad')">
                    <i class="fa fa-money"></i> Utilidades
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: tabActivo === 'inventario' }" href="#" @click.prevent="cambiarTab('inventario')">
                    <i class="fa fa-cubes"></i> Inventario
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: tabActivo === 'flujo' }" href="#" @click.prevent="cambiarTab('flujo')">
                    <i class="fa fa-exchange"></i> Flujo de Caja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: tabActivo === 'adeudos' }" href="#" @click.prevent="cambiarTab('adeudos')">
                    <i class="fa fa-exclamation-circle"></i> Adeudos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: tabActivo === 'top' }" href="#" @click.prevent="cambiarTab('top')">
                    <i class="fa fa-trophy"></i> Top Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: tabActivo === 'periodo' }" href="#" @click.prevent="cambiarTab('periodo')">
                    <i class="fa fa-calendar"></i> Por Período
                </a>
            </li>
        </ul>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <i class="fa fa-spinner fa-spin fa-3x"></i>
            <p class="mt-2">Cargando datos...</p>
        </div>

        <!-- ======================= DASHBOARD ======================= -->
        <div v-if="tabActivo === 'dashboard' && !loading">
            <div class="row">
                <!-- Card Ingresos Reales del Mes -->
                <div class="col-md-3 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">💰 Ingresos del Mes</h6>
                                    <h3 class="mb-0">${{ formatMoney(dashboardData.ingresos_mes) }}</h3>
                                </div>
                                <i class="fa fa-money fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card Notas Creadas -->
                <div class="col-md-3 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">📄 Notas del Mes</h6>
                                    <h3 class="mb-0">{{ dashboardData.notas_mes }} notas</h3>
                                    <small class="opacity-75">${{ formatMoney(dashboardData.comprometido_mes) }}</small>
                                </div>
                                <i class="fa fa-file-text-o fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card Valor Inventario -->
                <div class="col-md-3 mb-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Valor Inventario</h6>
                                    <h3 class="mb-0">${{ formatMoney(dashboardData.valor_inventario) }}</h3>
                                </div>
                                <i class="fa fa-cubes fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card Productos Bajo Stock -->
                <div class="col-md-3 mb-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Bajo Stock</h6>
                                    <h3 class="mb-0">{{ dashboardData.bajo_stock }} productos</h3>
                                </div>
                                <i class="fa fa-exclamation-triangle fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Selecciona una pestaña para ver reportes detallados.
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= VENTAS ======================= -->
        <div v-if="tabActivo === 'ventas' && !loading">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <i class="fa fa-line-chart"></i> Resumen de Ventas
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
                                <select v-model="filtros.modo_ventas" class="form-control" @change="cargarVentas">
                                    <option value="cobradas">💰 Ingresos Reales</option>
                                    <option value="generadas">📄 Notas Creadas</option>
                                </select>
                                <input type="date" v-model="filtros.fecha_inicio" class="form-control">
                                <input type="date" v-model="filtros.fecha_fin" class="form-control">
                                <button class="btn btn-primary" @click="cargarVentas">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Indicador de modo -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <span class="badge" :class="filtros.modo_ventas === 'cobradas' ? 'badge-success' : 'badge-secondary'">
                                <i class="fa" :class="filtros.modo_ventas === 'cobradas' ? 'fa-money' : 'fa-file-text-o'"></i>
                                {{ filtros.modo_ventas === 'cobradas' ? 'Ingresos Reales (dinero que entró)' : 'Notas Creadas (compromisos)' }}
                            </span>
                        </div>
                    </div>

                    <!-- KPIs para modo GENERADAS -->
                    <div v-if="filtros.modo_ventas === 'generadas'" class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>{{ ventasData.resumen?.total_notas || 0 }}</h5>
                                    <small class="text-muted">Total Notas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(ventasData.resumen?.monto_total) }}</h5>
                                    <small>Generado</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(ventasData.resumen?.cobrado) }}</h5>
                                    <small>Cobrado</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(ventasData.resumen?.por_cobrar) }}</h5>
                                    <small>Por Cobrar</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(ventasData.resumen?.abonos) }}</h5>
                                    <small>Abonos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(ventasData.resumen?.pagadas) }}</h5>
                                    <small>Notas Pagadas</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPIs para modo COBRADAS -->
                    <div v-else class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>${{ formatMoney(ventasData.resumen?.total_cobrado) }}</h4>
                                    <small>Total Cobrado</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>{{ ventasData.resumen?.cantidad_pagos || 0 }}</h5>
                                    <small class="text-muted">Movimientos</small>
                                </div>
                            </div>
                        </div>
                        <!-- Desglose por tipo de pago (solo si hay datos) -->
                        <div class="col-md-6" v-if="tieneTiposPago">
                            <div class="card">
                                <div class="card-body p-2">
                                    <small class="text-muted d-block mb-2 text-center">Desglose por tipo:</small>
                                    <div class="d-flex justify-content-around text-center">
                                        <div v-if="ventasData.resumen?.por_tipo?.unico">
                                            <small class="text-muted d-block">Únicos</small>
                                            <strong class="text-primary">${{ formatMoney(ventasData.resumen.por_tipo.unico.monto) }}</strong>
                                        </div>
                                        <div v-if="ventasData.resumen?.por_tipo?.inicial">
                                            <small class="text-muted d-block">Iniciales</small>
                                            <strong class="text-info">${{ formatMoney(ventasData.resumen.por_tipo.inicial.monto) }}</strong>
                                        </div>
                                        <div v-if="ventasData.resumen?.por_tipo?.abono">
                                            <small class="text-muted d-block">Abonos</small>
                                            <strong class="text-warning">${{ formatMoney(ventasData.resumen.por_tipo.abono.monto) }}</strong>
                                        </div>
                                        <div v-if="ventasData.resumen?.por_tipo?.liquidacion">
                                            <small class="text-muted d-block">Liquidaciones</small>
                                            <strong class="text-success">${{ formatMoney(ventasData.resumen.por_tipo.liquidacion.monto) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla detalle modo GENERADAS -->
                    <div v-if="filtros.modo_ventas === 'generadas'" class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="nota in ventasData.detalle" :key="nota.id">
                                    <td><strong>{{ nota.folio || nota.id }}</strong></td>
                                    <td>{{ nota.fecha }}</td>
                                    <td>{{ nota.cliente }}</td>
                                    <td>
                                        <span class="badge" :class="nota.tipo === 'venta' ? 'badge-primary' : 'badge-info'">
                                            {{ nota.tipo }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="getBadgeStatus(nota.status)">
                                            {{ nota.status }}
                                        </span>
                                    </td>
                                    <td class="text-right"><strong>${{ formatMoney(nota.total) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tabla detalle modo COBRADAS -->
                    <div v-else class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha Pago</th>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Tipo Pago</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in ventasData.detalle" :key="index">
                                    <td><strong>{{ item.folio || item.id }}</strong></td>
                                    <td>{{ item.fecha }}</td>
                                    <td>{{ item.cliente }}</td>
                                    <td>
                                        <span class="badge" :class="item.tipo === 'venta' ? 'badge-primary' : 'badge-info'">
                                            {{ item.tipo }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="getBadgeTipoPago(item.tipo_pago)">
                                            {{ formatTipoPago(item.tipo_pago) }}
                                        </span>
                                    </td>
                                    <td class="text-right text-success"><strong>${{ formatMoney(item.monto) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= UTILIDAD ======================= -->
        <div v-if="tabActivo === 'utilidad' && !loading">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <i class="fa fa-money"></i> Utilidad por Producto/Servicio
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
                                <select v-model="filtros.modo_utilidad" class="form-control" @change="cargarUtilidad">
                                    <option value="cobradas">💰 Utilidad Real (notas pagadas)</option>
                                    <option value="generadas">📄 Utilidad Proyectada (todas)</option>
                                </select>
                                <input type="date" v-model="filtros.fecha_inicio" class="form-control">
                                <input type="date" v-model="filtros.fecha_fin" class="form-control">
                                <select v-model="filtros.categoria_id" class="form-control">
                                    <option value="TODOS">Todas las categorías</option>
                                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                                <button class="btn btn-primary" @click="cargarUtilidad">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Indicador de modo -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <span class="badge" :class="filtros.modo_utilidad === 'cobradas' ? 'badge-success' : 'badge-secondary'">
                                <i class="fa" :class="filtros.modo_utilidad === 'cobradas' ? 'fa-check-circle' : 'fa-clock-o'"></i>
                                {{ filtros.modo_utilidad === 'cobradas' ? 'Utilidad Real (solo notas PAGADAS)' : 'Utilidad Proyectada (todas las notas)' }}
                            </span>
                        </div>
                    </div>

                    <!-- KPIs Utilidad -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(utilidadData.totales?.ingresos) }}</h5>
                                    <small>Ingresos Totales</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(utilidadData.totales?.costo) }}</h5>
                                    <small>Costo Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(utilidadData.totales?.utilidad) }}</h5>
                                    <small>Utilidad Bruta</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5>{{ utilidadData.totales?.margen || 0 }}%</h5>
                                    <small>Margen Global</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cards Servicios y Rentas (si hay datos) -->
                    <div v-if="utilidadData.totales?.servicios > 0 || utilidadData.totales?.rentas > 0" class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-success">
                                <strong><i class="fa fa-info-circle"></i> Ganancia Neta (100% utilidad):</strong>
                                <span v-if="utilidadData.totales?.servicios > 0" class="ml-3">
                                    <i class="fa fa-wrench"></i> Servicios: <strong>${{ formatMoney(utilidadData.totales.servicios) }}</strong>
                                </span>
                                <span v-if="utilidadData.totales?.rentas > 0" class="ml-3">
                                    <i class="fa fa-desktop"></i> Rentas: <strong>${{ formatMoney(utilidadData.totales.rentas) }}</strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla productos -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Ingresos</th>
                                    <th class="text-right">Costo</th>
                                    <th class="text-right">Utilidad</th>
                                    <th class="text-center">Margen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(prod, index) in utilidadData.productos" :key="index">
                                    <td>
                                        <span class="badge" :class="prod.tipo === 'producto' ? 'badge-primary' : (prod.tipo === 'servicio' ? 'badge-warning' : 'badge-info')">
                                            {{ prod.tipo }}
                                        </span>
                                    </td>
                                    <td>{{ prod.codigo }}</td>
                                    <td>{{ prod.nombre }}</td>
                                    <td><span class="badge badge-secondary">{{ prod.categoria }}</span></td>
                                    <td class="text-center">{{ prod.qty }}</td>
                                    <td class="text-right">${{ formatMoney(prod.ingresos) }}</td>
                                    <td class="text-right text-danger">${{ formatMoney(prod.costo) }}</td>
                                    <td class="text-right text-success"><strong>${{ formatMoney(prod.utilidad) }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge" :class="prod.margen >= 30 ? 'badge-success' : (prod.margen >= 15 ? 'badge-warning' : 'badge-danger')">
                                            {{ prod.margen }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= INVENTARIO ======================= -->
        <div v-if="tabActivo === 'inventario' && !loading">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <i class="fa fa-cubes"></i> Inventario Valorizado
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
                                <select v-model="filtros.categoria_id" class="form-control">
                                    <option value="TODOS">Todas las categorías</option>
                                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                                <select v-model="filtros.filtro_stock" class="form-control">
                                    <option value="TODOS">Todos los productos</option>
                                    <option value="BAJO">Bajo stock (≤5)</option>
                                    <option value="SIN">Sin stock</option>
                                </select>
                                <button class="btn btn-primary" @click="cargarInventario">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- KPIs Inventario -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5>{{ inventarioData.resumen?.total_productos || 0 }}</h5>
                                    <small>Total Productos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5>{{ inventarioData.resumen?.total_unidades || 0 }}</h5>
                                    <small>Total Unidades</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(inventarioData.resumen?.valor_total) }}</h5>
                                    <small>Valor Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>{{ (inventarioData.resumen?.bajo_stock || 0) + (inventarioData.resumen?.sin_stock || 0) }}</h5>
                                    <small>Alertas Stock</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla inventario -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Reserva</th>
                                    <th class="text-center">Disponible</th>
                                    <th class="text-right">Costo Unit.</th>
                                    <th class="text-right">Valor Total</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="prod in inventarioData.productos" :key="prod.id" :class="{'table-danger': prod.alerta === 'sin_stock', 'table-warning': prod.alerta === 'bajo_stock'}">
                                    <td>{{ prod.codigo }}</td>
                                    <td>{{ prod.nombre }}</td>
                                    <td><span class="badge badge-secondary">{{ prod.categoria }}</span></td>
                                    <td class="text-center"><strong>{{ prod.stock }}</strong></td>
                                    <td class="text-center text-muted">{{ prod.reserva }}</td>
                                    <td class="text-center">{{ prod.disponible }}</td>
                                    <td class="text-right">${{ formatMoney(prod.costo) }}</td>
                                    <td class="text-right"><strong>${{ formatMoney(prod.valor_stock) }}</strong></td>
                                    <td class="text-center">
                                        <span v-if="prod.alerta === 'sin_stock'" class="badge badge-danger">Sin Stock</span>
                                        <span v-else-if="prod.alerta === 'bajo_stock'" class="badge badge-warning">Bajo Stock</span>
                                        <span v-else class="badge badge-success">OK</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= FLUJO DE CAJA ======================= -->
        <div v-if="tabActivo === 'flujo' && !loading">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <i class="fa fa-exchange"></i> Flujo de Caja (Ingresos vs Egresos)
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="date" v-model="filtros.fecha_inicio" class="form-control">
                                <input type="date" v-model="filtros.fecha_fin" class="form-control">
                                <select v-model="filtros.facturado" class="form-control">
                                    <option :value="null">Todos</option>
                                    <option :value="true">Solo Facturado</option>
                                    <option :value="false">No Facturado</option>
                                </select>
                                <button class="btn btn-primary" @click="cargarFlujo">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Indicador de filtro aplicado -->
                    <div class="row mb-3" v-if="flujoData.filtro_facturado !== null">
                        <div class="col-12">
                            <span class="badge badge-info">
                                <i class="fa fa-filter"></i>
                                {{ flujoData.filtro_facturado === true ? 'Solo Facturado (SAT)' : 'No Facturado' }}
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Ingresos -->
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <i class="fa fa-arrow-down"></i> INGRESOS
                                </div>
                                <div class="card-body text-center">
                                    <h3 class="text-success mb-0">${{ formatMoney(flujoData.ingresos?.total) }}</h3>
                                    <small class="text-muted">Pagos recibidos en el período</small>
                                </div>
                            </div>
                        </div>
                        <!-- Egresos -->
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <i class="fa fa-arrow-up"></i> EGRESOS
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Compras (Proveedores)</td>
                                                <td class="text-right">${{ formatMoney(flujoData.egresos?.compras) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Gastos Operativos</td>
                                                <td class="text-right">${{ formatMoney(flujoData.egresos?.gastos) }}</td>
                                            </tr>
                                            <tr class="table-danger">
                                                <td><strong>Total Egresos</strong></td>
                                                <td class="text-right"><strong>${{ formatMoney(flujoData.egresos?.total) }}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Balance -->
                        <div class="col-md-4">
                            <div class="card" :class="flujoData.balance >= 0 ? 'border-success' : 'border-danger'">
                                <div class="card-header" :class="flujoData.balance >= 0 ? 'bg-success text-white' : 'bg-danger text-white'">
                                    <i class="fa fa-calculator"></i> BALANCE
                                </div>
                                <div class="card-body text-center">
                                    <h2 :class="flujoData.balance >= 0 ? 'text-success' : 'text-danger'">
                                        ${{ formatMoney(flujoData.balance) }}
                                    </h2>
                                    <p class="text-muted mb-0">
                                        {{ flujoData.balance >= 0 ? 'Flujo Positivo' : 'Flujo Negativo' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= ADEUDOS ======================= -->
        <div v-if="tabActivo === 'adeudos' && !loading">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <i class="fa fa-exclamation-circle"></i> Clientes con Adeudo
                        </div>
                        <div class="col-md-6 text-right">
                            <button class="btn btn-primary" @click="cargarAdeudos">
                                <i class="fa fa-refresh"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- KPIs -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-warning">
                                <div class="card-body text-center">
                                    <h5>{{ adeudosData.resumen?.total_clientes || 0 }}</h5>
                                    <small>Clientes con Adeudo</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(adeudosData.resumen?.total_adeudo) }}</h5>
                                    <small>Total por Cobrar</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla clientes -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Empresa</th>
                                    <th>Teléfono</th>
                                    <th class="text-center">Notas Pend.</th>
                                    <th class="text-right">Facturado</th>
                                    <th class="text-right">Abonos</th>
                                    <th class="text-right">Adeudo</th>
                                    <th class="text-center">Antigüedad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cliente in adeudosData.clientes" :key="cliente.client_id">
                                    <td><strong>{{ cliente.nombre }}</strong></td>
                                    <td>{{ cliente.empresa || '-' }}</td>
                                    <td>{{ cliente.telefono || '-' }}</td>
                                    <td class="text-center">{{ cliente.notas_pendientes }}</td>
                                    <td class="text-right">${{ formatMoney(cliente.total_facturado) }}</td>
                                    <td class="text-right text-success">${{ formatMoney(cliente.total_abonos) }}</td>
                                    <td class="text-right text-danger"><strong>${{ formatMoney(cliente.adeudo) }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge" :class="cliente.antiguedad_dias > 30 ? 'badge-danger' : (cliente.antiguedad_dias > 15 ? 'badge-warning' : 'badge-info')">
                                            {{ cliente.antiguedad_dias }} días
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= TOP PRODUCTOS ======================= -->
        <div v-if="tabActivo === 'top' && !loading">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <i class="fa fa-trophy"></i> Top Productos Vendidos
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="date" v-model="filtros.fecha_inicio" class="form-control">
                                <input type="date" v-model="filtros.fecha_fin" class="form-control">
                                <select v-model="filtros.ordenar_por" class="form-control">
                                    <option value="qty">Por Unidades</option>
                                    <option value="ingresos">Por Ingresos</option>
                                </select>
                                <button class="btn btn-primary" @click="cargarTop">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-right">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="prod in topData.productos" :key="prod.product_id">
                                    <td class="text-center">
                                        <span class="badge" :class="prod.ranking <= 3 ? 'badge-warning' : 'badge-secondary'">
                                            {{ prod.ranking }}
                                        </span>
                                    </td>
                                    <td>{{ prod.codigo }}</td>
                                    <td><strong>{{ prod.nombre }}</strong></td>
                                    <td><span class="badge badge-info">{{ prod.categoria }}</span></td>
                                    <td class="text-center"><strong>{{ prod.qty }}</strong></td>
                                    <td class="text-right"><strong>${{ formatMoney(prod.ingresos) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= VENTAS POR PERÍODO ======================= -->
        <div v-if="tabActivo === 'periodo' && !loading">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <i class="fa fa-calendar"></i> Ventas por Período
                        </div>
                        <div class="col-md-9">
                            <div class="input-group">
                                <select v-model="filtrosPeriodo.tipo_periodo" class="form-control">
                                    <option value="semanal">Semanal</option>
                                    <option value="mensual">Mensual</option>
                                    <option value="trimestral">Trimestral</option>
                                </select>
                                <select v-model="filtrosPeriodo.modo" class="form-control" @change="cargarPeriodo">
                                    <option value="cobradas">💰 Ingresos Reales</option>
                                    <option value="generadas">📄 Notas Creadas</option>
                                </select>
                                <select v-model="filtrosPeriodo.tipo_venta" class="form-control">
                                    <option value="todas">Todas</option>
                                    <option value="venta">Solo Ventas</option>
                                    <option value="renta">Solo Rentas</option>
                                </select>
                                <input type="date" v-model="filtrosPeriodo.fecha_inicio" class="form-control">
                                <input type="date" v-model="filtrosPeriodo.fecha_fin" class="form-control">
                                <button class="btn btn-primary" @click="cargarPeriodo">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- KPIs -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(periodoData.resumen?.total_ventas) }}</h5>
                                    <small>Total {{ filtrosPeriodo.modo === 'cobradas' ? 'Cobrado' : 'Generado' }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5>{{ periodoData.resumen?.total_tickets || 0 }}</h5>
                                    <small>Total Tickets</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(periodoData.resumen?.ticket_promedio) }}</h5>
                                    <small>Promedio</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2" v-if="filtrosPeriodo.modo === 'cobradas'">
                            <div class="card bg-warning">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(periodoData.resumen?.total_pendiente) }}</h5>
                                    <small>Pendiente</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card" :class="getTendenciaClass(periodoData.resumen?.comparacion_periodo_anterior?.tendencia)">
                                <div class="card-body text-center">
                                    <h5>
                                        <i :class="getTendenciaIcon(periodoData.resumen?.comparacion_periodo_anterior?.tendencia)"></i>
                                        {{ periodoData.resumen?.comparacion_periodo_anterior?.cambio_porcentaje || 0 }}%
                                    </h5>
                                    <small>vs Período Anterior</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla períodos -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Período</th>
                                    <th class="text-center">Tickets</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Promedio</th>
                                    <th class="text-right">Máximo</th>
                                    <th class="text-right">Mínimo</th>
                                    <th class="text-right" v-if="filtrosPeriodo.modo === 'cobradas'">Pendiente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in periodoData.periodos" :key="p.periodo">
                                    <td><strong>{{ formatPeriodo(p.periodo) }}</strong></td>
                                    <td class="text-center">{{ p.num_tickets }}</td>
                                    <td class="text-right"><strong>${{ formatMoney(p.total_ventas) }}</strong></td>
                                    <td class="text-right">${{ formatMoney(p.ticket_promedio) }}</td>
                                    <td class="text-right text-success">${{ formatMoney(p.ticket_maximo) }}</td>
                                    <td class="text-right text-muted">${{ formatMoney(p.ticket_minimo) }}</td>
                                    <td class="text-right text-warning" v-if="filtrosPeriodo.modo === 'cobradas'">
                                        ${{ formatMoney(p.pendiente_cobrar) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Info mejores/peores -->
                    <div class="row mt-3" v-if="periodoData.resumen?.mejor_periodo">
                        <div class="col-md-6">
                            <div class="alert alert-success mb-0">
                                <strong><i class="fa fa-trophy"></i> Mejor período:</strong>
                                {{ formatPeriodo(periodoData.resumen?.mejor_periodo?.periodo) }}
                                con ${{ formatMoney(periodoData.resumen?.mejor_periodo?.total_ventas) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-warning mb-0">
                                <strong><i class="fa fa-arrow-down"></i> Peor período:</strong>
                                {{ formatPeriodo(periodoData.resumen?.peor_periodo?.periodo) }}
                                con ${{ formatMoney(periodoData.resumen?.peor_periodo?.total_ventas) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</template>

<script>
export default {
    props: ['shop', 'userLimited', 'initialTab'],
    data() {
        return {
            tabActivo: this.initialTab || 'dashboard',
            loading: false,
            categorias: [],
            filtros: {
                fecha_inicio: this.getFirstDayOfMonth(),
                fecha_fin: this.getToday(),
                categoria_id: 'TODOS',
                filtro_stock: 'TODOS',
                ordenar_por: 'qty',
                facturado: null,
                modo_ventas: 'cobradas',
                modo_utilidad: 'cobradas'
            },
            // Data de cada reporte
            dashboardData: {
                ingresos_mes: 0,
                notas_mes: 0,
                comprometido_mes: 0,
                valor_inventario: 0,
                bajo_stock: 0
            },
            ventasData: { resumen: {}, detalle: [] },
            utilidadData: { totales: {}, productos: [] },
            inventarioData: { resumen: {}, productos: [] },
            flujoData: { ingresos: {}, egresos: {}, balance: 0, filtro_facturado: null },
            adeudosData: { resumen: {}, clientes: [] },
            topData: { productos: [] },
            periodoData: { resumen: {}, periodos: [] },
            // Filtros específicos
            filtrosPeriodo: {
                tipo_periodo: 'mensual',
                modo: 'cobradas',
                tipo_venta: 'todas',
                fecha_inicio: this.getThreeMonthsAgo(),
                fecha_fin: this.getToday()
            }
        }
    },
    computed: {
        tieneTiposPago() {
            const pt = this.ventasData.resumen?.por_tipo;
            return pt && (pt.unico || pt.inicial || pt.abono || pt.liquidacion);
        }
    },
    mounted() {
        this.cargarCategorias();
        this.cargarDashboard();
    },
    methods: {
        getFirstDayOfMonth() {
            const now = new Date();
            return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
        },
        getToday() {
            return new Date().toISOString().split('T')[0];
        },
        getThreeMonthsAgo() {
            const now = new Date();
            now.setMonth(now.getMonth() - 3);
            return now.toISOString().split('T')[0];
        },
        getNombreMes(mes) {
            const meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            return meses[mes] || '';
        },
        getYearsOptions() {
            const currentYear = new Date().getFullYear();
            return [currentYear - 2, currentYear - 1, currentYear, currentYear + 1];
        },
        formatPeriodo(periodo) {
            if (!periodo) return '';
            // 2025-12 -> Diciembre 2025
            if (periodo.includes('-S')) {
                const [year, week] = periodo.split('-S');
                return `Semana ${week} de ${year}`;
            }
            if (periodo.includes('-T')) {
                const [year, quarter] = periodo.split('-T');
                return `Trimestre ${quarter} de ${year}`;
            }
            const [year, month] = periodo.split('-');
            return `${this.getNombreMes(parseInt(month))} ${year}`;
        },
        getTendenciaClass(tendencia) {
            if (tendencia === 'alza') return 'bg-success text-white';
            if (tendencia === 'baja') return 'bg-danger text-white';
            return 'bg-secondary text-white';
        },
        getTendenciaIcon(tendencia) {
            if (tendencia === 'alza') return 'fa fa-arrow-up';
            if (tendencia === 'baja') return 'fa fa-arrow-down';
            return 'fa fa-minus';
        },
        formatMoney(value) {
            if (!value) return '0.00';
            return parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        getBadgeStatus(status) {
            if (status === 'PAGADA') return 'badge-success';
            if (status === 'POR COBRAR') return 'badge-warning';
            if (status === 'CANCELADA') return 'badge-danger';
            return 'badge-secondary';
        },
        getBadgeTipoPago(tipo) {
            const badges = {
                'unico': 'badge-success',
                'inicial': 'badge-info',
                'abono': 'badge-warning',
                'liquidacion': 'badge-primary'
            };
            return badges[tipo] || 'badge-secondary';
        },
        formatTipoPago(tipo) {
            const labels = {
                'unico': 'Único',
                'inicial': 'Inicial',
                'abono': 'Abono',
                'liquidacion': 'Liquidación'
            };
            return labels[tipo] || tipo;
        },
        cambiarTab(tab) {
            this.tabActivo = tab;
            // Cargar datos del tab seleccionado
            if (tab === 'dashboard') this.cargarDashboard();
            else if (tab === 'ventas') this.cargarVentas();
            else if (tab === 'utilidad') this.cargarUtilidad();
            else if (tab === 'inventario') this.cargarInventario();
            else if (tab === 'flujo') this.cargarFlujo();
            else if (tab === 'adeudos') this.cargarAdeudos();
            else if (tab === 'top') this.cargarTop();
            else if (tab === 'periodo') this.cargarPeriodo();
        },
        cargarCategorias() {
            axios.get('/admin/reports/categorias').then(response => {
                if (response.data.ok) {
                    this.categorias = response.data.categorias;
                }
            });
        },
        cargarDashboard() {
            this.loading = true;
            // Params base con fechas del mes actual
            const paramsCobradas = { ...this.filtros, modo: 'cobradas' };
            const paramsGeneradas = { ...this.filtros, modo: 'generadas' };

            // Cargar datos: ingresos reales + notas creadas + inventario
            Promise.all([
                axios.get('/admin/reports/ventas-resumen', { params: paramsCobradas }),
                axios.get('/admin/reports/ventas-resumen', { params: paramsGeneradas }),
                axios.get('/admin/reports/inventario')
            ]).then(([cobradasRes, generadasRes, invRes]) => {
                // Ingresos reales del mes
                this.dashboardData.ingresos_mes = cobradasRes.data.resumen?.total_cobrado || 0;
                // Notas creadas del mes
                this.dashboardData.notas_mes = generadasRes.data.resumen?.total_notas || 0;
                this.dashboardData.comprometido_mes = generadasRes.data.resumen?.monto_total || 0;
                // Inventario
                this.dashboardData.valor_inventario = invRes.data.resumen?.valor_total || 0;
                this.dashboardData.bajo_stock = (invRes.data.resumen?.bajo_stock || 0) + (invRes.data.resumen?.sin_stock || 0);
            }).finally(() => {
                this.loading = false;
            });
        },
        cargarVentas() {
            this.loading = true;
            const params = {
                fecha_inicio: this.filtros.fecha_inicio,
                fecha_fin: this.filtros.fecha_fin,
                modo: this.filtros.modo_ventas
            };
            axios.get('/admin/reports/ventas-resumen', { params })
                .then(response => {
                    if (response.data.ok) {
                        this.ventasData = response.data;
                    }
                })
                .finally(() => { this.loading = false; });
        },
        cargarUtilidad() {
            this.loading = true;
            const params = {
                fecha_inicio: this.filtros.fecha_inicio,
                fecha_fin: this.filtros.fecha_fin,
                categoria_id: this.filtros.categoria_id,
                modo: this.filtros.modo_utilidad
            };
            axios.get('/admin/reports/ventas-utilidad', { params })
                .then(response => {
                    if (response.data.ok) {
                        this.utilidadData = response.data;
                    }
                })
                .finally(() => { this.loading = false; });
        },
        cargarInventario() {
            this.loading = true;
            axios.get('/admin/reports/inventario', { params: this.filtros })
                .then(response => {
                    if (response.data.ok) {
                        this.inventarioData = response.data;
                    }
                })
                .finally(() => { this.loading = false; });
        },
        cargarFlujo() {
            this.loading = true;
            const params = {
                fecha_inicio: this.filtros.fecha_inicio,
                fecha_fin: this.filtros.fecha_fin
            };
            if (this.filtros.facturado !== null) {
                params.facturado = this.filtros.facturado;
            }
            axios.get('/admin/reports/ingresos-egresos', { params })
                .then(response => {
                    if (response.data.ok) {
                        this.flujoData = response.data;
                    }
                })
                .finally(() => { this.loading = false; });
        },
        cargarAdeudos() {
            this.loading = true;
            axios.get('/admin/reports/clientes-adeudos')
                .then(response => {
                    if (response.data.ok) {
                        this.adeudosData = response.data;
                    }
                })
                .finally(() => { this.loading = false; });
        },
        cargarTop() {
            this.loading = true;
            axios.get('/admin/reports/top-productos', { params: this.filtros })
                .then(response => {
                    if (response.data.ok) {
                        this.topData = response.data;
                    }
                })
                .finally(() => { this.loading = false; });
        },
        cargarPeriodo() {
            this.loading = true;
            axios.get('/admin/reports/ventas-periodo', { params: this.filtrosPeriodo })
                .then(response => {
                    if (response.data.ok) {
                        this.periodoData = response.data;
                    }
                })
                .finally(() => { this.loading = false; });
        }
    }
}
</script>

<style scoped>
.nav-tabs .nav-link {
    color: #666;
}
.nav-tabs .nav-link.active {
    font-weight: bold;
    color: #20a8d8;
}
.opacity-50 {
    opacity: 0.5;
}
</style>
