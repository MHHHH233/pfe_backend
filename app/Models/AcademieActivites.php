<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademieActivites extends Model
{
    protected $table = 'academie_activites';
    protected $primaryKey = 'id_activites';
    public $timestamps = true;

    protected $fillable = [
        'id_academie',
        'title',
        'description',
        'date_debut',
        'date_fin'
    ];

    public function academie()
    {
        return $this->belongsTo(Academie::class, 'id_academie');
    }

    public function members()
    {
        return $this->hasMany(ActivitesMembers::class, 'id_activites');
    }
} 