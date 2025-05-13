<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMedia extends Model
{
    use HasFactory;
    
    protected $table = 'social_media';
    
    protected $fillable = [
        'instagram',
        'facebook',
        'x',
        'whatsapp',
        'email',
        'localisation',
        'telephone',
        'address'
    ];
}
