<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add performance indexes
     */
    public function up(): void
    {
        // Índices para tabela students
        Schema::table('students', function (Blueprint $table) {
            if (!$this->hasIndex('students', 'students_student_type_index')) {
                $table->index('student_type');
            }
            if (!$this->hasIndex('students', 'students_institution_id_index')) {
                $table->index('institution_id');
            }
            if (!$this->hasIndex('students', 'students_candidate_id_index')) {
                $table->index('candidate_id');
            }
            if (!$this->hasIndex('students', 'students_status_index')) {
                $table->index('status');
            }
            if (!$this->hasIndex('students', 'students_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Índices para tabela candidates
        Schema::table('candidates', function (Blueprint $table) {
            // Verificar se as colunas existem antes de criar índices
            if (Schema::hasColumn('candidates', 'student_type') && !$this->hasIndex('candidates', 'candidates_student_type_index')) {
                $table->index('student_type');
            }
            if (Schema::hasColumn('candidates', 'institution_id') && !$this->hasIndex('candidates', 'candidates_institution_id_index')) {
                $table->index('institution_id');
            }
            if (Schema::hasColumn('candidates', 'created_at') && !$this->hasIndex('candidates', 'candidates_created_at_index')) {
                $table->index('created_at');
            }
        });

        // Índices para tabela student_class_enrollments
        Schema::table('student_class_enrollments', function (Blueprint $table) {
            if (!$this->hasIndex('student_class_enrollments', 'sce_student_id_index')) {
                $table->index('student_id', 'sce_student_id_index');
            }
            if (!$this->hasIndex('student_class_enrollments', 'sce_class_id_index')) {
                $table->index('class_id', 'sce_class_id_index');
            }
            if (!$this->hasIndex('student_class_enrollments', 'sce_is_active_index')) {
                $table->index('is_active', 'sce_is_active_index');
            }
        });

        // Índices para tabela student_subject_enrollments
        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            if (!$this->hasIndex('student_subject_enrollments', 'sse_student_id_index')) {
                $table->index('student_id', 'sse_student_id_index');
            }
            if (!$this->hasIndex('student_subject_enrollments', 'sse_subject_id_index')) {
                $table->index('subject_id', 'sse_subject_id_index');
            }
        });

        // Índices para tabela trainers
        Schema::table('trainers', function (Blueprint $table) {
            if (!$this->hasIndex('trainers', 'trainers_institution_id_index')) {
                $table->index('institution_id');
            }
            if (!$this->hasIndex('trainers', 'trainers_is_active_index')) {
                $table->index('is_active');
            }
        });
    }

    /**
     * Check if index exists
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['student_type']);
            $table->dropIndex(['institution_id']);
            $table->dropIndex(['candidate_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['student_type']);
            $table->dropIndex(['institution_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('student_class_enrollments', function (Blueprint $table) {
            $table->dropIndex('sce_student_id_index');
            $table->dropIndex('sce_class_id_index');
            $table->dropIndex('sce_is_active_index');
        });

        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            $table->dropIndex('sse_student_id_index');
            $table->dropIndex('sse_subject_id_index');
        });

        Schema::table('trainers', function (Blueprint $table) {
            $table->dropIndex(['institution_id']);
            $table->dropIndex(['is_active']);
        });
    }
};
