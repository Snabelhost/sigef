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
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->constrained()->after('student_id');
        });

        Schema::table('student_leaves', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->constrained()->after('student_id');
        });

        Schema::table('equipment_assignments', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->constrained()->after('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn('institution_id');
        });

        Schema::table('student_leaves', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn('institution_id');
        });

        Schema::table('equipment_assignments', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn('institution_id');
        });
    }
};
