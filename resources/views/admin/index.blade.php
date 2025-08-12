@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header de Bienvenida -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-gradient-primary text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center py-5">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <img src="{{ asset('img/j2b_1200px.png') }}" alt="J2Biznes Logo" class="img-fluid" style="max-height: 60px; filter: brightness(0) invert(1);">
                    </div>
                    <h1 class="display-6 fw-bold mb-3">¡Bienvenido a J2Biznes!</h1>
                    <p class="lead mb-0">Hola {{ auth()->user()->name }}, gestiona tu negocio de manera eficiente y efectiva</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="text-muted mb-3"><i class="fas fa-tachometer-alt me-2"></i>Accesos Rápidos</h4>
        </div>
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-store fa-2x text-primary"></i>
                    </div>
                    <h6 class="card-title">Mi Tienda</h6>
                    <p class="card-text text-muted small">Gestiona información de tu negocio</p>
                    <a href="{{ route('admin.shop') }}" class="btn btn-outline-primary btn-sm">Ver Detalles</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-file-contract fa-2x text-success"></i>
                    </div>
                    <h6 class="card-title">Contratos</h6>
                    <p class="card-text text-muted small">Administra plantillas y contratos</p>
                    <a href="{{ route('contract-templates.index') }}" class="btn btn-outline-success btn-sm">Gestionar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-cogs fa-2x text-warning"></i>
                    </div>
                    <h6 class="card-title">Configuraciones</h6>
                    <p class="card-text text-muted small">Personaliza tu plataforma</p>
                    <a href="{{ route('admin.configurations') }}" class="btn btn-outline-warning btn-sm">Configurar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-users fa-2x text-info"></i>
                    </div>
                    <h6 class="card-title">Clientes</h6>
                    <p class="card-text text-muted small">Administra tu base de clientes</p>
                    <a href="#" class="btn btn-outline-info btn-sm">Próximamente</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Sistema -->
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0">
                    <h5 class="mb-0 text-dark"><i class="fas fa-info-circle me-2 text-primary"></i>Acerca de J2Biznes</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">J2Biznes es una plataforma diseñada para ayudarte a gestionar tu negocio de manera eficiente y efectiva. Con nuestras herramientas, podrás administrar tus ventas, inventario, clientes y más, todo desde una sola plataforma integrada.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span class="small">Gestión integral de negocio</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span class="small">Contratos digitales</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span class="small">Administración de clientes</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span class="small">Reportes y análisis</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0">
                    <h6 class="mb-0 text-dark"><i class="fas fa-chart-line me-2 text-success"></i>Estado del Sistema</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Sistema</span>
                        <span class="badge bg-success">Activo</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Conexión</span>
                        <span class="badge bg-success">Estable</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Última actividad</span>
                        <span class="small text-muted">Ahora</span>
                    </div>
                    <hr class="my-3">
                    <div class="text-center">
                        <small class="text-muted">Versión 1.0.0</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}
</style>
@endsection
