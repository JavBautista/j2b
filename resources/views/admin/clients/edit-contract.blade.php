@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-edit text-warning"></i> Editar Contrato #{{ $contract->id }} - {{ $client->name }}
                    </h4>
                    <div>
                        <a href="{{ route('admin.clients.contracts', $client) }}" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Volver a Contratos
                        </a>
                        <a href="{{ route('admin.contracts.view', $contract) }}" class="btn btn-info">
                            <i class="fas fa-eye"></i> Ver Original
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Información del Cliente - Fila completa arriba -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-user"></i> Información del Cliente</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Nombre:</strong><br>{{ $client->name }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Email:</strong><br>{{ $client->email }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Empresa:</strong><br>{{ $client->company ?: 'N/A' }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Teléfono:</strong><br>{{ $client->movil ?: 'N/A' }}
                                        </div>
                                        @if($client->address)
                                        <div class="col-12 mt-2">
                                            <strong>Dirección:</strong><br>{{ $client->address }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Área de Trabajo: Variables y Editor -->
                    <div class="row">
                        <!-- Columna 1: Plantillas y Variables -->
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-cog"></i> Configuración</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Selector de Plantilla -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-file-alt"></i> <strong>Plantilla Base</strong>
                                        </label>
                                        <select id="template-selector" class="form-select" onchange="loadTemplateToEditor(this)">
                                            <option value="">Seleccionar plantilla...</option>
                                            @foreach($templates as $template)
                                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                                            @endforeach
                                        </select>
                                        @if($templates->count() === 0)
                                            <div class="mt-2">
                                                <a href="{{ route('admin.contracts') }}" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-plus"></i> Crear Plantilla
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Fechas del Contrato -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-alt"></i> <strong>Fechas del Contrato</strong>
                                        </label>
                                        <div class="row">
                                            <div class="col-12 mb-2">
                                                <label for="start_date" class="form-label small">
                                                    <i class="fas fa-play text-success"></i> Fecha de Inicio
                                                </label>
                                                <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="{{ $contract->start_date ? $contract->start_date->format('Y-m-d') : date('Y-m-d') }}">
                                            </div>
                                            <div class="col-12">
                                                <label for="expiration_date" class="form-label small">
                                                    <i class="fas fa-stop text-danger"></i> Fecha de Vencimiento
                                                </label>
                                                <input type="date" class="form-control form-control-sm" id="expiration_date" name="expiration_date" value="{{ $contract->expiration_date ? $contract->expiration_date->format('Y-m-d') : date('Y-m-d', strtotime('+1 year')) }}">
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Las fechas se incluirán automáticamente en el contrato.
                                        </small>
                                    </div>

                                    <!-- Variables dinámicas -->
                                    <div id="template-variables" style="display: none;">
                                        <div class="alert alert-info alert-sm mb-3">
                                            <i class="fas fa-info-circle"></i> 
                                            <strong>Variables:</strong> Completa y aplica al editor.
                                        </div>
                                        
                                        <div id="variables-container" class="mb-3">
                                            <!-- Los campos dinámicos se cargarán aquí -->
                                        </div>
                                        
                                        <div class="text-center mb-3">
                                            <button type="button" class="btn btn-warning btn-sm w-100" onclick="applyVariables()">
                                                <i class="fas fa-magic"></i> Aplicar Variables
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna 2: Editor (más espacio) -->
                        <div class="col-md-8">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-edit"></i> Editor de Contrato</h6>
                                    <div>
                                        <button type="button" class="btn btn-light btn-sm me-2" onclick="showPreview()">
                                            <i class="fas fa-eye"></i> Vista Previa
                                        </button>
                                        <a href="{{ route('admin.clients') }}" class="btn btn-secondary btn-sm me-2">
                                            <i class="fas fa-arrow-left"></i> Volver
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm" id="create-contract-btn" onclick="submitContractForm()">
                                            <i class="fas fa-save"></i> Actualizar Contrato
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <form action="{{ route('admin.clients.update-contract', ['client' => $client, 'contract' => $contract]) }}" method="POST" id="contract-form">
                                        @csrf
                                        @method('PUT')

                                        <!-- Editor Quill -->
                                        <div style="height: 500px;" id="contract-editor">
                                            <div class="text-muted text-center p-5">
                                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                                <p>Selecciona una plantilla para comenzar a editar</p>
                                            </div>
                                        </div>
                                        <input type="hidden" name="contract_content" id="contract-content">
                                        <input type="hidden" name="contract_template_id" id="selected-template-id">
                                        <input type="hidden" name="start_date" id="hidden-start-date">
                                        <input type="hidden" name="expiration_date" id="hidden-expiration-date">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Vista Previa -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-eye"></i> Vista Previa del Contrato
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div id="preview-content">
                    <div class="text-center text-muted p-5">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <p>No hay contenido para mostrar</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Cargar Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<!-- Cargar SweetAlert2 CSS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Cargar Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<style>
/* Mejorar la apariencia del layout */
.row {
    margin-left: -5px;
    margin-right: -5px;
}
.col-md-4, .col-md-8 {
    padding-left: 5px;
    padding-right: 5px;
}

/* Quill editor personalizado */
#contract-editor {
    border: 1px solid #ddd;
}
#contract-editor .ql-toolbar {
    border-bottom: 1px solid #ddd;
    border-top: none;
    border-left: none;
    border-right: none;
}
#contract-editor .ql-container {
    border: none;
    font-size: 14px;
}

/* Variables container más compacto */
#template-variables .form-control-sm {
    font-size: 0.85rem;
}
#variables-container {
    max-height: 300px;
    overflow-y: auto;
}

/* Cards con altura consistente */
.h-100 {
    min-height: 600px;
}
</style>

<script>
const clientData = {
    id: {{ $client->id }},
    name: "{{ $client->name }}",
    email: "{{ $client->email }}",
    company: "{{ $client->company ?: '' }}",
    phone: "{{ $client->movil ?: '' }}",
    address: "{{ $client->address ?: '' }}"
};

// Contenido existente del contrato para edición
const existingContractContent = {!! json_encode($contract->contract_content) !!};
const existingTemplateId = {{ $contract->contract_template_id }};

let quillEditor;
let currentTemplateData = null;
let templates = @json($templates);

// Inicializar Quill Editor
document.addEventListener('DOMContentLoaded', function() {
    quillEditor = new Quill('#contract-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                ['link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['clean']
            ]
        },
        placeholder: 'El contenido de la plantilla aparecerá aquí para que puedas editarlo...'
    });

    // Cargar contenido existente del contrato
    if (existingContractContent) {
        quillEditor.root.innerHTML = existingContractContent;
        document.getElementById('contract-content').value = existingContractContent;
        // Pre-seleccionar la plantilla original
        if (existingTemplateId) {
            document.getElementById('template-selector').value = existingTemplateId;
            document.getElementById('selected-template-id').value = existingTemplateId;
        }
    }

    // Escuchar cambios en el editor
    quillEditor.on('text-change', function(delta, oldDelta, source) {
        const content = quillEditor.root.innerHTML;
        document.getElementById('contract-content').value = content;
        
        // Habilitar botón si hay contenido
        const createBtn = document.getElementById('create-contract-btn');
        createBtn.disabled = content.trim() === '' || content.trim() === '<p><br></p>';
        
        // Debug opcional
        if (source === 'user') {
            console.log('Contenido actualizado por el usuario');
        }
    });
});

function loadTemplateToEditor(select) {
    const templateVariables = document.getElementById('template-variables');
    const variablesContainer = document.getElementById('variables-container');
    const selectedTemplateId = document.getElementById('selected-template-id');
    
    if (!select.value) {
        templateVariables.style.display = 'none';
        quillEditor.root.innerHTML = `
            <div class="text-muted text-center p-5">
                <i class="fas fa-file-alt fa-3x mb-3"></i>
                <p>Selecciona una plantilla arriba para comenzar a editar el contrato</p>
            </div>
        `;
        selectedTemplateId.value = '';
        return;
    }

    selectedTemplateId.value = select.value;
    
    // Buscar template seleccionado
    currentTemplateData = templates.find(t => t.id == select.value);
    
    if (currentTemplateData) {
        // Cargar contenido de la plantilla al editor
        fetch(`/admin/clients/${clientData.id}/contract-preview`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                template_id: select.value,
                contract_data: {}
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                // Usar el método correcto de Quill para cargar el HTML
                quillEditor.clipboard.dangerouslyPasteHTML(0, data.preview_html);
                
                // Actualizar el campo hidden
                document.getElementById('contract-content').value = data.preview_html;
                document.getElementById('create-contract-btn').disabled = false;
                
                console.log('Plantilla cargada en el editor correctamente');
            }
        });

        // Cargar campos de variables
        loadVariableFields(currentTemplateData.variables);
    }
}

function loadVariableFields(variables) {
    const templateVariables = document.getElementById('template-variables');
    const variablesContainer = document.getElementById('variables-container');
    
    // Limpiar contenedor
    variablesContainer.innerHTML = '';
    
    // Variables que se llenan automáticamente
    const autoVariables = [
        'cliente_nombre', 'cliente_empresa', 'cliente_email', 
        'cliente_telefono', 'cliente_movil', 'cliente_direccion',
        'cliente_ciudad', 'cliente_estado', 'cliente_cp', 'fecha_contrato'
    ];
    
    let hasManualVariables = false;
    variables.forEach(variable => {
        if (!autoVariables.includes(variable)) {
            hasManualVariables = true;
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-2';
            
            const fieldLabel = variable.replace(/_/g, ' ')
                                     .replace(/\b\w/g, l => l.toUpperCase());
            
            fieldDiv.innerHTML = `
                <label class="form-label mb-1">
                    <small><strong>${fieldLabel}</strong></small>
                </label>
                <input type="text" id="var_${variable}" class="form-control form-control-sm" 
                       placeholder="${fieldLabel}">
            `;
            variablesContainer.appendChild(fieldDiv);
        }
    });
    
    templateVariables.style.display = hasManualVariables ? 'block' : 'none';
}

function applyVariables() {
    if (!currentTemplateData) {
        console.warn('No hay datos de plantilla disponibles');
        return;
    }

    // Recopilar valores de variables
    const variableValues = {};

    // Variables automáticas del cliente  
    variableValues['cliente_nombre'] = clientData.name || '';
    variableValues['cliente_empresa'] = clientData.company || '';
    variableValues['cliente_email'] = clientData.email || '';
    variableValues['cliente_telefono'] = clientData.phone || '';
    variableValues['cliente_movil'] = clientData.phone || '';
    variableValues['cliente_direccion'] = clientData.address || '';
    variableValues['fecha_contrato'] = new Date().toLocaleDateString('es-MX');

    // Variables manuales del formulario
    currentTemplateData.variables.forEach(variable => {
        const input = document.getElementById(`var_${variable}`);
        if (input && input.value) {
            variableValues[variable] = input.value.trim();
        }
    });

    // Obtener el contenido HTML actual del editor
    let htmlContent = quillEditor.root.innerHTML;

    console.log('Variables a aplicar:', variableValues);
    console.log('Contenido HTML antes del reemplazo:', htmlContent);

    // Variable para verificar si hubo cambios
    let contentChanged = false;

    // Reemplazar todas las variables en el HTML
    Object.keys(variableValues).forEach(key => {
        if (variableValues[key] && variableValues[key] !== '') {
            // Crear regex para buscar la variable con formato de llaves dobles
            const regex = new RegExp('\\{\\{\\s*' + key + '\\s*\\}\\}', 'gi');
            const oldContent = htmlContent;
            
            // Reemplazar la variable con su valor
            htmlContent = htmlContent.replace(regex, variableValues[key]);
            
            if (oldContent !== htmlContent) {
                console.log('Variable {{' + key + '}} reemplazada con: ' + variableValues[key]);
                contentChanged = true;
            }
        }
    });

    console.log('Contenido HTML después del reemplazo:', htmlContent);

    if (contentChanged) {
        // Usar clipboard.dangerouslyPasteHTML (método recomendado por Quill.js)
        const currentPosition = quillEditor.getSelection() ? quillEditor.getSelection().index : 0;
        
        // Limpiar el contenido actual
        quillEditor.setText('');
        
        // Pegar el nuevo HTML
        quillEditor.clipboard.dangerouslyPasteHTML(0, htmlContent);
        
        // Restaurar la posición del cursor si es posible
        if (currentPosition > 0) {
            quillEditor.setSelection(currentPosition, 0);
        }

        // Actualizar el campo hidden del formulario
        document.getElementById('contract-content').value = htmlContent;

        // Notificación de éxito
        Swal.fire({
            icon: 'success',
            title: 'Variables aplicadas',
            text: 'Variables aplicadas correctamente al contrato.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    } else {
        console.log('No se encontraron variables para reemplazar o ya fueron aplicadas');
        Swal.fire({
            icon: 'info',
            title: 'Sin cambios',
            text: 'No hay variables pendientes de aplicar.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    }
}

function showPreview() {
    const content = quillEditor.root.innerHTML;
    const previewContent = document.getElementById('preview-content');
    
    if (content.trim() === '') {
        previewContent.innerHTML = `
            <div class="text-center text-muted p-5">
                <i class="fas fa-file-alt fa-3x mb-3"></i>
                <p>No hay contenido para mostrar</p>
            </div>
        `;
    } else {
        previewContent.innerHTML = content;
    }
    
    // Mostrar modal (usando Bootstrap 5)
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

// Función para validar y enviar formulario
function submitContractForm() {
    const form = document.getElementById('contract-form');
    const content = quillEditor.root.innerHTML.trim();
    const templateId = document.getElementById('selected-template-id').value;
    
    // Para edición, no es necesario validar plantilla (ya existe)
    // Solo asegurar que tenemos el ID de template en el campo hidden
    if (!templateId && existingTemplateId) {
        document.getElementById('selected-template-id').value = existingTemplateId;
    }
    
    // Validar que hay contenido
    if (content === '' || content === '<p><br></p>' || content.length < 50) {
        Swal.fire({
            icon: 'warning',
            title: 'Contenido insuficiente',
            text: 'Por favor, agrega contenido suficiente al contrato antes de crearlo.'
        });
        return false;
    }
    
    // Sincronizar fechas con campos hidden
    const startDate = document.getElementById('start_date').value;
    const expirationDate = document.getElementById('expiration_date').value;
    
    // Validar fechas
    if (!startDate || !expirationDate) {
        Swal.fire({
            icon: 'warning',
            title: 'Fechas requeridas',
            text: 'Por favor, completa las fechas de inicio y vencimiento del contrato.'
        });
        return false;
    }
    
    if (new Date(startDate) >= new Date(expirationDate)) {
        Swal.fire({
            icon: 'warning',
            title: 'Fechas inválidas',
            text: 'La fecha de vencimiento debe ser posterior a la fecha de inicio.'
        });
        return false;
    }
    
    // Actualizar campos hidden
    document.getElementById('hidden-start-date').value = startDate;
    document.getElementById('hidden-expiration-date').value = expirationDate;
    
    // Mostrar confirmación antes de crear
    Swal.fire({
        title: '¿Actualizar contrato?',
        text: 'El contrato será actualizado con el contenido actual del editor.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, actualizar contrato',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Actualizar el campo oculto con el contenido final
            document.getElementById('contract-content').value = content;
            // Mostrar loading
            Swal.fire({
                title: 'Actualizando contrato...',
                text: 'Por favor espera mientras se procesa.',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            // Enviar el formulario
            form.submit();
        }
    });
}
</script>

@endsection