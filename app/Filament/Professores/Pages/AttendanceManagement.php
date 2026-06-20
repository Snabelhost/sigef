<?php

namespace App\Filament\Professores\Pages;

use App\Models\TrainerClassAssignment;
use Illuminate\Support\Facades\Auth;

class AttendanceManagement extends \App\Filament\Pages\AttendanceManagement
{
    protected static ?string $slug = 'attendance-management';

    protected function attendanceTabs(): array
    {
        return ['students', 'trainers'];
    }

    protected function usesAuthenticatedInstitutionOnly(): bool
    {
        return true;
    }

    protected function authenticatedInstitutionId(): ?int
    {
        $trainer = Auth::user()?->trainer;

        if (! $trainer) {
            return parent::authenticatedInstitutionId();
        }

        $assignmentInstitutionId = TrainerClassAssignment::query()
            ->join('classes', 'classes.id', '=', 'trainer_class_assignments.class_id')
            ->where('trainer_class_assignments.trainer_id', $trainer->getKey())
            ->where('trainer_class_assignments.is_active', true)
            ->whereNotNull('classes.institution_id')
            ->orderByDesc('trainer_class_assignments.updated_at')
            ->value('classes.institution_id');

        if ((int) $assignmentInstitutionId > 0) {
            return (int) $assignmentInstitutionId;
        }

        return parent::authenticatedInstitutionId();
    }
}
