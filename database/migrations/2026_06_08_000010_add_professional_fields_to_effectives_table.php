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
            if (! Schema::hasColumn('effectives', 'education_level')) {
                $table->string('education_level')->nullable()->after('position');
            }

            if (! Schema::hasColumn('effectives', 'situation')) {
                $table->string('situation')->nullable()->after('education_level');
            }

            if (! Schema::hasColumn('effectives', 'specialization')) {
                $table->string('specialization')->nullable()->after('situation');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('effectives')) {
            return;
        }

        Schema::table('effectives', function (Blueprint $table): void {
            foreach (['specialization', 'situation', 'education_level'] as $column) {
                if (Schema::hasColumn('effectives', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
