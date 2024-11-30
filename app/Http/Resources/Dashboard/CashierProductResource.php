<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Category;
use App\Models\SubCategory;


class CashierProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'  => $this->id,
            'name_ar'  => $this->name_ar,
            'sale_price' => $this->sale_price,
            'image_url' => isset($this->productImages()->first()->image_url) ? $this->productImages()->first()->image_url : null,
            'sizes' => $this->productSizes,
            'cuts' => $this->productCuts,
            'preparations' => $this->productPreparations,
        ];
    }
}
