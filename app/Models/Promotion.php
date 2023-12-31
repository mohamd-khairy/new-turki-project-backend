<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'title_en',
        'title_ar',
        'title_color',
        'sub_title_en',
        'sub_title_ar',
        'sub_title_color',
        'redirect_url',
        'is_active',
        'type',
        'redirect_mobile_url',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(){
        return config('app.url').Storage::url('app/public/PromotionImages/'.$this->id.'/'.$this->image);
    }
}
