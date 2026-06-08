<?php

namespace App\Filament\Resources\CoursePlanResource\Pages;

use App\Filament\Resources\CourseMapResource;
use App\Filament\Resources\CoursePlanResource;
use Filament\Resources\Pages\ListRecords;

class ListCoursePlans extends ListRecords
{
    protected static string $resource = CoursePlanResource::class;

    public function mount(): void
    {
        $this->redirect(CourseMapResource::getUrl('index'), navigate: true);
    }
}
