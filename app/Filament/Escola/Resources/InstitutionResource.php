<?php

namespace App\Filament\Escola\Resources;

use App\Models\Institution;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables;

class InstitutionResource extends Resource
{
    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $model = Institution::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-building-library';
    protected static string|\UnitEnum|null $navigationGroup = 'Instituições';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Instituições';
    protected static ?string $modelLabel = 'Instituição';
    protected static ?string $pluralModelLabel = 'Instituições';

    // Read-only in Escola panel
    protected static bool $isScopedToTenant = false;

    public static function form(Schema $form): Schema
    {
        return \App\Filament\Resources\InstitutionResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('abbreviation')
                    ->label('Sigla')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Escola\Resources\InstitutionResource\Pages\ListInstitutions::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
