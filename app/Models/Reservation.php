<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'reservation';
    protected $primaryKey = 'id_reservation';
    public $timestamps = true;

    protected $fillable = [
        'id_client',
        'id_terrain',
        'date',
        'heure',
        'etat',
        'Name'
    ];

    protected $casts = [
        'etat' => 'string'
    ];

    public function client()
    {
        return $this->belongsTo(Compte::class, 'id_client');
    }

    public function terrain()
    {
        return $this->belongsTo(Terrain::class, 'id_terrain');
    }
} 