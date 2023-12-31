<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id' , 'product_id'];

    public function customers()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(){
        
        return $this->belongsTo(Product::class)->with('productImages');
    }
}
