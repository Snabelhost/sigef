<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('effectives')) {
            return;
        }

        Schema::table('effectives', function (Blueprint $table): void {
            if (! Schema::hasColumn('effectives', 'municipality')) {
                $table->string('municipality')->nullable()->after('province');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('effectives') || ! Schema::hasColumn('effectives', 'municipality')) {
            return;
        }

        Schema::table('effectives', function (Blueprint $table): void {
            $table->dropColumn('municipality');
        });
    }
};
