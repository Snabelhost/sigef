<?php

namespace App\Filament\Escola\Resources\EquipmentAssignmentResource\Pages;

use App\Filament\Escola\Resources\EquipmentAssignmentResource;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentAssignments extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = EquipmentAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction removido conforme solicitação
        ];
    }
}
