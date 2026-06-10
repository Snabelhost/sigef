<?php

namespace App\Filament\Resources\CardTemplateResource\Pages;

use App\Filament\Resources\CardTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateCardTemplate extends CreateRecord
{
    protected static string $resource = CardTemplateResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Criar')
            ->icon('heroicon-o-check')
            ->color('primary')
            ->extraAttributes(['class' => 'sigef-card-template-form-action'], true);
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Salvar e criar outro')
            ->icon('heroicon-o-plus-circle')
            ->color('gray')
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
}
