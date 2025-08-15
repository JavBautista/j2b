<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function extraFields()
    {
        return $this->hasMany(ExtraFieldShop::class);
    }

    public function contractTemplates()
    {
        return $this->hasMany(ContractTemplate::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Accessor para obtener la URL pública de la firma del representante legal
     */
    public function getLegalRepresentativeSignatureUrlAttribute()
    {
        if ($this->legal_representative_signature_path) {
            return asset('storage/' . $this->legal_representative_signature_path);
        }
        return null;
    }
}
