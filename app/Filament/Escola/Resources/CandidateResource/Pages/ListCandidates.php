<?php

namespace App\Filament\Escola\Resources\CandidateResource\Pages;

use App\Filament\Escola\Resources\CandidateResource;
use Filament\Resources\Pages\ListRecords;

class ListCandidates extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = CandidateResource::class;
}
