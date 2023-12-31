<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class SubCategory extends Model
{
    public $inPermission = true;

    protected $fillable = ['category_id', 'type_ar','type_en', 'description', 'image', 'thumbnail','sort'];
    
    protected $hidden = ['created_at', 'updated_at','pivot'];
   
    public static function uploadImage(Request $request, $category , $validatedData)
    {
        if ($request->has('image')) {
            if (Storage::exists('public/' . $category->image)) {
                Storage::delete('public/' . $category->image);
                Storage::delete('public/' . $category->thumbnail);
            }
            $file = $request->file('image');
            $extension = $file->hashName();
            $path = storage_path('app/public/category_images/');

            $thumbPath = Image::make($request->file('image'))->resize(166, 130, function ($constraint) {
                $constraint->aspectRatio();
            });
            $file->move($path, 'img_' . $extension);
            $thumbPath->save($path . 'thumb_' . $extension);

            $validatedData['image'] = 'category_images/img_' . $extension;
            $validatedData['thumbnail'] = 'category_images/thumb_' . $extension;

            return $validatedData;
        }
        return false;
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }
    

  public function subCategoryCities()
    {
        return $this->belongsToMany(City::class, 'sub_category_cities', 'sub_category_id','city_id')->with('notDeliveryDateCity');
    }


    public function products(){
        return $this->hasMany(Product::class)->with('productImages', 'tags');
    }



    public function getImageUrlAttribute (){
        return config('app.url').Storage::url($this->image);
    }

    public function getThumbnailUrlAttribute (){
        return config('app.url').Storage::url($this->thumbnail);
    }

}
