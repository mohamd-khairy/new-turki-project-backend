<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        return [
            'content' => $this->collection,
            'links' => [
                'next' => $request->url() . '?page=' . (int)$request->query('page') +1,
                'previous' => $request->url() . '?page=' . (int)$request->query('page') -1,
                'query' => $request->query()
            ],
        ];
    }
}
