<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;
}
