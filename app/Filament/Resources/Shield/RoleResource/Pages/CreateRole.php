<?php

namespace App\Filament\Resources\Shield\RoleResource\Pages;

use App\Filament\Resources\Shield\RoleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected static ?string $title = '';

    protected static ?string $breadcrumb = '';

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return RoleResource::stripPermissionFormData($data);
    }

    protected function afterCreate(): void
    {
        RoleResource::syncPermissionsFromFormState($this->record, $this->form->getRawState());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->icon('heroicon-o-check')
            ->label('Criar')
            ->color('primary');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->icon('heroicon-o-plus-circle')
            ->label('Salvar e criar outro')
            ->color('primary');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->icon('heroicon-o-x-mark')
            ->label('Cancelar')
            ->color('danger');
    }
}
