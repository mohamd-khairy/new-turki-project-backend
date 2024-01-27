<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    public $inPermission = true;

    protected $fillable = [
        'name', 'city_id', 'mobile', 'balance', 'details'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
