<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashback extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_back_amount',
        'cash_back_start_date',
        'cash_back_end_date',
        'country_id',
        'category_ids',
        'sub_category_ids',
        'customer_ids',
        'product_ids',
        'city_ids',
        'is_active',
        'expired_days',
        'expired_at'
    ];

    protected $casts = [
        'category_ids' => 'array',
        'sub_category_ids' => 'array',
        'customer_ids' => 'array',
        'product_ids' => 'array',
        'city_ids' => 'array',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
