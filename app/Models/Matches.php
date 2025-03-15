<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    protected $table = 'matches';
    protected $primaryKey = 'id_match';
    public $timestamps = false;

    protected $fillable = [
        'id_tournoi',
        'team1_id',
        'team2_id',
        'match_date',
        'score_team1',
        'score_team2',
        'stage'
    ];

    public function tournoi()
    {
        return $this->belongsTo(Tournoi::class, 'id_tournoi');
    }

    public function team1()
    {
        return $this->belongsTo(TournoiTeams::class, 'team1_id');
    }

    public function team2()
    {
        return $this->belongsTo(TournoiTeams::class, 'team2_id');
    }

    public function stage()
    {
        return $this->belongsTo(Stages::class, 'stage');
    }
} 