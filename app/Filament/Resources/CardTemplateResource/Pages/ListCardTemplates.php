<?php

namespace App\Filament\Resources\CardTemplateResource\Pages;

use App\Filament\Resources\CardTemplateResource;
use Filament\Resources\Pages\ListRecords;

class ListCardTemplates extends ListRecords
{
    protected static string $resource = CardTemplateResource::class;

    public function getTitle(): string
    {
        return 'Cartões';
    }
}
