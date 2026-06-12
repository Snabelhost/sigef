<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects') || Schema::hasColumn('subjects', 'course_id')) {
            return;
        }

        Schema::table('subjects', function (Blueprint $table): void {
            $table->foreignId('course_id')
                ->nullable()
                ->after('institution_id')
                ->constrained('courses')
                ->nullOnDelete();
        });

        DB::table('subjects')
            ->leftJoin('course_phases', 'course_phases.id', '=', 'subjects.course_phase_id')
            ->whereNotNull('subjects.course_phase_id')
            ->whereNotNull('course_phases.course_id')
            ->update(['subjects.course_id' => DB::raw('course_phases.course_id')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasColumn('subjects', 'course_id')) {
            return;
        }

        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
