<?php

namespace App\Filament\Escola\Resources\CoursePhaseResource\Pages;

use App\Filament\Escola\Resources\CoursePhaseResource;
use Filament\Resources\Pages\ListRecords;

class ListCoursePhases extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = CoursePhaseResource::class;
}
