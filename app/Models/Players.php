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
    
    public function sentRequests()
    {
        return $this->hasMany(PlayerRequest::class, 'sender', 'id_player');
    }
    
    public function receivedRequests()
    {
        return $this->hasMany(PlayerRequest::class, 'receiver', 'id_player');
    }
    
    /**
     * Get academies associated with this player through their compte.
     */
    public function academies()
    {
        return $this->belongsToMany(
            Academie::class,
            'academie_members',
            'id_compte',
            'id_academie',
            'id_compte'
        );
    }
    
    /**
     * Get player statistics.
     * This is a virtual relationship that returns player stats.
     */
    public function stats()
    {
        // If you have a dedicated PlayerStats model, you could use:
        // return $this->hasOne(PlayerStats::class, 'id_player');
        
        // For now, we'll use a virtual relationship that returns computed stats
        return $this->hasOne(Players::class, 'id_player')
            ->select([
                'id_player',
                'rating',
                'total_matches',
                'misses',
                'invites_accepted',
                'invites_refused',
                'total_invites'
            ]);
    }
} 