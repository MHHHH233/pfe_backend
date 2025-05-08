<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademieMembers extends Model
{
    protected $table = 'academie_members';
    protected $primaryKey = 'id_member';
    public $timestamps = true;

    protected $fillable = [
        'id_compte',
        'id_academie',
        'status',
        'subscription_plan',
        'date_joined'
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'id_compte');
    }

    public function academie()
    {
        return $this->belongsTo(Academie::class, 'id_academie');
    }

    public function activities()
    {
        return $this->hasManyThrough(
            AcademieActivites::class,
            Academie::class,
            'id_academie', // Foreign key on academie_members table
            'id_academie', // Foreign key on academie_activites table
            'id_academie', // Local key on academie_members table
            'id_academie'  // Local key on academie table
        );
    }
} 