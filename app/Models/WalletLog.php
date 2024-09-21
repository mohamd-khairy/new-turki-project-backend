<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletLog extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'last_amount', 'new_amount', 'action', 'user_id', 'action_id'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'action_id', 'ref_no');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
