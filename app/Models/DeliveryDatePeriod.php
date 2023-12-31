<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryDatePeriod extends Model
{
    use HasFactory;

    protected $fillable = ['delivery_date_id','delivery_period_id','is_active'];
     protected $hidden = ['pivot'];
}
