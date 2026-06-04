<?php

namespace App\Filament\Resources\Shield\RoleResource\Pages;

use App\Filament\Resources\Shield\RoleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return RoleResource::formDataWithPermissionState($data, $this->getRecord());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return RoleResource::stripPermissionFormData($data);
    }

    protected function afterSave(): void
    {
        RoleResource::syncPermissionsFromFormState($this->getRecord(), $this->form->getRawState());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Role $record): bool => in_array($record->name, array_filter([
                    config('filament-shield.super_admin.name'),
                    config('filament-shield.panel_user.name'),
                ]), true)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->icon('heroicon-o-check')
            ->label('Salvar')
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
