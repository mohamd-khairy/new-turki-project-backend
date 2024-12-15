<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'quantity',
        'old_quantity',
        'new_quantity',
        'order_ref_no',
        'order_product_id',
        'action',
        'customer_id',
        'user_id',
        'size_id'
    ];

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class)->with('product');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_ref_no', 'ref_no');
    }
}
