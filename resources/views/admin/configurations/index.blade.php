@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <div>
                    <h3 class="mb-1"><i class="fas fa-cogs me-2 text-primary"></i>Configuraciones del Sistema</h3>
                    <p class="text-muted mb-0">Personaliza y administra las opciones de tu plataforma</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Secciones de Configuración -->
    <div class="row">
        <!-- Configuraciones Generales -->
        <div class="col-12 mb-4">
            <h5 class="text-muted mb-3"><i class="fas fa-sliders-h me-2"></i>Configuraciones Disponibles</h5>
        </div>

        <!-- Configuraciones IA (solo si el usuario tiene acceso) -->
        @if(auth()->user()->can_use_ai)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm border-0 hover-config-card h-100">
                <div class="card-body text-center p-4">
                    <div class="config-icon mb-3">
                        <i class="fas fa-bolt fa-3x text-warning"></i>
                    </div>
                    <h5 class="card-title mb-2">Asistente IA</h5>
                    <p class="card-text text-muted mb-3">Configura el asistente de inteligencia artificial de tu tienda</p>
                    <div class="d-flex justify-content-center">
                        <a href="{{ route('admin.configurations.ai_settings') }}" class="btn btn-outline-warning">
                            <i class="fas fa-arrow-right me-1"></i>Configurar
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-light text-center border-0">
                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Disponible</small>
                </div>
            </div>
        </div>
        @endif

        <!-- Campos Extra -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm border-0 hover-config-card h-100">
                <div class="card-body text-center p-4">
                    <div class="config-icon mb-3">
                        <i class="fas fa-plus-square fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title mb-2">Campos Extra</h5>
                    <p class="card-text text-muted mb-3">Administra campos personalizados para notas y formularios</p>
                    <div class="d-flex justify-content-center">
                        <a href="{{ route('admin.configurations.extra_fields') }}" class="btn btn-outline-success">
                            <i class="fas fa-arrow-right me-1"></i>Configurar
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-light text-center border-0">
                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Disponible</small>
                </div>
            </div>
        </div>

        <!-- Configuraciones Futuras -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm border-0 hover-config-card h-100 opacity-75">
                <div class="card-body text-center p-4">
                    <div class="config-icon mb-3">
                        <i class="fas fa-palette fa-3x text-warning"></i>
                    </div>
                    <h5 class="card-title mb-2">Temas y Colores</h5>
                    <p class="card-text text-muted mb-3">Personaliza la apariencia visual de tu plataforma</p>
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-outline-warning" disabled>
                            <i class="fas fa-clock me-1"></i>Próximamente
                        </button>
                    </div>
                </div>
                <div class="card-footer bg-light text-center border-0">
                    <small class="text-muted"><i class="fas fa-hourglass-half text-warning me-1"></i>En desarrollo</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm border-0 hover-config-card h-100 opacity-75">
                <div class="card-body text-center p-4">
                    <div class="config-icon mb-3">
                        <i class="fas fa-bell fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title mb-2">Notificaciones</h5>
                    <p class="card-text text-muted mb-3">Configura alertas y notificaciones del sistema</p>
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-outline-info" disabled>
                            <i class="fas fa-clock me-1"></i>Próximamente
                        </button>
                    </div>
                </div>
                <div class="card-footer bg-light text-center border-0">
                    <small class="text-muted"><i class="fas fa-hourglass-half text-info me-1"></i>En desarrollo</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm border-0 hover-config-card h-100 opacity-75">
                <div class="card-body text-center p-4">
                    <div class="config-icon mb-3">
                        <i class="fas fa-users-cog fa-3x text-danger"></i>
                    </div>
                    <h5 class="card-title mb-2">Usuarios y Permisos</h5>
                    <p class="card-text text-muted mb-3">Administra roles y permisos de usuarios</p>
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-outline-danger" disabled>
                            <i class="fas fa-clock me-1"></i>Próximamente
                        </button>
                    </div>
                </div>
                <div class="card-footer bg-light text-center border-0">
                    <small class="text-muted"><i class="fas fa-hourglass-half text-danger me-1"></i>En desarrollo</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm border-0 hover-config-card h-100 opacity-75">
                <div class="card-body text-center p-4">
                    <div class="config-icon mb-3">
                        <i class="fas fa-envelope-open-text fa-3x text-secondary"></i>
                    </div>
                    <h5 class="card-title mb-2">Plantillas de Email</h5>
                    <p class="card-text text-muted mb-3">Personaliza mensajes y notificaciones por correo</p>
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-outline-secondary" disabled>
                            <i class="fas fa-clock me-1"></i>Próximamente
                        </button>
                    </div>
                </div>
                <div class="card-footer bg-light text-center border-0">
                    <small class="text-muted"><i class="fas fa-hourglass-half text-secondary me-1"></i>En desarrollo</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm border-0 hover-config-card h-100 opacity-75">
                <div class="card-body text-center p-4">
                    <div class="config-icon mb-3">
                        <i class="fas fa-chart-bar fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title mb-2">Reportes Personalizados</h5>
                    <p class="card-text text-muted mb-3">Configura reportes y dashboards personalizados</p>
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-outline-primary" disabled>
                            <i class="fas fa-clock me-1"></i>Próximamente
                        </button>
                    </div>
                </div>
                <div class="card-footer bg-light text-center border-0">
                    <small class="text-muted"><i class="fas fa-hourglass-half text-primary me-1"></i>En desarrollo</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Información Adicional -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="card-body text-center py-4">
                    <h6 class="text-muted mb-2"><i class="fas fa-info-circle me-2"></i>Información</h6>
                    <p class="mb-0 small text-muted">
                        Las configuraciones te permiten personalizar J2Biznes según las necesidades específicas de tu negocio.
                        Más opciones estarán disponibles en futuras actualizaciones.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-config-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.hover-config-card:hover:not(.opacity-75) {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.config-icon {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection