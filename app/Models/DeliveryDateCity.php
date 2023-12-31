<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryDateCity extends Model
{
    use HasFactory;

    protected $fillable = ['city_id', 'delivery_date_id', 'delivery_period_id'];
    protected $hidden = ['pivot'];
}
