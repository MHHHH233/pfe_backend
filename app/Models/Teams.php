<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teams extends Model
{
    protected $table = 'teams';
    protected $primaryKey = 'id_teams';
    public $timestamps = true;

    protected $fillable = [
        'capitain',
        'total_matches',
        'rating',
        'starting_time',
        'finishing_time',
        'misses',
        'invites_accepted',
        'invites_refused',
        'total_invites'
    ];

    public function captain()
    {
        return $this->belongsTo(Compte::class, 'capitain');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'id_rated_team');
    }
    
    public function members()
    {
        return $this->belongsToMany(Players::class, 'player_team', 'id_teams', 'id_player')
                    ->wherePivot('status', 'accepted');
    }
    
    public function player_team()
    {
        return $this->hasMany(PlayerTeam::class, 'id_teams');
    }
} 