<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('course_phase_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('academic_year_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('student_type', ['Alistado', 'Instruendo', 'Agente'])->default('Alistado');
            $table->string('classroom')->nullable()->comment('Sala de Aula');
            $table->boolean('is_active')->default(true);
            $table->timestamp('enrolled_at')->nullable();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Índice único para evitar duplicatas
            $table->unique(['student_id', 'class_id', 'course_phase_id'], 'unique_student_class_phase');
        });

        // Tabela pivô para disciplinas do aluno
        Schema::create('student_subject_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('course_phase_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Índice único
            $table->unique(['student_id', 'subject_id', 'class_id', 'course_phase_id'], 'unique_student_subject_class_phase');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subject_enrollments');
        Schema::dropIfExists('student_class_enrollments');
    }
};
