<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierPayment extends Model
{
    use HasFactory;

    protected $table = 'cashier_payments';

    protected $fillable = [
        'order_ref_no',
        'payment_id',
        'payment_value',
    ];
}
