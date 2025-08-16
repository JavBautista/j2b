<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $shop = $user->shop;
        
        $contracts = Contract::whereHas('client', function($query) use ($shop) {
                                $query->where('shop_id', $shop->id);
                            })
                           ->with(['client', 'template'])
                           ->orderBy('created_at', 'desc')
                           ->paginate(15);
                           
        return view('admin.contracts.contracts-index', compact('contracts'));
    }

    public function create()
    {
        $user = Auth::user();
        $shop = $user->shop;
        
        $templates = ContractTemplate::where('shop_id', $shop->id)
                                    ->where('is_active', true)
                                    ->get();
                                    
        $clients = Client::where('shop_id', $shop->id)
                        ->where('active', 1)
                        ->get();
        
        return view('admin.contracts.contracts-create', compact('templates', 'clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'contract_template_id' => 'required|exists:contract_templates,id',
            'contract_data' => 'required|array'
        ]);

        $user = Auth::user();
        $shop = $user->shop;
        
        // Verificar que el cliente pertenece a la tienda
        $client = Client::where('id', $request->client_id)
                       ->where('shop_id', $shop->id)
                       ->firstOrFail();
                       
        // Verificar que la plantilla pertenece a la tienda
        $template = ContractTemplate::where('id', $request->contract_template_id)
                                   ->where('shop_id', $shop->id)
                                   ->firstOrFail();

        $contract = Contract::create([
            'client_id' => $client->id,
            'contract_template_id' => $template->id,
            'contract_data' => $request->contract_data,
            'status' => 'draft'
        ]);

        return redirect()->route('contracts.show', $contract)
                        ->with('success', 'Contrato creado exitosamente');
    }

    public function show(Contract $contract)
    {
        $this->authorize('view', $contract);
        
        $client = $contract->client;
        $template = $contract->template;
        
        // Verificar si existe contenido personalizado del editor Quill
        if (!empty($contract->contract_content)) {
            // Usar contenido personalizado creado con el editor Quill
            $finalHtml = $contract->contract_content;
        } else {
            // Fallback: usar plantilla original con variables reemplazadas
            $contractData = array_merge([
                'cliente_nombre' => $client->name,
                'cliente_email' => $client->email,
                'cliente_telefono' => $client->phone,
                'cliente_direccion' => $client->address,
                'fecha_contrato' => $contract->created_at->format('d/m/Y'),
            ], $contract->contract_data);

            $finalHtml = $template->replaceVariables($contractData);
        }
        
        return view('admin.contracts.contracts-show', [
            'contract' => $contract,
            'finalHtml' => $finalHtml
        ]);
    }

    public function generatePdf(Contract $contract)
    {
        $this->authorize('view', $contract);
        
        $client = $contract->client;
        $template = $contract->template;
        
        // Verificar si existe contenido personalizado del editor Quill
        if (!empty($contract->contract_content)) {
            // Usar contenido personalizado creado con el editor Quill
            $finalHtml = $contract->contract_content;
        } else {
            // Fallback: usar plantilla original con variables reemplazadas
            $contractData = array_merge([
                'cliente_nombre' => $client->name,
                'cliente_email' => $client->email,
                'cliente_telefono' => $client->phone,
                'cliente_direccion' => $client->address,
                'fecha_contrato' => $contract->created_at->format('d/m/Y'),
            ], $contract->contract_data);

            $finalHtml = $template->replaceVariables($contractData);
        }
        
        // Agregar sección de firmas al final del contenido
        $signaturesHtml = $this->generateSignaturesSection($contract);
        $finalHtml .= $signaturesHtml;
        
        // Crear PDF con CSS integrado y estilos para firmas
        $pdfStyles = '<style>' . $template->css_styles . $this->getSignatureStyles() . '</style>';
        $htmlWithStyles = $pdfStyles . $finalHtml;
        $pdf = Pdf::loadHTML($htmlWithStyles);
        
        // Guardar PDF
        $pdfPath = 'contracts/' . $contract->id . '_' . time() . '.pdf';
        Storage::put('public/' . $pdfPath, $pdf->output());
        
        // Actualizar contrato
        $contract->update([
            'pdf_path' => $pdfPath,
            'status' => 'generated'
        ]);

        return $pdf->download('contrato_' . $contract->id . '.pdf');
    }

    private function generateSignaturesSection(Contract $contract)
    {
        $client = $contract->client;
        $shop = $client->shop;
        
        $html = '<div class="signatures-section">';
        $html .= '<br><br>'; // Espaciado en lugar de salto de página
        $html .= '<h3 class="signatures-title">Firmas del Contrato</h3>';
        $html .= '<div class="signatures-container">';
        
        // Firma del Cliente
        $html .= '<div class="signature-box">';
        $html .= '<h4 class="signature-label">Firma del Cliente</h4>';
        if ($contract->signature_path) {
            $clientSignaturePath = storage_path('app/public/' . $contract->signature_path);
            if (file_exists($clientSignaturePath)) {
                $clientSignatureData = base64_encode(file_get_contents($clientSignaturePath));
                $clientSignatureType = pathinfo($clientSignaturePath, PATHINFO_EXTENSION);
                $html .= '<div class="signature-image-container">';
                $html .= '<img src="data:image/' . $clientSignatureType . ';base64,' . $clientSignatureData . '" class="signature-image" alt="Firma del Cliente">';
                $html .= '</div>';
            } else {
                $html .= '<div class="signature-placeholder">Firma no disponible</div>';
            }
        } else {
            $html .= '<div class="signature-placeholder">Sin firma del cliente</div>';
        }
        $html .= '<div class="signature-line"></div>';
        $html .= '<p class="signature-name">' . $client->name . '</p>';
        $html .= '<p class="signature-title">Cliente</p>';
        $html .= '</div>';
        
        // Firma del Representante Legal (Vendedor/Tienda)
        $html .= '<div class="signature-box">';
        $html .= '<h4 class="signature-label">Firma del Representante Legal</h4>';
        if ($shop->legal_representative_signature_path) {
            $shopSignaturePath = storage_path('app/public/' . $shop->legal_representative_signature_path);
            if (file_exists($shopSignaturePath)) {
                $shopSignatureData = base64_encode(file_get_contents($shopSignaturePath));
                $shopSignatureType = pathinfo($shopSignaturePath, PATHINFO_EXTENSION);
                $html .= '<div class="signature-image-container">';
                $html .= '<img src="data:image/' . $shopSignatureType . ';base64,' . $shopSignatureData . '" class="signature-image" alt="Firma del Representante Legal">';
                $html .= '</div>';
            } else {
                $html .= '<div class="signature-placeholder">Firma no disponible</div>';
            }
        } else {
            $html .= '<div class="signature-placeholder">Sin firma del representante legal</div>';
        }
        $html .= '<div class="signature-line"></div>';
        $html .= '<p class="signature-name">' . ($shop->owner_name ?: $shop->name) . '</p>';
        $html .= '<p class="signature-title">Representante Legal</p>';
        $html .= '</div>';
        
        $html .= '</div>'; // End signatures-container
        
        // Información adicional
        $html .= '<div class="signature-info">';
        $html .= '<p><strong>Fecha del Contrato:</strong> ' . ($contract->start_date ? $contract->start_date->format('d/m/Y') : $contract->created_at->format('d/m/Y')) . '</p>';
        if ($contract->expiration_date) {
            $html .= '<p><strong>Fecha de Vencimiento:</strong> ' . $contract->expiration_date->format('d/m/Y') . '</p>';
        }
        $html .= '<p><strong>Contrato ID:</strong> #' . $contract->id . '</p>';
        $html .= '</div>';
        
        $html .= '</div>'; // End signatures-section
        
        return $html;
    }
    
    private function getSignatureStyles()
    {
        return '
        .signatures-section {
            margin-top: 40px;
            padding: 20px 0;
            border-top: 2px solid #333;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .signatures-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
        }
        
        .signatures-container {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 30px;
        }
        
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
            vertical-align: top;
            border: 1px solid #ddd;
        }
        
        .signature-box:first-child {
            border-right: none;
        }
        
        .signature-label {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #555;
        }
        
        .signature-image-container {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 15px auto;
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 4px;
            width: 90%;
        }
        
        .signature-image {
            max-height: 80px;
            max-width: 180px;
            object-fit: contain;
        }
        
        .signature-placeholder {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            border: 1px dashed #ccc;
            color: #888;
            font-style: italic;
            margin: 15px auto;
            border-radius: 4px;
            width: 90%;
            font-size: 12px;
        }
        
        .signature-line {
            border-bottom: 2px solid #333;
            margin: 15px auto 10px auto;
            width: 85%;
        }
        
        .signature-name {
            font-weight: bold;
            margin: 8px 0 3px 0;
            font-size: 13px;
            color: #333;
        }
        
        .signature-title {
            font-size: 11px;
            color: #666;
            margin: 0;
            font-style: italic;
        }
        
        .signature-info {
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .signature-info p {
            margin: 3px 0;
        }
        ';
    }

    public function destroy(Contract $contract)
    {
        $this->authorize('delete', $contract);
        
        // Eliminar archivo PDF si existe
        if ($contract->pdf_path) {
            Storage::delete('public/' . $contract->pdf_path);
        }
        
        // Eliminar archivo de firma si existe
        if ($contract->signature_path) {
            $this->deleteSignatureFile($contract->signature_path);
        }
        
        $contract->delete();

        return redirect()->route('contracts.index')
                        ->with('success', 'Contrato eliminado exitosamente');
    }

    // API METHODS FOR MOBILE APP
    
    // Método específico para clientes autenticados - Ver MIS contratos
    public function getMyContracts(Request $request)
    {
        try {
            $user = $request->user();
            
            // Verificar que el usuario tenga un cliente asociado
            if (!$user->client) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Usuario no tiene cliente asociado'
                ], 400);
            }
            
            $clientId = $user->client->id;
            
            // Obtener contratos del cliente autenticado
            $contracts = Contract::where('client_id', $clientId)
                               ->with(['template'])
                               ->orderBy('created_at', 'desc')
                               ->get();
            
            return response()->json([
                'ok' => true,
                'contracts' => $contracts,
                'total' => $contracts->count(),
                'client' => $user->client
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener mis contratos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClientContracts(Request $request, $client_id)
    {
        try {
            // Obtener contratos del cliente
            $contracts = Contract::where('client_id', $client_id)
                               ->with(['template'])
                               ->orderBy('created_at', 'desc')
                               ->get();
            
            return response()->json([
                'ok' => true,
                'contracts' => $contracts,
                'total' => $contracts->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener contratos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewContract(Request $request, Contract $contract)
    {
        try {
            $user = $request->user();
            
            // Verificar que el contrato pertenece al cliente del usuario
            if (!$user->client || $user->client->id !== $contract->client_id) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No autorizado para ver este contrato'
                ], 403);
            }
            
            $client = $contract->client;
            
            // Para clientes autenticados, mostrar el contenido personalizado real del contrato
            // No el template genérico con variables reemplazadas
            $finalHtml = 'Contenido del contrato no disponible';
            
            if ($contract->contract_content) {
                // Si tiene contenido personalizado, mostrarlo
                $finalHtml = $contract->contract_content;
            } else if ($contract->contract_data) {
                // Si tiene datos del contrato, mostrarlos en formato legible
                try {
                    $data = is_string($contract->contract_data) 
                        ? json_decode($contract->contract_data, true) 
                        : $contract->contract_data;
                    
                    if (is_array($data)) {
                        $finalHtml = '<h3>Datos del Contrato:</h3>';
                        foreach ($data as $key => $value) {
                            $finalHtml .= '<p><strong>' . ucfirst(str_replace('_', ' ', $key)) . ':</strong> ' . $value . '</p>';
                        }
                    } else {
                        $finalHtml = '<p>Datos del contrato: ' . $contract->contract_data . '</p>';
                    }
                } catch (\Exception $e) {
                    $finalHtml = '<p>Datos del contrato: ' . $contract->contract_data . '</p>';
                }
            }
            
            return response()->json([
                'ok' => true,
                'contract' => $contract,
                'contractHtml' => $finalHtml
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al cargar el contrato',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveSignature(Request $request)
    {
        $user = $request->user();
        
        try {
            $contract = Contract::findOrFail($request->contract_id);
            $this->authorize('view', $contract);
            
            $signature_base64 = $request->signature;
            
            if (!$signature_base64) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La firma es requerida.',
                ], 400);
            }
            
            // Procesar base64 y guardar como archivo
            $signaturePath = $this->processBase64ToImage($signature_base64, $contract->id, 'contract');
            
            // Guardar la ruta en la base de datos y actualizar estado
            $contract->signature_path = $signaturePath;
            $contract->status = 'signed';
            $contract->save();
            
            return response()->json([
                'ok' => true,
                'contract' => $contract,
                'signature_url' => $this->getSignaturePublicUrl($signaturePath),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al guardar la firma.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSignature(Request $request)
    {
        $user = $request->user();
        
        try {
            $contract = Contract::findOrFail($request->contract_id);
            $this->authorize('view', $contract);
            
            $signature_base64 = $request->signature;
            
            if (!$signature_base64) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La firma es requerida.',
                ], 400);
            }
            
            // Eliminar la firma anterior si existe
            if ($contract->signature_path) {
                $this->deleteSignatureFile($contract->signature_path);
            }
            
            // Procesar la nueva firma y guardar como archivo
            $signaturePath = $this->processBase64ToImage($signature_base64, $contract->id, 'contract');
            
            // Actualizar la ruta en la base de datos
            $contract->signature_path = $signaturePath;
            $contract->status = 'signed';
            $contract->save();
            
            return response()->json([
                'ok' => true,
                'contract' => $contract,
                'signature_url' => $this->getSignaturePublicUrl($signaturePath),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar la firma.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteSignature(Request $request)
    {
        $user = $request->user();
        
        try {
            $contract = Contract::findOrFail($request->contract_id);
            $this->authorize('view', $contract);
            
            if ($contract->signature_path) {
                $this->deleteSignatureFile($contract->signature_path);
                $contract->signature_path = null;
                $contract->status = 'generated'; // Volver a estado anterior
                $contract->save();
            }
            
            return response()->json([
                'ok' => true,
                'contract' => $contract,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar la firma.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function processBase64ToImage($base64Data, $contractId, $prefix = 'contract')
    {
        try {
            // Remover el prefijo data:image/...;base64, si existe
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
                $imageType = $matches[1]; // png, jpeg, etc.
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            } else {
                $imageType = 'png'; // Tipo por defecto
            }

            // Validar que sea PNG o JPEG
            if (!in_array(strtolower($imageType), ['png', 'jpeg', 'jpg'])) {
                throw new \Exception('Formato de imagen no válido. Solo se permiten PNG y JPEG.');
            }

            // Decodificar base64
            $imageData = base64_decode($base64Data);
            if ($imageData === false) {
                throw new \Exception('Error al decodificar la imagen base64.');
            }

            // Generar nombre único para el archivo
            $timestamp = now()->format('YmdHis');
            $filename = "{$prefix}_{$contractId}_{$timestamp}.png";
            $relativePath = "signatures/{$filename}";

            // Crear directorio si no existe
            $fullPath = storage_path('app/public/signatures');
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Guardar el archivo
            $fullFilePath = "{$fullPath}/{$filename}";
            if (file_put_contents($fullFilePath, $imageData) === false) {
                throw new \Exception('Error al guardar el archivo de imagen.');
            }

            return $relativePath;

        } catch (\Exception $e) {
            throw new \Exception("Error procesando la imagen: " . $e->getMessage());
        }
    }

    private function deleteSignatureFile($signaturePath)
    {
        if ($signaturePath && Storage::disk('public')->exists($signaturePath)) {
            Storage::disk('public')->delete($signaturePath);
        }
    }

    private function getSignaturePublicUrl($signaturePath)
    {
        if ($signaturePath) {
            return asset('storage/' . $signaturePath);
        }
        return null;
    }

    // Método público para imprimir contratos (sin autenticación - para móviles)
    public function printContract(Request $request)
    {
        if (!isset($request->id)) return null;
        
        $id = $request->id;
        $name_file = $this->removeSpecialChar($request->name_file ?? 'contrato_' . $id);
        
        $contract = Contract::with(['client', 'template'])
                           ->findOrFail($id);
                           
        if (!$contract->template) {
            abort(404, 'Template no encontrado');
        }
        
        $client = $contract->client;
        $template = $contract->template;
        
        // Preparar datos para mostrar (igual que en el método show)
        $contractData = array_merge([
            'cliente_nombre' => $client->name,
            'cliente_email' => $client->email,
            'cliente_telefono' => $client->phone,
            'cliente_direccion' => $client->address,
            'fecha_contrato' => $contract->created_at->format('d/m/Y'),
        ], $contract->contract_data ?? []);

        // Verificar si existe contenido personalizado del editor Quill (igual que generatePdf)
        if (!empty($contract->contract_content)) {
            // Usar contenido personalizado creado con el editor Quill
            $finalHtml = $contract->contract_content;
        } else {
            // Fallback: usar plantilla original con variables reemplazadas
            $finalHtml = $template->replaceVariables($contractData);
        }
        
        // Agregar sección de firmas al final del contenido (igual que generatePdf)
        $signaturesHtml = $this->generateSignaturesSection($contract);
        $finalHtml .= $signaturesHtml;
        
        // Crear PDF con CSS integrado y estilos para firmas (igual que generatePdf)
        $pdfStyles = '<style>' . $template->css_styles . $this->getSignatureStyles() . '</style>';
        $htmlWithStyles = $pdfStyles . $finalHtml;
        
        $pdf = Pdf::loadHTML($htmlWithStyles);
        
        return $pdf->stream($name_file . '.pdf', array("Attachment" => false));
    }

    // Método auxiliar para limpiar caracteres especiales (como en ReceiptController)
    private function removeSpecialChar($str)
    {
        $res = preg_replace('/[@\.\;\" "]+/', '_', $str);
        return $res;
    }
}
