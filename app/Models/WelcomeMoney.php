<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WelcomeMoney extends Model
{
    use HasFactory;

    protected $fillable = [
        'welcome_amount',
        'welcome_start_date',
        'welcome_end_date',
        'country_id',
        'is_active',
        'expired_days',
        'expired_at'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
