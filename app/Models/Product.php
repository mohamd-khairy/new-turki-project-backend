<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory;

    use  LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    public $inPermission = true;

    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'weight',
        'calories',
        'is_active',
        'no_rating',
        'no_sale',
        'price',
        'sale_price',
        'is_active',
        'is_available',
        'is_kwar3',
        'is_Ras',
        'is_lyh',
        'is_karashah',
        'is_shalwata',
        'shalwata_id',
        'is_delivered',
        'is_picked_up',
        'category_id',
        'integrate_id',
        'sub_category_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'is_shalwata' => 'boolean',
        'is_karashah' => 'boolean',
        'is_kwar3' => 'boolean',
        'is_Ras' => 'boolean',
        'is_lyh' => 'boolean',
    ];


    protected $hidden = ['created_at', 'updated_at', 'pivot'];

    public static function uploadProductImages($productId, $request)
    {
        if ($request->has('images')) {
            ProductImage::where(['product_id' => $productId])->delete();

            foreach ($request->file('images') as $file) {

                $extension = $file->hashName();

                $path = storage_path('app/public/product_images/' . $productId . '/');

                $thumbPath = Image::make($file)->resize(166, 130, function ($constraint) {
                    $constraint->aspectRatio();
                });

                $file->move($path, 'img_' . $extension);

                $thumbPath->save($path . 'thumb_' . $extension);

                ProductImage::create([
                    'image' => 'product_images/' . $productId . '/img_' . $extension,
                    'thumbnail' => 'product_images/' . $productId . '/thumb_' . $extension,
                    'product_id' => $productId
                ]);
            }
        }
    }

    public static function uploadImage(Request $request, $product, $validatedData)
    {
        if ($request->has('image')) {
            if (Storage::exists('public/' . $product->image)) {
                Storage::delete('public/' . $product->image);
                Storage::delete('public/' . $product->thumbnail);

                ProductImage::where(['product_id' => $product->id])->delete();
            }
            $file = $request->file('image');
            $extension = $file->hashName();
            $path = storage_path('app/public/product_images/');

            $thumbPath = Image::make($request->file('image'))->resize(166, 130, function ($constraint) {
                $constraint->aspectRatio();
            });
            $file->move($path, 'img_' . $extension);
            $thumbPath->save($path . 'thumb_' . $extension);

            $validatedData['image'] = 'product_images/img_' . $extension;
            $validatedData['thumbnail'] = 'product_images/thumb_' . $extension;

            return $validatedData;
        }
        return false;
    }


    public function notDeliveryDate()
    {
        return $this->hasMany(DeliveryDate::class);
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }


    public function subCategories()
    {
        return $this->belongsToMany(SubCategory::class, 'sub_category_products');
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'product_cities');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }


    public function productSizes()
    {
        return $this->belongsToMany(Size::class, 'product_sizes')->orderBy('sort', 'asc')->distinct();
    }

    public function productCuts()
    {
        return $this->belongsToMany(Cut::class, 'product_cuts')->distinct();
    }

    public function productPreparations()
    {
        return $this->belongsToMany(Preparation::class, 'product_preparations')->distinct();
    }

    public function productCities()
    {
        return $this->belongsToMany(City::class, 'product_cities');
    }

    public function productPaymentTypes()
    {
        return $this->belongsToMany(PaymentType::class, 'product_payment_types');
    }

    public function shalwata()
    {
        return $this->belongsTo(Shalwata::class, 'shalwata_id');
    }


    public function productImages()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_products');
    }

    // public function  getImageAttribute(){
    //     return config('app.url').Storage::url($this->image);
    // }

    public function  getThumbnailAttribute()
    {
        return config('app.url') . Storage::url($this->thumbnail);
    }



}
