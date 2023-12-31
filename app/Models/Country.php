<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Country extends Model
{
    public $inPermission = true;

    use HasFactory, LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    protected $fillable = [
        'name_ar',
        'name_en',
        'currency_ar',
        'currency_en',
        'phone_code',
        'latitude',
        'longitude',
        'code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function city()
    {
        return $this->hasMany(City::class, 'city_id');
    }
}
