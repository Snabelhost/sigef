<?php

namespace App\Filament\Resources\EquipmentAssignmentResource\Pages;

use App\Filament\Resources\EquipmentAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentAssignments extends ListRecords
{
    protected static string $resource = EquipmentAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction removido conforme solicitação
        ];
    }
}
