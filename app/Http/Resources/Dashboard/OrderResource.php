<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'id' => $this->ref_no,
            'total_amount' => $this->total_amount,
            'order_state' => $this->orderState->state_ar,
            'payment_type' => $this->paymentType->name_ar,
        ];
    }
}
