@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Contratos Generados</h4>
                    <div>
                        <a href="{{ route('admin.contracts') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-file-contract"></i> Plantillas
                        </a>
                        <a href="{{ route('contracts.create') }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> Nuevo Contrato
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($contracts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Plantilla</th>
                                        <th>Estado</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contracts as $contract)
                                    <tr>
                                        <td><strong>#{{ $contract->id }}</strong></td>
                                        <td>
                                            {{ $contract->client->name }}<br>
                                            <small class="text-muted">{{ $contract->client->email }}</small>
                                        </td>
                                        <td>{{ $contract->template->name }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($contract->status === 'draft') bg-warning
                                                @elseif($contract->status === 'generated') bg-success
                                                @elseif($contract->status === 'sent') bg-info
                                                @endif
                                            ">
                                                @if($contract->status === 'draft') Borrador
                                                @elseif($contract->status === 'generated') Generado
                                                @elseif($contract->status === 'sent') Enviado
                                                @endif
                                            </span>
                                        </td>
                                        <td>{{ $contract->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('contracts.show', $contract) }}" 
                                                   class="btn btn-sm btn-outline-info" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('contracts.generate-pdf', $contract) }}" 
                                                   class="btn btn-sm btn-outline-success" title="Descargar PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <form method="POST" action="{{ route('contracts.destroy', $contract) }}" 
                                                      style="display:inline;" onsubmit="return confirm('¿Estás seguro?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        {{ $contracts->links() }}
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay contratos generados</h5>
                            <p class="text-muted">Crea tu primer contrato seleccionando una plantilla y un cliente.</p>
                            <a href="{{ route('contracts.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Primer Contrato
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection