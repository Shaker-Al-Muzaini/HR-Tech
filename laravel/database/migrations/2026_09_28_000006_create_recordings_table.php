<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول recordings — ملفات تسجيل المقابلات
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('storage_path');              // المسار في Docker volume
            $table->bigInteger('file_size_bytes')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->enum('format', ['webm', 'mp4', 'mkv'])->default('webm');
            $table->enum('status', [
                'recording',    // قيد التسجيل
                'processing',   // قيد المعالجة
                'ready',        // جاهز
                'failed',       // فشل
            ])->default('recording');
            $table->timestamps();

            $table->index('interview_session_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
