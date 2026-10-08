<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول suggested_questions — بنك الأسئلة (المرحلة 1 — JSON ثابت)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suggested_questions', function (Blueprint $table) {
            $table->id();
            $table->string('text_ar');                   // نص السؤال بالعربية
            $table->string('text_en')->nullable();       // نص السؤال بالإنجليزية
            $table->string('category');                  // general, behavioral, technical, closing
            $table->string('job_title')->default('general'); // تخصيص لوظيفة معينة
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'job_title', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggested_questions');
    }
};
