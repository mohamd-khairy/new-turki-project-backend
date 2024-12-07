<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en', 'price', 'sale_price', 'is_active', 'weight', 'calories', 'use_again',
        'foodics_integrate_id'
    ];

    protected $hidden = ['created_at', 'updated_at', 'pivot'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function size_store()
    {
        return $this->hasMany(SizeStore::class);
    }

    public function stores()
    {
        return $this->hasMany(SizeStore::class)->with(['store', 'product']);
    }
}
