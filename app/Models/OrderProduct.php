<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_ref_no',
        'total_price',
        'quantity',
        'product_id',
        'preparation_id',
        'size_id',
        'cut_id',
        'shalwata_id',
        'is_kwar3',
        'is_Ras',
        'is_lyh',
        'is_karashah'
    ];

    protected $hidden = [ 'preparation_id', 'size_id', 'cut_id'];

    protected $casts = [
        'is_karashah' => 'boolean',
        'is_kwar3' => 'boolean',
        'is_Ras' => 'boolean',
        'is_lyh' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_ref_no');
    }

    public function product()
    {

        return $this->belongsTo(Product::class, 'product_id');
    }

    public function preparation()
    {
        return $this->belongsTo(Preparation::class, 'preparation_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    public function cut()
    {
        return $this->belongsTo(Cut::class, 'cut_id');
    }

    public function shalwata()
    {
        return $this->belongsTo(Shalwata::class, 'shalwata_id');
    }
}
