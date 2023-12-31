<?php

namespace App\Http\Resources;
use App\Models\Category;

use App\Models\ProductImage;
use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcategoryListResource extends JsonResource
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
            'id'      => $this->id,
            'type_ar'  => $this->type_ar,
            'type_en' => $this->type_en,
            'sort' => $this->sort,
            'image_url' => $this->image_url,
            'category' => $this->category,
            'description'=> $this->description,
            'cities'=> $this->subCategoryCities,
            'products' => $this->products()->with('productImages', 'tags')
                ->get(),
          ];
    }
}
