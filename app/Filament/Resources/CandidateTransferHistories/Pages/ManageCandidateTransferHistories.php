<?php

namespace App\Filament\Resources\CandidateTransferHistories\Pages;

use App\Filament\Resources\CandidateTransferHistories\CandidateTransferHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCandidateTransferHistories extends ManageRecords
{
    protected static string $resource = CandidateTransferHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
