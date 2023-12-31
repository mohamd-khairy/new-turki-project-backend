<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArbPayment extends Model
{
    protected $table = 'arb_payments';
    
      public $fillable = [
        'order_id',
        'paymentId',
        'transId',
        'ref',
        'paymentTimestamp',
        'trackId',
        'authRespCode',
        'authCode',
        'amt',
        'date',
        'cardType',
        'result',
        'fcCustId',
        'error',
        'errorText',
      
    ];

}
