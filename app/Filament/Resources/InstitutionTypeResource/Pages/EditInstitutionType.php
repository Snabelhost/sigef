<?php

namespace App\Filament\Resources\InstitutionTypeResource\Pages;

use App\Filament\Resources\InstitutionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstitutionType extends EditRecord
{
    protected static string $resource = InstitutionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
