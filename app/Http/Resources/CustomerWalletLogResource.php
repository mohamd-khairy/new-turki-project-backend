<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerWalletLogResource extends JsonResource
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
            'wallet' => $this->wallet,
            'wallet_logs'  => $this->wallet_logs->map(function ($item) {
                return [
                    "id" => $item->id,
                    "last_amount" => $item->last_amount,
                    "new_amount" => $item->new_amount,
                    "action" => $item->action,
                    "action_id" => $item->action_id,
                    "expired_days" => $item->expired_days,
                    "expired_at" => $item->expired_at,
                    "message_ar" => $item->message_ar,
                    "message_en" => $item->message_en,
                    "created_at" => $item->created_at,
                    'action_item' => $item->action_id ? Order::where('ref_no', $item->action_id)->first() : null
                ];
            })
        ];
    }
}
