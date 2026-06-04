<?php

namespace App\Filament\Resources\StudentLeaveResource\Pages;

use App\Filament\Resources\StudentLeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentLeave extends EditRecord
{
    protected static string $resource = StudentLeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
