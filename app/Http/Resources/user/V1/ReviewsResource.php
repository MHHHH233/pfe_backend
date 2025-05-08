<?php

namespace App\Http\Resources\User\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id_review,
            'id_compte' => $this->id_compte,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->when($this->relationLoaded('compte'), function () {
                return [
                    'id' => $this->compte->id_compte,
                    'nom' => $this->compte->nom,
                    'prenom' => $this->compte->prenom,
                    'email' => $this->compte->email
                ];
            })
        ];
    }
} 