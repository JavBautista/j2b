@extends('client.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Contrato #{{ $contract->id }}</h4>
                    <div>
                        <a href="{{ route('contracts.index') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <a href="{{ route('contracts.generate-pdf', $contract) }}" class="btn btn-success">
                            <i class="fas fa-file-pdf"></i> Descargar PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6><i class="fas fa-user"></i> Información del Cliente</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Nombre:</strong> {{ $contract->client->name }}</p>
                                    <p><strong>Email:</strong> {{ $contract->client->email }}</p>
                                    <p><strong>Teléfono:</strong> {{ $contract->client->phone ?? 'No especificado' }}</p>
                                    <p><strong>Dirección:</strong> {{ $contract->client->address ?? 'No especificada' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6><i class="fas fa-file-contract"></i> Información del Contrato</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Plantilla:</strong> {{ $contract->template->name }}</p>
                                    <p><strong>Estado:</strong> 
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
                                    </p>
                                    <p><strong>Fecha de creación:</strong> {{ $contract->created_at->format('d/m/Y H:i') }}</p>
                                    @if($contract->pdf_path)
                                        <p><strong>PDF:</strong> 
                                            <a href="{{ asset('storage/' . $contract->pdf_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt"></i> Ver PDF
                                            </a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($contract->contract_data))
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6><i class="fas fa-info-circle"></i> Datos Específicos del Contrato</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($contract->contract_data as $key => $value)
                                        <div class="col-md-6 mb-2">
                                            <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6><i class="fas fa-eye"></i> Vista Previa del Contrato</h6>
                                </div>
                                <div class="card-body">
                                    <div class="border p-4" style="background: white; min-height: 600px;">
                                        {!! $finalHtml !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
{!! $contract->template->css_styles !!}
</style>
@endsection