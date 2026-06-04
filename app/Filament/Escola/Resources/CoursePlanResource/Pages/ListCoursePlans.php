<?php

namespace App\Filament\Escola\Resources\CoursePlanResource\Pages;

use App\Filament\Escola\Resources\CoursePlanResource;
use Filament\Resources\Pages\ListRecords;

class ListCoursePlans extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = CoursePlanResource::class;
}
