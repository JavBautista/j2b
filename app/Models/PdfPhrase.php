<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfPhrase extends Model
{
    use HasFactory;

    protected $fillable = ['phrase', 'link_url', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Obtener una frase aleatoria de las activas.
     * Retorna ['phrase' => ..., 'link_url' => ...] o defaults.
     */
    public static function getRandom(): array
    {
        $phrase = self::where('is_active', true)->inRandomOrder()->first();

        return [
            'phrase' => $phrase ? $phrase->phrase : 'Tu negocio, simplificado.',
            'link_url' => $phrase ? $phrase->link_url : 'https://j2biznes.com',
        ];
    }
}
