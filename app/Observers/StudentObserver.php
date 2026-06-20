<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\StudentType;

class StudentObserver
{
    /**
     * Handle the Student "creating" event.
     */
    public function creating(Student $student): void
    {
        $this->syncStudentTypeId($student);
    }

    /**
     * Handle the Student "updating" event.
     */
    public function updating(Student $student): void
    {
        if ($student->isDirty('student_type')) {
            $this->syncStudentTypeId($student);
        }
    }

    /**
     * Handle the Student "updated" event.
     */
    public function updated(Student $student): void
    {
        if (blank($student->institution_id)) {
            return;
        }

        $this->syncInstitutionReferences($student);
    }

    private function syncStudentTypeId(Student $student): void
    {
        if (filled($student->student_type)) {
            $student->student_type_id = StudentType::getIdByName($student->student_type);
        }
    }

    /**
     * Keep dependent student records aligned with the student's current school.
     */
    private function syncInstitutionReferences(Student $student): void
    {
        $institutionId = (int) $student->institution_id;

        if ($student->candidate_id) {
            $student->candidate()->update([
                'institution_id' => $institutionId,
                'student_type' => $student->student_type,
            ]);
        }

        $student->evaluations()->update(['institution_id' => $institutionId]);
        $student->leaves()->update(['institution_id' => $institutionId]);
        $student->attendances()->update(['institution_id' => $institutionId]);
        $student->equipmentAssignments()->update(['institution_id' => $institutionId]);
    }
}
