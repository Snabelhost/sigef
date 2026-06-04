<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\StudentType;

class StudentObserver
{
    /**
     * Handle the Student "creating" event.
     * Sincroniza student_type_id com student_type antes de criar
     */
    public function creating(Student $student): void
    {
        $this->syncStudentTypeId($student);
    }

    /**
     * Handle the Student "updating" event.
     * Sincroniza student_type_id com student_type antes de atualizar
     */
    public function updating(Student $student): void
    {
        // Só sincroniza se o student_type foi alterado
        if ($student->isDirty('student_type')) {
            $this->syncStudentTypeId($student);
        }
    }

    /**
     * Sincroniza o student_type_id com base no student_type (string)
     */
    private function syncStudentTypeId(Student $student): void
    {
        if (!empty($student->student_type)) {
            // Buscar ou criar o tipo de aluno
            $studentTypeId = StudentType::getIdByName($student->student_type);
            $student->student_type_id = $studentTypeId;
        }
    }
}
