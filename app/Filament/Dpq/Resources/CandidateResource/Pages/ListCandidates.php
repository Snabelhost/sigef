<?php

namespace App\Filament\Dpq\Resources\CandidateResource\Pages;

use App\Filament\Dpq\Resources\CandidateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCandidates extends ListRecords
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
