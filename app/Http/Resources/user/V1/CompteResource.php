<?php

namespace App\Http\Resources\user\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        // Add player requests data if the player relationship is loaded
        if ($this->relationLoaded('player')) {
            if ($this->player && $this->player->relationLoaded('sentRequests')) {
                $data['player']['sentRequests'] = $this->player->sentRequests;
            }

            if ($this->player && $this->player->relationLoaded('receivedRequests')) {
                $data['player']['receivedRequests'] = $this->player->receivedRequests;
            }
        }

        return $data;
    }
}
