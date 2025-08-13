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
        
        // Preparar datos para mostrar
        $contractData = array_merge([
            'cliente_nombre' => $client->name,
            'cliente_email' => $client->email,
            'cliente_telefono' => $client->phone,
            'cliente_direccion' => $client->address,
            'fecha_contrato' => $contract->created_at->format('d/m/Y'),
        ], $contract->contract_data);

        // Generar HTML final para vista previa
        $finalHtml = $template->replaceVariables($contractData);
        
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
        
        // Preparar datos para reemplazar variables
        $contractData = array_merge([
            'cliente_nombre' => $client->name,
            'cliente_email' => $client->email,
            'cliente_telefono' => $client->phone,
            'cliente_direccion' => $client->address,
            'fecha_contrato' => $contract->created_at->format('d/m/Y'),
        ], $contract->contract_data);

        // Generar HTML final
        $finalHtml = $template->replaceVariables($contractData);
        
        // Crear PDF con CSS integrado
        $htmlWithStyles = '<style>' . $template->css_styles . '</style>' . $finalHtml;
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
            $template = $contract->template;
            
            // Preparar datos para mostrar
            $contractData = array_merge([
                'cliente_nombre' => $client->name,
                'cliente_email' => $client->email,
                'cliente_telefono' => $client->phone,
                'cliente_direccion' => $client->address,
                'fecha_contrato' => $contract->created_at->format('d/m/Y'),
            ], $contract->contract_data ?? []);

            // Generar HTML final
            $finalHtml = $template ? $template->replaceVariables($contractData) : 'Contenido no disponible';
            
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

        // Generar HTML final
        $finalHtml = $template->replaceVariables($contractData);
        
        // Crear PDF con CSS integrado y estilos adicionales para firma
        $additionalStyles = '
            <style>
                .signature-section {
                    margin-top: 40px;
                    page-break-inside: avoid;
                }
                .signature-container {
                    text-align: center;
                    margin: 20px 0;
                }
                .signature-title {
                    color: #333;
                    margin-bottom: 15px;
                    font-size: 16px;
                    font-weight: bold;
                }
                .signature-frame {
                    border: 2px solid #007bff;
                    padding: 15px;
                    margin: 10px auto;
                    width: fit-content;
                    border-radius: 8px;
                    background-color: #f8f9fa;
                }
                .signature-image {
                    max-width: 350px;
                    height: auto;
                    max-height: 150px;
                }
                .signature-info {
                    margin-top: 10px;
                    color: #666;
                    font-size: 12px;
                    line-height: 1.4;
                }
                .signature-separator {
                    margin: 20px 0;
                    border: none;
                    height: 1px;
                    background-color: #ddd;
                }
            </style>
        ';
        $htmlWithStyles = $additionalStyles . '<style>' . $template->css_styles . '</style>' . $finalHtml;
        
        // Agregar firma si existe (usando public_path para PDFs)
        if ($contract->signature_path) {
            $signaturePublicPath = public_path('storage/' . $contract->signature_path);
            $signatureHtml = '
                <div class="signature-section">
                    <hr class="signature-separator">
                    <div class="signature-container">
                        <h3 class="signature-title">Firma Digital del Cliente</h3>
                        <div class="signature-frame">
                            <img src="' . $signaturePublicPath . '" class="signature-image" alt="Firma Digital">
                        </div>
                        <div class="signature-info">
                            <strong>Firmado digitalmente el ' . $contract->updated_at->format('d/m/Y \a \l\a\s H:i') . '</strong><br>
                            <em>Este documento tiene validez legal con la firma digital del cliente</em><br>
                            <small>Contrato ID: ' . $contract->id . ' | Cliente: ' . $client->name . '</small>
                        </div>
                    </div>
                </div>';
            $htmlWithStyles .= $signatureHtml;
        }
        
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
