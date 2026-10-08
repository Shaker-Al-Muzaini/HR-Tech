<?php

namespace App\Console\Commands;

use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireOverdueInterviews extends Command
{
    protected $signature   = 'interview:expire-overdue';
    protected $description = 'إنهاء المقابلات المجدولة التي انتهى وقتها (تحويلها إلى completed)';

    /**
     * فترة السماح بالدقائق بعد انتهاء الوقت المحدد
     */
    private const GRACE_MINUTES = 5;

    public function handle(): int
    {
        $now     = Carbon::now();
        $expired = 0;

        Interview::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->whereNotNull('duration_minutes')
            ->get()
            ->each(function (Interview $interview) use ($now, &$expired) {
                $endTime = $interview->scheduled_at
                    ->copy()
                    ->addMinutes($interview->duration_minutes)
                    ->addMinutes(self::GRACE_MINUTES);

                if (! $endTime->isPast()) {
                    return;
                }

                // ─── تحويل المقابلة إلى completed ───
                $interview->update([
                    'status'          => 'completed',
                    'completion_type' => 'time_expired',
                    'expired_at'      => $now,
                    'ended_at'        => $endTime,
                ]);

                // ─── إنهاء كل الجلسات المرتبطة ───
                $interview->sessions()
                    ->whereNotIn('status', ['completed', 'failed', 'cancelled'])
                    ->update([
                        'status'   => 'cancelled',
                        'ended_at' => $now,
                    ]);

                $expired++;

                Log::info("Interview #{$interview->id} auto-expired (time exceeded)");
            });

        $this->info("✅ Expired {$expired} interview(s).");

        return self::SUCCESS;
    }
}