<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaticNotification extends Model
{
    use HasFactory;

    protected $table = 'static_notifications';

    protected $fillable = [
        'title',
        'body',
        'data',
        'config',
        'is_active',
        'type'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
