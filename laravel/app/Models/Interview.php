<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Interview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id', 'hr_user_id', 'job_title', 'department',
        'status', 'language', 'scheduled_at', 'started_at',
        'ended_at', 'duration_minutes', 'hr_notes', 'final_decision',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at'   => 'datetime',
        'ended_at'     => 'datetime',
    ];

    // ─── Relationships ───

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function hrUser()
    {
        return $this->belongsTo(User::class, 'hr_user_id');
    }

    public function sessions()
    {
        return $this->hasMany(InterviewSession::class);
    }

    public function activeSession()
    {
        return $this->hasOne(InterviewSession::class)->whereIn('status', ['pending', 'calibrating', 'active']);
    }

    // ─── Helpers ───

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->ended_at) {
            return $this->started_at->diffInMinutes($this->ended_at);
        }
        return null;
    }
}
