<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    protected ImageManager $manager;

    // Configuración de compresión (estilo WhatsApp)
    protected int $maxWidth = 1280;
    protected int $maxHeight = 1280;
    protected int $quality = 75;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Procesa y guarda una imagen optimizada
     *
     * @param UploadedFile $file Archivo subido
     * @param string $folder Carpeta destino (ej: 'tasks', 'products')
     * @return string Ruta relativa del archivo guardado
     */
    public function processAndStore(UploadedFile $file, string $folder): string
    {
        // Leer la imagen
        $image = $this->manager->read($file->getRealPath());

        // Redimensionar si excede el tamaño máximo (mantiene proporción)
        $image->scaleDown(width: $this->maxWidth, height: $this->maxHeight);

        // Generar nombre único
        $filename = $this->generateFilename($file);
        $relativePath = "{$folder}/{$filename}";
        $fullPath = storage_path("app/public/{$relativePath}");

        // Asegurar que el directorio existe
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Guardar como JPEG con compresión
        $image->toJpeg(quality: $this->quality)->save($fullPath);

        return $relativePath;
    }

    /**
     * Genera un nombre único para el archivo
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $timestamp = now()->format('YmdHis');
        $random = substr(md5(uniqid()), 0, 8);
        return "{$timestamp}_{$random}.jpg";
    }

    /**
     * Permite configurar parámetros de compresión personalizados
     */
    public function setConfig(int $maxWidth = null, int $maxHeight = null, int $quality = null): self
    {
        if ($maxWidth) $this->maxWidth = $maxWidth;
        if ($maxHeight) $this->maxHeight = $maxHeight;
        if ($quality) $this->quality = $quality;
        return $this;
    }
}
