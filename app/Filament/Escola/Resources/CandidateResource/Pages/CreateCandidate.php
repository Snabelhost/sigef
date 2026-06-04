<?php

namespace App\Filament\Escola\Resources\CandidateResource\Pages;

use App\Filament\Escola\Resources\CandidateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCandidate extends CreateRecord
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = CandidateResource::class;
}
