<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerRequest extends Model
{
    protected $table = 'player_request';
    protected $primaryKey = 'id_request';
    public $timestamps = false;

    protected $fillable = [
        'sender',
        'receiver',
        'match_date',
        'starting_time',
        'message'
    ];

    // Relationships
    public function sender()
    {
        return $this->belongsTo(Player::class, 'sender', 'id_player');
    }

    public function receiver()
    {
        return $this->belongsTo(Player::class, 'receiver', 'id_player');
    }
}
