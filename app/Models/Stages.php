<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stages extends Model
{
    protected $table = 'stages';
    protected $primaryKey = 'id_stage';
    public $timestamps = false;

    protected $fillable = [
        'stage_name'
    ];

    public function matches()
    {
        return $this->hasMany(Matches::class, 'stage');
    }
} 