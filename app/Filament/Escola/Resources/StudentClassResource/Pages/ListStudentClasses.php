<?php

namespace App\Filament\Escola\Resources\StudentClassResource\Pages;

use App\Filament\Escola\Resources\StudentClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentClasses extends ListRecords
{
    protected static string $resource = StudentClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('Nova Turma'),
        ];
    }

    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }
}
