<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (! Schema::hasColumn('classes', 'capacity')) {
                $table->unsignedInteger('capacity')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'capacity')) {
                $table->dropColumn('capacity');
            }
        });
    }
};
