<?php

namespace App\Filament\Escola\Resources\TrainerResource\Pages;

use App\Filament\Escola\Resources\TrainerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainer extends CreateRecord
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = TrainerResource::class;
}
