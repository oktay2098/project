<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
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
            'code' => $this->code,
            'status' => $this->status,
            'symbol' => $this->symbol,
            'language' => $this->language,
            'is_prefix_symbol' => $this->is_prefix_symbol,
            'is_default' => $this->is_default,
        ];
    }
}
