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
        'google_id',
        'google_avatar',
    ];

    public function player()
    {
        return $this->hasOne(Players::class, 'id_compte');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_client');
    }

    public function reviews()
    {
        return $this->hasMany(Reviews::class, 'id_compte');
    }

    public function reportedBugs()
    {
        return $this->hasMany(ReportedBug::class, 'id_compte');
    }

    public function notifications()
    {
        return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable')
                    ->orderBy('created_at', 'desc');
    }
} 