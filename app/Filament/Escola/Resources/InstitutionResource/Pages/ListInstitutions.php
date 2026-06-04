<?php

namespace App\Filament\Escola\Resources\InstitutionResource\Pages;

use App\Filament\Escola\Resources\InstitutionResource;
use Filament\Resources\Pages\ListRecords;

class ListInstitutions extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = InstitutionResource::class;
}
