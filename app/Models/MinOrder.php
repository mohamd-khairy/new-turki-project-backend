<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id','min_order', 'city_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];


    public function country()
    {
        return $this->belongsTo(Country::class , 'countries');
    }

}
