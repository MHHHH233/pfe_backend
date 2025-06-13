<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Academie extends Model
{
    use HasFactory;

    protected $table = 'academie';
    protected $primaryKey = 'id_academie';
    public $timestamps = true;

    protected $fillable = [
        'nom',
        'description',
        'date_creation',
        'plan_base',
        'plan_premium',
        'logo',
        'cover_image',
        'location',
        'contact_email',
        'contact_phone',
        'status',
        'owner_id',
    ];

    /**
     * Get the owner of the academie.
     */
    public function owner()
    {
        return $this->belongsTo(Compte::class, 'owner_id', 'id_compte');
    }

    /**
     * Get the members of the academie.
     */
    public function members()
    {
        return $this->hasMany(AcademieMembers::class, 'id_academie', 'id_academie');
    }

    /**
     * Get the payments associated with the academie.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'id_academie', 'id_academie');
    }

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
} 