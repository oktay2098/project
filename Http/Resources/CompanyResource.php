<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'id'           => $this->id,
            'starter_id'   => $this->starter_id,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'web'          => $this->web,
            'identity'     => $this->identity,
            'tax_identity' => $this->tax_identity,
            'created_at'   => $this->created_at ? $this->created_at->format('d/m/Y H:i') : null,
            'updated_at'   => $this->updated_at ? $this->updated_at->format('d/m/Y H:i') : null,
            'starter'      => $this->starter,
            'users'        => $this->users
        ];
    }
}
