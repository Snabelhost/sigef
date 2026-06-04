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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action', 50); // create, update, delete, view, login, logout
            $table->string('module', 100); // Nome do recurso/página acessada
            $table->string('description', 500)->nullable(); // Descrição da ação
            $table->string('model_type', 191)->nullable(); // Tipo do modelo afetado
            $table->unsignedBigInteger('model_id')->nullable(); // ID do modelo afetado
            $table->json('old_values')->nullable(); // Valores antigos (para update/delete)
            $table->json('new_values')->nullable(); // Valores novos (para create/update)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable(); // Browser/Device info
            $table->string('device_type', 50)->nullable(); // desktop, mobile, tablet
            $table->string('browser', 100)->nullable();
            $table->string('platform', 100)->nullable(); // Windows, Mac, Android, iOS
            $table->string('url', 500)->nullable(); // URL acessada
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->timestamps();
            
            // Índices para consultas rápidas
            $table->index('user_id');
            $table->index('action');
            $table->index('module');
            $table->index('created_at');
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
