<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Players extends Model
{
    protected $table = 'players';
    protected $primaryKey = 'id_player';
    public $timestamps = false;

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
} 