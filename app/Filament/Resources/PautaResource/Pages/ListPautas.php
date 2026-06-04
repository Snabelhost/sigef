<?php

namespace App\Filament\Resources\PautaResource\Pages;

use App\Filament\Resources\PautaResource;
use Filament\Resources\Pages\ListRecords;

class ListPautas extends ListRecords
{
    protected static string $resource = PautaResource::class;

    public function getTitle(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
