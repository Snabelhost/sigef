<?php

namespace App\Filament\Comando\Resources\TrainerResource\Pages;

use App\Filament\Comando\Resources\TrainerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainers extends ListRecords
{
    protected static string $resource = TrainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
