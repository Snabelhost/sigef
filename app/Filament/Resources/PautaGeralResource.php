<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PautaGeralResource\Pages;
use App\Models\StudentClass;
use Filament\Resources\Resource;

class PautaGeralResource extends Resource
{
    protected static ?string $model = StudentClass::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-document-duplicate';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão do Centro';
    protected static ?string $navigationLabel = 'Pauta Geral';
    protected static ?string $modelLabel = 'Pauta Geral';
    protected static ?string $pluralModelLabel = 'Pautas Gerais';
    protected static ?int $navigationSort = 11;
    protected static ?string $slug = 'pauta-geral';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPautaGeral::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Pauta') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
