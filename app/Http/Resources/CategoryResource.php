<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'id' => $this->id,
            'type_ar'  => $this->type_ar,
            'type_en' => $this->type_en,
            'description'=> $this->description,
            'background color 1' => $this->color,
            'background color 2' => $this->backgroundColor,
            'image_url' => $this->image_url,
            'sort' => $this->sort,
            'cities' => $this->categoryCities,
          ];
    }
}
