<?php

namespace App\Filament\Escola\Resources\EvaluationResource\Pages;

use App\Filament\Escola\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvaluations extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
