<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\EquipmentAssignmentResource\Pages;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class EquipmentAssignmentResource extends \App\Filament\Resources\EquipmentAssignmentResource
{
    protected static bool $shouldSkipAuthorization = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                Filament::getTenant()?->id,
                fn (Builder $query, int $institutionId): Builder => $query->where('institution_id', $institutionId)
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipmentAssignments::route('/'),
        ];
    }
}
