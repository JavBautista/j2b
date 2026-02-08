<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShopAiSettings;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Service;
use App\Models\Client;
use App\Services\AI\EmbeddingService;

class AiSettingsController extends Controller
{
    /**
     * Muestra el índice de configuraciones IA
     */
    public function index()
    {
        // Verificar que el usuario tenga acceso a IA
        if (!auth()->user()->can_use_ai) {
            return redirect()->route('admin.configurations')
                ->with('error', 'No tienes acceso a las configuraciones de IA.');
        }

        return view('admin.configurations.ai-settings.index');
    }

    /**
     * Muestra la página de configuración del prompt
     */
    public function prompt()
    {
        if (!auth()->user()->can_use_ai) {
            return redirect()->route('admin.configurations')
                ->with('error', 'No tienes acceso a las configuraciones de IA.');
        }

        return view('admin.configurations.ai-settings.prompt');
    }

    /**
     * Muestra la página de indexación de productos
     */
    public function indexing()
    {
        if (!auth()->user()->can_use_ai) {
            return redirect()->route('admin.configurations')
                ->with('error', 'No tienes acceso a las configuraciones de IA.');
        }

        return view('admin.configurations.ai-settings.indexing');
    }

    /**
     * Obtiene la configuración IA de la tienda actual
     */
    public function get(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $user = auth()->user();

        if (!$user->can_use_ai) {
            return response()->json(['ok' => false, 'message' => 'Sin acceso a IA'], 403);
        }

        $shop = Shop::find($user->shop_id);

        $settings = ShopAiSettings::where('shop_id', $user->shop_id)->first();

        // Si no existe, crear una configuración vacía con prompt por defecto
        if (!$settings) {
            $defaultPrompt = $this->getDefaultPrompt($shop);
            $settings = [
                'id' => null,
                'shop_id' => $user->shop_id,
                'system_prompt' => $defaultPrompt,
            ];
        }

        return response()->json([
            'ok' => true,
            'settings' => $settings,
            'shop_name' => $shop->name ?? 'Mi Tienda',
        ]);
    }

    /**
     * Guarda o actualiza la configuración IA
     */
    public function save(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        if (!auth()->user()->can_use_ai) {
            return response()->json(['ok' => false, 'message' => 'Sin acceso a IA'], 403);
        }

        $request->validate([
            'system_prompt' => 'required|string|max:5000',
        ]);

        $user = auth()->user();

        $settings = ShopAiSettings::updateOrCreate(
            ['shop_id' => $user->shop_id],
            ['system_prompt' => $request->system_prompt]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Configuración guardada correctamente',
            'settings' => $settings,
        ]);
    }

    /**
     * Obtener estado del indexado de embeddings
     */
    public function getIndexStatus(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $user = auth()->user();

        if (!$user->can_use_ai) {
            return response()->json(['ok' => false, 'message' => 'Sin acceso a IA'], 403);
        }

        $shopId = $user->shop_id;

        // Contar productos activos
        $productsActive = Product::where('shop_id', $shopId)
            ->where('active', true)
            ->count();

        // Contar servicios activos
        $servicesActive = Service::where('shop_id', $shopId)
            ->where('active', true)
            ->count();

        // Contar clientes activos
        $clientsActive = Client::where('shop_id', $shopId)
            ->where('active', true)
            ->count();

        // Obtener conteo de Qdrant (vía Python service)
        $embeddingService = new EmbeddingService();
        $indexedCounts = $embeddingService->getIndexedCounts($shopId);

        // Última sincronización
        $settings = ShopAiSettings::where('shop_id', $shopId)->first();
        $lastSync = $settings?->last_embedding_sync;

        return response()->json([
            'ok' => true,
            'data' => [
                'products' => [
                    'indexed' => $indexedCounts['products'] ?? 0,
                    'active' => $productsActive,
                    'pending' => max(0, $productsActive - ($indexedCounts['products'] ?? 0))
                ],
                'services' => [
                    'indexed' => $indexedCounts['services'] ?? 0,
                    'active' => $servicesActive,
                    'pending' => max(0, $servicesActive - ($indexedCounts['services'] ?? 0))
                ],
                'clients' => [
                    'indexed' => $indexedCounts['clients'] ?? 0,
                    'active' => $clientsActive,
                    'pending' => max(0, $clientsActive - ($indexedCounts['clients'] ?? 0))
                ],
                'last_sync' => $lastSync?->format('Y-m-d H:i'),
                'is_synced' => ($indexedCounts['products'] ?? 0) >= $productsActive
                            && ($indexedCounts['services'] ?? 0) >= $servicesActive
                            && ($indexedCounts['clients'] ?? 0) >= $clientsActive
            ]
        ]);
    }

    /**
     * Indexar productos en Qdrant
     */
    public function indexProducts(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $user = auth()->user();

        if (!$user->can_use_ai) {
            return response()->json(['ok' => false, 'message' => 'Sin acceso a IA'], 403);
        }

        $shopId = $user->shop_id;

        try {
            $embeddingService = new EmbeddingService();
            $result = $embeddingService->indexProducts($shopId);

            // Actualizar timestamp
            ShopAiSettings::updateOrCreate(
                ['shop_id' => $shopId],
                ['last_embedding_sync' => now()]
            );

            return response()->json([
                'ok' => true,
                'message' => "{$result['indexed']} productos indexados correctamente",
                'indexed' => $result['indexed'],
                'errors' => $result['errors'] ?? 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al indexar productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Indexar servicios en Qdrant
     */
    public function indexServices(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $user = auth()->user();

        if (!$user->can_use_ai) {
            return response()->json(['ok' => false, 'message' => 'Sin acceso a IA'], 403);
        }

        $shopId = $user->shop_id;

        try {
            $embeddingService = new EmbeddingService();
            $result = $embeddingService->indexServices($shopId);

            // Actualizar timestamp
            ShopAiSettings::updateOrCreate(
                ['shop_id' => $shopId],
                ['last_embedding_sync' => now()]
            );

            return response()->json([
                'ok' => true,
                'message' => "{$result['indexed']} servicios indexados correctamente",
                'indexed' => $result['indexed'],
                'errors' => $result['errors'] ?? 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al indexar servicios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Indexar clientes en Qdrant
     */
    public function indexClients(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $user = auth()->user();

        if (!$user->can_use_ai) {
            return response()->json(['ok' => false, 'message' => 'Sin acceso a IA'], 403);
        }

        $shopId = $user->shop_id;

        try {
            $embeddingService = new EmbeddingService();
            $result = $embeddingService->indexClients($shopId);

            // Actualizar timestamp
            ShopAiSettings::updateOrCreate(
                ['shop_id' => $shopId],
                ['last_embedding_sync' => now()]
            );

            return response()->json([
                'ok' => true,
                'message' => "{$result['indexed']} clientes indexados correctamente",
                'indexed' => $result['indexed'],
                'errors' => $result['errors'] ?? 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al indexar clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Indexar todo el catálogo (productos + servicios)
     */
    public function indexAll(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $user = auth()->user();

        if (!$user->can_use_ai) {
            return response()->json(['ok' => false, 'message' => 'Sin acceso a IA'], 403);
        }

        $shopId = $user->shop_id;

        try {
            $embeddingService = new EmbeddingService();
            $result = $embeddingService->indexCatalog($shopId);

            // Actualizar timestamp
            ShopAiSettings::updateOrCreate(
                ['shop_id' => $shopId],
                ['last_embedding_sync' => now()]
            );

            return response()->json([
                'ok' => true,
                'message' => "Catálogo indexado: {$result['products']} productos, {$result['services']} servicios",
                'products_indexed' => $result['products'],
                'services_indexed' => $result['services'],
                'errors' => $result['errors'] ?? 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al indexar catálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera el prompt por defecto para una tienda usando sus datos reales
     */
    private function getDefaultPrompt($shop)
    {
        $shopName = $shop->name ?? 'Mi Tienda';

        // Construir sección "Sobre nosotros"
        $aboutUs = '';
        if (!empty($shop->description)) {
            $aboutUs = $shop->description;
        } else {
            $aboutUs = '[Describe aquí tu negocio: qué vendes, a qué te dedicas, tu propuesta de valor]';
        }

        // Agregar slogan si existe
        if (!empty($shop->slogan)) {
            $aboutUs = "\"{$shop->slogan}\"\n\n" . $aboutUs;
        }

        // Construir dirección
        $address = $this->buildAddress($shop);

        // Construir contacto
        $contact = $this->buildContact($shop);

        // Construir redes sociales
        $social = $this->buildSocialMedia($shop);

        // Construir prompt
        $prompt = "Eres el asistente virtual de {$shopName}.

## Sobre nosotros:
{$aboutUs}";

        // Agregar misión/visión/valores si existen
        if (!empty($shop->mission)) {
            $prompt .= "\n\n**Nuestra misión:** {$shop->mission}";
        }
        if (!empty($shop->vision)) {
            $prompt .= "\n\n**Nuestra visión:** {$shop->vision}";
        }
        if (!empty($shop->values)) {
            $prompt .= "\n\n**Nuestros valores:**\n{$shop->values}";
        }

        $prompt .= "

## Nuestros servicios/productos principales:
- [Servicio o producto 1]
- [Servicio o producto 2]
- [Servicio o producto 3]
(Personaliza esta sección con tus productos y servicios reales)

## Horarios de atención:
[Indica tus horarios de atención si aplica]";

        // Agregar ubicación si existe
        if (!empty($address)) {
            $prompt .= "

## Ubicación:
{$address}";
        }

        // Agregar contacto si existe
        if (!empty($contact)) {
            $prompt .= "

## Contacto:
{$contact}";
        }

        // Agregar redes sociales si existen
        if (!empty($social)) {
            $prompt .= "

## Redes sociales:
{$social}";
        }

        $prompt .= "

## Instrucciones para el asistente:
- Responde siempre en español
- Sé amable y profesional
- Si no sabes algo específico, indica que pueden contactar directamente a la tienda
- Ayuda a los usuarios con información sobre productos, servicios y precios
- No inventes información que no tengas disponible
- Cuando menciones datos de contacto, usa la información proporcionada arriba";

        return $prompt;
    }

    /**
     * Construye la dirección formateada
     */
    private function buildAddress($shop)
    {
        $parts = [];

        if (!empty($shop->address)) {
            $addr = $shop->address;
            if (!empty($shop->number_out)) {
                $addr .= ' #' . $shop->number_out;
            }
            if (!empty($shop->number_int)) {
                $addr .= ' Int. ' . $shop->number_int;
            }
            $parts[] = $addr;
        }

        if (!empty($shop->district)) {
            $parts[] = "Col. {$shop->district}";
        }

        $cityState = [];
        if (!empty($shop->city)) $cityState[] = $shop->city;
        if (!empty($shop->state)) $cityState[] = $shop->state;
        if (!empty($cityState)) {
            $parts[] = implode(', ', $cityState);
        }

        if (!empty($shop->zip_code)) {
            $parts[] = "C.P. {$shop->zip_code}";
        }

        return implode("\n", $parts);
    }

    /**
     * Construye la información de contacto
     */
    private function buildContact($shop)
    {
        $parts = [];

        if (!empty($shop->phone)) {
            $parts[] = "Teléfono: {$shop->phone}";
        }
        if (!empty($shop->whatsapp)) {
            $parts[] = "WhatsApp: {$shop->whatsapp}";
        }
        if (!empty($shop->email)) {
            $parts[] = "Email: {$shop->email}";
        }
        if (!empty($shop->web)) {
            $parts[] = "Web: {$shop->web}";
        }

        return implode("\n", $parts);
    }

    /**
     * Construye la información de redes sociales
     */
    private function buildSocialMedia($shop)
    {
        $parts = [];

        if (!empty($shop->facebook)) {
            $parts[] = "Facebook: {$shop->facebook}";
        }
        if (!empty($shop->instagram)) {
            $parts[] = "Instagram: {$shop->instagram}";
        }
        if (!empty($shop->twitter)) {
            $parts[] = "Twitter: {$shop->twitter}";
        }

        return implode("\n", $parts);
    }

    /**
     * Restaura el prompt por defecto
     */
    public function resetPrompt(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $user = auth()->user();

        if (!$user->can_use_ai) {
            return response()->json(['ok' => false, 'message' => 'Sin acceso a IA'], 403);
        }

        $shop = Shop::find($user->shop_id);
        $defaultPrompt = $this->getDefaultPrompt($shop);

        return response()->json([
            'ok' => true,
            'default_prompt' => $defaultPrompt,
        ]);
    }
}
