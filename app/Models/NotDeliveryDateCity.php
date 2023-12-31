<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotDeliveryDateCity extends Model
{
    use HasFactory;

    public $inPermission = true;

    protected $fillable = ['city_id', 'delivery_date', 'delivery_period_id'];
    protected $hidden = ['pivot'];
}
