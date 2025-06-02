<?php

namespace App\Http\Resources\User\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_payment' => $this->id_payment,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'payment_details' => $this->payment_details,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'reservation' => $this->when($this->id_reservation, function() {
                return [
                    'id_reservation' => $this->reservation->id_reservation,
                    'num_res' => $this->reservation->num_res,
                    'date' => $this->reservation->date,
                    'heure' => $this->reservation->heure,
                    'etat' => $this->reservation->etat,
                ];
            }),
            'academie' => $this->when($this->id_academie, function() {
                return [
                    'id_academie' => $this->academie->id_academie,
                    'name' => $this->academie->name,
                ];
            }),
        ];
    }
} 