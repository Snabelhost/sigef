<?php

namespace App\Filament\Escola\Resources\AcademicYearResource\Pages;

use App\Filament\Escola\Resources\AcademicYearResource;
use Filament\Resources\Pages\ListRecords;

class ListAcademicYears extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = AcademicYearResource::class;
}
