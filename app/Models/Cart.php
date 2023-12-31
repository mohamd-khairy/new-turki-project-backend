<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        "comment",
        'applied_discount_code',
        'product_id',
        'quantity',
        'preparation_id',
        'size_id',
        'cut_id',
        'is_shalwata',
        'is_karashah',
        'is_kwar3',
        'is_Ras',
        'is_lyh',
        'shalwata_id',
        'city_id'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:m',
        'updated_at' => 'datetime:Y-m-d H:m',
        'is_karashah' => 'boolean',
        'is_kwar3' => 'boolean',
        'is_Ras' => 'boolean',
        'is_lyh'=> 'boolean',
    ];

   protected $hidden = ['created_at', 'updated_at','pivot', 'city_id'];


    public function customer(){
        return $this->belongsTo(Customer::class)->with('addresses');
    }

    public function address(){
        return $this->belongsTo(Address::class);
    }

    public function coupon(){
        return $this->belongsTo(Discount::class, 'applied_discount_code');
    }

    public function preparation(){
        return $this->belongsTo(Preparation::class);
    }

    public function size(){
        return $this->belongsTo(Size::class);
    }

    public function cut(){
        return $this->belongsTo(Cut::class);
    }

    public function shalwata(){
        return $this->belongsTo(Shalwata::class);
    }

    public function city(){
        return $this->belongsTo(City::class)->with("notDeliveryDateCity");
    }

    public function product(){
        return $this->belongsTo(Product::class)->with('productImages', 'shalwata','productPaymentTypes', 'notDeliveryDate');
    }

    public function scopeCartDetails($query){
        return $query
//            ->with('product.notDeliveryDate')
            ->with('preparation')
            ->with('size')
            ->with('cut')
            ->with('shalwata');
    }
}
