<?php

namespace App\Filament\Escola\Resources\StudentLeaveResource\Pages;

use App\Filament\Escola\Resources\StudentLeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentLeaves extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = StudentLeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
