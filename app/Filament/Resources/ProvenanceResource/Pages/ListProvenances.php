<?php

namespace App\Filament\Resources\ProvenanceResource\Pages;

use App\Filament\Resources\ProvenanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProvenances extends ListRecords
{
    protected static string $resource = ProvenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Proveniência'),
        ];
    }

    public function getTitle(): string
    {
        return 'Proveniências';
    }
}
