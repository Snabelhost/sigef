<?php

namespace App\Filament\Resources\Shield\RoleResource\Pages;

use App\Filament\Resources\Shield\RoleResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected static ?string $title = 'Visualizar Acesso';

    protected static ?string $breadcrumb = '';

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return RoleResource::formDataWithPermissionState($data, $this->getRecord());
    }
}
