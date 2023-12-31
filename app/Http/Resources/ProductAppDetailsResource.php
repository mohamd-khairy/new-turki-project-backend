<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Shalwata;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAppDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
          'id'  => $this->id,
          'name_ar'  => $this->name_ar,
          'name_en' => $this->name_en,
          'description_ar'  => $this->description_ar,
          'description_en' => $this->description_en,
          'price' => $this->price,
          'sale_price'=> $this->sale_price,
          'weight' => $this->weight,
          'calories' => $this->calories,
          'no_rating' => $this->no_rating,
          'sub_category' => SubCategory::find($this->sub_category_id),
          'is_picked_up' => $this->is_picked_up,
          'is_delivered' => $this->is_delivered,
          'is_shalwata' => $this->is_shalwata,
          'shalwata' => $this->is_shalwata == 1? Shalwata::first() : null,
          'is_active' => $this->is_active,
          'is_available' => $this->is_available,
          'is_kwar3' => $this->is_kwar3,
          'is_Ras' => $this->is_Ras,
          'is_lyh' => $this->is_lyh,
          'is_karashah' => $this->is_karashah,
          'images' => $this->productImages,
          'sizes' => $this->productSizes,
          'cuts' => $this->productCuts,
          'preparations' => $this->productPreparations,

        ];
    }
}
