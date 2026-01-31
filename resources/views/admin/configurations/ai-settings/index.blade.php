@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-bolt text-warning"></i> Configuraciones del Asistente IA
            </h4>
            <p class="text-muted mb-0">Administra la inteligencia artificial de tu tienda</p>
        </div>
        <a href="{{ route('admin.configurations') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Opciones de configuración -->
    <div class="row">
        <!-- Contexto de Tienda (Prompt) -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm border-0 hover-config-card h-100">
                <div class="card-body text-center p-4">
                    <div class="config-icon mb-3">
                        <i class="fas fa-comment-dots fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title mb-2">Contexto de Tienda</h5>
                    <p class="card-text text-muted mb-3">Define la personalidad e información que el asistente conoce sobre tu negocio</p>
                    <div class="d-flex justify-content-center">
                        <a href="{{ route('admin.configurations.ai_settings.prompt') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-right me-1"></i>Configurar
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-light text-center border-0">
                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Disponible</small>
                </div>
            </div>
        </div>

        <!-- Indexar Productos -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm border-0 hover-config-card h-100">
                <div class="card-body text-center p-4">
                    <div class="config-icon mb-3">
                        <i class="fas fa-database fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title mb-2">Indexar Productos</h5>
                    <p class="card-text text-muted mb-3">Sincroniza tus productos y servicios para que el asistente pueda buscarlos</p>
                    <div class="d-flex justify-content-center">
                        <a href="{{ route('admin.configurations.ai_settings.indexing') }}" class="btn btn-outline-success">
                            <i class="fas fa-arrow-right me-1"></i>Configurar
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-light text-center border-0">
                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Disponible</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="card border-0" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-lightbulb text-warning me-3 fa-lg"></i>
                        <div>
                            <p class="mb-0 small">
                                <strong>Tip:</strong> Primero configura el <em>Contexto de Tienda</em> con la información de tu negocio,
                                luego <em>Indexa tus Productos</em> para que el asistente pueda responder preguntas específicas sobre tu catálogo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-config-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.hover-config-card:hover {
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
