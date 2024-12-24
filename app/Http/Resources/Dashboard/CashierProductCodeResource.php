<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class CashierProductCodeResource extends JsonResource
{
    public function toArray($request)
    {
        $size = $this->productSizes ? $this->productSizes->where('product_code', $request->product_code)->first() : null;

        return [
            'id'  => $this->id,
            'name_ar'  => $this->name_ar,
            'sale_price' => isset($size->sale_price) ? $size->sale_price : $this->sale_price,
            'image_url' => isset($this->productImages()->first()->image_url) ? $this->productImages()->first()->image_url : null,
            'sizes' => isset($size) ? $size : null,
            'cuts' => $this->productCuts,
            'preparations' => $this->productPreparations,
        ];
    }
}
