<?php

namespace App\Http\Resources\user;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialMediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instagram' => $this->instagram,
            'facebook' => $this->facebook,
            'x' => $this->x,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'localisation' => $this->localisation,
            'telephone' => $this->telephone,
            'address' => $this->address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
