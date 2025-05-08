<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Academie extends Model
{
    protected $table = 'academie';
    protected $primaryKey = 'id_academie';
    public $timestamps = false;

    protected $fillable = [
        'nom',
        'description',
        'date_creation',
        'plan_base',
        'plan_premium'
    ];

    // Relationships
    public function coaches()
    {
        return $this->hasMany(AcademieCoach::class, 'id_academie');
    }

    public function activities()
    {
        return $this->hasMany(AcademieActivites::class, 'id_academie');
    }

    public function programmes()
    {
        return $this->hasMany(AcademieProgramme::class, 'id_academie');
    }
    
    public function members()
    {
        return $this->hasMany(AcademieMembers::class, 'id_academie');
    }
} 