<?php

namespace App\Filament\Resources\CourseMapResource\Pages;

use App\Filament\Resources\CourseMapResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseMap extends CreateRecord
{
    protected static string $resource = CourseMapResource::class;

    public function getTitle(): string
    {
        return 'Criar Mapa de Curso';
    }
}
