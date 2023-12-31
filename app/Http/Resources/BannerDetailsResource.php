<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Category;
use App\Models\SubCategory;


class BannerDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'  => $this->id,
            'title'  => $this->title,
            'title_color' => $this->title_color,
            'sub_title' => $this->sub_title,
            'sub_title_color' => $this->sub_title_color,
            'button_text' => $this->button_text,
            'button_text_color' => $this->button_text_color,
            'redirect_url' => $this->redirect_url,
            'redirect_mobile_url' => $this->redirect_mobile_url,
            'is_active' => $this->is_active,
            'type' => $this->type,
            'image' => $this->image,
            'url' => $this->url,
            'product_id' => $this->product_id,
            'cities' => $this->bannerCities,
            'categories' => $this->bannerCategories,
        ];
    }
}
