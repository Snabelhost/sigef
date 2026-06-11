<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        if (Schema::hasColumn('attendances', 'student_id')) {
            DB::statement('ALTER TABLE attendances MODIFY student_id BIGINT UNSIGNED NULL');
        }

        Schema::table('attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('attendances', 'effective_id')) {
                $table->foreignId('effective_id')
                    ->nullable()
                    ->after('trainer_id')
                    ->constrained('effectives')
                    ->nullOnDelete();

                $table->index(['effective_id', 'date'], 'attendances_effective_date_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            if (Schema::hasColumn('attendances', 'effective_id')) {
                $table->dropForeign(['effective_id']);
                $table->dropIndex('attendances_effective_date_index');
                $table->dropColumn('effective_id');
            }
        });

        if (Schema::hasColumn('attendances', 'student_id')) {
            DB::statement('ALTER TABLE attendances MODIFY student_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
