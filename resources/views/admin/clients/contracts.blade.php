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
                        <div class="table-responsive" style="overflow: visible !important; padding-bottom: 200px;">
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
                                                @case('cancelled')
                                                    <span class="badge badge-danger"
                                                          title="Motivo: {{ $contract->cancellation_reason ? htmlspecialchars($contract->cancellation_reason, ENT_QUOTES, 'UTF-8') : 'No especificado' }}"
                                                          data-bs-toggle="tooltip">
                                                        Cancelado
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="badge badge-light">{{ $contract->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <i class="fa fa-calendar text-muted"></i>
                                            {{ $contract->created_at->format('d/m/Y H:i') }}
                                            @if($contract->status === 'cancelled')
                                                <br>
                                                <small class="text-danger">
                                                    <i class="fa fa-ban"></i> Cancelado: {{ $contract->cancelled_at->format('d/m/Y H:i') }}
                                                </small>
                                            @endif
                                        </td>
                                        <td style="position: relative;">
                                            <div class="dropdown" style="position: static;">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                        type="button"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                    <i class="fa fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-responsive" style="position: absolute !important; top: 100% !important; right: 0 !important; left: auto !important; z-index: 1050 !important; transform: none !important;">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.contracts.view', $contract) }}">
                                                            <i class="fa fa-eye text-info"></i> Ver Contrato
                                                        </a>
                                                    </li>
                                                    @if(!$contract->signature_path && $contract->status !== 'cancelled')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('admin.clients.edit-contract', ['client' => $client, 'contract' => $contract]) }}">
                                                                <i class="fa fa-edit text-warning"></i> Editar Contrato
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    @if($contract->pdf_path)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('contracts.generate-pdf', $contract) }}" target="_blank">
                                                                <i class="fa fa-download text-primary"></i> Descargar PDF
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('contracts.generate-pdf', $contract) }}">
                                                                <i class="fa fa-refresh text-info"></i> Regenerar PDF
                                                            </a>
                                                        </li>
                                                    @else
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('contracts.generate-pdf', $contract) }}">
                                                                <i class="fa fa-file-pdf-o text-success"></i> Generar PDF
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.clients.contract-logs', ['client' => $client, 'contract' => $contract]) }}">
                                                            <i class="fa fa-history text-secondary"></i> Ver Historial
                                                        </a>
                                                    </li>
                                                    @if($contract->status !== 'cancelled' && $contract->status !== 'signed')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item" href="#"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#cancelModal{{ $contract->id }}">
                                                                <i class="fa fa-ban text-danger"></i> Cancelar Contrato
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                           onclick="event.preventDefault(); if(confirm('¿Estás seguro de que quieres eliminar este contrato?')) { document.getElementById('delete-form-{{ $contract->id }}').submit(); }">
                                                            <i class="fa fa-trash text-danger"></i> Eliminar Contrato
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <form id="delete-form-{{ $contract->id }}" action="{{ route('admin.contracts.delete', $contract) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Modales de cancelación -->
                        @foreach($contracts as $contract)
                            <div class="modal fade" id="cancelModal{{ $contract->id }}" tabindex="-1" aria-labelledby="cancelModalLabel{{ $contract->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="cancelModalLabel{{ $contract->id }}">
                                                <i class="fa fa-ban text-danger"></i> Cancelar Contrato #{{ $contract->id }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('admin.clients.cancel-contract', ['client' => $client, 'contract' => $contract]) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="alert alert-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                    <strong>¿Estás seguro de cancelar este contrato?</strong><br>
                                                    Esta acción no se puede deshacer.
                                                </div>

                                                <div class="mb-3">
                                                    <strong>Contrato:</strong> {{ $contract->template->name }}<br>
                                                    <strong>Estado actual:</strong>
                                                    @switch($contract->status)
                                                        @case('draft') <span class="badge badge-secondary">Borrador</span> @break
                                                        @case('generated') <span class="badge badge-info">Generado</span> @break
                                                        @case('sent') <span class="badge badge-warning">Enviado</span> @break
                                                    @endswitch
                                                </div>

                                                <div class="mb-3">
                                                    <label for="cancellation_reason{{ $contract->id }}" class="form-label">
                                                        <strong class="text-danger">*</strong> Motivo de cancelación:
                                                    </label>
                                                    <textarea class="form-control"
                                                              id="cancellation_reason{{ $contract->id }}"
                                                              name="cancellation_reason"
                                                              rows="4"
                                                              placeholder="Ej: El cliente se echó para atrás y decidió comprar en lugar de rentar..."
                                                              required></textarea>
                                                    <div class="form-text">Máximo 1000 caracteres</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fa fa-times"></i> Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fa fa-ban"></i> Confirmar Cancelación
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
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

@push('styles')
<style>
    /* Minimal fix for dropdown overflow */
    .card-body {
        overflow: visible;
    }
</style>
@endpush

@push('scripts')
<script>
    // DEBUG: Log contract data
    console.log('=== CONTRACT DEBUG ===');
    console.log('Contracts data:', @json($contracts));

    @foreach($contracts as $contract)
        console.log('Contract {{ $contract->id }}:', {
            id: {{ $contract->id }},
            status: '{{ $contract->status }}',
            cancellation_reason: @json($contract->cancellation_reason),
            template_name: @json($contract->template->name ?? null),
            pdf_path: @json($contract->pdf_path),
            signature_path: @json($contract->signature_path)
        });
    @endforeach

    // Activar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        try {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                console.log('Initializing tooltip for:', tooltipTriggerEl);
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            console.log('Tooltips initialized successfully');
        } catch (error) {
            console.error('Error initializing tooltips:', error);
        }
    });
</script>
@endpush
@endsection