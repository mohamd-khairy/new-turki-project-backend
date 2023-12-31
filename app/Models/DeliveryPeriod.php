<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryPeriod extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar', 'name_en', 'time_hhmm', 'is_active', 'from', 'to'];
    protected $hidden = ['pivot'];
}
