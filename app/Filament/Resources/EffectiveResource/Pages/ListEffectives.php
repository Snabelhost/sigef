<?php

namespace App\Filament\Resources\EffectiveResource\Pages;

use App\Filament\Resources\EffectiveResource;
use Filament\Resources\Pages\ListRecords;

class ListEffectives extends ListRecords
{
    protected static string $resource = EffectiveResource::class;

    public function getTitle(): string
    {
        return 'Efectivos';
    }
}
