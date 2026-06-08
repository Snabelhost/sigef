<?php

namespace App\Filament\Resources\CourseMapResource\Pages;

use App\Filament\Resources\CourseMapResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseMaps extends ListRecords
{
    protected static string $resource = CourseMapResource::class;

    protected function loadTableColumnsFromSession(): array
    {
        return $this->getDefaultTableColumnState();
    }

    protected function getHeaderActions(): array
    {
        return [
            CourseMapResource::makeUnifiedCreateAction('Novo Mapa e Plano de Curso'),
        ];
    }

    public function getTitle(): string
    {
        return 'Mapas e Planos de Curso';
    }
}
