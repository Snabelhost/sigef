<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained();
            $table->foreignId('academic_year_id')->constrained();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('course_plan_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained();
            $table->integer('order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_plan_subjects');
        Schema::dropIfExists('course_plans');
    }
};
