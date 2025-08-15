@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1"><i class="fas fa-edit me-2 text-primary"></i>Editar Información de la Tienda</h3>
                    <p class="text-muted mb-0">Actualiza los datos de tu negocio</p>
                </div>
                <div>
                    <a href="{{ route('admin.shop') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.shop.update', $shop->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ $shop->id }}">
        
        <div class="row">
            <!-- Información General -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-building me-2"></i>Información General</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">
                                <i class="fas fa-store text-primary me-1"></i>Nombre de la Tienda *
                            </label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $shop->name }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">
                                <i class="fas fa-align-left text-info me-1"></i>Descripción
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe tu negocio...">{{ $shop->description }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="zip_code" class="form-label fw-bold">
                                    <i class="fas fa-map-pin text-warning me-1"></i>Código Postal
                                </label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="{{ $shop->zip_code }}" placeholder="00000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label fw-bold">
                                    <i class="fas fa-city text-success me-1"></i>Ciudad *
                                </label>
                                <input type="text" class="form-control" id="city" name="city" value="{{ $shop->city }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="state" class="form-label fw-bold">
                                <i class="fas fa-map text-secondary me-1"></i>Estado
                            </label>
                            <input type="text" class="form-control" id="state" name="state" value="{{ $shop->state }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dirección -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Dirección</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="address" class="form-label fw-bold">
                                <i class="fas fa-road text-danger me-1"></i>Calle y Número *
                            </label>
                            <input type="text" class="form-control" id="address" name="address" value="{{ $shop->address }}" placeholder="Ej. Av. Principal 123" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="number_out" class="form-label fw-bold">
                                    <i class="fas fa-hashtag text-info me-1"></i>Número Exterior
                                </label>
                                <input type="text" class="form-control" id="number_out" name="number_out" value="{{ $shop->number_out }}" placeholder="123">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="number_int" class="form-label fw-bold">
                                    <i class="fas fa-home text-warning me-1"></i>Número Interior
                                </label>
                                <input type="text" class="form-control" id="number_int" name="number_int" value="{{ $shop->number_int }}" placeholder="A, B, 1, 2...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="district" class="form-label fw-bold">
                                <i class="fas fa-location-dot text-primary me-1"></i>Colonia/Distrito
                            </label>
                            <input type="text" class="form-control" id="district" name="district" value="{{ $shop->district }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Contacto -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-phone me-2"></i>Contacto</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="phone" class="form-label fw-bold">
                                <i class="fas fa-phone text-primary me-1"></i>Teléfono Principal
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="{{ $shop->phone }}" placeholder="+52 123 456 7890">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope text-warning me-1"></i>Email de Contacto
                            </label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ $shop->email }}" placeholder="contacto@mitienda.com">
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="whatsapp" name="whatsapp" value="1" {{ $shop->whatsapp ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="whatsapp">
                                    <i class="fab fa-whatsapp text-success me-1"></i>
                                    El teléfono es número de WhatsApp
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="web" class="form-label fw-bold">
                                <i class="fas fa-globe text-info me-1"></i>Sitio Web
                            </label>
                            <input type="url" class="form-control" id="web" name="web" value="{{ $shop->web }}" placeholder="https://mitienda.com">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Redes Sociales -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-share-alt me-2"></i>Redes Sociales</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="facebook" class="form-label fw-bold">
                                <i class="fab fa-facebook text-primary me-1"></i>Facebook
                            </label>
                            <input type="url" class="form-control" id="facebook" name="facebook" value="{{ $shop->facebook }}" placeholder="https://facebook.com/mitienda">
                        </div>

                        <div class="mb-3">
                            <label for="instagram" class="form-label fw-bold">
                                <i class="fab fa-instagram text-danger me-1"></i>Instagram
                            </label>
                            <input type="url" class="form-control" id="instagram" name="instagram" value="{{ $shop->instagram }}" placeholder="https://instagram.com/mitienda">
                        </div>

                        <div class="mb-3">
                            <label for="twitter" class="form-label fw-bold">
                                <i class="fab fa-twitter text-info me-1"></i>Twitter/X
                            </label>
                            <input type="url" class="form-control" id="twitter" name="twitter" value="{{ $shop->twitter }}" placeholder="https://twitter.com/mitienda">
                        </div>

                        <div class="mb-3">
                            <label for="video_channel" class="form-label fw-bold">
                                <i class="fab fa-youtube text-danger me-1"></i>Canal de Video
                            </label>
                            <input type="url" class="form-control" id="video_channel" name="video_channel" value="{{ $shop->video_channel }}" placeholder="https://youtube.com/c/mitienda">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Corporativa -->
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Información Corporativa</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="slogan" class="form-label fw-bold">
                                    <i class="fas fa-quote-left text-primary me-1"></i>Eslogan
                                </label>
                                <input type="text" class="form-control" id="slogan" name="slogan" value="{{ $shop->slogan }}" placeholder="Tu frase comercial">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="presentation" class="form-label fw-bold">
                                    <i class="fas fa-handshake text-success me-1"></i>Presentación
                                </label>
                                <textarea class="form-control" id="presentation" name="presentation" rows="2" placeholder="Breve presentación de tu empresa">{{ $shop->presentation }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="mission" class="form-label fw-bold">
                                    <i class="fas fa-bullseye text-warning me-1"></i>Misión
                                </label>
                                <textarea class="form-control" id="mission" name="mission" rows="3" placeholder="Misión de tu empresa">{{ $shop->mission }}</textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="vision" class="form-label fw-bold">
                                    <i class="fas fa-eye text-info me-1"></i>Visión
                                </label>
                                <textarea class="form-control" id="vision" name="vision" rows="3" placeholder="Visión de tu empresa">{{ $shop->vision }}</textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="values" class="form-label fw-bold">
                                    <i class="fas fa-heart text-danger me-1"></i>Valores
                                </label>
                                <textarea class="form-control" id="values" name="values" rows="3" placeholder="Valores de tu empresa">{{ $shop->values }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Información Bancaria -->
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="fas fa-university me-2"></i>Información Bancaria</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bank_name" class="form-label fw-bold">
                                    <i class="fas fa-building-columns text-primary me-1"></i>Banco
                                </label>
                                <input type="text" class="form-control" id="bank_name" name="bank_name" value="{{ $shop->bank_name }}" placeholder="Nombre del banco">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="bank_number" class="form-label fw-bold">
                                    <i class="fas fa-credit-card text-success me-1"></i>Número de Cuenta
                                </label>
                                <input type="text" class="form-control" id="bank_number" name="bank_number" value="{{ $shop->bank_number }}" placeholder="Número de cuenta bancaria">
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-1"></i>
                            <small>Esta información será utilizada para generar facturas y documentos oficiales.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-save me-1"></i>Guardar Cambios
                        </button>
                        <a href="{{ route('admin.shop') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <div class="mt-2">
                            <small class="text-muted">* Campos obligatorios</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Formulario Independiente para Firma del Representante Legal -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-signature me-2"></i>Firma del Representante Legal</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.shop.update-signature', $shop->id) }}" method="POST" enctype="multipart/form-data" id="signatureForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" value="{{ $shop->id }}">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="legal_representative_signature" class="form-label fw-bold">
                                        <i class="fas fa-pen-nib text-danger me-1"></i>Subir Firma Digital
                                    </label>
                                    <input type="file" class="form-control" id="legal_representative_signature" name="legal_representative_signature" accept="image/*" required>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Formatos permitidos: PNG, JPG, JPEG. Tamaño máximo: 2MB.
                                    </small>
                                </div>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <small><strong>Importante:</strong> Esta firma será utilizada en los contratos generados. Asegúrate de que sea legible y oficial.</small>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class="fas fa-upload me-1"></i>Actualizar Firma
                                    </button>
                                    @if($shop->legal_representative_signature_path)
                                    <button type="button" class="btn btn-outline-danger btn-lg ms-2" onclick="deleteSignature()">
                                        <i class="fas fa-trash me-1"></i>Eliminar Firma
                                    </button>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="signature-preview text-center">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-eye text-info me-1"></i>Vista Actual
                                    </label>
                                    <div class="signature-container border rounded p-3" style="min-height: 120px; background-color: #f8f9fa;">
                                        @if($shop->legal_representative_signature_path)
                                            <img src="{{ $shop->legal_representative_signature_url }}" 
                                                 alt="Firma del representante legal" 
                                                 class="img-fluid signature-image"
                                                 style="max-height: 100px;">
                                            <small class="d-block text-muted mt-2">Firma actual</small>
                                        @else
                                            <div class="d-flex flex-column justify-content-center align-items-center h-100">
                                                <i class="fas fa-signature fa-3x text-muted mb-2"></i>
                                                <small class="text-muted">Sin firma cargada</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para eliminar firma -->
    <form id="deleteSignatureForm" action="{{ route('admin.shop.delete-signature', $shop->id) }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>

<script>
function deleteSignature() {
    if (confirm('¿Estás seguro de que deseas eliminar la firma del representante legal?')) {
        document.getElementById('deleteSignatureForm').submit();
    }
}
</script>

<style>
.form-label.fw-bold {
    color: #495057;
    font-size: 0.9rem;
}
.card-header h6 {
    font-weight: 600;
}
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.form-check-input:checked {
    background-color: #25d366;
    border-color: #25d366;
}
</style>
@endsection
