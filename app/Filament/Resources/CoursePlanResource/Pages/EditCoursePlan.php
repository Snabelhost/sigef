<?php

namespace App\Filament\Resources\CoursePlanResource\Pages;

use App\Filament\Resources\CoursePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCoursePlan extends EditRecord
{
    protected static string $resource = CoursePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
