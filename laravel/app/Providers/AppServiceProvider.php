<?php

namespace App\Providers;

use App\Models\InterviewSession;
use App\Observers\InterviewSessionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ═══════════════════════════════════════════════════════
        // مزامنة حالة المقابلة مع حالة الجلسة تلقائياً
        // ═══════════════════════════════════════════════════════
        InterviewSession::observe(InterviewSessionObserver::class);
    }
}