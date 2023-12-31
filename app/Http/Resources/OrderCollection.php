<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'order' => $this->collection,
            'products'  => $this->orderProducts()->with('product'),
            'orderState' => $this->orderState
          ];
    }
}
