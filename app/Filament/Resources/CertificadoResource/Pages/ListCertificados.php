<?php

namespace App\Filament\Resources\CertificadoResource\Pages;

use App\Filament\Resources\CertificadoResource;
use Filament\Resources\Pages\ListRecords;

class ListCertificados extends ListRecords
{
    protected static string $resource = CertificadoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
