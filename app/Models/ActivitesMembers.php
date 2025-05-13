<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitesMembers extends Model
{
    protected $table = 'activites_members';
    protected $primaryKey = 'id_activity_member';
    public $timestamps = true;

    protected $fillable = [
        'id_member_ref',
        'id_activites',
        'date_joined'
    ];

    public function member()
    {
        return $this->belongsTo(AcademieMembers::class, 'id_member_ref', 'id_member');
    }

    public function activity()
    {
        return $this->belongsTo(AcademieActivites::class, 'id_activites');
    }
    
    // Add alias for 'activite' to maintain compatibility with existing code
    public function activite()
    {
        return $this->activity();
    }
}