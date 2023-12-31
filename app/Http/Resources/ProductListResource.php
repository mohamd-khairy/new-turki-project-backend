<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Category;
use App\Models\SubCategory;


class ProductListResource extends JsonResource
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
            'image' => $this->productImages()->get(),
            'category' => Category::find($this->category_id),
            'sub_category' => SubCategory::find($this->sub_category_id),
            'tags' => $this->tags,
            'is_picked_up' => $this->is_picked_up,
            'is_delivered' => $this->is_delivered,
            'is_active' => $this->is_active,
            'cities' => $this->cities,
            'payment_types' => $this->productPaymentTypes,
        ];
    }
}
