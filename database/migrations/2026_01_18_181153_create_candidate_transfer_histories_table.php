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
        Schema::create('candidate_transfer_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('from_institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('to_institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Informações do candidato no momento da transferência
            $table->string('candidate_name')->nullable();
            $table->string('bi_number')->nullable();
            $table->string('student_type')->nullable();
            $table->string('phone')->nullable();
            $table->string('province')->nullable();
            $table->string('status')->nullable();
            
            // Observações
            $table->text('notes')->nullable();
            
            $table->timestamp('transferred_at');
            $table->timestamps();
            
            // Índices
            $table->index(['candidate_id', 'transferred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_transfer_histories');
    }
};
