@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1"><i class="fas fa-edit me-2 text-warning"></i>Editar Campo Extra</h3>
                    <p class="text-muted mb-0">Modifica las propiedades del campo "{{ $extraField->field_name }}"</p>
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
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Campo: {{ $extraField->field_name }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.configurations.extra-fields.update', $extraField->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label for="field_name" class="form-label fw-bold">
                                <i class="fas fa-tag text-primary me-1"></i>Nombre del Campo *
                            </label>
                            <input type="text" 
                                   class="form-control @error('field_name') is-invalid @enderror" 
                                   id="field_name" 
                                   name="field_name" 
                                   value="{{ old('field_name', $extraField->field_name) }}"
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
                                       {{ old('active', $extraField->active) ? 'checked' : '' }}>
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

                        <!-- Información del campo -->
                        <div class="alert alert-light border">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-info-circle text-info me-1"></i>Información del Campo
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Creado:</strong> {{ $extraField->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Última modificación:</strong> {{ $extraField->updated_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                            </div>
                            <hr class="my-2">
                            <small class="text-muted">
                                <strong>Estado actual:</strong>
                                @if($extraField->active)
                                    <span class="badge bg-success ms-1"><i class="fas fa-check me-1"></i>Activo</span>
                                @else
                                    <span class="badge bg-secondary ms-1"><i class="fas fa-pause me-1"></i>Inactivo</span>
                                @endif
                            </small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <div>
                                <!-- Botón de eliminar -->
                                <form action="{{ route('admin.configurations.extra_fields.destroy', $extraField->id) }}" 
                                      method="POST" 
                                      style="display: inline;"
                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar este campo? Esta acción no se puede deshacer.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="fas fa-trash me-1"></i>Eliminar Campo
                                    </button>
                                </form>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.configurations.extra_fields') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save me-1"></i>Guardar Cambios
                                </button>
                            </div>
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
                        <i class="fas fa-shield-alt me-1"></i>
                        Los cambios se aplicarán inmediatamente en todos los formularios que utilicen este campo.
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
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}
.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}
</style>
@endsection

