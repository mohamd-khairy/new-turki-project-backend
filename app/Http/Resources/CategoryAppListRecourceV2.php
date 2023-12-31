<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryAppListRecourceV2 extends JsonResource
{

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
            'banner_url' => $this->banner_url,
            'active_temp' => $this->active_temp
          ];
    }
}