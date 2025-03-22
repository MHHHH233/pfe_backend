<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportedBug extends Model
{
    protected $table = 'reported_bug';
    protected $primaryKey = 'id_bug';
    public $timestamps = true;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'id_compte',
        'description',
        'status'
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'id_compte');
    }

    // Helper methods for status
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isResolved()
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }
} 