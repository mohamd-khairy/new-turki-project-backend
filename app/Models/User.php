<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Intervention\Image\ImageManagerStatic as Image;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    use  LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    public bool $inPermission = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'username', 'email', 'mobile_country_code', 'mobile', 'password', 'age', 'country_code', 'gender',
        'email_verified_at', 'mobile_verified_at', 'is_active', 'avatar', 'avatar_thumb', 'name'
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];


    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
    ];


    public function getAvatarAttribute()
    {
        return $this->attributes['avatar'] ? config('app.url') . Storage::url($this->attributes['avatar']) : config('app.url') . '/' . ('images/logo.png');;
    }

    public static function uploadImage(Request $request, $user, $validatedData)
    {
        if ($request->has('avatar')) {

            $file = $request->file('avatar');

            if ($file) {
                if ($user && Storage::exists($user->avatar)) {
                    Storage::delete($user->avatar);
                    Storage::delete($user->avatar_thumb);
                }

                $extension = md5(time()) . '.' .  $file->getClientOriginalExtension();


                $path = storage_path('app/public/profile/');

                $thumbPath = Image::make($request->file('avatar'))->resize(166, 130, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $file->move($path, 'img_' . $extension);
                $thumbPath->save($path . 'thumb_' . $extension);

                $validatedData['avatar'] = 'profile/img_' . $extension;
                $validatedData['avatar_thumb'] = 'profile/thumb_' . $extension;
            }
        }
        return $validatedData;
    }
}
