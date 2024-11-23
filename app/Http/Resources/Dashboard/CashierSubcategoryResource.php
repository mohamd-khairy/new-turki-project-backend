<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class CashierSubcategoryResource extends JsonResource
{
    public function toArray($request)
    {

        return [
            'id'      => $this->id,
            'type_ar'  => $this->type_ar,
            'image_url' => $this->image_url,
        ];
    }
}
