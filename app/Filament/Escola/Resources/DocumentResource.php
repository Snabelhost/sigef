<?php

namespace App\Filament\Escola\Resources;

use App\Models\Document;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class DocumentResource extends Resource
{
    protected static bool $shouldSkipAuthorization = false;

    protected static ?string $model = Document::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';
    protected static string|\UnitEnum|null $navigationGroup = 'Documentos';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Documentos';
    protected static ?string $modelLabel = 'Documento';
    protected static ?string $pluralModelLabel = 'Documentos';

    public static function getEloquentQuery(): Builder
    {
        $userId = Auth::id();

        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where(function (Builder $query) use ($userId) {
                $query->where('sender_user_id', $userId)
                    ->orWhereHas('recipients', fn(Builder $q) => $q->where('user_id', $userId));
            });
    }

    public static function form(Schema $form): Schema
    {
        return \App\Filament\Resources\DocumentResource::form($form);
    }

    public static function infolist(Schema $schema): Schema
    {
        return \App\Filament\Resources\DocumentResource::infolist($schema);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\DocumentResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Escola\Resources\DocumentResource\Pages\ListDocuments::route('/'),
            'create' => \App\Filament\Escola\Resources\DocumentResource\Pages\CreateDocument::route('/create'),
            'view' => \App\Filament\Escola\Resources\DocumentResource\Pages\ViewDocument::route('/{record}'),
            'edit' => \App\Filament\Escola\Resources\DocumentResource\Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?\Closure $modifyQuery = null): ?\Illuminate\Database\Eloquent\Model
    {
        $userId = Auth::id();

        return Document::withoutGlobalScopes()
            ->where(function (Builder $query) use ($userId) {
                $query->where('sender_user_id', $userId)
                    ->orWhereHas('recipients', fn(Builder $q) => $q->where('user_id', $userId));
            })
            ->find($key);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return null;
            }
            $unreadCount = \App\Models\DocumentRecipient::where('user_id', $userId)
                ->where('status', 'pending')
                ->count();
            return $unreadCount > 0 ? (string) $unreadCount : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Document') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
