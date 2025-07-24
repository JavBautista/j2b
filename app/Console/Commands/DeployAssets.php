<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeployAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:assets {--force : Forzar limpieza completa de assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimizar y desplegar assets para producción con cache busting';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Iniciando deployment de assets...');
        $this->newLine();

        // Paso 1: Limpiar cache si se solicita
        if ($this->option('force')) {
            $this->info('🧹 Limpiando assets existentes...');
            $this->cleanAssets();
        }

        // Paso 2: Limpiar cache de Laravel
        $this->info('🗑️  Limpiando cache de Laravel...');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('route:clear');

        // Paso 3: Compilar assets para producción
        $this->info('📦 Compilando assets para producción...');
        $exitCode = $this->executeCommand('npm run production');
        
        if ($exitCode !== 0) {
            $this->error('❌ Error al compilar assets');
            return 1;
        }

        // Paso 4: Verificar mix-manifest
        $this->info('🔍 Verificando mix-manifest...');
        $this->call('mix:check');

        // Paso 5: Generar cache optimizado
        $this->info('⚡ Generando cache optimizado...');
        $this->call('config:cache');
        // $this->call('route:cache'); // Comentado por conflicto de rutas duplicadas
        $this->call('view:cache');

        // Paso 6: Reporte final
        $this->newLine();
        $this->info('✅ Deployment completado exitosamente');
        $this->displayAssetInfo();

        return 0;
    }

    /**
     * Clean existing assets
     */
    private function cleanAssets()
    {
        $directories = [
            public_path('css'),
            public_path('js'),
        ];

        foreach ($directories as $dir) {
            if (File::isDirectory($dir)) {
                File::cleanDirectory($dir);
                $this->line("   ✅ Limpiado: {$dir}");
            }
        }

        $manifestPath = public_path('mix-manifest.json');
        if (File::exists($manifestPath)) {
            File::delete($manifestPath);
            $this->line("   ✅ Eliminado: mix-manifest.json");
        }
    }

    /**
     * Execute shell command
     */
    private function executeCommand($command)
    {
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            
            while ($line = fgets($pipes[1])) {
                $this->line('   ' . trim($line));
            }
            
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            return proc_close($process);
        }

        return 1;
    }

    /**
     * Display asset information
     */
    private function displayAssetInfo()
    {
        $manifestPath = public_path('mix-manifest.json');
        
        if (!File::exists($manifestPath)) {
            $this->error('❌ mix-manifest.json no encontrado');
            return;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        
        $this->info('📊 Assets generados:');
        
        $webAssets = [
            '/css/web.css' => 'CSS Web',
            '/js/web.js' => 'JS Web',
            '/css/dashboard.css' => 'CSS Dashboard',
            '/js/dashboard.js' => 'JS Dashboard'
        ];

        foreach ($webAssets as $asset => $description) {
            if (isset($manifest[$asset])) {
                $path = public_path($asset);
                $size = File::exists($path) ? $this->formatBytes(File::size($path)) : 'N/A';
                $url = $manifest[$asset];
                
                $this->line("   📄 {$description}: {$url} ({$size})");
            }
        }

        $this->newLine();
        $this->info('💡 Recomendaciones:');
        $this->line('   • Los assets tienen cache busting automático');
        $this->line('   • Configura headers de cache largo en tu servidor web');
        $this->line('   • Los archivos se actualizarán automáticamente en cada deploy');
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