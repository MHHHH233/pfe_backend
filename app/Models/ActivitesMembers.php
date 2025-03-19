<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitesMembers extends Model
{
    protected $table = 'activites_members';
    protected $primaryKey = 'id_member';
    public $timestamps = true;

    protected $fillable = [
        'id_compte',
        'id_activites',
        'date_joined'
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'id_compte');
    }

    public function activity()
    {
        return $this->belongsTo(AcademieActivites::class, 'id_activites');
    }
}