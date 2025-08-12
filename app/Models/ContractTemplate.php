<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    protected $fillable = [
        'shop_id','name', 'html_content', 'css_styles', 'variables', 'is_active'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean'
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function replaceVariables($data)
    {
        $html = $this->html_content;
        
        foreach ($data as $key => $value) {
            $html = str_replace("{{" . $key . "}}", $value, $html);
        }
        
        return $html;
    }

    public function shop(){
        return $this->belongsTo(Shop::class);
    }
}
