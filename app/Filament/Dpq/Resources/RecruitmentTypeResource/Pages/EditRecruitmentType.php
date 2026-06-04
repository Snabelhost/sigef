<?php

namespace App\Filament\Dpq\Resources\RecruitmentTypeResource\Pages;

use App\Filament\Dpq\Resources\RecruitmentTypeResource;
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
