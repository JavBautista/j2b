@extends('admin.layouts.app')

@section('content')
<div class="container-fluid admin-dashboard">
    <div class="animated fadeIn">

        <!-- Welcome Banner -->
        <div class="welcome-banner mb-4">
            <div class="welcome-content">
                <h1 class="welcome-title">Hola, {{ auth()->user()->name }} 👋</h1>
                <p class="welcome-subtitle">Panel de administración · ¿En qué puedo ayudarte?</p>
            </div>
        </div>

        <!-- Mensajes Flash -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {!! session('success') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {!! session('error') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <!-- ASISTENTE IA - PROTAGONISTA (70%) -->
            <div class="col-lg-8 col-xl-9 mb-4">
                <div class="card chat-card shadow-lg">
                    <div class="card-header chat-header">
                        <div class="d-flex align-items-center">
                            <div class="chat-icon-wrapper">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Asistente Administrativo J2Biznes</h5>
                                <small class="text-muted">Gestión inteligente con IA</small>
                            </div>
                        </div>
                    </div>

                    <!-- Componente Vue del Chat IA -->
                    <admin-chat-ai-component></admin-chat-ai-component>
                </div>
            </div>

            <!-- ACCESOS RÁPIDOS - DISCRETOS (30%) -->
            <div class="col-lg-4 col-xl-3">
                <div class="quick-access-section">
                    <h6 class="quick-access-title mb-3">
                        <i class="fas fa-bolt me-2"></i>Accesos Rápidos
                    </h6>

                    <!-- Mi Tienda -->
                    <a href="{{ route('admin.shop') }}" class="quick-action-card mb-3">
                        <div class="quick-icon bg-gradient-primary">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="quick-info">
                            <h6 class="mb-0">Mi Tienda</h6>
                            <small>Gestionar información</small>
                        </div>
                        <i class="fas fa-chevron-right quick-arrow"></i>
                    </a>

                    <!-- Contratos -->
                    <a href="{{ route('contract-templates.index') }}" class="quick-action-card mb-3">
                        <div class="quick-icon bg-gradient-success">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="quick-info">
                            <h6 class="mb-0">Contratos</h6>
                            <small>Plantillas y contratos</small>
                        </div>
                        <i class="fas fa-chevron-right quick-arrow"></i>
                    </a>

                    <!-- Clientes -->
                    <a href="{{ route('admin.clients') }}" class="quick-action-card mb-3">
                        <div class="quick-icon bg-gradient-info">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="quick-info">
                            <h6 class="mb-0">Clientes</h6>
                            <small>Gestionar clientes</small>
                        </div>
                        <i class="fas fa-chevron-right quick-arrow"></i>
                    </a>

                    <!-- Configuraciones -->
                    <a href="{{ route('admin.configurations') }}" class="quick-action-card mb-3">
                        <div class="quick-icon bg-gradient-warning">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="quick-info">
                            <h6 class="mb-0">Configuraciones</h6>
                            <small>Personalizar sistema</small>
                        </div>
                        <i class="fas fa-chevron-right quick-arrow"></i>
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection
