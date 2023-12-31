<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryDate extends Model
{
    use HasFactory;

    protected $fillable = ['date', 'product_id'];
    protected $hidden = ['pivot', 'product_id'];

    public function periods()
    {
        return $this->belongsToMany(DeliveryPeriod::class, 'delivery_date_cities', 'delivery_date_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
