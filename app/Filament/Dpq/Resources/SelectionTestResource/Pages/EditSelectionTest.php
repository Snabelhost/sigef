<?php

namespace App\Filament\Dpq\Resources\SelectionTestResource\Pages;

use App\Filament\Dpq\Resources\SelectionTestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSelectionTest extends EditRecord
{
    protected static string $resource = SelectionTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
