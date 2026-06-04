<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubjectResource extends Resource
{
    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $model = Subject::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-bookmark-square';
    protected static ?string $navigationLabel = 'Disciplinas';
    protected static ?string $modelLabel = 'Disciplina';
    protected static ?string $pluralModelLabel = 'Disciplinas';
    protected static ?int $navigationSort = 5;
    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';

    // Disable tenancy scoping - Subject is not scoped to institution
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['phase', 'phase.course']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome da Disciplina')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                Forms\Components\TextInput::make('workload_hours')
                    ->label('Carga Horária')
                    ->numeric()
                    ->suffix('horas'),
                Forms\Components\Select::make('course_id_helper')
                    ->label('Curso')
                    ->options(\App\Models\Course::orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                        if ($record && $record->phase) {
                            $component->state($record->phase->course_id);
                        }
                    }),
                Forms\Components\CheckboxList::make('phases')
                    ->label('Fases')
                    ->options([
                        '1ª Fase' => '1ª Fase',
                        '2ª Fase' => '2ª Fase',
                    ])
                    ->columns(2)
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    protected static function handlePhaseData(array &$data): void
    {
        $courseId = $data['course_id_helper'] ?? null;
        unset($data['course_id_helper']);

        if ($courseId) {
            $firstPhase = $data['phases'][0] ?? null;
            if ($firstPhase) {
                $phase = \App\Models\CoursePhase::firstOrCreate(
                    ['course_id' => $courseId, 'name' => $firstPhase],
                    ['order' => \App\Models\CoursePhase::where('course_id', $courseId)->max('order') + 1]
                );
                $data['course_phase_id'] = $phase->id;
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Disciplina')
                    ->searchable(),
                Tables\Columns\TextColumn::make('workload_hours')
                    ->label('Carga H.')
                    ->suffix('h')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phase.course.name')
                    ->label('Curso')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phases')
                    ->label('Fases')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(fn($record) => $record->phases ?? []),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->label('Curso')
                    ->relationship('phase.course', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('phases')
                    ->label('Fase')
                    ->options([
                        '1ª Fase' => '1ª Fase',
                        '2ª Fase' => '2ª Fase',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value']) {
                            return $query->whereJsonContains('phases', $data['value']);
                        }
                        return $query;
                    }),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('Nova Disciplina')
                    ->modalWidth('3xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        static::handlePhaseData($data);
                        return $data;
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Disciplina criada com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('3xl')
                        ->mutateFormDataUsing(function (array $data): array {
                            static::handlePhaseData($data);
                            return $data;
                        })
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Disciplina atualizada com sucesso!'),
                    \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
