<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('recruitment_type_id')->nullable()->change();
            $table->unsignedBigInteger('academic_year_id')->nullable()->change();
            $table->string('education_level')->nullable()->change();
            $table->string('education_area')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('recruitment_type_id')->nullable(false)->change();
            $table->unsignedBigInteger('academic_year_id')->nullable(false)->change();
            $table->string('education_level')->nullable(false)->change();
            $table->string('education_area')->nullable(false)->change();
        });
    }
};
