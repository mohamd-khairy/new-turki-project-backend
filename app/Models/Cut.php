<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cut extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar','name_en', 'price', 'is_active' , 'foodics_integrate_id'];

    protected $hidden = ['created_at', 'updated_at','pivot'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
