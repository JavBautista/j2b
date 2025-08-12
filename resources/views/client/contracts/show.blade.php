@extends('client.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Vista Previa: {{ $template->name }}</h4>
                    <div>
                        <a href="{{ route('contract-templates.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <a href="{{ route('contract-templates.edit', $template->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="border p-4" style="background: white; min-height: 600px;">
                                {!! $previewHtml !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Información de la Plantilla</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Nombre:</strong> {{ $template->name }}</p>
                                    <p><strong>Estado:</strong> 
                                        <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $template->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </p>
                                    <p><strong>Creada:</strong> {{ $template->created_at->format('d/m/Y H:i') }}</p>
                                    <p><strong>Variables disponibles:</strong></p>
                                    <div class="d-flex flex-wrap">
                                        @foreach($template->variables as $variable)
                                            <span class="badge bg-info me-1 mb-1">{{ $variable }}</span>
                                        @endforeach
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
{!! $template->css_styles !!}
</style>
@endsection