<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aumentar tamanho da coluna student_type na tabela students
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_type', 100)->nullable()->change();
        });

        // Aumentar tamanho da coluna student_type na tabela student_class_enrollments
        Schema::table('student_class_enrollments', function (Blueprint $table) {
            $table->string('student_type', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_type', 50)->nullable()->change();
        });

        Schema::table('student_class_enrollments', function (Blueprint $table) {
            $table->string('student_type', 50)->nullable()->change();
        });
    }
};
