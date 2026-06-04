<?php

namespace App\Filament\Escola\Resources\TrainerClassAssignmentResource\Pages;

use App\Filament\Escola\Resources\TrainerClassAssignmentResource;
use Filament\Resources\Pages\ListRecords;

class ListTrainerClassAssignments extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = TrainerClassAssignmentResource::class;
}
