<?php

namespace App\Filament\Escola\Resources\SubjectResource\Pages;

use App\Filament\Escola\Resources\SubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubjects extends ListRecords
{
    protected static string $resource = SubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('Nova Disciplina'),
        ];
    }

    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }
}
