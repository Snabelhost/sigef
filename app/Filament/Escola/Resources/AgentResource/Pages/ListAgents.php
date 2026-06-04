<?php

namespace App\Filament\Escola\Resources\AgentResource\Pages;

use App\Filament\Escola\Resources\AgentResource;
use Filament\Resources\Pages\ListRecords;

class ListAgents extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = AgentResource::class;
}
