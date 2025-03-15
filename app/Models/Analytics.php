<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Analytics extends Model
{
    protected $table = 'analytics';
    protected $primaryKey = 'analytic_id';
    public $timestamps = false;

    protected $fillable = [
        'analytic_name',
        'total'
    ];
} 