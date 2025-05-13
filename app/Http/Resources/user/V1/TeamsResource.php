<?php

namespace App\Http\Resources\user\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_teams' => $this->id_teams,
            'team_name' => $this->team_name,
            'capitain' => $this->capitain,
            'total_matches' => $this->total_matches,
            'rating' => $this->rating,
            'members_count' => $this->when(isset($this->members_count), $this->members_count),
            'members' => $this->when($this->relationLoaded('members'), function() {
                return $this->members->map(function($member) {
                    return [
                        'id_player' => $member->id_player,
                        'name' => $member->compte ? $member->compte->nom . ' ' . $member->compte->prenom : 'Unknown',
                        'position' => $member->position,
                        'rating' => $member->rating
                    ];
                });
            }),
            'captain_details' => $this->when($this->relationLoaded('captain'), function() {
                return [
                    'id_compte' => $this->captain->id_compte,
                    'name' => $this->captain->nom . ' ' . $this->captain->prenom,
                    'email' => $this->captain->email
                ];
            }),
            'ratings' => $this->when($this->relationLoaded('ratings'), function() {
                return $this->ratings->map(function($rating) {
                    return [
                        'id' => $rating->id,
                        'rating' => $rating->rating,
                        'comment' => $rating->comment,
                        'created_at' => $rating->created_at
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
