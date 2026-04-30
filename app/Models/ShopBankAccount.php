<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopBankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'alias',
        'bank_code',
        'bank_name',
        'bank_rfc',
        'clabe',
        'account_number',
        'holder_name',
        'is_default',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function partialPayments()
    {
        return $this->hasMany(PartialPayments::class, 'shop_bank_account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefaultFor(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId)->where('is_default', true);
    }

    /**
     * Marca esta cuenta como default y desmarca las demás del shop en una sola transacción.
     */
    public function setAsDefault(): void
    {
        \DB::transaction(function () {
            self::where('shop_id', $this->shop_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);
            $this->update(['is_default' => true]);
        });
    }
}
