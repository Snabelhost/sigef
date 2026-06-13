<?php

namespace App\Filament\Professores\Pages;

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
}
