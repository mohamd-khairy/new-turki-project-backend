<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPointLog extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'last_amount', 'new_amount', 'action'];
}
