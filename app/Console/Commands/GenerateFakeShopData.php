<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateFakeShopData extends Command
{
    protected $signature = 'shop:generate-fake-data {shop_id} {--no-images : No descargar imágenes}';
    protected $description = 'Genera categorías, productos y servicios fake de tecnología para una tienda';

    private $categories = [
        'Laptops' => [
            'description' => 'Laptops y notebooks para trabajo, estudio y gaming',
            'products' => [
                ['name' => 'Laptop HP Pavilion 15', 'desc' => 'Laptop HP Pavilion 15.6" Intel Core i5, 8GB RAM, 256GB SSD', 'cost' => 8500, 'retail' => 12999, 'wholesale' => 11500],
                ['name' => 'MacBook Air M2', 'desc' => 'Apple MacBook Air 13.6" Chip M2, 8GB RAM, 256GB SSD', 'cost' => 15000, 'retail' => 22999, 'wholesale' => 20500],
                ['name' => 'Lenovo IdeaPad 3', 'desc' => 'Lenovo IdeaPad 3 14" AMD Ryzen 5, 8GB RAM, 512GB SSD', 'cost' => 7500, 'retail' => 11499, 'wholesale' => 10000],
                ['name' => 'Dell Inspiron 14', 'desc' => 'Dell Inspiron 14" Intel Core i7, 16GB RAM, 512GB SSD', 'cost' => 11000, 'retail' => 16999, 'wholesale' => 15000],
                ['name' => 'ASUS VivoBook 15', 'desc' => 'ASUS VivoBook 15.6" Intel Core i3, 8GB RAM, 256GB SSD', 'cost' => 6000, 'retail' => 8999, 'wholesale' => 8000],
                ['name' => 'Laptop Gamer MSI GF63', 'desc' => 'MSI GF63 Thin 15.6" i5, GTX 1650, 8GB RAM, 512GB SSD', 'cost' => 12000, 'retail' => 17999, 'wholesale' => 16000],
                ['name' => 'HP EliteBook 840', 'desc' => 'HP EliteBook 840 G9 14" i7, 16GB RAM, 512GB SSD, empresarial', 'cost' => 14000, 'retail' => 21999, 'wholesale' => 19500],
                ['name' => 'Acer Aspire 5', 'desc' => 'Acer Aspire 5 15.6" AMD Ryzen 7, 16GB RAM, 512GB SSD', 'cost' => 9000, 'retail' => 13999, 'wholesale' => 12500],
            ],
        ],
        'Smartphones' => [
            'description' => 'Teléfonos celulares y smartphones de todas las marcas',
            'products' => [
                ['name' => 'iPhone 15', 'desc' => 'Apple iPhone 15 128GB, pantalla 6.1" Super Retina XDR', 'cost' => 14000, 'retail' => 19999, 'wholesale' => 18000],
                ['name' => 'Samsung Galaxy S24', 'desc' => 'Samsung Galaxy S24 256GB, pantalla 6.2" Dynamic AMOLED', 'cost' => 12000, 'retail' => 17999, 'wholesale' => 16000],
                ['name' => 'Xiaomi Redmi Note 13', 'desc' => 'Xiaomi Redmi Note 13 128GB, pantalla 6.67" AMOLED', 'cost' => 3000, 'retail' => 4999, 'wholesale' => 4200],
                ['name' => 'Motorola Edge 40', 'desc' => 'Motorola Edge 40 256GB, pantalla 6.55" pOLED 144Hz', 'cost' => 6500, 'retail' => 9999, 'wholesale' => 8800],
                ['name' => 'Samsung Galaxy A54', 'desc' => 'Samsung Galaxy A54 5G 128GB, pantalla 6.4" Super AMOLED', 'cost' => 5000, 'retail' => 7999, 'wholesale' => 7000],
                ['name' => 'iPhone SE 2022', 'desc' => 'Apple iPhone SE 3ra gen 64GB, chip A15 Bionic', 'cost' => 6500, 'retail' => 9499, 'wholesale' => 8500],
                ['name' => 'OPPO Reno 10', 'desc' => 'OPPO Reno 10 5G 256GB, pantalla 6.7" AMOLED', 'cost' => 5500, 'retail' => 8499, 'wholesale' => 7500],
            ],
        ],
        'Accesorios de Cómputo' => [
            'description' => 'Teclados, ratones, mousepads, bases y accesorios para computadora',
            'products' => [
                ['name' => 'Teclado Mecánico Redragon K552', 'desc' => 'Teclado mecánico RGB, switches red, TKL', 'cost' => 500, 'retail' => 899, 'wholesale' => 750],
                ['name' => 'Mouse Logitech G502', 'desc' => 'Mouse gaming Logitech G502 Hero 25,600 DPI, 11 botones', 'cost' => 600, 'retail' => 999, 'wholesale' => 850],
                ['name' => 'Mousepad XL HyperX Fury', 'desc' => 'Mousepad extendido 90x42cm, superficie de tela', 'cost' => 250, 'retail' => 499, 'wholesale' => 400],
                ['name' => 'Base Enfriadora para Laptop', 'desc' => 'Base enfriadora con 5 ventiladores LED, ajustable', 'cost' => 200, 'retail' => 399, 'wholesale' => 320],
                ['name' => 'Hub USB-C 7 en 1', 'desc' => 'Hub USB-C con HDMI 4K, USB 3.0, SD, ethernet', 'cost' => 350, 'retail' => 649, 'wholesale' => 550],
                ['name' => 'Webcam Logitech C920', 'desc' => 'Webcam Full HD 1080p, micrófono estéreo, autofocus', 'cost' => 800, 'retail' => 1299, 'wholesale' => 1100],
                ['name' => 'Teclado Inalámbrico Logitech K380', 'desc' => 'Teclado Bluetooth multidispositivo, compacto', 'cost' => 400, 'retail' => 699, 'wholesale' => 600],
                ['name' => 'Mouse Inalámbrico Logitech M185', 'desc' => 'Mouse inalámbrico 2.4GHz, compacto, pilas AA', 'cost' => 150, 'retail' => 299, 'wholesale' => 250],
                ['name' => 'Soporte Monitor Doble Brazo', 'desc' => 'Soporte articulado para 2 monitores 17-32"', 'cost' => 600, 'retail' => 1099, 'wholesale' => 900],
            ],
        ],
        'Componentes PC' => [
            'description' => 'Procesadores, memorias RAM, discos duros, tarjetas de video y más',
            'products' => [
                ['name' => 'SSD Kingston A400 480GB', 'desc' => 'Disco sólido SATA III 2.5", lectura 500MB/s', 'cost' => 400, 'retail' => 699, 'wholesale' => 580],
                ['name' => 'Memoria RAM DDR4 8GB', 'desc' => 'Kingston Fury Beast 8GB DDR4 3200MHz', 'cost' => 350, 'retail' => 599, 'wholesale' => 500],
                ['name' => 'SSD NVMe Samsung 970 EVO 1TB', 'desc' => 'SSD M.2 NVMe, lectura 3,500MB/s, escritura 3,300MB/s', 'cost' => 1200, 'retail' => 1899, 'wholesale' => 1650],
                ['name' => 'Fuente de Poder EVGA 600W', 'desc' => 'Fuente de poder 600W 80+ Bronze, no modular', 'cost' => 700, 'retail' => 1099, 'wholesale' => 950],
                ['name' => 'Tarjeta de Video GTX 1650', 'desc' => 'NVIDIA GeForce GTX 1650 4GB GDDR6', 'cost' => 3000, 'retail' => 4599, 'wholesale' => 4000],
                ['name' => 'Procesador AMD Ryzen 5 5600', 'desc' => 'AMD Ryzen 5 5600 6 núcleos, 12 hilos, 3.5GHz', 'cost' => 2000, 'retail' => 3199, 'wholesale' => 2800],
                ['name' => 'Disco Duro WD Blue 1TB', 'desc' => 'HDD 3.5" 7200RPM SATA III, ideal para almacenamiento', 'cost' => 500, 'retail' => 849, 'wholesale' => 720],
                ['name' => 'Memoria RAM DDR4 16GB Kit', 'desc' => 'Corsair Vengeance LPX 2x8GB DDR4 3200MHz', 'cost' => 650, 'retail' => 1099, 'wholesale' => 950],
            ],
        ],
        'Monitores y Pantallas' => [
            'description' => 'Monitores LED, IPS y gaming para trabajo y entretenimiento',
            'products' => [
                ['name' => 'Monitor Samsung 24" FHD', 'desc' => 'Monitor 24" Full HD IPS, 75Hz, HDMI/VGA', 'cost' => 2000, 'retail' => 3199, 'wholesale' => 2800],
                ['name' => 'Monitor LG 27" 4K UHD', 'desc' => 'Monitor 27" 4K UHD IPS, HDR10, USB-C', 'cost' => 5000, 'retail' => 7999, 'wholesale' => 7000],
                ['name' => 'Monitor Gamer ASUS 27" 165Hz', 'desc' => 'Monitor gaming 27" FHD IPS, 165Hz, 1ms, FreeSync', 'cost' => 3500, 'retail' => 5499, 'wholesale' => 4800],
                ['name' => 'Monitor HP 22" FHD', 'desc' => 'Monitor 22" Full HD IPS, antirreflejo, HDMI', 'cost' => 1500, 'retail' => 2499, 'wholesale' => 2200],
                ['name' => 'Monitor Curvo Samsung 32"', 'desc' => 'Monitor curvo 32" FHD VA, 75Hz, Eye Saver', 'cost' => 3800, 'retail' => 5999, 'wholesale' => 5200],
            ],
        ],
        'Impresoras y Escáneres' => [
            'description' => 'Impresoras de inyección, láser, multifuncionales y escáneres',
            'products' => [
                ['name' => 'Impresora HP DeskJet 2775', 'desc' => 'Multifuncional inyección de tinta, WiFi, imprime/copia/escanea', 'cost' => 800, 'retail' => 1399, 'wholesale' => 1200],
                ['name' => 'Impresora Láser Brother HL-1212W', 'desc' => 'Impresora láser monocromática WiFi, 21ppm', 'cost' => 1500, 'retail' => 2499, 'wholesale' => 2200],
                ['name' => 'Multifuncional Epson L3250', 'desc' => 'Multifuncional tanque de tinta, WiFi, ultra bajo costo', 'cost' => 2500, 'retail' => 3999, 'wholesale' => 3500],
                ['name' => 'Impresora Térmica de Tickets', 'desc' => 'Impresora POS térmica 80mm USB, corte automático', 'cost' => 800, 'retail' => 1499, 'wholesale' => 1200],
                ['name' => 'Escáner HP ScanJet Pro 2000', 'desc' => 'Escáner de documentos ADF, 35ppm, dúplex', 'cost' => 3000, 'retail' => 4999, 'wholesale' => 4300],
            ],
        ],
        'Redes y Conectividad' => [
            'description' => 'Routers, switches, access points, cables y adaptadores de red',
            'products' => [
                ['name' => 'Router TP-Link Archer C6', 'desc' => 'Router WiFi AC1200 Dual Band, 4 antenas, MU-MIMO', 'cost' => 500, 'retail' => 899, 'wholesale' => 750],
                ['name' => 'Switch TP-Link 8 Puertos', 'desc' => 'Switch de red 8 puertos Gigabit, plug & play', 'cost' => 300, 'retail' => 549, 'wholesale' => 450],
                ['name' => 'Access Point Ubiquiti UniFi AC Lite', 'desc' => 'Access point empresarial WiFi AC, PoE, montaje techo', 'cost' => 1200, 'retail' => 1999, 'wholesale' => 1700],
                ['name' => 'Cable UTP Cat6 Bobina 305m', 'desc' => 'Bobina cable de red Cat6 305m, interior, cobre', 'cost' => 1000, 'retail' => 1699, 'wholesale' => 1450],
                ['name' => 'Adaptador WiFi USB TP-Link', 'desc' => 'Adaptador WiFi USB AC600 Dual Band, tamaño nano', 'cost' => 150, 'retail' => 299, 'wholesale' => 250],
                ['name' => 'Router Mesh TP-Link Deco M5 (3pack)', 'desc' => 'Sistema mesh WiFi AC1300, cubre hasta 500m²', 'cost' => 2500, 'retail' => 3999, 'wholesale' => 3500],
            ],
        ],
        'Audio y Video' => [
            'description' => 'Audífonos, bocinas, micrófonos y accesorios de audio/video',
            'products' => [
                ['name' => 'Audífonos Sony WH-1000XM4', 'desc' => 'Audífonos over-ear Bluetooth, cancelación de ruido', 'cost' => 3500, 'retail' => 5499, 'wholesale' => 4800],
                ['name' => 'Bocina JBL Flip 6', 'desc' => 'Bocina portátil Bluetooth, resistente al agua IP67', 'cost' => 1200, 'retail' => 1999, 'wholesale' => 1700],
                ['name' => 'Micrófono Blue Yeti', 'desc' => 'Micrófono USB condensador, 4 patrones, streaming/podcast', 'cost' => 1500, 'retail' => 2499, 'wholesale' => 2100],
                ['name' => 'Audífonos Gamer HyperX Cloud II', 'desc' => 'Audífonos gaming 7.1 surround, micrófono desmontable', 'cost' => 800, 'retail' => 1399, 'wholesale' => 1200],
                ['name' => 'Bocina Bose SoundLink Flex', 'desc' => 'Bocina portátil Bluetooth, IP67, 12hrs batería', 'cost' => 1800, 'retail' => 2899, 'wholesale' => 2500],
                ['name' => 'AirPods Pro 2da Gen', 'desc' => 'Apple AirPods Pro con cancelación de ruido, USB-C', 'cost' => 3000, 'retail' => 4699, 'wholesale' => 4100],
            ],
        ],
    ];

    private $services = [
        ['name' => 'Formateo e Instalación de Windows', 'desc' => 'Formateo completo, instalación de Windows 10/11, drivers y programas básicos', 'price' => 500],
        ['name' => 'Limpieza Interna de Laptop', 'desc' => 'Limpieza de ventilador, disipador, cambio de pasta térmica', 'price' => 400],
        ['name' => 'Limpieza Interna de PC Escritorio', 'desc' => 'Limpieza completa de componentes, ventiladores y filtros', 'price' => 350],
        ['name' => 'Diagnóstico General', 'desc' => 'Revisión completa de hardware y software, reporte detallado', 'price' => 200],
        ['name' => 'Instalación de SSD + Clonación', 'desc' => 'Cambio de disco duro a SSD con clonación de sistema operativo', 'price' => 400],
        ['name' => 'Ampliación de Memoria RAM', 'desc' => 'Instalación de memoria RAM adicional (no incluye la RAM)', 'price' => 200],
        ['name' => 'Reparación de Pantalla de Laptop', 'desc' => 'Cambio de pantalla dañada (no incluye la pantalla)', 'price' => 600],
        ['name' => 'Configuración de Red WiFi', 'desc' => 'Instalación y configuración de router, access points y red WiFi', 'price' => 800],
        ['name' => 'Eliminación de Virus y Malware', 'desc' => 'Limpieza profunda de virus, malware, adware y optimización', 'price' => 350],
        ['name' => 'Instalación de Cámaras de Seguridad', 'desc' => 'Instalación y configuración de sistema CCTV (precio por cámara)', 'price' => 500],
        ['name' => 'Soporte Técnico Remoto (1hr)', 'desc' => 'Asistencia técnica remota por 1 hora, cualquier problema', 'price' => 250],
        ['name' => 'Ensamble de PC a la Medida', 'desc' => 'Ensamble completo de PC, instalación de SO, drivers y pruebas', 'price' => 800],
        ['name' => 'Recuperación de Datos', 'desc' => 'Recuperación de archivos de disco duro dañado o formateado', 'price' => 1200],
        ['name' => 'Mantenimiento Preventivo Empresarial', 'desc' => 'Mantenimiento preventivo por equipo, limpieza y optimización', 'price' => 300],
        ['name' => 'Cambio de Teclado de Laptop', 'desc' => 'Reemplazo de teclado dañado en laptop (no incluye teclado)', 'price' => 450],
    ];

    public function handle()
    {
        $shopId = (int) $this->argument('shop_id');
        $noImages = $this->option('no-images');

        $shop = Shop::find($shopId);
        if (!$shop) {
            $this->error("Shop con ID {$shopId} no existe.");
            return 1;
        }

        $this->info("Generando datos fake para: {$shop->name} (ID: {$shopId})");
        $this->info("========================================");

        // 1. Crear categorías y productos
        $totalProducts = 0;
        foreach ($this->categories as $catName => $catData) {
            $category = Category::create([
                'shop_id' => $shopId,
                'active' => 1,
                'name' => $catName,
                'description' => $catData['description'],
                'image' => null,
            ]);
            $this->info("Categoría creada: {$catName} (ID: {$category->id})");

            foreach ($catData['products'] as $index => $prod) {
                $product = Product::create([
                    'shop_id' => $shopId,
                    'category_id' => $category->id,
                    'active' => 1,
                    'key' => 'FAKE-' . strtoupper(Str::random(6)),
                    'barcode' => '7501' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT),
                    'name' => $prod['name'],
                    'description' => $prod['desc'],
                    'cost' => $prod['cost'],
                    'retail' => $prod['retail'],
                    'wholesale' => $prod['wholesale'],
                    'wholesale_premium' => round($prod['wholesale'] * 0.95, 2),
                    'stock' => rand(5, 50),
                    'reserve' => 0,
                    'image' => null,
                ]);

                // Descargar imagen si no se desactivó
                if (!$noImages) {
                    $this->downloadProductImage($product);
                }

                $totalProducts++;
            }
            $this->info("  → {$this->countProducts($catData)} productos creados");
        }

        // 2. Crear servicios
        $totalServices = 0;
        foreach ($this->services as $svc) {
            Service::create([
                'shop_id' => $shopId,
                'active' => 1,
                'name' => $svc['name'],
                'description' => $svc['desc'],
                'price' => $svc['price'],
            ]);
            $totalServices++;
        }
        $this->info("----------------------------------------");
        $this->info("Servicios creados: {$totalServices}");

        // Resumen
        $this->info("========================================");
        $this->info("RESUMEN:");
        $this->info("  Categorías: " . count($this->categories));
        $this->info("  Productos:  {$totalProducts}");
        $this->info("  Servicios:  {$totalServices}");
        $this->info("  Imágenes:   " . ($noImages ? 'NO (--no-images)' : 'SÍ'));
        $this->info("Listo!");

        return 0;
    }

    private function countProducts(array $catData): int
    {
        return count($catData['products']);
    }

    private function downloadProductImage(Product $product): void
    {
        try {
            // Usar picsum.photos para imágenes aleatorias de buena calidad
            $imageUrl = 'https://picsum.photos/640/480';
            $imageContent = @file_get_contents($imageUrl);

            if ($imageContent === false) {
                $this->warn("  No se pudo descargar imagen para: {$product->name}");
                return;
            }

            $filename = 'products/' . $product->id . '_' . Str::random(8) . '.jpg';
            Storage::disk('public')->put($filename, $imageContent);

            $product->image = $filename;
            $product->save();

            // También crear en product_images
            ProductImage::create([
                'product_id' => $product->id,
                'image' => $filename,
                'main' => 1,
                'order' => 0,
            ]);

        } catch (\Exception $e) {
            $this->warn("  Error imagen para {$product->name}: " . $e->getMessage());
        }
    }
}
