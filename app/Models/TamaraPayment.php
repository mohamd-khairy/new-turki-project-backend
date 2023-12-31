<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TamaraPayment extends Model
{
      public $fillable = [
        'order_ref_no',
        'tamara_order_id',
        'payment_type', 'instalments',
        'status',
    ];

}
