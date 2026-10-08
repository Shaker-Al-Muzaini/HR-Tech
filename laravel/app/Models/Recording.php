<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_session_id',
        'filename',
        'storage_path',
        'file_size_bytes',
        'duration_seconds',
        'format',
        'status',
    ];

    protected $casts = [
        'file_size_bytes'  => 'integer',
        'duration_seconds' => 'integer',
    ];

    // ─── Relationships ───

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }

    // ─── Helpers ───

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function getHumanSizeAttribute(): string
    {
        if (! $this->file_size_bytes) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $size  = $this->file_size_bytes;
        $i     = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    public function getHumanDurationAttribute(): string
    {
        if (! $this->duration_seconds) {
            return '—';
        }
        $m = intdiv($this->duration_seconds, 60);
        $s = $this->duration_seconds % 60;
        return sprintf('%02d:%02d', $m, $s);
    }
}