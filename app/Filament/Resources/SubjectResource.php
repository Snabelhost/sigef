<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Filament\Resources\SubjectResource\RelationManagers;
use App\Models\Subject;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-bookmark-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Disciplinas';
    protected static ?string $modelLabel = 'Disciplina';
    protected static ?string $pluralModelLabel = 'Disciplinas';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['course', 'phase', 'phase.course']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::subjectFormSchema());
    }

    protected static function subjectFormSchema(): array
    {
        return [
                Forms\Components\TextInput::make('name')
                    ->label('Nome da Disciplina')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                Forms\Components\TextInput::make('workload_hours')
                    ->label('Carga Horária')
                    ->numeric()
                    ->suffix('horas'),
                Forms\Components\Select::make('course_id')
                    ->label('Curso')
                    ->options(\App\Models\Course::orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                        if ($record && blank($component->getState()) && $record->phase) {
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
                    ->default([])
                    ->helperText('Deixe vazio quando a disciplina nao tiver fase.'),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->rows(2)
                    ->columnSpanFull(),
        ];
    }

    protected static function handlePhaseData(array &$data): void
    {
        $courseId = $data['course_id'] ?? null;
        $phases = collect($data['phases'] ?? [])
            ->filter(fn ($phase): bool => filled($phase))
            ->values()
            ->all();

        $data['phases'] = $phases;
        $data['course_phase_id'] = null;

        if ($courseId && $phases !== []) {
            // Manter compatibilidade: usar a primeira fase seleccionada para o course_phase_id
            $firstPhase = $phases[0] ?? null;
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
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Curso')
                    ->formatStateUsing(fn ($state, Subject $record): string => $state ?: ($record->phase?->course?->name ?: '-'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('phases')
                    ->label('Fases')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Sem fase' ? 'gray' : 'primary')
                    ->getStateUsing(fn(Subject $record): array => $record->phases ?: ['Sem fase']),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Curso')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('phases')
                    ->label('Fase')
                    ->options([
                        '__sem_fase' => 'Sem fase',
                        '1ª Fase' => '1ª Fase',
                        '2ª Fase' => '2ª Fase',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === '__sem_fase') {
                            return $query->where(function (Builder $blankQuery): void {
                                $blankQuery
                                    ->whereNull('phases')
                                    ->orWhereJsonLength('phases', 0);
                            });
                        }

                        if ($value) {
                            return $query->whereJsonContains('phases', $value);
                        }

                        return $query;
                    }),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth('5xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        static::handlePhaseData($data);
                        return $data;
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Disciplina criada com sucesso!')
                    ->label('Nova Disciplina'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                        ->label('Visualizar')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading('Visualizar Disciplina')
                        ->modalWidth('5xl')
                        ->schema(static::subjectFormSchema())
                        ->mutateRecordDataUsing(fn (array $data, Subject $record): array => [
                            ...$data,
                            'course_id' => $record->course_id ?: $record->phase?->course_id,
                        ])
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger')),
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('5xl')
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Subject') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
