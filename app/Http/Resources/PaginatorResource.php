<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaginatorResource extends JsonResource
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
            'data' => $request,
            'next_page'  => $request->url() . $request->query(),
            'previous_page' => $request->url() . $request->query(),
            'per_page' => $request->url() . $request->query(),
            'total_items' => $request->url() . $request->query(),
          ];
    }
}
