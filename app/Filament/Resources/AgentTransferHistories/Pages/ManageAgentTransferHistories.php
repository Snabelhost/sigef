<?php

namespace App\Filament\Resources\AgentTransferHistories\Pages;

use App\Filament\Resources\AgentTransferHistories\AgentTransferHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAgentTransferHistories extends ManageRecords
{
    protected static string $resource = AgentTransferHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
