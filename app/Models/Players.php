<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Players extends Model
{
    protected $table = 'players';
    protected $primaryKey = 'id_player';
    public $timestamps = true;

    protected $fillable = [
        'id_compte',
        'position',
        'total_matches',
        'rating',
        'starting_time',
        'finishing_time',
        'misses',
        'invites_accepted',
        'invites_refused',
        'total_invites'
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'id_compte');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'id_rated_player');
    }
    
    public function teams()
    {
        return $this->belongsToMany(Teams::class, 'player_team', 'id_player', 'id_teams');
    }
} 