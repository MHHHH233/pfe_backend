<?php

namespace App\Http\Resources\Admin\V1;

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
            'compte' => $this->when($this->relationLoaded('compte'), function () {
                return [
                    'id' => $this->compte->id_compte,
                    'username' => $this->compte->username,
                    'email' => $this->compte->email
                ];
            })
        ];
    }
} 