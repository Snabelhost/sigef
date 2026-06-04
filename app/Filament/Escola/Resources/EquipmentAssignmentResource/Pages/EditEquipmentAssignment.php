<?php

namespace App\Filament\Escola\Resources\EquipmentAssignmentResource\Pages;

use App\Filament\Escola\Resources\EquipmentAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentAssignment extends EditRecord
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = EquipmentAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
