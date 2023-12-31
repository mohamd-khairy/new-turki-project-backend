<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Shalwata;
//use App\Models\SubCategory;

class BestSellerResource extends JsonResource
{  
    public function toArray($request)
    {
        return [
            'id'  => $this->id,
          'name_ar'  => $this->name_ar,
          'name_en' => $this->name_en,
          'description_ar' => $this->description_ar,
          'description_en' => $this->description_en,
          'price' => $this->price,
          'sale price' => $this->sale_price,
          'weight' => $this->weight,
          'calories' => $this->calories,
          'no_rating' => $this->no_rating,
          'no_sale' => $this->no_sale,
          'no_clicked' => $this->no_clicked,
          'category' => Category::find($this->category_id),
          'sub_category' => SubCategory::find($this->sub_category_id),
          'is_picked_up' => $this->is_picked_up,
          'is_delivered' => $this->is_delivered,
          'is_shalwata' => $this->is_shalwata,
          'shalwata' => $this->is_shalwata == 1? Shalwata::first() : null,
          'is_active' => $this->is_active,
          'cities' => $this->cities, // this is from Product model relation or ProductCity::where('product_id', $this->id)
          'payment_types' => $this->productPaymentTypes,
          'images' => $this->productImages,
          'sizes' => $this->productSizes,
          'cuts' => $this->productCuts,
          'preparations' => $this->productPreparations,
        ];
    }
}
