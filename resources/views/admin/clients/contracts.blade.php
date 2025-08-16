@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa fa-file-text"></i> Contratos de {{ $client->name }}
                    </h4>
                    <div>
                        <a href="{{ route('admin.clients.assign-contract', $client) }}" class="btn btn-success">
                            <i class="fa fa-plus"></i> Crear Nuevo Contrato
                        </a>
                        <a href="{{ route('admin.clients') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Volver a Clientes
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Información del Cliente -->
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Cliente:</strong> {{ $client->name }}
                            </div>
                            <div class="col-md-3">
                                <strong>Email:</strong> {{ $client->email }}
                            </div>
                            <div class="col-md-3">
                                <strong>Empresa:</strong> {{ $client->company ?: 'N/A' }}
                            </div>
                            <div class="col-md-3">
                                <strong>Teléfono:</strong> {{ $client->movil ?: 'N/A' }}
                            </div>
                        </div>
                    </div>

                    @if($contracts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Plantilla</th>
                                        <th>Vigencia</th>
                                        <th>Estado</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contracts as $contract)
                                    <tr>
                                        <td>#{{ $contract->id }}</td>
                                        <td>
                                            <i class="fa fa-file-text-o text-primary"></i>
                                            {{ $contract->template->name }}
                                        </td>
                                        <td>
                                            @if($contract->start_date && $contract->expiration_date)
                                                <div class="small">
                                                    <i class="fa fa-calendar text-success"></i> {{ $contract->start_date->format('d/m/Y') }}<br>
                                                    <i class="fa fa-calendar text-danger"></i> {{ $contract->expiration_date->format('d/m/Y') }}
                                                    @if($contract->expiration_date->isPast())
                                                        <span class="badge badge-danger ms-1">Vencido</span>
                                                    @elseif($contract->expiration_date->diffInDays() <= 30)
                                                        <span class="badge badge-warning ms-1">Por vencer</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">No definida</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($contract->status)
                                                @case('draft')
                                                    <span class="badge badge-secondary">Borrador</span>
                                                    @break
                                                @case('generated')
                                                    <span class="badge badge-info">Generado</span>
                                                    @break
                                                @case('sent')
                                                    <span class="badge badge-warning">Enviado</span>
                                                    @break
                                                @case('signed')
                                                    <span class="badge badge-success">Firmado</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-light">{{ $contract->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <i class="fa fa-calendar text-muted"></i>
                                            {{ $contract->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.contracts.view', $contract) }}" 
                                                   class="btn btn-info btn-sm" 
                                                   title="Ver Contrato">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if(!$contract->signature_path)
                                                    <a href="{{ route('admin.clients.edit-contract', ['client' => $client, 'contract' => $contract]) }}" 
                                                       class="btn btn-warning btn-sm" 
                                                       title="Editar Contrato">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                @else
                                                    <button class="btn btn-secondary btn-sm" 
                                                            title="Contrato firmado - No se puede editar" 
                                                            disabled>
                                                        <i class="fa fa-lock"></i>
                                                    </button>
                                                @endif
                                                @if($contract->pdf_path)
                                                <a href="{{ asset('storage/' . $contract->pdf_path) }}" 
                                                   target="_blank" 
                                                   class="btn btn-primary btn-sm" 
                                                   title="Descargar PDF">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                                <a href="{{ route('contracts.generate-pdf', $contract) }}" 
                                                   class="btn btn-info btn-sm" 
                                                   title="Regenerar PDF con firmas actualizadas">
                                                    <i class="fa fa-refresh"></i>
                                                </a>
                                                @else
                                                <a href="{{ route('contracts.generate-pdf', $contract) }}" 
                                                   class="btn btn-success btn-sm" 
                                                   title="Generar PDF">
                                                    <i class="fa fa-file-pdf-o"></i>
                                                </a>
                                                @endif
                                                <form action="{{ route('admin.contracts.delete', $contract) }}" 
                                                      method="POST" 
                                                      style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-danger btn-sm" 
                                                            title="Eliminar"
                                                            onclick="return confirm('¿Estás seguro de que quieres eliminar este contrato?')">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa fa-file-text fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay contratos creados</h5>
                            <p class="text-muted">Este cliente aún no tiene contratos asociados.</p>
                            <a href="{{ route('admin.clients.assign-contract', $client) }}" class="btn btn-success">
                                <i class="fa fa-plus"></i> Crear Primer Contrato
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection