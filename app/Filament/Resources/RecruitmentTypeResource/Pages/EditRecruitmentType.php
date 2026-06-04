<?php

namespace App\Filament\Resources\RecruitmentTypeResource\Pages;

use App\Filament\Resources\RecruitmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentType extends EditRecord
{
    protected static string $resource = RecruitmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
