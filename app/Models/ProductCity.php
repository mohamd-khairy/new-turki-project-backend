<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCity extends Model
{
    use HasFactory;

    protected $fillable = ['product_id','city_id'];

    public function product()
    {
        return $this->belongsTo(Product::class , 'product_id');
    }

}
