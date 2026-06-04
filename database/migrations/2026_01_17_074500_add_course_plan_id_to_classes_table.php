<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'course_plan_id')) {
                $table->foreignId('course_plan_id')->nullable()->after('course_map_id')->constrained('course_plans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'course_plan_id')) {
                $table->dropForeign(['course_plan_id']);
                $table->dropColumn('course_plan_id');
            }
        });
    }
};
