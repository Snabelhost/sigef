<?php

namespace App\Filament\Escola\Resources\CourseMapResource\Pages;

use App\Filament\Escola\Resources\CourseMapResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseMaps extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = CourseMapResource::class;
}
