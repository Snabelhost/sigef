<?php

namespace App\Filament\Resources\StudentTransferHistories\Pages;

use App\Filament\Resources\StudentTransferHistories\StudentTransferHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentTransferHistories extends ManageRecords
{
    protected static string $resource = StudentTransferHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
