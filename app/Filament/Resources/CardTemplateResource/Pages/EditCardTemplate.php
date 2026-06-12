<?php

namespace App\Filament\Resources\CardTemplateResource\Pages;

use App\Filament\Resources\CardTemplateResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditCardTemplate extends EditRecord
{
    protected static string $resource = CardTemplateResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (Filament::getCurrentPanel()?->getId() === 'escola') {
            $data['institution_id'] = Filament::getTenant()?->id;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Salvar')
            ->icon('heroicon-o-check')
            ->color('primary')
            ->extraAttributes(['class' => 'sigef-card-template-form-action'], true);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->extraAttributes(['class' => 'sigef-card-template-form-action sigef-card-template-cancel-action'], true);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
