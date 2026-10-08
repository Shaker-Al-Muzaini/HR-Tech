<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ═══════════════════════════════════════════════════════════
// Scheduled Tasks
// ═══════════════════════════════════════════════════════════

/**
 * إنهاء المقابلات المنتهية تلقائياً
 * يعمل كل دقيقة — يحوّل المقابلات المجدولة التي انتهى وقتها
 * إلى completed (completion_type = time_expired)
 */
Schedule::command('interview:expire-overdue')->everyMinute();