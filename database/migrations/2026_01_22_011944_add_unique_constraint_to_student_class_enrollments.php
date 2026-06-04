<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migration adiciona uma constraint única para garantir que
     * um aluno só pode ter uma inscrição ativa por vez.
     */
    public function up(): void
    {
        Schema::table('student_class_enrollments', function (Blueprint $table) {
            // Adicionar índice único em student_id para garantir que cada aluno
            // só pode ter uma inscrição em turma por vez
            $table->unique('student_id', 'unique_student_enrollment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_class_enrollments', function (Blueprint $table) {
            $table->dropUnique('unique_student_enrollment');
        });
    }
};
