<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\City;

class ProductCouponResource extends JsonResource
{  
    public function toArray($request)
    {
        return [
         'id'  => $this->id,
         'name_ar'  => $this->name_ar,
         'name_en' => $this->name_en,
        ];
    }
}
