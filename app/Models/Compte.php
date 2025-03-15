<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compte extends Model
{
    protected $table = 'compte';
    protected $primaryKey = 'id_compte';
    public $timestamps = false;

    protected $fillable = [
        'nom',
        'prenom',
        'age',
        'email',
        'password',
        'type',
        'pfp',
        'telephone',
        'date_inscription'
    ];

    public function player()
    {
        return $this->hasOne(Players::class, 'id_compte');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_client');
    }
} 