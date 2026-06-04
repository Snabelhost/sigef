<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Permite que campos obrigatórios sejam nulos para agentes cadastrados manualmente
     */
    public function up(): void
    {
        // Tornar gender nullable
        DB::statement("ALTER TABLE candidates MODIFY gender ENUM('Masculino', 'Feminino') NULL");
        
        // Tornar institution_id nullable (caso não tenha sido feito)
        DB::statement('ALTER TABLE candidates MODIFY institution_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE candidates MODIFY gender ENUM('Masculino', 'Feminino') NOT NULL");
    }
};
