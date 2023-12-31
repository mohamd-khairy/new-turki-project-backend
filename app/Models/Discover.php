<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;



class Discover extends Model
{
    use HasFactory;

    protected $fillable = [
        'titel_ar',
        'titel_en',
        'sub_titel_ar',
        'sub_titel_en',
        'description_ar',
        'description_en',
        'thumbnail',
        'sub_thumbnail',
        'image',
        'sub_image',
        'is_active',
        'category_id',
    ];
    
    protected $hidden = ['created_at', 'updated_at','pivot'];
    
    protected $appends = ['image_url', 'thumbnail_url','sub_image_url'];
    
   

    public function getSubImageUrlAttribute(){
        return config('app.url').Storage::url('app/public/discover_sub_image/'.'/'.$this->sub_image);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class ,'product_discovers')->with('productImages');
    }

    public function discoverCities()
    {
        return $this->belongsToMany(City::class, 'discover_cities');
    }

    public static function uploadImage(Request $request, $discover , $validatedData)
    {
        if ($request->has('image')) {
            if (Storage::exists('public/' . $discover->image)) {
                Storage::delete('public/' . $discover->image);
                Storage::delete('public/' . $discover->thumbnail);
            }
            $file = $request->file('image');
            $extension = $file->hashName();
            $path = storage_path('app/public/discover_images/');
          //  $path = storage_path('app/public/discover_images/');

            $thumbPath = Image::make($request->file('image'))->resize(166, 130, function ($constraint) {
                $constraint->aspectRatio();
            });
       
            $file->move($path, 'img_' . $extension);
            $thumbPath->save($path . 'thumb_' . $extension);

            $validatedData['image'] = 'discover_images/img_' . $extension;
            $validatedData['thumbnail'] = 'discover_images/thumb_' . $extension;
 
            return $validatedData;
        }
        return false;
    }
    
 
    
      public function getImageUrlAttribute (){
        return config('app.url').Storage::url($this->image);
    }

    public function getThumbnailUrlAttribute (){
        return config('app.url').Storage::url($this->thumbnail);
    }

   
}
