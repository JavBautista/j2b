<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Auth;

class ContractTemplateController extends Controller
{
    /**
     * Obtener todas las variables disponibles para plantillas
     */
    private function getDefaultVariables()
    {
        // Variables que se llenan AUTOMÁTICAMENTE desde la BD
        $clientAutoVariables = [
            // Datos básicos del cliente (desde modelo Client)
            'cliente_nombre',      // → client.name
            'cliente_empresa',     // → client.company  
            'cliente_email',       // → client.email
            'cliente_telefono',    // → client.phone
            'cliente_movil',       // → client.movil
            'cliente_direccion',   // → client.address
            'cliente_ciudad',      // → client.city
            'cliente_estado',      // → client.state
            'cliente_cp',          // → client.zip_code
            'cliente_referencia',  // → client.reference
        ];
        
        // Variables que el ADMIN debe completar manualmente al crear el contrato
        $manualVariables = [
            // Datos de la empresa/vendedor
            'vendedor_nombre',
            'vendedor_direccion',
            'empresa_nombre',
            'empresa_telefono',
            'cuenta_bancaria',
            'clabe_interbancaria',
            'banco_nombre',
            
            // Fechas importantes  
            'fecha_contrato',
            'fecha_vencimiento',
            'fecha_entrega',
            
            // Datos financieros
            'monto_total',
            'monto_letra',
            'dia_pago',
            'interes_moratorio',
            
            // Descripción del producto/servicio
            'producto_descripcion',
            'producto_modelo',
            'producto_serie',
            'producto_estado',
            'contador_inicial_color',
            'contador_inicial_negro',
            
            // Garantía
            'garantia_copias',
            'garantia_copias_letra',
            'garantia_meses',
            
            // Ubicación del contrato
            'ciudad_contrato',
            'estado_contrato'
        ];
        
        return array_merge($clientAutoVariables, $manualVariables);
    }
    public function index()
    {
        $user = Auth::user();
        $shop = $user->shop;
        
        $contractTemplates = ContractTemplate::where('shop_id', $shop->id)
                                      ->orderBy('created_at', 'desc')
                                      ->get();
                                      
        $defaultVariables = $this->getDefaultVariables();
                                      
        return view('admin.contracts.index', [
            'templates' => $contractTemplates,
            'defaultVariables' => $defaultVariables
        ]);
    }

    public function create()
    {
        $defaultVariables = $this->getDefaultVariables();
        return view('admin.contracts.create-template', compact('defaultVariables'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'required|string',
            'css_styles' => 'nullable|string',
            'variables' => 'required'
        ]);

        $user = Auth::user();
        $shop = $user->shop;

        $variables = json_decode($request->variables, true);

        if (!is_array($variables)) {
            return back()->withErrors(['variables' => 'El campo variables debe ser un arreglo válido.']);
        }

        ContractTemplate::create([
            'shop_id' => $shop->id,
            'name' => $request->name,
            'html_content' => $request->html_content,
            'css_styles' => $request->css_styles,
            'variables' => $variables,
            'is_active' => true
        ]);

        //return redirect()->route('contract-templates.index')
        //                ->with('success', 'Plantilla creada exitosamente');

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Plantilla guardada']);
        } else {
            return redirect()->route('contract-templates.index')
                            ->with('success', 'Plantilla creada exitosamente');
        }
    }

    public function show(ContractTemplate $contractTemplate)
    {
        $this->authorize('view', $contractTemplate);
        
        // Datos de ejemplo para preview
        $sampleData = [
            'cliente_nombre' => 'Juan Pérez',
            'cliente_email' => 'juan@ejemplo.com',
            'fecha_contrato' => now()->format('d/m/Y'),
            'monto_total' => '$10,000.00'
        ];

        $previewHtml = $contractTemplate->replaceVariables($sampleData);
        
        return view('admin.contracts.show', [
            'template' => $contractTemplate,
            'previewHtml' => $previewHtml
        ]);
    }

    public function edit(ContractTemplate $contractTemplate)
    {
        $this->authorize('update', $contractTemplate);
        
        $defaultVariables = $this->getDefaultVariables();

        return view('admin.contracts.edit', [
            'template' => $contractTemplate,
            'defaultVariables' => $defaultVariables
        ]);
    }

    public function update(Request $request, ContractTemplate $contractTemplate)
    {
        $this->authorize('update', $contractTemplate);
        
        // Validar los campos básicos
        $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'required|string',
            'css_styles' => 'nullable|string',
            'variables' => 'required'
        ]);

        // Procesar variables (viene como JSON string desde el frontend)
        $variables = $request->variables;
        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        
        if (!is_array($variables)) {
            return back()->withErrors(['variables' => 'El campo variables debe ser un arreglo válido.']);
        }

        $contractTemplate->update([
            'name' => $request->name,
            'html_content' => $request->html_content,
            'css_styles' => $request->css_styles,
            'variables' => $variables
        ]);

        return redirect()->route('contract-templates.index')
                        ->with('success', 'Plantilla actualizada exitosamente');
    }

    public function destroy(ContractTemplate $contractTemplate)
    {
        $this->authorize('delete', $contractTemplate);
        
        // Alternar estado activo/inactivo
        $newStatus = !$contractTemplate->is_active;
        $contractTemplate->update(['is_active' => $newStatus]);
        
        $message = $newStatus ? 'Plantilla activada exitosamente' : 'Plantilla desactivada exitosamente';

        return redirect()->route('contract-templates.index')
                        ->with('success', $message);
    }
}
