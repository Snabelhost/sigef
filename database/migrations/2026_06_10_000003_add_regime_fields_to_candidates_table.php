<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidates')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table): void {
            if (! Schema::hasColumn('candidates', 'staff_type')) {
                $table->string('staff_type', 40)->default('regime_geral')->after('institution_id');
            }

            if (! Schema::hasColumn('candidates', 'blood_type')) {
                $table->string('blood_type', 10)->nullable()->after('gender');
            }

            if (! Schema::hasColumn('candidates', 'country')) {
                $table->string('country')->nullable()->after('blood_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('candidates')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table): void {
            foreach (['country', 'blood_type', 'staff_type'] as $column) {
                if (Schema::hasColumn('candidates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
