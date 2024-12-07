<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'size_id',
        'product_id',
        'store_id',
        'quantity',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
