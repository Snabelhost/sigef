<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primeiro, atualizar registros existentes que têm enrollment_date NULL
        DB::statement("UPDATE students SET enrollment_date = CURDATE() WHERE enrollment_date IS NULL");

        // Alterar a coluna para ser nullable (compatível com todas as versões MySQL)
        Schema::table('students', function (Blueprint $table) {
            $table->date('enrollment_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('enrollment_date')->nullable()->change();
        });
    }
};
