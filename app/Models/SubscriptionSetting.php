<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'label', 'description'];

    /**
     * Helper estático para obtener valor por key
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        // Cast según tipo
        switch ($setting->type) {
            case 'integer':
                return (int) $setting->value;
            case 'boolean':
                return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
            case 'decimal':
                return (float) $setting->value;
            default:
                return $setting->value;
        }
    }

    /**
     * Helper para actualizar valor
     */
    public static function set(string $key, $value)
    {
        return self::where('key', $key)->update(['value' => $value]);
    }
}
