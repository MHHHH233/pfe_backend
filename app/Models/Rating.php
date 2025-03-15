<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = 'rating';
    protected $primaryKey = 'id_rating';
    public $timestamps = false;

    protected $fillable = [
        'id_rating_player',
        'id_rated_player',
        'id_rated_team',
        'stars'
    ];

    protected $casts = [
        'stars' => 'string'
    ];

    public function ratingPlayer()
    {
        return $this->belongsTo(Players::class, 'id_rating_player');
    }

    public function ratedPlayer()
    {
        return $this->belongsTo(Players::class, 'id_rated_player');
    }

    public function ratedTeam()
    {
        return $this->belongsTo(Teams::class, 'id_rated_team');
    }
} 