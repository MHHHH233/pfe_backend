<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contact';
    protected $primaryKey = 'id_contact';
    public $timestamps = true;

    protected $fillable = [
        'numero',
        'address',
        'instagram',
        'facebook',
        'whatsapp',
        'email'
    ];
} 