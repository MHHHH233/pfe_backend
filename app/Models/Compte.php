<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Compte extends Authenticatable
{
    use HasApiTokens, HasRoles;
    protected $table = 'compte';
    protected $primaryKey = 'id_compte';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'prenom',
        'age',
        'email',
        'password',
        'type',
        'pfp',
        'telephone',
        'created_at',
        'updated_at'
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