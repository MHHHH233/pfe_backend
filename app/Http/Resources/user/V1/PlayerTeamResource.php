<?php

namespace App\Http\Resources\user\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerTeamResource extends JsonResource
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
            'player_id' => $this->id_player,
            'team_id' => $this->id_teams,
            'status' => $this->status,
            'player' => $this->whenLoaded('player', function() {
                return [
                    'id' => $this->player->id_player,
                    'position' => $this->player->position,
                    'rating' => $this->player->rating,
                    'compte' => $this->whenLoaded('player.compte', function() {
                        return [
                            'id' => $this->player->compte->id_compte,
                            'nom' => $this->player->compte->nom,
                            'prenom' => $this->player->compte->prenom,
                            'profile_picture' => $this->player->compte->profile_picture,
                        ];
                    })
                ];
            }),
            'team' => $this->whenLoaded('team', function() {
                return [
                    'id' => $this->team->id_teams,
                    'name' => $this->team->team_name ?? null,
                    'rating' => $this->team->rating,
                    'captain_id' => $this->team->capitain,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 