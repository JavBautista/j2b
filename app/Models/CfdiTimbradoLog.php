<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiTimbradoLog extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
        'attempts' => 'array',
        'metadata' => 'array',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function cfdiInvoice()
    {
        return $this->belongsTo(CfdiInvoice::class);
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRequestPayloadDecodedAttribute(): ?array
    {
        return $this->decodePayload($this->request_payload);
    }

    public function getResponsePayloadDecodedAttribute(): ?array
    {
        return $this->decodePayload($this->response_payload);
    }

    private function decodePayload(?string $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'gzip:')) {
            $binary = base64_decode(substr($raw, 5), true);
            if ($binary === false) {
                return null;
            }
            $raw = gzdecode($binary);
            if ($raw === false) {
                return null;
            }
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
