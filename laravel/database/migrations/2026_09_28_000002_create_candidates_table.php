<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول candidates — المرشحون للوظائف
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('nationality', 10)->nullable();
            $table->string('applied_position')->nullable();
            $table->json('metadata')->nullable(); // بيانات إضافية مرنة
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('applied_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
