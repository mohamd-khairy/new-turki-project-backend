<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class StoreResource extends JsonResource
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

            "id" => $this->id,
            "size_id" => $this->size_id,
            "store_id" => $this->store_id,
            "stock_id" => $this->stock_id,
            "product_id" => $this->product_id,
            "quantity" => $this->quantity,
            "store" => [
                "id" => $this->store->id,
                "name" => $this->store->name,
                "city_id" => $this->store->city_id,
                "user_id" => $this->store->user_id,
            ],
            "stock" =>  [
                "id" => $this->product->id,
                "store_id" => $this->store->id,
                "invoice_id" => $this->product_id,,
                "product_id" => $this->product->id,
                "product_name" => $this->product->name_ar,
                "quantity" => $this->product_id,
                "price" => $this->product_id,
            ]


        ];
    }
}
