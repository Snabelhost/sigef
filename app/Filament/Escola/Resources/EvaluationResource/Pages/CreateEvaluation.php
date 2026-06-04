<?php

namespace App\Filament\Escola\Resources\EvaluationResource\Pages;

use App\Filament\Escola\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEvaluation extends CreateRecord
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = EvaluationResource::class;
}
