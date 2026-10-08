<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول time_windows — مخرجات كل نافذة زمنية (5 ثوانٍ)
 * هذا هو قلب النظام — يخزن المؤشرات الخام من FastAPI
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained()->cascadeOnDelete();
            $table->integer('window_index');              // رقم النافذة (0, 1, 2, ...)
            $table->float('start_time_sec');             // بداية النافذة بالثواني
            $table->float('end_time_sec');               // نهاية النافذة بالثواني

            // ─── مؤشرات الصوت الخام ───
            $table->float('pitch_mean_hz')->nullable();
            $table->float('pitch_variance')->nullable();
            $table->float('speech_rate')->nullable();
            $table->float('pause_ratio')->nullable();
            $table->float('intensity_mean_db')->nullable();
            $table->float('prosody_confidence')->default(0);

            // ─── مخرجات النسخ ───
            $table->text('transcript')->nullable();
            $table->string('language_detected', 10)->nullable();
            $table->float('whisper_confidence')->default(0);

            // ─── مؤشرات الوجه الخام (AU) ───
            $table->float('au1_inner_brow_raise')->nullable();
            $table->float('au4_brow_lowerer')->nullable();
            $table->float('au6_cheek_raiser')->nullable();
            $table->float('au12_lip_corner_puller')->nullable();
            $table->float('head_movement_variance')->nullable();
            $table->float('face_confidence')->default(0);

            // ─── الكائنات المكتشفة ───
            $table->json('objects_detected')->nullable();
            $table->boolean('phone_detected')->default(false);
            $table->integer('person_count')->default(1);

            // ─── جودة الإشارة ───
            $table->float('audio_snr_db')->nullable();
            $table->enum('audio_quality', ['excellent', 'acceptable', 'low'])->default('acceptable');
            $table->float('face_detection_rate')->nullable();
            $table->enum('face_quality', ['excellent', 'acceptable', 'low'])->default('acceptable');
            $table->enum('overall_quality', ['excellent', 'acceptable', 'low'])->default('acceptable');
            $table->boolean('human_review_required')->default(false);

            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['interview_session_id', 'window_index']);
            $table->index('overall_quality');
            $table->index('human_review_required');
            $table->index('phone_detected');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_windows');
    }
};
