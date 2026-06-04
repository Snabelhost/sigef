<?php

namespace App\Filament\Dpq\Resources;

use App\Filament\Dpq\Resources\SelectionTestResource\Pages;
use App\Models\SelectionTest;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SelectionTestResource extends Resource
{
    protected static ?string $model = SelectionTest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-clipboard-document-check';
    protected static ?string $modelLabel = 'Teste de Seleção';
    protected static ?string $pluralModelLabel = 'Testes de Seleção';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Dados do Teste')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Teste')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\Select::make('test_type')
                            ->label('Tipo de Teste')
                            ->options([
                                'fisico' => 'Físico',
                                'psicologico' => 'Psicológico',
                                'medico' => 'Médico',
                                'escrito' => 'Escrito',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('max_score')
                            ->label('Pontuação Máxima')
                            ->numeric()
                            ->default(100),
                        Forms\Components\TextInput::make('passing_score')
                            ->label('Pontuação Mínima')
                            ->numeric()
                            ->default(50),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ])->columns(2),
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('test_type')
                    ->label('Tipo'),
                Tables\Columns\TextColumn::make('max_score')
                    ->label('Pontuação Máx.'),
                Tables\Columns\TextColumn::make('passing_score')
                    ->label('Pontuação Mín.'),
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
    \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSelectionTests::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:SelectionTest') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
