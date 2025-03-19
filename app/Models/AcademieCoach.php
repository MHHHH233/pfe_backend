<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademieCoach extends Model
{
    protected $table = 'academie_coach';
    protected $primaryKey = 'id_coach';
    public $timestamps = true;

    protected $fillable = [
        'id_academie',
        'nom',
        'pfp',
        'description',
        'instagram'
    ];

    public function academie()
    {
        return $this->belongsTo(Academie::class, 'id_academie');
    }
} 