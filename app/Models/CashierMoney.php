<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierMoney extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_money',
        'end_money',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
