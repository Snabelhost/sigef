<?php

namespace App\Filament\Comando\Resources\StudentResource\Pages;

use App\Filament\Comando\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;
}
