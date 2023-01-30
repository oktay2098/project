<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'id'                => $this->id,
            'model_type'        => strtolower(str_replace('App\\Models\\', '', $this->model_type)),
            'model_id'          => $this->model_id,
            'geo'               => $this->geo,
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            'country_code'      => $this->country_code,
            'language'          => $this->language,
            'formatted_address' => $this->formatted_address,
            'country'           => $this->country,
            'administrative_1'  => $this->administrative_1,
            'administrative_2'  => $this->administrative_2,
            'administrative_3'  => $this->administrative_3,
            'administrative_4'  => $this->administrative_4,
            'locality'          => $this->locality,
            'route'             => $this->route,
            'street_number'     => $this->street_number,
            'postal_code'       => $this->postal_code,
            'created_at'        => $this->created_at ? $this->created_at->format('d/m/Y H:i') : null,
            'updated_at'        => $this->updated_at ? $this->updated_at->format('d/m/Y H:i') : null,
            'owner'             => $this->owner
        ];
    }
}
