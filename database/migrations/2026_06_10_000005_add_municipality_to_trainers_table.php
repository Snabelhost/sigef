<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trainers')) {
            return;
        }

        Schema::table('trainers', function (Blueprint $table): void {
            if (! Schema::hasColumn('trainers', 'municipality')) {
                $table->string('municipality')->nullable()->after('province');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('trainers') || ! Schema::hasColumn('trainers', 'municipality')) {
            return;
        }

        Schema::table('trainers', function (Blueprint $table): void {
            $table->dropColumn('municipality');
        });
    }
};
