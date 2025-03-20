<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderState extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_en',
        'state_ar',
        'customer_state_en',
        'customer_state_ar',
        'code',
        'is_active',
        'odoo_status'
    ];
   protected $primaryKey = 'code';

    public function orderproduct(){
        return $this->hasMany(OrderProduct::class);
    }

    public function order(){
        return $this->belongsTo(Order::class);
    }


}
