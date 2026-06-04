<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tornar academic_year_id nullable
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->nullable()->change();
        });
        
        // Definir um valor padrão para registros existentes que têm NULL
        $defaultAcademicYear = DB::table('academic_years')->where('is_active', true)->first();
        if ($defaultAcademicYear) {
            DB::table('candidates')->whereNull('academic_year_id')->update([
                'academic_year_id' => $defaultAcademicYear->id
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->nullable(false)->change();
        });
    }
};
