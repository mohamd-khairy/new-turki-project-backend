<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar','name_en', 'price', 'sale_price','is_active','weight','calories' , 'use_again'];
    
    protected $hidden = ['created_at', 'updated_at','pivot'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
