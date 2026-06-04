<?php

namespace App\Filament\Resources\RecruitmentTypeResource\Pages;

use App\Filament\Resources\RecruitmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentTypes extends ListRecords
{
    protected static string $resource = RecruitmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
