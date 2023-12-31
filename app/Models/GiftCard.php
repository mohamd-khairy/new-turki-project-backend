<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftCard extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'amount',
        'is_active',
        'expire_at',
    ];

    protected $casts = [
        'expire_at' => 'datetime:Y-m-d H:m',
        'created_at' => 'datetime:Y-m-d H:m',
        'updated_at' => 'datetime:Y-m-d H:m',
    ];

    public function setExpireAtAttribute($value) {
        $this->attributes['expire_at'] = (new Carbon($value))->format('Y-m-d H:m');
    }

    public static function isValid(GiftCard $giftCard)
    {
        if ($giftCard == null) {
            return null;
        }

        $expire_at = Carbon::make($giftCard->expire_at)->timestamp;

        //if not valid reject
        if($expire_at < Carbon::now()->timestamp) {
            return null;
        }

        return $giftCard;
    }

}
