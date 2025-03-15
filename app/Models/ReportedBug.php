<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportedBug extends Model
{
    protected $table = 'reported_bug';
    protected $primaryKey = 'id_bug';
    public $timestamps = false;

    protected $fillable = [
        'id_compte',
        'description'
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'id_compte');
    }
} 