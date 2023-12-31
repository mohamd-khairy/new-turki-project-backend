<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Address extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'customer_id', 'country_iso_code', 'country_id', 'city_id', 'address', 'comment', 'label', 'is_default', 'long', 'lat',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id')->select(['name_en', 'name_ar']);
    }
}
