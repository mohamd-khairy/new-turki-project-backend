<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NgeniusPayment extends Model
{
    
  use HasFactory;

  protected $fillable = [
        'order_id',
        'paymentId',
        'eventId',
        'eventName',
        'ref',
        'paymentTimestamp',
        'amount',
        'currencyCode',
        'result',
        'fcCustId',
        'error',
        'errorText',
        'payload',
        'outletId'
      
    ];

}
