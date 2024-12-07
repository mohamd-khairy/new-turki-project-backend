<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class SizeResource extends JsonResource
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
            "name_ar" =>  $this->name_ar,
            "name_en" => $this->name_en,
            "weight" => $this->weight,
            "calories" => $this->calories,
            "price" => $this->price,
            "sale_price" => $this->sale_price,
            "sort" => $this->sort,
            "integrate_id" => $this->integrate_id,
            "foodics_integrate_id" => $this->foodics_integrate_id,
            "use_again" => $this->use_again,
            "stores" => StoreResource::collection($this->stores),
        ];
    }
}
