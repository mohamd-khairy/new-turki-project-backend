<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Banner extends Model
{
    public $inPermission = true;

    use HasFactory, LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    protected $fillable = [
        'image',
        'title',
        'title_color',
        'sub_title',
        'sub_title_color',
        'button_text',
        'button_text_color',
        'redirect_url',
        'is_active',
        'type',
        'redirect_mobile_url',
        'product_id',
    ];

    protected $appends = ['url'];

    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly($this->getFillable());
    }

    public function getUrlAttribute()
    {
        return config('app.url') . Storage::url('marketingBoxImages/' . $this->id . '/' . $this->image);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function bannerCities()
    {
        return $this->belongsToMany(City::class, 'banner_cities');
    }

    public function bannerCategories()
    {
        return $this->belongsToMany(Category::class, 'banner_categories');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
