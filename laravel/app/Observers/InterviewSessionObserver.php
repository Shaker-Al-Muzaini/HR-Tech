<?php

namespace App\Observers;

use App\Models\InterviewSession;
use Illuminate\Support\Facades\Log;

/**
 * يزامن Interview.status مع InterviewSession.status تلقائياً
 * عندما يُنهي FastAPI الجلسة أو يفشل الاتصال
 */
class InterviewSessionObserver
{
    public function updated(InterviewSession $session): void
    {
        if (! $session->isDirty('status')) {
            return;
        }

        $interview = $session->interview;
        if (! $interview) {
            return;
        }

        // ─── تحديد الحالة الجديدة للمقابلة ───
        $interviewStatus = match ($session->status) {
            'active'    => 'active',
            'completed' => 'completed',
            'failed'    => 'cancelled',
            'cancelled' => 'cancelled',
            default     => null,
        };

        if (! $interviewStatus || $interview->status === $interviewStatus) {
            return;
        }

        $updates = ['status' => $interviewStatus];

        if ($interviewStatus === 'completed') {
            if (! $interview->ended_at) {
                $updates['ended_at'] = $session->ended_at ?? now();
            }
            // إذا لم يُحدد completion_type بعد → HR أنهى (الافتراضي)
            if (! $interview->completion_type) {
                $updates['completion_type'] = 'ended_by_hr';
            }
        }

        if ($interviewStatus === 'active' && ! $interview->started_at) {
            $updates['started_at'] = now();
        }

        $interview->update($updates);

        Log::info('Interview status synced', [
            'interview_id' => $interview->id,
            'session_id'   => $session->id,
            'new_status'   => $interviewStatus,
            'completion'   => $updates['completion_type'] ?? null,
        ]);
    }
}