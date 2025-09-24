@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa fa-history"></i> Historial de Cambios - Contrato #{{ $contract->id }}
                    </h4>
                    <div>
                        <a href="{{ route('admin.contracts.view', $contract) }}" class="btn btn-info">
                            <i class="fa fa-eye"></i> Ver Contrato
                        </a>
                        <a href="{{ route('admin.clients.contracts', $client) }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Volver a Contratos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Información del Contrato -->
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Cliente:</strong> {{ $client->name }}
                            </div>
                            <div class="col-md-3">
                                <strong>Plantilla:</strong> {{ $contract->template->name }}
                            </div>
                            <div class="col-md-3">
                                <strong>Estado Actual:</strong>
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
                            </div>
                            <div class="col-md-3">
                                <strong>Total de Eventos:</strong> <span class="badge badge-primary">{{ $logs->total() }}</span>
                            </div>
                        </div>
                    </div>

                    @if($logs->count() > 0)
                        <!-- Timeline de Logs -->
                        <div class="timeline">
                            @foreach($logs as $log)
                                <div class="timeline-item">
                                    <div class="timeline-marker">
                                        <i class="fa {{ $log->action_icon }}"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <h6 class="timeline-title">
                                                {{ $log->action_name }}
                                                <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</small>
                                            </h6>
                                            <div class="timeline-meta">
                                                @if($log->user)
                                                    <span class="badge badge-info">
                                                        <i class="fa fa-user"></i> {{ $log->user->name }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">
                                                        <i class="fa fa-cog"></i> Sistema
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        @if($log->description)
                                            <div class="timeline-description">
                                                <p>{{ $log->description }}</p>
                                            </div>
                                        @endif

                                        @if($log->old_values || $log->new_values)
                                            <div class="timeline-changes">
                                                <div class="row">
                                                    @if($log->old_values)
                                                        <div class="col-md-6">
                                                            <h6 class="text-danger">
                                                                <i class="fa fa-minus-circle"></i> Valores Anteriores:
                                                            </h6>
                                                            <div class="change-details">
                                                                @foreach($log->old_values as $key => $value)
                                                                    <div class="change-item">
                                                                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                                        <span class="old-value">{{ $value ?: 'N/A' }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if($log->new_values)
                                                        <div class="col-md-6">
                                                            <h6 class="text-success">
                                                                <i class="fa fa-plus-circle"></i> Valores Nuevos:
                                                            </h6>
                                                            <div class="change-details">
                                                                @foreach($log->new_values as $key => $value)
                                                                    <div class="change-item">
                                                                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                                        <span class="new-value">{{ $value ?: 'N/A' }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        <div class="timeline-footer">
                                            <small class="text-muted">
                                                <i class="fa fa-globe"></i> IP: {{ $log->ip_address ?: 'N/A' }}
                                                @if($log->user_agent)
                                                    | <i class="fa fa-desktop"></i> {{ Str::limit($log->user_agent, 50) }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-center">
                            {{ $logs->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Sin historial de cambios</h5>
                            <p class="text-muted">Este contrato aún no tiene eventos registrados.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 30px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 70px;
}

.timeline-marker {
    position: absolute;
    left: 20px;
    top: 5px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.timeline-marker i {
    font-size: 10px;
}

.timeline-content {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 10px;
}

.timeline-title {
    margin: 0;
    flex: 1;
}

.timeline-meta {
    margin-left: 10px;
}

.timeline-description {
    margin-bottom: 15px;
    color: #6c757d;
}

.timeline-changes {
    background: #f8f9fa;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 10px;
}

.change-details {
    background: white;
    border-radius: 4px;
    padding: 10px;
}

.change-item {
    margin-bottom: 5px;
    padding: 5px;
    border-left: 3px solid #dee2e6;
    padding-left: 10px;
}

.old-value {
    color: #dc3545;
    background: #f8d7da;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

.new-value {
    color: #155724;
    background: #d4edda;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

.timeline-footer {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #dee2e6;
}

/* Action specific colors */
.fa-plus.text-success { color: #28a745 !important; }
.fa-edit.text-info { color: #17a2b8 !important; }
.fa-ban.text-danger { color: #dc3545 !important; }
.fa-trash.text-danger { color: #dc3545 !important; }
.fa-file-pdf-o.text-primary { color: #007bff !important; }
.fa-eye.text-muted { color: #6c757d !important; }
.fa-pencil.text-success { color: #28a745 !important; }
</style>
@endsection