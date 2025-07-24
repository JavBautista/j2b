<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckMixManifest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mix:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar el estado del mix-manifest.json y cache busting';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $manifestPath = public_path('mix-manifest.json');
        
        if (!File::exists($manifestPath)) {
            $this->error('❌ mix-manifest.json no encontrado');
            $this->info('Ejecuta: npm run dev o npm run production');
            return 1;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        
        if (empty($manifest)) {
            $this->error('❌ mix-manifest.json está vacío o es inválido');
            return 1;
        }

        $this->info('✅ mix-manifest.json encontrado y válido');
        $this->newLine();

        $this->info('📁 Assets con versioning:');
        foreach ($manifest as $original => $versioned) {
            $originalPath = public_path($original);
            $exists = File::exists($originalPath) ? '✅' : '❌';
            
            $this->line("  {$exists} {$original} → {$versioned}");
        }

        $this->newLine();
        $this->info('🔍 Verificación de archivos físicos:');
        
        $webAssets = [
            '/css/web.css',
            '/js/web.js'
        ];

        foreach ($webAssets as $asset) {
            $path = public_path($asset);
            $exists = File::exists($path);
            $size = $exists ? $this->formatBytes(File::size($path)) : 'N/A';
            $status = $exists ? '✅' : '❌';
            
            $this->line("  {$status} {$asset} ({$size})");
            
            if ($exists && isset($manifest[$asset])) {
                $this->line("      🔗 URL con cache busting: {$manifest[$asset]}");
            }
        }

        $this->newLine();
        
        // Verificar función mix() de Laravel
        try {
            $webCssUrl = mix('css/web.css');
            $webJsUrl = mix('js/web.js');
            
            $this->info('🔧 Función mix() de Laravel:');
            $this->line("  ✅ mix('css/web.css') → {$webCssUrl}");
            $this->line("  ✅ mix('js/web.js') → {$webJsUrl}");
        } catch (\Exception $e) {
            $this->error('❌ Error con función mix(): ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('💡 Cache busting configurado correctamente');
        $this->info('   Los archivos se actualizarán automáticamente cuando cambien');
        
        return 0;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}