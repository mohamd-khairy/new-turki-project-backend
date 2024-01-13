<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'user_id', 'supplier_id', 'store_id', 'product_id',
        'invoice', 'invoice_price', 'product_name', 'quantity', 'price', 'tax', 'paid', 'notes'
    ];

    public function getInvoiceAttribute()
    {
        return $this->attributes['invoice']? config('app.url') . Storage::url($this->attributes['invoice']) : null;
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
