<?php

namespace App\Filament\Escola\Resources\CertificadoResource\Pages;

use App\Filament\Escola\Resources\CertificadoResource;
use Filament\Resources\Pages\ListRecords;

class ListCertificados extends ListRecords
{
    protected static string $resource = CertificadoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
