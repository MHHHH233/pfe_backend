<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerTeam extends Model
{
    protected $table = 'player_team';
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REFUSED = 'refused';
    
    protected $fillable = [
        'id_player',
        'id_teams',
        'status'
    ];
    
    public function player()
    {
        return $this->belongsTo(Players::class, 'id_player');
    }
    
    public function team()
    {
        return $this->belongsTo(Teams::class, 'id_teams');
    }
} 