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
        Schema::create('student_transfer_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('from_institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('to_institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Informações do aluno no momento da transferência
            $table->string('student_name')->nullable();
            $table->string('student_number')->nullable();
            $table->string('bi_number')->nullable();
            $table->string('student_type')->nullable(); // recruta, instruendo, formando superior, em formação
            $table->string('rank')->nullable();
            $table->string('provenance')->nullable();
            $table->string('phone')->nullable();
            $table->string('course')->nullable();
            $table->string('student_class')->nullable();
            
            // CIA, Pelotão, Secção
            $table->string('cia')->nullable();
            $table->string('platoon')->nullable();
            $table->string('section')->nullable();
            
            // Observações
            $table->text('notes')->nullable();
            
            $table->timestamp('transferred_at');
            $table->timestamps();
            
            // Índices
            $table->index(['student_id', 'transferred_at']);
            $table->index('student_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_transfer_histories');
    }
};
