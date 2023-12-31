<?php

namespace App\Http\Resources;
use App\Models\Category;

use App\Models\ProductImage;
use App\Models\Banner;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryListWithBannerResource extends JsonResource
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
            'image_url' => $this->image_url,
           'banners' => $this->banners()->get(),
                
          ];
    }
}
