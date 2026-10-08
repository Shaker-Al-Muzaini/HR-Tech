<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول interviews — المقابلات المجدولة
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hr_user_id')->constrained('users')->restrictOnDelete();
            $table->string('job_title');
            $table->string('department')->nullable();
            $table->enum('status', [
                'scheduled',    // مجدولة
                'active',       // جارية
                'completed',    // منتهية
                'cancelled',    // ملغاة
                'no_show',      // المرشح لم يحضر
            ])->default('scheduled');
            $table->enum('language', ['ar', 'en', 'ar-en'])->default('ar');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->text('hr_notes')->nullable();           // ملاحظات HR النهائية
            $table->string('final_decision')->nullable();   // قرار HR (ليس النظام)
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
            $table->index('hr_user_id');
            $table->index('candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
