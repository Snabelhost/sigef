<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\PautaResource\Pages;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class PautaResource extends \App\Filament\Resources\PautaResource
{
    protected static bool $shouldSkipAuthorization = false;
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                Filament::getTenant()?->id,
                fn (Builder $query, int $institutionId): Builder => $query->where('institution_id', $institutionId),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\MiniPauta::route('/'),
            'list' => Pages\ListPautas::route('/list'),
            'pauta-geral' => Pages\PautaGeral::route('/pauta-geral'),
        ];
    }
}
