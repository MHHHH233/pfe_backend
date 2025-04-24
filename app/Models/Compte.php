<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Compte extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    protected $table = 'compte';
    protected $primaryKey = 'id_compte';
    public $timestamps = true;

    protected $fillable = [
        'nom',
        'prenom',
        'age',
        'email',
        'password',
        'role',
        'pfp',
        'telephone',
    ];

    public function player()
    {
        return $this->hasOne(Players::class, 'id_compte');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_client');
    }

    public function playerSentRequests()
    {
        return $this->hasManyThrough(
            PlayerRequest::class,
            Players::class,
            'id_compte', // Foreign key on players table
            'sender',    // Foreign key on player_request table
            'id_compte', // Local key on compte table
            'id_player'  // Local key on players table
        );
    }

    public function playerReceivedRequests()
    {
        return $this->hasManyThrough(
            PlayerRequest::class,
            Players::class,
            'id_compte', // Foreign key on players table
            'receiver',  // Foreign key on player_request table
            'id_compte', // Local key on compte table
            'id_player'  // Local key on players table
        );
    }
}
