<?php

namespace App\Filament\Dpq\Resources\InstitutionResource\Pages;

use App\Filament\Dpq\Resources\InstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstitution extends EditRecord
{
    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
