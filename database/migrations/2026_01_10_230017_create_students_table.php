<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained();
            $table->foreignId('course_map_id')->constrained();
            $table->string('student_number')->unique();
            $table->string('student_type'); // Sociedade Civil, Mobilidade, Regime Geral, Regime Especial
            $table->string('status')->default('alistado'); // alistado, recruta, instruendo, etc.
            $table->string('nuri')->nullable();
            $table->string('cia')->nullable();
            $table->string('platoon')->nullable(); // Pelotão
            $table->string('section')->nullable(); // Secção
            $table->foreignId('current_phase_id')->nullable()->constrained('course_phases');
            $table->date('enrollment_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
