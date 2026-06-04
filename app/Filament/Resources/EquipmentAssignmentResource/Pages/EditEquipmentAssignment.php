<?php

namespace App\Filament\Resources\EquipmentAssignmentResource\Pages;

use App\Filament\Resources\EquipmentAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentAssignment extends EditRecord
{
    protected static string $resource = EquipmentAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
