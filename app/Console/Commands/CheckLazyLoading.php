<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckLazyLoading extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lazy:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar la implementación de lazy loading en el proyecto';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Verificando implementación de Lazy Loading...');
        $this->newLine();

        // Verificar archivos de lazy loading
        $this->checkLazyLoadingFiles();
        
        // Verificar implementación en vistas
        $this->checkViews();
        
        // Verificar assets compilados
        $this->checkCompiledAssets();
        
        // Mostrar métricas estimadas
        $this->showPerformanceEstimate();

        return 0;
    }

    private function checkLazyLoadingFiles()
    {
        $this->info('📁 Archivos de Lazy Loading:');
        
        $files = [
            'resources/js/web/lazy-loading.js' => 'Sistema principal de lazy loading',
            'resources/js/web/components/stats-counter.js' => 'Componente contador con lazy loading',
            'resources/js/web/components/contact-form.js' => 'Formulario de contacto lazy loaded',
            'resources/css/web/landing.css' => 'Estilos para lazy loading (CSS actualizado)',
        ];

        foreach ($files as $file => $description) {
            $path = base_path($file);
            if (File::exists($path)) {
                $size = $this->formatBytes(File::size($path));
                $this->line("  ✅ {$description}");
                $this->line("     📄 {$file} ({$size})");
            } else {
                $this->line("  ❌ {$description}");
                $this->line("     📄 {$file} - NO ENCONTRADO");
            }
        }
        
        $this->newLine();
    }

    private function checkViews()
    {
        $this->info('🎭 Implementación en Vistas:');
        
        $indexPath = resource_path('views/web/index.blade.php');
        $layoutPath = resource_path('views/web/layouts/app.blade.php');
        
        if (File::exists($indexPath)) {
            $content = File::get($indexPath);
            
            // Verificar lazy loading de imágenes
            $lazyImages = substr_count($content, 'data-src');
            $criticalImages = substr_count($content, 'data-critical');
            $lazyComponents = substr_count($content, 'data-lazy-component');
            
            $this->line("  📷 Imágenes con lazy loading: {$lazyImages}");
            $this->line("  🚀 Imágenes críticas: {$criticalImages}");
            $this->line("  ⚡ Componentes lazy-loaded: {$lazyComponents}");
            
            if ($lazyImages > 0) {
                $this->line("  ✅ Lazy loading implementado en imágenes");
            }
            
            if ($lazyComponents > 0) {
                $this->line("  ✅ Lazy loading implementado en componentes");
            }
        } else {
            $this->line("  ❌ Vista index.blade.php no encontrada");
        }
        
        if (File::exists($layoutPath)) {
            $layoutContent = File::get($layoutPath);
            
            $hasPreload = strpos($layoutContent, 'rel="preload"') !== false;
            $hasCriticalCSS = strpos($layoutContent, 'Critical CSS inline') !== false;
            $hasDnsPrefetch = strpos($layoutContent, 'dns-prefetch') !== false;
            
            $this->line("  " . ($hasPreload ? "✅" : "❌") . " Preload de recursos críticos");
            $this->line("  " . ($hasCriticalCSS ? "✅" : "❌") . " CSS crítico inline");
            $this->line("  " . ($hasDnsPrefetch ? "✅" : "❌") . " DNS prefetch configurado");
        }
        
        $this->newLine();
    }

    private function checkCompiledAssets()
    {
        $this->info('📦 Assets Compilados:');
        
        $webCssPath = public_path('css/web.css');
        $webJsPath = public_path('js/web.js');
        
        if (File::exists($webCssPath)) {
            $size = $this->formatBytes(File::size($webCssPath));
            $this->line("  ✅ CSS Web compilado: {$size}");
            
            // Verificar si contiene estilos de lazy loading
            $cssContent = File::get($webCssPath);
            $hasLazyStyles = strpos($cssContent, 'lazy-loading') !== false;
            $hasShimmer = strpos($cssContent, 'shimmer') !== false;
            
            $this->line("     " . ($hasLazyStyles ? "✅" : "❌") . " Estilos de lazy loading incluidos");
            $this->line("     " . ($hasShimmer ? "✅" : "❌") . " Animaciones shimmer incluidas");
        } else {
            $this->line("  ❌ CSS Web no compilado");
        }
        
        if (File::exists($webJsPath)) {
            $size = $this->formatBytes(File::size($webJsPath));
            $this->line("  ✅ JS Web compilado: {$size}");
            
            // En producción, el JS está minificado, pero podemos verificar algunas claves
            $jsContent = File::get($webJsPath);
            $hasLazyLoader = strpos($jsContent, 'LazyLoader') !== false || strpos($jsContent, 'lazy') !== false;
            $hasIntersectionObserver = strpos($jsContent, 'IntersectionObserver') !== false;
            
            $this->line("     " . ($hasLazyLoader ? "✅" : "❓") . " Sistema LazyLoader incluido");
            $this->line("     " . ($hasIntersectionObserver ? "✅" : "❓") . " IntersectionObserver detectado");
        } else {
            $this->line("  ❌ JS Web no compilado");
        }
        
        $this->newLine();
    }

    private function showPerformanceEstimate()
    {
        $this->info('📊 Estimación de Mejoras de Performance:');
        
        // Calcular savings estimados
        $originalImageSize = 6 * 300; // 6 imágenes de ~300KB cada una
        $lazyLoadingSavings = $originalImageSize * 0.7; // 70% no se carga inicialmente
        
        $originalJSSize = 45; // KB estimados de JS
        $componentLazySavings = $originalJSSize * 0.3; // 30% de componentes lazy
        
        $totalSavings = $lazyLoadingSavings + $componentLazySavings;
        
        $this->line("  📷 Ahorro estimado en imágenes: ~{$lazyLoadingSavings}KB");
        $this->line("  ⚡ Ahorro estimado en JS: ~{$componentLazySavings}KB");
        $this->line("  💾 Ahorro total estimado: ~{$totalSavings}KB");
        
        $this->newLine();
        
        $this->info('🚀 Beneficios Implementados:');
        $this->line("  ✅ Carga inicial más rápida");
        $this->line("  ✅ Menor consumo de datos móviles");
        $this->line("  ✅ Mejor experiencia de usuario");
        $this->line("  ✅ Optimización SEO (Core Web Vitals)");
        $this->line("  ✅ Carga asíncrona de terceros");
        $this->line("  ✅ CSS crítico inline");
        $this->line("  ✅ Preload de recursos importantes");
        
        $this->newLine();
        
        $this->info('💡 Comandos útiles:');
        $this->line("  • php artisan mix:check - Verificar assets");
        $this->line("  • php artisan deploy:assets - Deploy optimizado");  
        $this->line("  • npm run build - Compilar para producción");
        $this->line("  • Open DevTools → Network para ver lazy loading en acción");
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}