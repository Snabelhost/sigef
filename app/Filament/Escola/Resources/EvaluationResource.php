<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\EvaluationResource\Pages;

class EvaluationResource extends \App\Filament\Resources\EvaluationResource
{
    protected static bool $shouldSkipAuthorization = false;
    protected static bool $isScopedToTenant = false;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluations::route('/'),
        ];
    }
}
