<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservation';
    protected $primaryKey = 'id_reservation';
    public $timestamps = true;

    protected $fillable = [
        'id_client',
        'id_terrain',
        'date',
        'heure',
        'etat',
        'Name',
        'num_res',
        'advance_payment',
    ];

    protected $casts = [
        'etat' => 'string',
        'advance_payment' => 'float'
    ];

    /**
     * Get the client that owns the reservation.
     */
    public function client()
    {
        return $this->belongsTo(Compte::class, 'id_client', 'id_compte');
    }

    /**
     * Get the terrain that is reserved.
     */
    public function terrain()
    {
        return $this->belongsTo(Terrain::class, 'id_terrain', 'id_terrain');
    }

    /**
     * Get the payment associated with the reservation.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class, 'id_reservation', 'id_reservation');
    }
} 