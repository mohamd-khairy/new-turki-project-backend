<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar','name_en', 'code', 'active'];

    protected $hidden = ['created_at', 'updated_at','pivot'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
