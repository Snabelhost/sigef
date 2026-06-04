<?php

namespace App\Filament\Resources\CourseMapResource\Pages;

use App\Filament\Resources\CourseMapResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourseMaps extends ListRecords
{
    protected static string $resource = CourseMapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Mapa de Curso'),
        ];
    }

    public function getTitle(): string
    {
        return 'Mapas de Curso';
    }
}
