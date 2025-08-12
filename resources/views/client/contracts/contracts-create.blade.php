@extends('client.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Crear Nuevo Contrato</h4>
                    <a href="{{ route('contracts.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('contracts.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label">Plantilla de Contrato *</label>
                            <select name="contract_template_id" class="form-select" required onchange="loadTemplateVariables(this)">
                                <option value="">Seleccionar plantilla...</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" data-variables="{{ json_encode($template->variables) }}">
                                        {{ $template->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if($templates->count() === 0)
                                <div class="text-center mt-3">
                                    <p class="text-muted">No hay plantillas disponibles.</p>
                                    <a href="{{ route('client.contracts') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear Plantilla
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Cliente *</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">Seleccionar cliente...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">
                                        {{ $client->name }} - {{ $client->email }}
                                    </option>
                                @endforeach
                            </select>
                            @if($clients->count() === 0)
                                <div class="text-center mt-3">
                                    <p class="text-muted">No hay clientes disponibles.</p>
                                    <a href="{{ route('client.clients') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear Cliente
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="mb-4" id="template-variables" style="display: none;">
                            <h6>Datos Específicos del Contrato</h6>
                            <div id="variables-container">
                                <!-- Los campos dinámicos se cargarán aquí -->
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">Cancelar</button>
                            <button type="submit" class="btn btn-primary" {{ ($templates->count() === 0 || $clients->count() === 0) ? 'disabled' : '' }}>
                                <i class="fas fa-save"></i> Crear Contrato
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadTemplateVariables(select) {
    const templateVariables = document.getElementById('template-variables');
    const variablesContainer = document.getElementById('variables-container');
    
    if (!select.value) {
        templateVariables.style.display = 'none';
        return;
    }
    
    const selectedOption = select.selectedOptions[0];
    const variables = JSON.parse(selectedOption.dataset.variables);
    
    // Limpiar contenedor
    variablesContainer.innerHTML = '';
    
    // Variables que se llenan automáticamente
    const autoVariables = ['cliente_nombre', 'cliente_email', 'cliente_telefono', 'cliente_direccion', 'fecha_contrato'];
    
    // Crear campos para variables personalizadas
    variables.forEach(variable => {
        if (!autoVariables.includes(variable)) {
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-3';
            fieldDiv.innerHTML = `
                <label class="form-label">${variable.replace(/_/g, ' ').toUpperCase()}</label>
                <input type="text" name="contract_data[${variable}]" class="form-control" 
                       placeholder="Ingrese ${variable.replace(/_/g, ' ')}" required>
            `;
            variablesContainer.appendChild(fieldDiv);
        }
    });
    
    templateVariables.style.display = variables.length > autoVariables.length ? 'block' : 'none';
}
</script>
@endsection