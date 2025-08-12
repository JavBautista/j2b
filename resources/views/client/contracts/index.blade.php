@extends('client.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Plantillas de Contratos</h4>
                    <div>
                        <a href="{{ route('contracts.index') }}" class="btn btn-outline-primary me-2">
                            <i class="fas fa-file-contract"></i> Ver Contratos
                        </a>
                        <a href="{{ route('contract-templates.create') }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> Nueva Plantilla
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($templates->count() > 0)
                        <div class="table-responsive" style="min-height: 400px;">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Variables</th>
                                        <th>Fecha Creación</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <strong>{{ $template->name }}</strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ count($template->variables) }} variables disponibles
                                                <i class="fas fa-info-circle ms-1" 
                                                   title="{{ implode(', ', array_slice($template->variables, 0, 10)) }}{{ count($template->variables) > 10 ? '...' : '' }}"
                                                   data-bs-toggle="tooltip" data-bs-placement="top"></i>
                                            </small>
                                        </td>
                                        <td>{{ $template->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $template->is_active ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('contract-templates.show', $template->id) }}">
                                                            <i class="fas fa-eye me-2"></i>Ver Plantilla
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('contract-templates.edit', $template->id) }}">
                                                            <i class="fas fa-edit me-2"></i>Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item" onclick="createContract({{ $template->id }})">
                                                            <i class="fas fa-file-plus me-2"></i>Crear Contrato
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('contract-templates.destroy', $template->id) }}" 
                                                              onsubmit="return confirm('¿Estás seguro de desactivar esta plantilla?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-trash me-2"></i>Desactivar
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay plantillas creadas</h5>
                            <p class="text-muted">Crea tu primera plantilla de contrato para comenzar.</p>
                            <a href="{{ route('contract-templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Primera Plantilla
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal eliminado - ahora usamos página completa -->

    <!-- Modal para crear contrato -->
    <div class="modal fade" id="createContractModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Contrato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createContractForm" action="{{ route('contracts.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="contract_template_id" id="template_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Cliente *</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">Seleccionar cliente...</option>
                                @foreach(App\Models\Client::where('shop_id', auth()->user()->shop->id)->where('active', 1)->get() as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }} - {{ $client->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Datos del Contrato</label>
                            <div id="contractDataFields">
                                <!-- Campos dinámicos se agregarán aquí -->
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Crear Contrato</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function createContract(templateId) {
    document.getElementById('template_id').value = templateId;
    
    // Obtener variables de la plantilla y crear campos dinámicos
    const template = @json($templates).find(t => t.id === templateId);
    const fieldsContainer = document.getElementById('contractDataFields');
    fieldsContainer.innerHTML = '';
    
    template.variables.forEach(variable => {
        if (!['cliente_nombre', 'cliente_email', 'cliente_telefono', 'cliente_direccion', 'fecha_contrato'].includes(variable)) {
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-2';
            fieldDiv.innerHTML = `
                <label class="form-label">${variable.replace('_', ' ').toUpperCase()}</label>
                <input type="text" name="contract_data[${variable}]" class="form-control" placeholder="Ingrese ${variable}">
            `;
            fieldsContainer.appendChild(fieldDiv);
        }
    });
    
    new bootstrap.Modal(document.getElementById('createContractModal')).show();
}
</script>
@endsection