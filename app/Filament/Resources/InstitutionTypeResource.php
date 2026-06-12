<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstitutionTypeResource\Pages;
use App\Filament\Resources\InstitutionTypeResource\RelationManagers;
use App\Models\InstitutionType;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class InstitutionTypeResource extends Resource
{
    protected static ?string $model = InstitutionType::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-squares-2x2';
    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Tipos de Instituição';
    protected static ?string $modelLabel = 'Tipo de Instituição';
    protected static ?string $pluralModelLabel = 'Tipos de Instituição';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('institutions');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(191),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('institutions_count')
                    ->label('Instituicoes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalSubmitAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Registo criado com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                \Filament\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->modalSubmitAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                    ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Registo atualizado com sucesso!'),
                \Filament\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->before(function (InstitutionType $record, \Filament\Actions\DeleteAction $action): void {
                        $institutionsCount = (int) ($record->institutions_count ?? $record->institutions()->count());

                        if ($institutionsCount === 0) {
                            return;
                        }

                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Nao e possivel excluir')
                            ->body("Este tipo de instituicao esta vinculado a {$institutionsCount} instituicao(oes). Altere ou remova essas instituicoes primeiro.")
                            ->persistent()
                            ->send();

                        $action->cancel();
                    }),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->action(function (\Filament\Actions\DeleteBulkAction $action, Collection $records): void {
                            $records->loadCount('institutions');

                            $blocked = $records->filter(fn (InstitutionType $record): bool => (int) $record->institutions_count > 0);
                            $deletable = $records->reject(fn (InstitutionType $record): bool => (int) $record->institutions_count > 0);

                            $deletable->each(fn (InstitutionType $record): bool|null => $record->delete());

                            if ($blocked->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Alguns registos nao foram excluidos')
                                    ->body($blocked->count() . ' tipo(s) de instituicao possuem instituicoes vinculadas. Altere ou remova essas instituicoes primeiro.')
                                    ->persistent()
                                    ->send();
                            }

                            if ($deletable->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title($deletable->count() . ' tipo(s) de instituicao excluido(s) com sucesso.')
                                    ->send();
                            }

                            if ($blocked->isNotEmpty() && $deletable->isEmpty()) {
                                $action->failure();

                                return;
                            }

                            $action->success();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutionTypes::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:InstitutionType') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}



