<?php

namespace App\Filament\Resources\CourseMapResource\Pages;

use App\Filament\Resources\CourseMapResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseMap extends EditRecord
{
    protected static string $resource = CourseMapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar'),
        ];
    }

    public function getTitle(): string
    {
        return 'Editar Mapa de Curso';
    }
}
