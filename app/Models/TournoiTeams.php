<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournoiTeams extends Model
{
    protected $table = 'tournoi_teams';
    protected $primaryKey = 'id_teams';
    public $timestamps = true;

    protected $fillable = [
        'id_tournoi',
        'team_name',
        'descrption',
        'capitain'
    ];

    public function tournoi()
    {
        return $this->belongsTo(Tournoi::class, 'id_tournoi');
    }

    public function captain()
    {
        return $this->belongsTo(Compte::class, 'capitain');
    }
} 