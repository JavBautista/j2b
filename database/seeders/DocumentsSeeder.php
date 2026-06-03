<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;

class DocumentsSeeder extends Seeder
{
    /**
     * Carga inicial del contrato SaaS Copigama desde el .md de xdev (si existe).
     * Idempotente: no duplica si ya hay un documento con el mismo título,
     * y no falla si el archivo no está presente (p.ej. en producción).
     */
    public function run(): void
    {
        $path = base_path('xdev/legal-comercial/CONTRATO_SaaS_Copigama.md');
        $title = 'Contrato de Prestación de Servicios y Licencia SaaS — Copigama';

        if (!file_exists($path)) {
            $this->command->warn("DocumentsSeeder: no se encontró {$path}, se omite.");
            return;
        }

        if (Document::where('title', $title)->exists()) {
            $this->command->info('DocumentsSeeder: el contrato Copigama ya existe, se omite.');
            return;
        }

        Document::create([
            'title'     => $title,
            'category'  => 'contrato',
            'content'   => file_get_contents($path),
            'version'   => '1.0',
            'notes'     => 'Carga inicial desde xdev/legal-comercial/CONTRATO_SaaS_Copigama.md',
            'is_active' => true,
        ]);

        $this->command->info('DocumentsSeeder: contrato Copigama importado.');
    }
}
