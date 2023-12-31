<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'color', 'created_at', 'updated_at'];

    protected $hidden = ['created_at', 'updated_at','pivot'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tags');
    }
}