<?php

namespace App\Filament\Escola\Resources\CourseResource\Pages;

use App\Filament\Escola\Resources\CourseResource;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = CourseResource::class;
}
