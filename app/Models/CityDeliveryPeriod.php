<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityDeliveryPeriod extends Model
{
    use HasFactory;

    protected $fillable = ['delivery_period_id', 'city_id'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function delivery_period()
    {
        return $this->belongsTo(DeliveryPeriod::class);
    }
}
