<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\TrainerResource\Pages;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class TrainerResource extends \App\Filament\Resources\TrainerResource
{
    protected static bool $shouldSkipAuthorization = false;
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                Filament::getTenant()?->id,
                fn (Builder $query, int $institutionId): Builder => $query->where(function (Builder $query) use ($institutionId): void {
                    $query
                        ->where('institution_id', $institutionId)
                        ->orWhereHas('institutions', fn (Builder $institutionQuery): Builder => $institutionQuery->where('institutions.id', $institutionId))
                        ->orWhereHas('subjectAuthorizations', fn (Builder $authorizationQuery): Builder => $authorizationQuery->where('institution_id', $institutionId));
                }),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainers::route('/'),
        ];
    }
}
