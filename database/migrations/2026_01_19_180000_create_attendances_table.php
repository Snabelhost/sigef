<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->foreignId('trainer_id')->nullable()->constrained('trainers')->onDelete('set null');
            $table->date('date');
            $table->enum('status', ['P', 'F', 'J', 'A'])->default('P')->comment('P=Presente, F=Falta, J=Justificada, A=Atraso');
            $table->enum('period', ['morning', 'afternoon', 'full_day'])->default('full_day');
            $table->text('observation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Índices para performance
            $table->index(['student_id', 'date']);
            $table->index(['class_id', 'date']);
            $table->index(['institution_id', 'date']);
            
            // Evitar duplicatas
            $table->unique(['student_id', 'date', 'period', 'subject_id'], 'unique_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
