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
        Schema::table('candidates', function (Blueprint $table) {
            $table->index('full_name');
            $table->index('status');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index('status');
            $table->index('student_type');
        });

        Schema::table('trainers', function (Blueprint $table) {
            $table->index('full_name');
            $table->index('is_active');
            $table->index('trainer_type');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->index('name');
            $table->index('acronym');
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->index('evaluation_type');
            $table->index('score');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['full_name']);
            $table->dropIndex(['status']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['student_type']);
        });

        Schema::table('trainers', function (Blueprint $table) {
            $table->dropIndex(['full_name']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['trainer_type']);
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['acronym']);
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropIndex(['evaluation_type']);
            $table->dropIndex(['score']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['is_active']);
        });
    }
};
