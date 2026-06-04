<?php

namespace App\Filament\Comando\Resources;

use App\Filament\Comando\Resources\EvaluationResource\Pages;
use App\Models\Evaluation;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EvaluationResource extends Resource
{
    protected static ?string $model = Evaluation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-document-check';
    protected static ?string $modelLabel = 'Avaliação';
    protected static ?string $pluralModelLabel = 'Avaliações';
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['student.candidate', 'subject']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Dados da Avaliação')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Formando')
                            ->relationship('student', 'student_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->student_number} - {$record->candidate->full_name}")
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('subject_id')
                            ->label('Disciplina')
                            ->relationship('subject', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('evaluation_type')
                            ->label('Tipo de Avaliação')
                            ->options([
                                'frequencia' => 'Frequência',
                                'exame' => 'Exame',
                                'pratico' => 'Prático',
                                'comportamental' => 'Comportamental',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('score')
                            ->label('Nota/Valor')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20),
                        Forms\Components\DateTimePicker::make('evaluated_at')
                            ->label('Data e Hora')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('observations')
                            ->label('Observações')
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
                Tables\Columns\TextColumn::make('student.student_number')
                    ->label('Nº Ordem')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('student.candidate.full_name')
                    ->label('Formando')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->sortable(),
                Tables\Columns\TextColumn::make('evaluation_type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Nota')
                    ->numeric()
                    ->sortable()
                    ->color(fn (string $state): string => $state < 10 ? 'danger' : 'success'),
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
            'index' => Pages\ListEvaluations::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Evaluation') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
