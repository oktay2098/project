<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'company_id'        => $this->company_id,
            'status'            => $this->status,
            'loggedin_at'       => $this->loggedin_at       ? $this->loggedin_at->format('d/m/Y H:i')       : null,
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->format('d/m/Y H:i') : null,
            'created_at'        => $this->created_at        ? $this->created_at->format('d/m/Y H:i')        : null,
            'updated_at'        => $this->updated_at        ? $this->updated_at->format('d/m/Y H:i')        : null,
            'type'           => $this->user_type,
            'company'           => $this->company,
            'company_started'   => $this->companyStarted
        ];
    }
}
