<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_payment';
    
    protected $fillable = [
        'stripe_payment_intent_id',
        'amount',
        'status',
        'payment_method',
        'currency',
        'id_reservation',
        'id_academie',
        'id_compte',
        'payment_details',
    ];

    /**
     * Get the reservation associated with the payment.
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation', 'id_reservation');
    }

    /**
     * Get the academie associated with the payment.
     */
    public function academie()
    {
        return $this->belongsTo(Academie::class, 'id_academie', 'id_academie');
    }

    /**
     * Get the user account associated with the payment.
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class, 'id_compte', 'id_compte');
    }
} 