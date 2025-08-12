@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1"><i class="fas fa-plus-square me-2 text-success"></i>Campos Extras</h3>
                    <p class="text-muted mb-0">Administra campos personalizados para notas y formularios</p>
                </div>
                <div>
                    <a href="{{ route('admin.configurations') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="{{ route('admin.configurations.extra_fields.create') }}" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Agregar Campo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas -->
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

    @if(session('error'))
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    @endif

    <!-- Tabla de Campos -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark">
                            <i class="fas fa-list me-2 text-primary"></i>Lista de Campos Personalizados
                        </h6>
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Total: {{ count($extra_fields ?? []) }} campos
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($extra_fields ?? []) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-bold">
                                            <i class="fas fa-tag me-1 text-primary"></i>Nombre del Campo
                                        </th>
                                        <th class="fw-bold text-center">
                                            <i class="fas fa-toggle-on me-1 text-success"></i>Estado
                                        </th>
                                        <th class="fw-bold text-center">
                                            <i class="fas fa-eye me-1 text-info"></i>Visibilidad
                                        </th>
                                        <th class="fw-bold text-center">
                                            <i class="fas fa-cogs me-1 text-warning"></i>Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($extra_fields as $extra_field)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-input-text text-muted me-2"></i>
                                                <strong>{{ $extra_field->field_name }}</strong>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if ($extra_field->active)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Activo
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-pause me-1"></i>Inactivo
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($extra_field->show ?? false)
                                                <span class="badge bg-info">
                                                    <i class="fas fa-eye me-1"></i>Visible
                                                </span>
                                            @else
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-eye-slash me-1"></i>Oculto
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.configurations.extra_fields.edit', $extra_field->id) }}" 
                                                   class="btn btn-outline-warning btn-sm" 
                                                   title="Editar campo">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <form action="{{ route('admin.configurations.extra_fields.toggle', $extra_field->id) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" 
                                                            class="btn btn-outline-info btn-sm" 
                                                            title="{{ ($extra_field->show ?? false) ? 'Ocultar campo' : 'Mostrar campo' }}">
                                                        <i class="fas fa-{{ ($extra_field->show ?? false) ? 'eye-slash' : 'eye' }}"></i>
                                                    </button>
                                                </form>
                                                
                                                <form action="{{ route('admin.configurations.extra_fields.destroy', $extra_field->id) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger btn-sm" 
                                                            onclick="return confirm('¿Estás seguro de que quieres eliminar este campo extra? Esta acción no se puede deshacer.')"
                                                            title="Eliminar campo">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-plus-square fa-3x text-muted mb-3"></i>
                                                <h6 class="text-muted">No hay campos extras configurados</h6>
                                                <p class="text-muted small">Crea tu primer campo personalizado para comenzar.</p>
                                                <a href="{{ route('admin.configurations.extra_fields.create') }}" class="btn btn-success">
                                                    <i class="fas fa-plus me-1"></i>Crear Primer Campo
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-plus-square fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No hay campos extras configurados</h6>
                            <p class="text-muted">Crea tu primer campo personalizado para comenzar a personalizar tus formularios.</p>
                            <a href="{{ route('admin.configurations.extra_fields.create') }}" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Crear Primer Campo
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="text-muted mb-1">
                                <i class="fas fa-lightbulb me-2 text-warning"></i>¿Qué son los campos extras?
                            </h6>
                            <p class="mb-0 small text-muted">
                                Los campos extras te permiten agregar información personalizada a tus formularios según las necesidades específicas de tu negocio.
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <span class="badge bg-primary">
                                <i class="fas fa-magic me-1"></i>Personalizable
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.empty-state i {
    opacity: 0.5;
}
.btn-group .btn {
    border-radius: 0;
}
.btn-group .btn:first-child {
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
}
.btn-group .btn:last-child {
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
}
.table th {
    border-top: none;
    font-size: 0.9rem;
}
</style>
@endsection
