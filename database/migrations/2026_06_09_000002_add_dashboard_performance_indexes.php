<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('candidates', ['institution_id', 'student_type', 'created_at'], 'idx_candidates_dashboard_filters');
        $this->addIndex('students', ['institution_id', 'course_map_id', 'student_type', 'created_at'], 'idx_students_dashboard_filters');
        $this->addIndex('student_leaves', ['leave_type', 'created_at'], 'idx_student_leaves_type_date');
        $this->addIndex('student_leaves', ['student_id', 'leave_type'], 'idx_student_leaves_student_type');
        $this->addIndex('course_maps', ['institution_id', 'course_id'], 'idx_course_maps_institution_course');
        $this->addIndex('subjects', ['institution_id', 'course_phase_id'], 'idx_subjects_institution_phase');
        $this->addIndex('trainers', ['institution_id', 'is_active'], 'idx_trainers_institution_active');
    }

    public function down(): void
    {
        $this->dropIndex('trainers', 'idx_trainers_institution_active');
        $this->dropIndex('subjects', 'idx_subjects_institution_phase');
        $this->dropIndex('course_maps', 'idx_course_maps_institution_course');
        $this->dropIndex('student_leaves', 'idx_student_leaves_student_type');
        $this->dropIndex('student_leaves', 'idx_student_leaves_type_date');
        $this->dropIndex('students', 'idx_students_dashboard_filters');
        $this->dropIndex('candidates', 'idx_candidates_dashboard_filters');
    }

    private function addIndex(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table) || $this->hasIndex($table, $index)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->index($columns, $index);
        });
    }

    private function dropIndex(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
