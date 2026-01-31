<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    protected $fillable = [
        'type',
        'title',
        'content',
        'version',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTerms($query)
    {
        return $query->where('type', 'terms');
    }

    public function scopePrivacy($query)
    {
        return $query->where('type', 'privacy');
    }

    // Métodos estáticos para obtener documentos activos
    public static function getActiveTerms()
    {
        return self::terms()->active()->first();
    }

    public static function getActivePrivacy()
    {
        return self::privacy()->active()->first();
    }
}
