<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournoi extends Model
{
    protected $table = 'tournoi';
    protected $primaryKey = 'id_tournoi';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'capacite',
        'type',
        'date_debut',
        'date_fin',
        'frais_entree',
        'award'
    ];

    protected $casts = [
        'type' => 'string'
    ];

    public function teams()
    {
        return $this->hasMany(TournoiTeams::class, 'id_tournoi');
    }

    public function matches()
    {
        return $this->hasMany(Matches::class, 'id_tournoi');
    }
} 