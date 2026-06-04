<?php

namespace App\Filament\Comando\Resources\EvaluationResource\Pages;

use App\Filament\Comando\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvaluation extends EditRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
