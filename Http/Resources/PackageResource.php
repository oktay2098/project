<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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
            'title' => $this->getTranslations('title'),
            'status' => $this->status,
            'period' => $this->period,
            'type' => $this->type,
            'user_type' => $this->user_type,
            'ads_type' => $this->ads_type,
            'languages' => $this->languages,
            'items' => $this->items,
        ];
    }
}
