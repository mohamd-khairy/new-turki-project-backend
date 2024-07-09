<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class City extends Model
{
    use HasFactory, LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    public $inPermission = true;

    protected $table = 'cities';

    protected $fillable = [
        'country_id',
        'name_ar',
        'name_en',
        'is_active',
        'integrate_id',
        'is_available_for_delivery',
        'polygon',
        'allow_cash',
        'min_price'
    ];

    protected $appends = ['polygons'];

    protected $hidden = ['created_at', 'updated_at', 'pivot'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available_for_delivery' => 'boolean',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_cities');
    }

    public function notDeliveryDateCity()
    {
        return $this->belongsTo(NotDeliveryDateCity::class);
    }

    public function getPolygonAttribute()
    {
        if (isset($this->attributes['polygon'])) {
            return json_decode($this->attributes['polygon']);
        }
        return [];
    }


    public function getPolygonsAttribute()
    {
        if (isset($this->attributes['polygon'])) {
            $polygon = json_decode($this->attributes['polygon']);
            return collect($polygon)->map(function ($i) {
                $v = explode(' ', $i);
                if (isset($v[1])) {
                    return ['lat' => $v[0], 'long' => $v[1]];
                }
                return null;
            });
        }
        return ['lat' => 0, 'long' => 0];
    }

    public function days()
    {
        return $this->hasMany(CityDay::class);
    }

    public function periods()
    {
        return $this->hasMany(CityDeliveryPeriod::class);
    }
}
