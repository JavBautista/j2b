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
}
