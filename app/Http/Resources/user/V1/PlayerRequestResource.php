<?php

namespace App\Http\Resources\user\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\user\V1\PlayersResource;
use App\Http\Resources\user\V1\TeamsResource;
use App\Models\Players;

class PlayerRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id_request,
            'sender' => $this->sender,
            'receiver' => $this->receiver,
            'team_id' => $this->team_id,
            'request_type' => $this->request_type,
            'match_date' => $this->match_date,
            'starting_time' => $this->starting_time,
            'message' => $this->message,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Always include sender and receiver details
        $data['sender_details'] = new PlayersResource($this->sender);
        $data['receiver_details'] = new PlayersResource($this->receiver);
        
        // Include team relationship if loaded
        if ($this->relationLoaded('team')) {
            $data['team_details'] = new TeamsResource($this->team);
        }

        return $data;
    }
}
