<?php

namespace App\Filament\Escola\Resources\StudentClassResource\Pages;

use App\Filament\Escola\Resources\StudentClassResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentClass extends CreateRecord
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = StudentClassResource::class;
}
