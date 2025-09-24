@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa fa-file-text"></i> Contrato #{{ $contract->id }}
                    </h4>
                    <div>
                        <a href="{{ route('contracts.generate-pdf', $contract) }}"
                           target="_blank"
                           class="btn btn-primary">
                            <i class="fa fa-download"></i> Descargar PDF
                        </a>
                        <a href="{{ route('admin.clients.contracts', $contract->client) }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Volver a Contratos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Información del Contrato -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fa fa-info-circle"></i> Información del Contrato</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Cliente:</strong> {{ $contract->client->name }}</p>
                                    <p><strong>Plantilla:</strong> {{ $contract->template->name }}</p>
                                    <p><strong>Estado:</strong> 
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
                                            @case('cancelled')
                                                <span class="badge badge-danger">Cancelado</span>
                                                @break
                                            @default
                                                <span class="badge badge-light">{{ $contract->status }}</span>
                                        @endswitch
                                    </p>
                                    <p><strong>Fecha de Creación:</strong> {{ $contract->created_at->format('d/m/Y H:i') }}</p>
                                    @if($contract->start_date)
                                    <p><strong>Fecha de Inicio:</strong> 
                                        <span class="badge badge-success">{{ $contract->start_date->format('d/m/Y') }}</span>
                                    </p>
                                    @endif
                                    @if($contract->expiration_date)
                                    <p><strong>Fecha de Vencimiento:</strong> 
                                        <span class="badge {{ $contract->expiration_date->isPast() ? 'badge-danger' : ($contract->expiration_date->diffInDays() <= 30 ? 'badge-warning' : 'badge-info') }}">
                                            {{ $contract->expiration_date->format('d/m/Y') }}
                                            @if($contract->expiration_date->isPast())
                                                (Vencido)
                                            @elseif($contract->expiration_date->diffInDays() <= 30)
                                                ({{ $contract->expiration_date->diffInDays() }} días restantes)
                                            @endif
                                        </span>
                                    </p>
                                    @endif
                                    @if($contract->updated_at != $contract->created_at)
                                    <p><strong>Última Modificación:</strong> {{ $contract->updated_at->format('d/m/Y H:i') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fa fa-user"></i> Datos del Cliente</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Nombre:</strong> {{ $contract->client->name }}</p>
                                    <p><strong>Email:</strong> {{ $contract->client->email }}</p>
                                    <p><strong>Empresa:</strong> {{ $contract->client->company ?: 'N/A' }}</p>
                                    <p><strong>Teléfono:</strong> {{ $contract->client->movil ?: 'N/A' }}</p>
                                    @if($contract->client->address)
                                    <p><strong>Dirección:</strong> {{ $contract->client->address }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido del Contrato -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fa fa-file-text-o"></i> Contenido del Contrato</h6>
                        </div>
                        <div class="card-body">
                            <div class="contract-content" style="border: 1px solid #ddd; padding: 20px; background: white; min-height: 400px;" v-pre>
                                @if($contract->contract_content)
                                    {!! $contract->contract_content !!}
                                @else
                                    <div class="text-center text-muted p-5">
                                        <i class="fa fa-file-text-o fa-3x mb-3"></i>
                                        <p>No hay contenido disponible para este contrato.</p>
                                        @if($contract->contract_data)
                                        <p><small>Este contrato utiliza el formato de datos anterior.</small></p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($contract->signature_path)
                    <!-- Firma -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fa fa-pencil"></i> Firma del Cliente</h6>
                        </div>
                        <div class="card-body text-center">
                            <img src="{{ asset('storage/' . $contract->signature_path) }}" 
                                 alt="Firma del cliente" 
                                 style="max-height: 150px; border: 1px solid #ddd; padding: 10px;">
                            <p class="mt-2 text-muted">
                                <small>Firmado digitalmente</small>
                            </p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.contract-content {
    font-family: 'Times New Roman', serif;
    line-height: 1.6;
}

.contract-content h1, .contract-content h2, .contract-content h3 {
    color: #333;
    margin-bottom: 15px;
}

.contract-content p {
    margin-bottom: 10px;
    text-align: justify;
}

.contract-content strong {
    font-weight: bold;
}

@media print {
    .card-header, .btn, .breadcrumb {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .contract-content {
        border: none !important;
        padding: 0 !important;
    }
}
</style>
@endsection