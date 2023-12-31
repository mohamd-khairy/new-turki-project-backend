<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shalwata extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar','name_en', 'price', 'is_active'];
    
    protected $hidden = ['created_at', 'updated_at','pivot'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
