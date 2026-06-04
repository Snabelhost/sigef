<?php

namespace App\Filament\Comando\Resources;

use App\Filament\Comando\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-academic-cap';
    protected static ?string $modelLabel = 'Formando';
    protected static ?string $pluralModelLabel = 'Formandos';
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['candidate']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Informação do Formando')
                    ->schema([
                        Forms\Components\Select::make('candidate_id')
                            ->label('Candidato')
                            ->relationship('candidate', 'full_name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('student_number')
                            ->label('Nº de Ordem')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191),
                        Forms\Components\Select::make('student_type')
                            ->label('Tipo de Formando')
                            ->options([
                                'cadete' => 'Cadete',
                                'subalterno' => 'Subalterno',
                                'sargento' => 'Sargento',
                                'praça' => 'Praça',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'alistado' => 'Alistado',
                                'frequenta' => 'Frequenta',
                                'concluiu' => 'Concluiu',
                                'desistiu' => 'Desistiu',
                                'expulso' => 'Expulso',
                                'falecido' => 'Falecido',
                            ])
                            ->required()
                            ->default('alistado'),
                        Forms\Components\DatePicker::make('enrollment_date')
                            ->label('Data de Matrícula')
                            ->required()
                            ->default(now()),
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
                Tables\Columns\TextColumn::make('student_number')
                    ->label('Nº Ordem')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('studentTypeRelation.name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($record) => $record->studentTypeRelation?->color ?? 'gray')
                    ->default(fn ($record) => $record->student_type),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'alistado',
                        'primary' => 'frequenta',
                        'success' => 'concluiu',
                        'danger' => ['desistiu', 'expulso', 'falecido'],
                    ]),
                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('Matrícula')
                    ->date()
                    ->sortable(),
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
            'index' => Pages\ListStudents::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Student') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
