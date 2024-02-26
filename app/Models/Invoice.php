<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'supplier_id', 'city_id',  'invoice', 'invoice_price', 'tax', 'paid', 'notes', 'date'];

    protected $hidden = ['created_at', 'updated_at'];

    public function getInvoiceAttribute()
    {
        return $this->attributes['invoice'] ? config('app.url') . Storage::url($this->attributes['invoice']) : null;
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class)->with('store');
    }
}
