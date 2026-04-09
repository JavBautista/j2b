@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                <i class="fa fa-plus-circle" style="color: var(--j2b-primary);"></i>
                Nuevo Campo Extra
            </h4>
            <p class="mb-0" style="color: var(--j2b-gray-500);">
                Crea un campo personalizado para tus formularios
            </p>
        </div>
        <a href="{{ route('admin.configurations.extra_fields') }}" class="j2b-btn j2b-btn-outline">
            <i class="fa fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="j2b-card">
                <div class="j2b-card-header">
                    <h6 class="mb-0">
                        <i class="fa fa-plus-square" style="color: var(--j2b-primary);"></i>
                        Nuevo Campo Personalizado
                    </h6>
                </div>
                <div class="j2b-card-body">
                    <form action="{{ route('admin.configurations.extra-fields.store') }}" method="POST">
                        @csrf

                        <!-- Nombre -->
                        <div class="j2b-form-group mb-3">
                            <label class="j2b-label"><strong class="text-danger">*</strong> Nombre del Campo</label>
                            <input type="text"
                                   class="j2b-input @error('field_name') is-invalid @enderror"
                                   name="field_name"
                                   value="{{ old('field_name') }}"
                                   placeholder="Ej: Marca, Modelo, Color, Placas..."
                                   required>
                            @error('field_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small style="color: var(--j2b-gray-500);">
                                Este nombre se mostrara en los formularios.
                            </small>
                        </div>

                        <!-- Opciones -->
                        <div class="j2b-form-section mb-3">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-cog"></i> Opciones
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                                               {{ old('active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">
                                            <strong>Campo Activo</strong>
                                        </label>
                                    </div>
                                    <small style="color: var(--j2b-gray-500);">Disponible para usar en formularios.</small>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="filterable" name="filterable" value="1"
                                               {{ old('filterable') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="filterable">
                                            <strong>Filtrable en ventas</strong>
                                        </label>
                                    </div>
                                    <small style="color: var(--j2b-gray-500);">Aparecera como filtro de busqueda en ventas.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Modulos -->
                        <div class="j2b-form-section mb-3">
                            <h6 class="j2b-form-section-title">
                                <i class="fa fa-th-large"></i> Usar en modulos
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="apply_to_receipts" name="apply_to_receipts" value="1"
                                               {{ old('apply_to_receipts', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="apply_to_receipts">
                                            <strong><i class="fa fa-file-text" style="color: var(--j2b-primary);"></i> Notas de Venta</strong>
                                        </label>
                                    </div>
                                    <small style="color: var(--j2b-gray-500);">Aparecera al crear/editar notas de venta.</small>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="apply_to_tasks" name="apply_to_tasks" value="1"
                                               {{ old('apply_to_tasks') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="apply_to_tasks">
                                            <strong><i class="fa fa-wrench" style="color: #ffc107;"></i> Tareas / Servicios</strong>
                                        </label>
                                    </div>
                                    <small style="color: var(--j2b-gray-500);">Aparecera al crear/editar tareas de servicio.</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.configurations.extra_fields') }}" class="j2b-btn j2b-btn-secondary">
                                <i class="fa fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="j2b-btn j2b-btn-primary">
                                <i class="fa fa-check"></i> Crear Campo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
