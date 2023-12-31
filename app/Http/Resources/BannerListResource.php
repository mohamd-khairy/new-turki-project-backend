<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Category;
use App\Models\SubCategory;


class BannerListResource extends JsonResource
{
    public function toArray($request)
    {
        $types = [
            '0' => 'بنر عادي',
            '1' => 'بنر خارجي',
            '2' => 'بنر داخلي',
            '3' => 'بنر عادي',
        ];
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
            'display_type' => isset($types[$this->type]) ? $types[$this->type] : $this->type,
            'image' => $this->image,
            'url' => $this->url,
            'product_id' => $this->product_id,
            'categories' => $this->bannerCategories,
            'cities' => $this->bannerCities,
        ];
    }
}
