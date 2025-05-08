<?php

namespace App\Http\Resources\user\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademieMembersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_member' => $this->id_member,
            'id_compte' => $this->id_compte,
            'id_academie' => $this->id_academie,
            'status' => $this->status,
            'subscription_plan' => $this->subscription_plan,
            'date_joined' => $this->date_joined,
            'academie' => new AcademieResource($this->whenLoaded('academie')),
            'compte' => new CompteResource($this->whenLoaded('compte')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 