<?php

namespace App\Http\Resources;

use App\Models\Category;

use App\Models\ProductImage;
use App\Models\Banner;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryListWithBannerTestResource extends JsonResource
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
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'button_text' => $this->button_text,
            'title_color' => $this->title_color,
            'sub_title_color' => $this->sub_title_color,
            'button_text_color' => $this->button_text_color,
            'redirect_url' => $this->redirect_url,
            'redirect_mobile_url' => $this->redirect_mobile_url,
            'is_active' => $this->is_active,
            'type' => $this->type,
            'image' => $this->image,
            'product_id' => $this->product_id,
            'url' => $this->url,

        ];
    }
}
