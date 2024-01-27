<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoneySafe extends Model
{
    use HasFactory;
    public $inPermission = true;

    protected $fillable = [
        'name', 'currency', 'balance', 'city_id', 'user_id'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
