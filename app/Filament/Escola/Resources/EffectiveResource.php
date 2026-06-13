<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\EffectiveResource\Pages;

class EffectiveResource extends \App\Filament\Resources\EffectiveResource
{
    protected static bool $shouldSkipAuthorization = false;
    protected static bool $isScopedToTenant = false;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEffectives::route('/'),
        ];
    }
}
