<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    use  LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    public $inPermission = true;

    protected $fillable = [
        'mobile_country_code',
        'mobile',
        'name',
        'email',
        'avatar',
        'avatar_thumb',
        'age',
        'gender',
        'nationality',
        'is_active',
        'wallet',
        'integrate_id',
        'deleted_at',
        'loyalty_points'
    ];

    protected $hidden = [
        'avatar',
        'avatar_thumb'
    ];

    protected $appends = [
        'avatarUrl',
        'avatarThumbUrl',
        'name_mobile'
    ];
    protected $casts = [
        'disabledDate' => 'datetime'
    ];

    public function getNameMobileAttribute()
    {
        return ($this->name ?? '') . ':' . ($this->mobile ?? '');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function wallet_orders()
    {
        return $this->hasMany(Order::class)->where('using_wallet', 1);
    }


    public function favorites()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function uploadAvatar(Request $request)
    {

        if ($request->has('avatar')) {
            if (Storage::exists('public/' . $this->avatar)) {
                Storage::delete('public/' . $this->avatar);
                Storage::delete('public/' . $this->avatar_thumb);
            }
            $file = $request->file('avatar');
            $extension = 'ava_' . time() . '_' . $file->hashName();
            $path = public_path('storage/uploads/avatars');

            $thumbPath = Image::make($request->file('avatar'))->resize(100, 100, function ($constraint) {
                $constraint->aspectRatio();
            });
            $file->move($path, $extension);
            $thumbPath->save($path . 'thumb_' . $extension);

            $this->avatar = 'uploads/avatars/' . $extension;
            $this->avatar_thumb = 'uploads/avatars/thumb_' . $extension;
            $this->update();
        }
    }

    public function getAvatarThumbUrlAttribute()
    {
        return config('app.url') . Storage::url($this->avatar_thumb);
    }

    public function getAvatarUrlAttribute()
    {
        return config('app.url') . Storage::url($this->avatar);
    }
}
