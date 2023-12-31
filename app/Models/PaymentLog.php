<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = ['payment_name','payment_ref', 'order_ref', '	descraption', 'payment_status', 'created_at', 'updated_at'];
    
}
