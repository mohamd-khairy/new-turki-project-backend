<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomNotification extends Model
{
    use HasFactory;

    protected $table = 'custom_notifications';

    protected $fillable = [
        'title',
        'body',
        'image',
        'scheduled_at',
        'sent_at',
        'is_for_all',
        'is_by_city',
        'is_by_country',
        'is_by_category',
        'is_by_subcategory',
        'is_by_product',
        'is_by_size',
        'for_clients_only',
        'product_ids',
        'size_ids',
        'category_parent_ids',
        'category_child_ids',
        'city_ids',
        'country_ids',
        'client_ids',
        'is_active',
    ];

    public $casts = [
        'product_ids' => 'array',
        'size_ids' => 'array',
        'category_parent_ids' => 'array',
        'category_child_ids' => 'array',
        'city_ids' => 'array',
        'country_ids' => 'array',
        'client_ids' => 'array',
        'is_for_all' => 'boolean',
        'is_by_city' => 'boolean',
        'is_by_country' => 'boolean',
        'is_by_category' => 'boolean',
        'is_by_subcategory' => 'boolean',
        'is_by_product' => 'boolean',
        'is_by_size' => 'boolean',
        'for_clients_only' => 'boolean',
    ];
}
