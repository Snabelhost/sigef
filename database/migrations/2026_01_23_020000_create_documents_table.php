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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('reference_number')->nullable(); // Número de referência do documento
            $table->longText('content'); // Conteúdo HTML do TipTap
            $table->enum('priority', ['normal', 'urgent', 'confidential'])->default('normal');
            $table->enum('status', ['draft', 'sent', 'archived'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            // Indexes para performance
            $table->index(['sender_institution_id', 'status']);
            $table->index(['sent_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
