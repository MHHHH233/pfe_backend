<?php

namespace App\Http\Resources\Admin\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Admin\V1\TeamsResource;

class PlayersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id_player,
            'id_compte' => $this->id_compte,
            'position' => $this->position,
            'total_matches' => $this->total_matches,
            'rating' => $this->rating,
            'starting_time' => $this->starting_time,
            'finishing_time' => $this->finishing_time,
            'misses' => $this->misses,
            'invites_accepted' => $this->invites_accepted,
            'invites_refused' => $this->invites_refused,
            'total_invites' => $this->total_invites,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Include compte relationship if loaded
        if ($this->relationLoaded('compte')) {
            $data['compte'] = $this->compte;
        }

        // Include ratings relationship if loaded
        if ($this->relationLoaded('ratings')) {
            $data['ratings'] = $this->ratings;
        }

        // Include teams relationship if loaded
        if ($this->relationLoaded('teams')) {
            $data['teams'] = TeamsResource::collection($this->teams);
        }

        return $data;
    }
}
