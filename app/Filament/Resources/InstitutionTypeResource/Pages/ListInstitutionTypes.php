<?php

namespace App\Filament\Resources\InstitutionTypeResource\Pages;

use App\Filament\Resources\InstitutionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstitutionTypes extends ListRecords
{
    protected static string $resource = InstitutionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
