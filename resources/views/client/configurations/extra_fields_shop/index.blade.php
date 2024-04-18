@extends('client.layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Campos Extras</h5>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            <a href="{{ route('client.configurations.extra_fields.create') }}" class="btn btn-primary mb-3">Agregar Nuevo Campo</a>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre del Campo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($extra_fields as $extra_field)
                    <tr>
                        <td>{{ $extra_field->field_name }}</td>
                        <td>
                            @if ($extra_field->active)
                            <span class="badge bg-success">Activo</span>
                            @else
                            <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('client.configurations.extra_fields.edit', $extra_field->id) }}" class="btn btn-warning btn-sm">Editar</a>
                            <form action="{{ route('client.configurations.extra_fields.toggle', $extra_field->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-info btn-sm">{{ $extra_field->show ? 'Ocultar' : 'Mostrar' }}</button>
                            </form>
                            <form action="{{ route('client.configurations.extra_fields.destroy', $extra_field->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que quieres eliminar este campo extra?')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center">No hay campos extras</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
