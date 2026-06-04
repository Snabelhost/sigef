<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained();
            $table->foreignId('course_phase_id')->nullable()->constrained();
            $table->string('evaluation_type'); // Prova, Trabalho, Pontualidade, etc.
            $table->decimal('score', 8, 2);
            $table->text('observations')->nullable();
            $table->foreignId('evaluated_by')->constrained('users');
            $table->timestamp('evaluated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
