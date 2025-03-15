<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademieProgramme extends Model
{
    protected $table = 'academie_programme';
    protected $primaryKey = 'id_programme';
    public $timestamps = false;

    protected $fillable = [
        'id_academie',
        'jour',
        'horaire',
        'programme'
    ];

    public function academie()
    {
        return $this->belongsTo(Academie::class, 'id_academie');
    }
} 