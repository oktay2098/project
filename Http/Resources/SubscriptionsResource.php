<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionsResource extends JsonResource
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
            'expire_date' => $this->expire_date?->format("Y-m-d"),
            'price' => $this->price,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'status' => $this->status,
            'plan' => [
                'id' => $this->plan?->id,
                'price' => $this->plan?->total,
                'currency' => $this->plan?->currency,
                'period' => $this->plan?->package->period,
                'ads_number' => $this->plan?->ads_number,
                'type' => $this->plan?->package->type,
                'user_type' => $this->plan?->package->user_type,
                'ads_type' => $this->plan?->package->ads_type,
            ],
        ];
    }
}
