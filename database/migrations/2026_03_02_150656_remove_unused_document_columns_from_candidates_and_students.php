<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'registro_criminal')) {
                $table->dropColumn('registro_criminal');
            }
            if (Schema::hasColumn('candidates', 'carta_conducao')) {
                $table->dropColumn('carta_conducao');
            }
            if (Schema::hasColumn('candidates', 'passaporte')) {
                $table->dropColumn('passaporte');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'carta_conducao')) {
                $table->dropColumn('carta_conducao');
            }
            if (Schema::hasColumn('students', 'passaporte')) {
                $table->dropColumn('passaporte');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('registro_criminal')->nullable();
            $table->string('carta_conducao')->nullable();
            $table->string('passaporte')->nullable();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('carta_conducao')->nullable();
            $table->string('passaporte')->nullable();
        });
    }
};
