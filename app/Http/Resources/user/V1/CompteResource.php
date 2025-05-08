<?php

namespace App\Http\Resources\User\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\User\V1\ReviewsResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_compte' => $this->id_compte,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'age' => $this->age,
            'email' => $this->email,
            'role' => $this->role,
            'pfp' => $this->pfp,
            'telephone' => $this->telephone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'player' => $this->whenLoaded('player'),
            'reservations' => $this->whenLoaded('reservations'),
            'reviews' => $this->whenLoaded('reviews', function() {
                return $this->reviews->map(function($review) {
                    return [
                        'id_review' => $review->id_review,
                        'name' => $review->name,
                        'description' => $review->description,
                        'status' => $review->status,
                        'created_at' => $review->created_at,
                        'updated_at' => $review->updated_at
                    ];
                });
            })
        ];
    }
}
