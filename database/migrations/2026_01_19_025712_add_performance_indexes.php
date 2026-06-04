<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Índices para Students - tabela mais acessada
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'institution_id') && !$this->hasIndex('students', 'idx_students_institution')) {
                $table->index('institution_id', 'idx_students_institution');
            }
            if (Schema::hasColumn('students', 'student_type') && !$this->hasIndex('students', 'idx_students_type')) {
                $table->index('student_type', 'idx_students_type');
            }
            if (Schema::hasColumn('students', 'candidate_id') && !$this->hasIndex('students', 'idx_students_candidate')) {
                $table->index('candidate_id', 'idx_students_candidate');
            }
            if (Schema::hasColumn('students', 'created_at') && !$this->hasIndex('students', 'idx_students_created')) {
                $table->index('created_at', 'idx_students_created');
            }
            if (Schema::hasColumn('students', 'institution_id') && Schema::hasColumn('students', 'student_type') && !$this->hasIndex('students', 'idx_students_inst_type')) {
                $table->index(['institution_id', 'student_type'], 'idx_students_inst_type');
            }
        });

        // Índices para Candidates
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'institution_id') && !$this->hasIndex('candidates', 'idx_candidates_institution')) {
                $table->index('institution_id', 'idx_candidates_institution');
            }
            if (Schema::hasColumn('candidates', 'student_type') && !$this->hasIndex('candidates', 'idx_candidates_type')) {
                $table->index('student_type', 'idx_candidates_type');
            }
            if (Schema::hasColumn('candidates', 'full_name') && !$this->hasIndex('candidates', 'idx_candidates_name')) {
                $table->index('full_name', 'idx_candidates_name');
            }
            if (Schema::hasColumn('candidates', 'institution_id') && Schema::hasColumn('candidates', 'student_type') && !$this->hasIndex('candidates', 'idx_candidates_inst_type')) {
                $table->index(['institution_id', 'student_type'], 'idx_candidates_inst_type');
            }
        });

        // Índices para StudentClassEnrollments
        if (Schema::hasTable('student_class_enrollments')) {
            Schema::table('student_class_enrollments', function (Blueprint $table) {
                if (Schema::hasColumn('student_class_enrollments', 'student_id') && !$this->hasIndex('student_class_enrollments', 'idx_enrollments_student')) {
                    $table->index('student_id', 'idx_enrollments_student');
                }
                if (Schema::hasColumn('student_class_enrollments', 'class_id') && !$this->hasIndex('student_class_enrollments', 'idx_enrollments_class')) {
                    $table->index('class_id', 'idx_enrollments_class');
                }
                if (Schema::hasColumn('student_class_enrollments', 'enrolled_at') && !$this->hasIndex('student_class_enrollments', 'idx_enrollments_date')) {
                    $table->index('enrolled_at', 'idx_enrollments_date');
                }
                if (Schema::hasColumn('student_class_enrollments', 'student_id') && Schema::hasColumn('student_class_enrollments', 'class_id') && !$this->hasIndex('student_class_enrollments', 'idx_enrollments_student_class')) {
                    $table->index(['student_id', 'class_id'], 'idx_enrollments_student_class');
                }
            });
        }

        // Índices para StudentSubjectEnrollments
        if (Schema::hasTable('student_subject_enrollments')) {
            Schema::table('student_subject_enrollments', function (Blueprint $table) {
                if (Schema::hasColumn('student_subject_enrollments', 'student_id') && !$this->hasIndex('student_subject_enrollments', 'idx_subject_enrollments_student')) {
                    $table->index('student_id', 'idx_subject_enrollments_student');
                }
                if (Schema::hasColumn('student_subject_enrollments', 'subject_id') && !$this->hasIndex('student_subject_enrollments', 'idx_subject_enrollments_subject')) {
                    $table->index('subject_id', 'idx_subject_enrollments_subject');
                }
                if (Schema::hasColumn('student_subject_enrollments', 'student_id') && Schema::hasColumn('student_subject_enrollments', 'subject_id') && !$this->hasIndex('student_subject_enrollments', 'idx_subject_enrollments_combo')) {
                    $table->index(['student_id', 'subject_id'], 'idx_subject_enrollments_combo');
                }
            });
        }

        // Índices para Trainers
        Schema::table('trainers', function (Blueprint $table) {
            if (Schema::hasColumn('trainers', 'institution_id') && !$this->hasIndex('trainers', 'idx_trainers_institution')) {
                $table->index('institution_id', 'idx_trainers_institution');
            }
            if (Schema::hasColumn('trainers', 'is_active') && !$this->hasIndex('trainers', 'idx_trainers_active')) {
                $table->index('is_active', 'idx_trainers_active');
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
            if ($this->hasIndex('students', 'idx_students_institution')) $table->dropIndex('idx_students_institution');
            if ($this->hasIndex('students', 'idx_students_type')) $table->dropIndex('idx_students_type');
            if ($this->hasIndex('students', 'idx_students_candidate')) $table->dropIndex('idx_students_candidate');
            if ($this->hasIndex('students', 'idx_students_created')) $table->dropIndex('idx_students_created');
            if ($this->hasIndex('students', 'idx_students_inst_type')) $table->dropIndex('idx_students_inst_type');
        });

        Schema::table('candidates', function (Blueprint $table) {
            if ($this->hasIndex('candidates', 'idx_candidates_institution')) $table->dropIndex('idx_candidates_institution');
            if ($this->hasIndex('candidates', 'idx_candidates_type')) $table->dropIndex('idx_candidates_type');
            if ($this->hasIndex('candidates', 'idx_candidates_name')) $table->dropIndex('idx_candidates_name');
            if ($this->hasIndex('candidates', 'idx_candidates_inst_type')) $table->dropIndex('idx_candidates_inst_type');
        });

        if (Schema::hasTable('student_class_enrollments')) {
            Schema::table('student_class_enrollments', function (Blueprint $table) {
                if ($this->hasIndex('student_class_enrollments', 'idx_enrollments_student')) $table->dropIndex('idx_enrollments_student');
                if ($this->hasIndex('student_class_enrollments', 'idx_enrollments_class')) $table->dropIndex('idx_enrollments_class');
                if ($this->hasIndex('student_class_enrollments', 'idx_enrollments_date')) $table->dropIndex('idx_enrollments_date');
                if ($this->hasIndex('student_class_enrollments', 'idx_enrollments_student_class')) $table->dropIndex('idx_enrollments_student_class');
            });
        }

        if (Schema::hasTable('student_subject_enrollments')) {
            Schema::table('student_subject_enrollments', function (Blueprint $table) {
                if ($this->hasIndex('student_subject_enrollments', 'idx_subject_enrollments_student')) $table->dropIndex('idx_subject_enrollments_student');
                if ($this->hasIndex('student_subject_enrollments', 'idx_subject_enrollments_subject')) $table->dropIndex('idx_subject_enrollments_subject');
                if ($this->hasIndex('student_subject_enrollments', 'idx_subject_enrollments_combo')) $table->dropIndex('idx_subject_enrollments_combo');
            });
        }

        Schema::table('trainers', function (Blueprint $table) {
            if ($this->hasIndex('trainers', 'idx_trainers_institution')) $table->dropIndex('idx_trainers_institution');
            if ($this->hasIndex('trainers', 'idx_trainers_active')) $table->dropIndex('idx_trainers_active');
        });
    }
};
