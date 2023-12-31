<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempCouponProducts extends Model
{
    use HasFactory;

    protected $fillable = ['order_id','coupon_code', 'product_ids'];
}
