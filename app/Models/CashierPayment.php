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

    public function payment_type()
    {
        return $this->belongsTo(PaymentType::class, 'payment_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_ref_no', 'ref_no');
    }
}
