<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $guarded=[];
    
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'location_image',
        'location_latitude',
        'location_longitude',
        'active',
        'shop_id',
        'level',
        'user_id',
        'origin'
    ];

    public function rents(){
        return $this->hasMany(Rent::class);
    }

    public function shop(){
        return $this->belongsTo(Shop::class);
    }

    public function contracts(){
        return $this->hasMany(Contract::class);
    }

    public function addresses(){
        return $this->hasMany(ClientAddress::class);
    }

    public function activeAddresses(){
        return $this->hasMany(ClientAddress::class)->where('active', true);
    }

    public function primaryAddress(){
        return $this->hasOne(ClientAddress::class)->where('is_primary', true);
    }

    public function fiscalData(){
        return $this->hasMany(ClientFiscalData::class);
    }

    public function defaultFiscalData(){
        return $this->hasOne(ClientFiscalData::class)->where('is_default', true);
    }
}
