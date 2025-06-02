<?php

namespace App\Http\Resources\Admin\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id_payment,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'stripe_payment_method_id' => $this->stripe_payment_method_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'currency' => $this->currency,
            'id_reservation' => $this->id_reservation,
            'id_academie' => $this->id_academie,
            'id_compte' => $this->id_compte,
            'payment_details' => $this->payment_details ? json_decode($this->payment_details) : null,
            'compte' => $this->whenLoaded('compte', function() {
                return [
                    'id' => $this->compte->id_compte,
                    'email' => $this->compte->email,
                    'nom' => $this->compte->nom,
                    'prenom' => $this->compte->prenom
                ];
            }),
            'reservation' => $this->whenLoaded('reservation'),
            'academie' => $this->whenLoaded('academie'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 