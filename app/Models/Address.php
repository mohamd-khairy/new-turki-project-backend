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
        'foodics_integrate_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            // This code will be executed when a new record is being created
            $res = foodics_create_customer_address($model);
        });
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id')->select(['name_en', 'name_ar']);
    }
}
