<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOtpLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'mobile_country_code',
        'mobile',
        'mobile_verification_code',
        'no_attempts',
        'disabled',
        'disabled_at'
    ];
}
