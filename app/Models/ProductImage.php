<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class ProductImage extends Model
{
    protected $fillable = ['image','thumbnail','product_id', 'is_default'];
    protected $appends = ['image_url', 'thumbnail_url'];
    protected $hidden = ['image', 'thumbnail','created_at','updated_at'];

    public static function uploadFile(Request $request, $product, $uploadedData)
    {
        if ($request->has('image')) {
            $uploadedFile = $request->file('image');
            $extension = 'image_'.$uploadedFile->hashName();
            $path = storage_path('app/public/product_images/' . $product->id . '/images/');
            $uploadedFile->move($path, $extension);

            $thumbPath = Image::make($request->file('image'))->resize(166, 130, function ($constraint) {
                $constraint->aspectRatio();
            });

            $thumbPath->save($path . 'thumb_' . $extension);

            $uploadedData['file'] = 'product_images/' . $product->id .'/images/'. $extension;

            ProductImage::create(
                ['image' => 'product_images/' . $product->id .'/images/'. $extension,
                'thumbnail' => 'product_images/' . $product->id .'/images/thumb_'. $extension,
                'product_id' => $product->id
                ]);
           
        }
        return $uploadedData;
    }
    
        public static function uploadFileFromArray($data, $product)
    {
        if (isset($data)) {
            $uploadedFile = $data;
            $extension = 'image_'.$uploadedFile->hashName();
            $path = storage_path('app/public/product_images/' . $product->id . '/images/');
            $uploadedFile->move($path, $extension);

            $thumbPath = Image::make($uploadedFile)->resize(166, 130, function ($constraint) {
                $constraint->aspectRatio();
            });

            $thumbPath->save($path . 'thumb_' . $extension);

            ProductImage::create(
                ['image' => 'product_images/' . $product->id .'/images/'. $extension,
                'thumbnail' => 'product_images/' . $product->id .'/images/thumb_'. $extension,
                'product_id' => $product->id
                ]);
        }
        
    }

    public static function deleteFiles($productImage){
        if (Storage::exists('public/' . $productImage->image)) {
            Storage::delete('public/' . $productImage->image);
            Storage::delete('public/' . $productImage->thumbnail);
            $productImage->delete();
        }
    }

    public function products(){
        return $this->belongsToMany(Product::class, 'product_images', 'product_id');
    }
    
    public function getThumbnailUrlAttribute(){
        return config('app.url').Storage::url($this->thumbnail);
    }

    public function getImageUrlAttribute(){
        return config('app.url').Storage::url($this->image);
    }
}
