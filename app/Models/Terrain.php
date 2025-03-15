<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Terrain extends Model
{
    protected $table = 'terrain';
    protected $primaryKey = 'id_terrain';
    public $timestamps = false;

    protected $fillable = [
        'nom_terrain',
        'capacite',
        'type'
    ];

    protected $casts = [
        'capacite' => 'string',
        'type' => 'string'
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_terrain');
    }
} 