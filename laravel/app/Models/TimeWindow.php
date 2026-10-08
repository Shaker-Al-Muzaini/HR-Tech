<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeWindow extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'interview_session_id', 'window_index', 'start_time_sec', 'end_time_sec',
        // Prosody
        'pitch_mean_hz', 'pitch_variance', 'speech_rate', 'pause_ratio',
        'intensity_mean_db', 'prosody_confidence',
        // Whisper
        'transcript', 'language_detected', 'whisper_confidence',
        // Face
        'au1_inner_brow_raise', 'au4_brow_lowerer', 'au6_cheek_raiser',
        'au12_lip_corner_puller', 'head_movement_variance', 'face_confidence',
        // Objects
        'objects_detected', 'phone_detected', 'person_count',
        // Quality
        'audio_snr_db', 'audio_quality', 'face_detection_rate', 'face_quality',
        'overall_quality', 'human_review_required',
        'recorded_at',
    ];

    protected $casts = [
        'objects_detected'        => 'array',
        'phone_detected'          => 'boolean',
        'human_review_required'   => 'boolean',
        'recorded_at'             => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }

    /**
     * إنشاء TimeWindow من مخرج FastAPI (TimeWindowOutput JSON)
     */
    public static function createFromFastApiOutput(array $data, int $sessionId): static
    {
        return static::create([
            'interview_session_id'   => $sessionId,
            'window_index'           => $data['window_index'],
            'start_time_sec'         => $data['start_time_sec'],
            'end_time_sec'           => $data['end_time_sec'],
            // Prosody
            'pitch_mean_hz'          => $data['prosody']['pitch_mean_hz'] ?? null,
            'pitch_variance'         => $data['prosody']['pitch_variance'] ?? null,
            'speech_rate'            => $data['prosody']['speech_rate_syllables_per_sec'] ?? null,
            'pause_ratio'            => $data['prosody']['pause_ratio'] ?? null,
            'intensity_mean_db'      => $data['prosody']['intensity_mean_db'] ?? null,
            'prosody_confidence'     => $data['prosody']['confidence'] ?? 0,
            // Whisper
            'transcript'             => $data['transcript']['transcript'] ?? null,
            'language_detected'      => $data['transcript']['language_detected'] ?? null,
            'whisper_confidence'     => $data['transcript']['confidence'] ?? 0,
            // Face
            'au1_inner_brow_raise'   => $data['face']['au1_inner_brow_raise'] ?? null,
            'au4_brow_lowerer'       => $data['face']['au4_brow_lowerer'] ?? null,
            'au6_cheek_raiser'       => $data['face']['au6_cheek_raiser'] ?? null,
            'au12_lip_corner_puller' => $data['face']['au12_lip_corner_puller'] ?? null,
            'head_movement_variance' => $data['face']['head_movement_variance'] ?? null,
            'face_confidence'        => $data['face']['confidence'] ?? 0,
            // Objects
            'objects_detected'       => $data['objects']['objects_detected'] ?? [],
            'phone_detected'         => $data['objects']['phone_detected'] ?? false,
            'person_count'           => $data['objects']['person_count'] ?? 1,
            // Quality
            'audio_snr_db'           => $data['quality']['audio_snr_db'] ?? null,
            'audio_quality'          => $data['quality']['audio_quality'] ?? 'acceptable',
            'face_detection_rate'    => $data['quality']['face_detection_rate'] ?? null,
            'face_quality'           => $data['quality']['face_quality'] ?? 'acceptable',
            'overall_quality'        => $data['quality']['overall_quality'] ?? 'acceptable',
            'human_review_required'  => $data['quality']['human_review_required'] ?? false,
        ]);
    }
}
