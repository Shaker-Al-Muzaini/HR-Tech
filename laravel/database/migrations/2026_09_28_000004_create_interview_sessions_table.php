<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول interview_sessions — جلسات WebRTC الفعلية
 * كل مقابلة قد تحتوي على جلسة واحدة أو أكثر (في حالة إعادة الاتصال)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained()->cascadeOnDelete();
            $table->uuid('session_uuid')->unique(); // يُطابق session_id في FastAPI
            $table->string('candidate_token', 64)->unique(); // رابط المرشح المؤقت
            $table->enum('status', [
                'pending',      // بانتظار دخول المرشح
                'calibrating',  // مرحلة المعايرة (30-60 ثانية)
                'active',       // جارية
                'completed',    // منتهية
                'failed',       // فشل اتصال
            ])->default('pending');
            $table->integer('window_count')->default(0);     // عدد النوافذ الزمنية المعالجة
            $table->timestamp('candidate_joined_at')->nullable();
            $table->timestamp('calibration_completed_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('fastapi_session_id')->nullable(); // session_id من FastAPI
            $table->timestamps();

            $table->index('session_uuid');
            $table->index('candidate_token');
            $table->index(['interview_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_sessions');
    }
};
