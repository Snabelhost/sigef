<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_maps', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('organ');
            $table->date('end_date')->nullable()->after('start_date');
            
            // Tornar academic_year_id opcional
            $table->foreignId('academic_year_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_maps', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
