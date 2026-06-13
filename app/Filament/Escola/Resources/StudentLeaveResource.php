<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\StudentLeaveResource\Pages;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class StudentLeaveResource extends \App\Filament\Resources\StudentLeaveResource
{
    protected static bool $shouldSkipAuthorization = false;
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                Filament::getTenant()?->id,
                fn (Builder $query, int $institutionId): Builder => $query->whereHas(
                    'student',
                    fn (Builder $studentQuery): Builder => $studentQuery->where('institution_id', $institutionId),
                ),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentLeaves::route('/'),
        ];
    }
}
