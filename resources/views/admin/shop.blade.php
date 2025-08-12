@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header con botón de editar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1"><i class="fas fa-store me-2 text-primary"></i>Detalles de la Tienda</h3>
                    <p class="text-muted mb-0">Administra la información de tu negocio</p>
                </div>
                <a href="{{ route('admin.shop.edit') }}" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Editar Información
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <!-- Información General -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-building me-2"></i>Información General</h6>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <label class="fw-bold text-muted small">NOMBRE</label>
                        <p class="mb-3">{{ $shop->name ?: 'No especificado' }}</p>
                    </div>
                    <div class="info-item">
                        <label class="fw-bold text-muted small">DESCRIPCIÓN</label>
                        <p class="mb-3">{{ $shop->description ?: 'No especificada' }}</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="fw-bold text-muted small">CÓDIGO POSTAL</label>
                                <p class="mb-3"><span class="badge bg-light text-dark">{{ $shop->zip_code ?: 'N/A' }}</span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="fw-bold text-muted small">CIUDAD</label>
                                <p class="mb-3">{{ $shop->city ?: 'No especificada' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <label class="fw-bold text-muted small">DIRECCIÓN COMPLETA</label>
                        <p class="mb-0">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                            {{ $shop->address }}
                            @if($shop->number_out) #{{ $shop->number_out }} @endif
                            @if($shop->number_int) Int. {{ $shop->number_int }} @endif
                            <br>
                            <small class="text-muted">
                                {{ $shop->district }}{{ $shop->district && $shop->city ? ', ' : '' }}{{ $shop->city }}{{ $shop->state ? ', ' . $shop->state : '' }}
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Contacto -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-phone me-2"></i>Información de Contacto</h6>
                </div>
                <div class="card-body">
                    <div class="contact-item d-flex align-items-center mb-3">
                        <div class="contact-icon">
                            <i class="fab fa-whatsapp text-success fa-lg"></i>
                        </div>
                        <div class="ms-3">
                            <small class="text-muted fw-bold">WHATSAPP</small>
                            <p class="mb-0">{{ $shop->whatsapp ?: 'No especificado' }}</p>
                        </div>
                    </div>
                    <div class="contact-item d-flex align-items-center mb-3">
                        <div class="contact-icon">
                            <i class="fas fa-phone text-primary fa-lg"></i>
                        </div>
                        <div class="ms-3">
                            <small class="text-muted fw-bold">TELÉFONO</small>
                            <p class="mb-0">{{ $shop->phone ?: 'No especificado' }}</p>
                        </div>
                    </div>
                    <div class="contact-item d-flex align-items-center mb-3">
                        <div class="contact-icon">
                            <i class="fas fa-envelope text-warning fa-lg"></i>
                        </div>
                        <div class="ms-3">
                            <small class="text-muted fw-bold">EMAIL</small>
                            <p class="mb-0">{{ $shop->email ?: 'No especificado' }}</p>
                        </div>
                    </div>
                    <div class="contact-item d-flex align-items-center">
                        <div class="contact-icon">
                            <i class="fas fa-globe text-info fa-lg"></i>
                        </div>
                        <div class="ms-3">
                            <small class="text-muted fw-bold">PÁGINA WEB</small>
                            <p class="mb-0">
                                @if($shop->web)
                                    <a href="{{ $shop->web }}" target="_blank" class="text-decoration-none">{{ $shop->web }}</a>
                                @else
                                    No especificada
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Redes Sociales -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-share-alt me-2"></i>Redes Sociales</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="social-item">
                                <i class="fab fa-facebook text-primary fa-lg me-2"></i>
                                <small class="fw-bold text-muted">FACEBOOK</small>
                                <p class="mb-0 small">{{ $shop->facebook ?: 'No configurado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="social-item">
                                <i class="fab fa-twitter text-info fa-lg me-2"></i>
                                <small class="fw-bold text-muted">TWITTER</small>
                                <p class="mb-0 small">{{ $shop->twitter ?: 'No configurado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="social-item">
                                <i class="fab fa-instagram text-danger fa-lg me-2"></i>
                                <small class="fw-bold text-muted">INSTAGRAM</small>
                                <p class="mb-0 small">{{ $shop->instagram ?: 'No configurado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="social-item">
                                <i class="fab fa-pinterest text-danger fa-lg me-2"></i>
                                <small class="fw-bold text-muted">PINTEREST</small>
                                <p class="mb-0 small">{{ $shop->pinterest ?: 'No configurado' }}</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="social-item">
                                <i class="fab fa-youtube text-danger fa-lg me-2"></i>
                                <small class="fw-bold text-muted">CANAL DE VIDEO</small>
                                <p class="mb-0 small">{{ $shop->video_channel ?: 'No configurado' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Corporativa -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Información Corporativa</h6>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <label class="fw-bold text-muted small">ESLOGAN</label>
                        <p class="mb-0 fst-italic">{{ $shop->slogan ?: 'No definido' }}</p>
                    </div>
                    <div class="info-item mb-3">
                        <label class="fw-bold text-muted small">PRESENTACIÓN</label>
                        <p class="mb-0 small">{{ $shop->presentation ?: 'No definida' }}</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <div class="info-item">
                                <label class="fw-bold text-muted small">MISIÓN</label>
                                <p class="mb-0 small">{{ $shop->mission ?: 'No definida' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="info-item">
                                <label class="fw-bold text-muted small">VISIÓN</label>
                                <p class="mb-0 small">{{ $shop->vision ?: 'No definida' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <label class="fw-bold text-muted small">VALORES</label>
                        <p class="mb-0 small">{{ $shop->values ?: 'No definidos' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Bancaria -->
        @if($shop->bank_number || $shop->bank_name)
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-university me-2"></i>Información Bancaria</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="fw-bold text-muted small">BANCO</label>
                                <p class="mb-0">{{ $shop->bank_name ?: 'No especificado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="fw-bold text-muted small">NÚMERO DE CUENTA</label>
                                <p class="mb-0">
                                    <span class="badge bg-dark">{{ $shop->bank_number ?: 'No especificado' }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.info-item label {
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}
.contact-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.05);
    border-radius: 50%;
}
.social-item {
    padding: 0.5rem;
    background: rgba(0,0,0,0.02);
    border-radius: 0.375rem;
    border-left: 3px solid #dee2e6;
}
</style>
@endsection
