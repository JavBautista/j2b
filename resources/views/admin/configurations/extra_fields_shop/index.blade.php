@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1" style="color: var(--j2b-dark); font-weight: 600;">
                <i class="fa fa-plus-square" style="color: var(--j2b-primary);"></i>
                Campos Extras
            </h4>
            <p class="mb-0" style="color: var(--j2b-gray-500);">
                Administra campos personalizados para tus formularios
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.configurations') }}" class="j2b-btn j2b-btn-outline">
                <i class="fa fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('admin.configurations.extra_fields.create') }}" class="j2b-btn j2b-btn-primary">
                <i class="fa fa-plus"></i> Agregar Campo
            </a>
        </div>
    </div>

    <!-- Alertas -->
    @if(session('success'))
        <div class="j2b-banner-alert j2b-banner-success mb-3">
            <i class="fa fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="j2b-banner-alert mb-3" style="background: rgba(255,71,87,0.1); border: 1px solid var(--j2b-danger);">
            <i class="fa fa-exclamation-circle" style="color: var(--j2b-danger);"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Tabla de Campos -->
    <div class="j2b-card">
        <div class="j2b-card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fa fa-list" style="color: var(--j2b-primary);"></i>
                Lista de Campos Personalizados
            </h6>
            <small style="color: var(--j2b-gray-500);">
                Total: {{ count($extra_fields ?? []) }} campos
            </small>
        </div>
        <div class="j2b-card-body p-0">
            @if(count($extra_fields ?? []) > 0)
                <div class="j2b-table-responsive">
                    <table class="j2b-table">
                        <thead>
                            <tr>
                                <th>Nombre del Campo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Filtrable</th>
                                <th class="text-center">Modulos</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($extra_fields as $extra_field)
                            <tr>
                                <td>
                                    <strong>{{ $extra_field->field_name }}</strong>
                                </td>
                                <td class="text-center">
                                    @if ($extra_field->active)
                                        <span class="j2b-badge j2b-badge-success">Activo</span>
                                    @else
                                        <span class="j2b-badge" style="background: var(--j2b-gray-300); color: var(--j2b-gray-700);">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($extra_field->filterable)
                                        <span class="j2b-badge j2b-badge-info">Si</span>
                                    @else
                                        <span style="color: var(--j2b-gray-500);">No</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($extra_field->apply_to_receipts)
                                        <span class="j2b-badge j2b-badge-success" style="margin-right: 2px;">
                                            <i class="fa fa-file-text"></i> Ventas
                                        </span>
                                    @endif
                                    @if($extra_field->apply_to_tasks)
                                        <span class="j2b-badge j2b-badge-warning">
                                            <i class="fa fa-wrench"></i> Tareas
                                        </span>
                                    @endif
                                    @if(!$extra_field->apply_to_receipts && !$extra_field->apply_to_tasks)
                                        <span style="color: var(--j2b-gray-500);">Ninguno</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('admin.configurations.extra_fields.edit', $extra_field->id) }}"
                                           class="j2b-btn j2b-btn-sm j2b-btn-outline" title="Editar">
                                            <i class="fa fa-pencil"></i>
                                        </a>

                                        <form action="{{ route('admin.configurations.extra_fields.toggle', $extra_field->id) }}"
                                              method="POST" style="display: inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit"
                                                    class="j2b-btn j2b-btn-sm j2b-btn-outline"
                                                    title="{{ $extra_field->active ? 'Desactivar' : 'Activar' }}">
                                                <i class="fa fa-{{ $extra_field->active ? 'eye-slash' : 'eye' }}"></i>
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.configurations.extra_fields.destroy', $extra_field->id) }}"
                                              method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="j2b-btn j2b-btn-sm" style="color: var(--j2b-danger); border: 1px solid var(--j2b-danger); background: transparent;"
                                                    onclick="return confirm('Estas seguro de que quieres eliminar este campo?')"
                                                    title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fa fa-inbox fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                                    <p style="color: var(--j2b-gray-500);">No hay campos extras configurados</p>
                                    <a href="{{ route('admin.configurations.extra_fields.create') }}" class="j2b-btn j2b-btn-primary">
                                        <i class="fa fa-plus"></i> Crear Primer Campo
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3" style="color: var(--j2b-gray-300);"></i>
                    <p style="color: var(--j2b-gray-500);">No hay campos extras configurados</p>
                    <a href="{{ route('admin.configurations.extra_fields.create') }}" class="j2b-btn j2b-btn-primary">
                        <i class="fa fa-plus"></i> Crear Primer Campo
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Info -->
    <div class="j2b-banner-alert j2b-banner-info mt-3">
        <i class="fa fa-lightbulb-o"></i>
        <div>
            <strong>Campos extras</strong> &mdash; Agrega informacion personalizada a tus notas de venta o tareas de servicio segun las necesidades de tu negocio.
        </div>
    </div>
</div>
@endsection
