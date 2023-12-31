<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDeliveryOtpLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'mobile_country_code',
        'mobile',
        'mobile_verification_code',
        'no_attempts',
        'disabled',
        'order_id',
        'user_id',
        'disabled_at',
    ];
}
