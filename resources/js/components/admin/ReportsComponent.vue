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
        </ul>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <i class="fa fa-spinner fa-spin fa-3x"></i>
            <p class="mt-2">Cargando datos...</p>
        </div>

        <!-- ======================= DASHBOARD ======================= -->
        <div v-if="tabActivo === 'dashboard' && !loading">
            <div class="row">
                <!-- Card Ventas del Mes -->
                <div class="col-md-3 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Ventas del Mes</h6>
                                    <h3 class="mb-0">${{ formatMoney(dashboardData.ventas_mes) }}</h3>
                                </div>
                                <i class="fa fa-shopping-cart fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card Adeudos -->
                <div class="col-md-3 mb-4">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Por Cobrar</h6>
                                    <h3 class="mb-0">${{ formatMoney(dashboardData.por_cobrar) }}</h3>
                                </div>
                                <i class="fa fa-clock-o fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card Inventario -->
                <div class="col-md-3 mb-4">
                    <div class="card bg-success text-white">
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
                        <div class="col-md-6">
                            <i class="fa fa-line-chart"></i> Resumen de Ventas
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
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
                    <!-- KPIs -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>{{ ventasData.resumen?.total_notas || 0 }}</h5>
                                    <small class="text-muted">Total Notas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(ventasData.resumen?.monto_total) }}</h5>
                                    <small class="text-muted">Monto Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(ventasData.resumen?.pagadas) }}</h5>
                                    <small>Pagadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning">
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
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>${{ formatMoney(ventasData.resumen?.adeudos) }}</h5>
                                    <small>Adeudos</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla detalle -->
                    <div class="table-responsive">
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
                </div>
            </div>
        </div>

        <!-- ======================= UTILIDAD ======================= -->
        <div v-if="tabActivo === 'utilidad' && !loading">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <i class="fa fa-money"></i> Utilidad por Producto
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
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

                    <!-- Tabla productos -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Ingresos</th>
                                    <th class="text-right">Costo</th>
                                    <th class="text-right">Utilidad</th>
                                    <th class="text-center">Margen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="prod in utilidadData.productos" :key="prod.product_id">
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
                        <div class="col-md-6">
                            <i class="fa fa-exchange"></i> Flujo de Caja (Ingresos vs Egresos)
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="date" v-model="filtros.fecha_inicio" class="form-control">
                                <input type="date" v-model="filtros.fecha_fin" class="form-control">
                                <button class="btn btn-primary" @click="cargarFlujo">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Ingresos -->
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <i class="fa fa-arrow-down"></i> INGRESOS
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Pagos Completos</td>
                                                <td class="text-right">${{ formatMoney(flujoData.ingresos?.pagos_completos) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Abonos Recibidos</td>
                                                <td class="text-right">${{ formatMoney(flujoData.ingresos?.abonos) }}</td>
                                            </tr>
                                            <tr class="table-success">
                                                <td><strong>Total Ingresos</strong></td>
                                                <td class="text-right"><strong>${{ formatMoney(flujoData.ingresos?.total) }}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
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
                ordenar_por: 'qty'
            },
            // Data de cada reporte
            dashboardData: {
                ventas_mes: 0,
                por_cobrar: 0,
                valor_inventario: 0,
                bajo_stock: 0
            },
            ventasData: { resumen: {}, detalle: [] },
            utilidadData: { totales: {}, productos: [] },
            inventarioData: { resumen: {}, productos: [] },
            flujoData: { ingresos: {}, egresos: {}, balance: 0 },
            adeudosData: { resumen: {}, clientes: [] },
            topData: { productos: [] }
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
            // Cargar datos resumidos para dashboard
            Promise.all([
                axios.get('/admin/reports/ventas-resumen', { params: this.filtros }),
                axios.get('/admin/reports/inventario'),
                axios.get('/admin/reports/clientes-adeudos')
            ]).then(([ventasRes, invRes, adeudosRes]) => {
                this.dashboardData.ventas_mes = ventasRes.data.resumen?.monto_total || 0;
                this.dashboardData.por_cobrar = ventasRes.data.resumen?.por_cobrar || 0;
                this.dashboardData.valor_inventario = invRes.data.resumen?.valor_total || 0;
                this.dashboardData.bajo_stock = (invRes.data.resumen?.bajo_stock || 0) + (invRes.data.resumen?.sin_stock || 0);
            }).finally(() => {
                this.loading = false;
            });
        },
        cargarVentas() {
            this.loading = true;
            axios.get('/admin/reports/ventas-resumen', { params: this.filtros })
                .then(response => {
                    if (response.data.ok) {
                        this.ventasData = response.data;
                    }
                })
                .finally(() => { this.loading = false; });
        },
        cargarUtilidad() {
            this.loading = true;
            axios.get('/admin/reports/ventas-utilidad', { params: this.filtros })
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
            axios.get('/admin/reports/ingresos-egresos', { params: this.filtros })
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
