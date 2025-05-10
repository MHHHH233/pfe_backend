<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PlayerRequest extends Model
{
    protected $table = 'player_request';
    protected $primaryKey = 'id_request';
    public $timestamps = true;

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';
    
    const TYPE_MATCH = 'match';
    const TYPE_TEAM = 'team';

    protected $fillable = [
        'sender',
        'receiver',
        'team_id',
        'request_type',
        'match_date',
        'starting_time',
        'message',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'match_date' => 'date',
        'expires_at' => 'datetime',
        'starting_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'match_date',
        'expires_at',
        'created_at',
        'updated_at'
    ];

    // Relationships
    public function sender()
    {
        return $this->belongsTo(Players::class, 'sender', 'id_player');
    }

    public function receiver()
    {
        return $this->belongsTo(Players::class, 'receiver', 'id_player');
    }
    
    public function team()
    {
        return $this->belongsTo(Teams::class, 'team_id', 'id_teams');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('expires_at', '>', Carbon::now());
    }
    
    public function scopeTeamInvites($query)
    {
        return $query->where('request_type', self::TYPE_TEAM);
    }
    
    public function scopeMatchInvites($query)
    {
        return $query->where('request_type', self::TYPE_MATCH);
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || 
               ($this->request_type === self::TYPE_MATCH && $this->match_date->isPast()) || 
               $this->status === self::STATUS_EXPIRED;
    }

    public function expire(): bool
    {
        return $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function accept(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        $this->status = self::STATUS_ACCEPTED;
        if ($this->save()) {
            // Update player statistics
            $receiver = Players::find($this->receiver);
            $receiver->increment('invites_accepted');
            $receiver->increment('total_invites');
            
            // If this is a team invitation, add the player to the team
            if ($this->request_type === self::TYPE_TEAM && $this->team_id) {
                $receiver->teams()->attach($this->team_id);
            }
            
            return true;
        }
        return false;
    }

    public function reject(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        $this->status = self::STATUS_REJECTED;
        if ($this->save()) {
            // Update player statistics
            $receiver = Players::find($this->receiver);
            $receiver->increment('invites_refused');
            $receiver->increment('total_invites');
            return true;
        }
        return false;
    }
}
