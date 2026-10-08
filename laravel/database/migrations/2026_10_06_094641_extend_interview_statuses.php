<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════
        // 1. interview_sessions.status: إضافة 'cancelled'
        // ═══════════════════════════════════════════════════════
        $this->dropStatusConstraint('interview_sessions');

        DB::statement("
            ALTER TABLE interview_sessions
            ADD CONSTRAINT interview_sessions_status_check
            CHECK (status IN (
                'pending', 'calibrating', 'active',
                'completed', 'failed', 'cancelled'
            ))
        ");

        // ═══════════════════════════════════════════════════════
        // 2. interviews: إضافة completion_type + expired_at
        // ═══════════════════════════════════════════════════════
        Schema::table('interviews', function (Blueprint $table) {
            $table->enum('completion_type', [
                'ended_by_hr',      // HR ضغط "إنهاء المقابلة"
                'time_expired',     // انتهى الوقت المحدد
                'candidate_left',   // المرشح غادر ولم يعد
            ])->nullable()->after('final_decision');

            $table->timestamp('expired_at')->nullable()->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn(['completion_type', 'expired_at']);
        });

        $this->dropStatusConstraint('interview_sessions');

        DB::statement("
            ALTER TABLE interview_sessions
            ADD CONSTRAINT interview_sessions_status_check
            CHECK (status IN (
                'pending', 'calibrating', 'active', 'completed', 'failed'
            ))
        ");
    }

    /**
     * حذف أي check constraint على عمود status في الجدول المعطى
     */
    private function dropStatusConstraint(string $table): void
    {
        $constraints = DB::select("
            SELECT conname
            FROM pg_constraint
            WHERE conrelid = ?::regclass
              AND contype = 'c'
              AND pg_get_constraintdef(oid) LIKE '%status%'
        ", [$table]);

        foreach ($constraints as $c) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$c->conname}");
        }
    }
};