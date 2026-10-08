<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InterviewSession extends Model
{
    protected $fillable = [
        'interview_id', 'session_uuid', 'candidate_token',
        'status', 'window_count', 'candidate_joined_at',
        'calibration_completed_at', 'ended_at', 'fastapi_session_id',
    ];

    protected $casts = [
        'candidate_joined_at'       => 'datetime',
        'calibration_completed_at'  => 'datetime',
        'ended_at'                  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($session) {
            if (empty($session->session_uuid)) {
                $session->session_uuid = (string) Str::uuid();
            }
            if (empty($session->candidate_token)) {
                $session->candidate_token = Str::random(48);
            }
        });
    }

    // ─── Relationships ───

    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }

    public function timeWindows()
    {
        return $this->hasMany(TimeWindow::class);
    }

    public function recording()
    {
        return $this->hasOne(Recording::class);
    }

    // ─── Helpers ───

    public function getCandidateLinkAttribute(): string
    {
        return route('candidate.join', ['token' => $this->candidate_token]);
    }

    public function getLowQualityWindowsAttribute(): int
    {
        return $this->timeWindows()->where('overall_quality', 'low')->count();
    }

    public function getPhoneDetectedCountAttribute(): int
    {
        return $this->timeWindows()->where('phone_detected', true)->count();
    }
}
