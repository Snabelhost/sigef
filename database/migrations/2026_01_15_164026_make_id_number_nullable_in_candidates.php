<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Permite que o campo id_number seja nulo para agentes cadastrados manualmente
     */
    public function up(): void
    {
        // Alterar diretamente via SQL para evitar problemas com índice único
        DB::statement('ALTER TABLE candidates MODIFY id_number VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE candidates MODIFY id_number VARCHAR(255) NOT NULL');
    }
};
