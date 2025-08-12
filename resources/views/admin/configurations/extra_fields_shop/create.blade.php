@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1"><i class="fas fa-plus-circle me-2 text-success"></i>Agregar Nuevo Campo Extra</h3>
                    <p class="text-muted mb-0">Crea un campo personalizado para tus formularios</p>
                </div>
                <div>
                    <a href="{{ route('admin.configurations.extra_fields') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a Lista
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-plus-square me-2"></i>Nuevo Campo Personalizado</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.configurations.extra-fields.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="field_name" class="form-label fw-bold">
                                <i class="fas fa-tag text-primary me-1"></i>Nombre del Campo *
                            </label>
                            <input type="text" 
                                   class="form-control @error('field_name') is-invalid @enderror" 
                                   id="field_name" 
                                   name="field_name" 
                                   value="{{ old('field_name') }}"
                                   placeholder="Ej. Campo especial, Información adicional..."
                                   required>
                            @error('field_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Este será el nombre que se mostrará en los formularios.
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="active" 
                                       name="active" 
                                       value="1"
                                       {{ old('active', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="active">
                                    <i class="fas fa-toggle-on text-success me-1"></i>
                                    Campo Activo
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-lightbulb me-1"></i>
                                Los campos activos estarán disponibles para usar en los formularios.
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>¿Qué son los campos extras?</strong><br>
                            Los campos extras te permiten agregar información personalizada a tus formularios, adaptándolos a las necesidades específicas de tu negocio.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.configurations.extra_fields') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Crear Campo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="card-body text-center py-3">
                    <small class="text-muted">
                        <i class="fas fa-question-circle me-1"></i>
                        Una vez creado, podrás editar el nombre del campo y cambiar su estado en cualquier momento.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-label.fw-bold {
    color: #495057;
    font-size: 0.9rem;
}
.form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}
.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}
</style>
@endsection
