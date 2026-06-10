<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentClassResource\Pages;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseMap;
use App\Models\Institution;
use App\Models\StudentClass;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class StudentClassResource extends Resource
{
    protected static ?string $model = StudentClass::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return 'Turma';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Turmas';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['courseMap.course', 'courseMap.institution', 'courseMap.academicYear', 'academicYear', 'institution']);
    }

    /**
     * Gera automaticamente o nome da turma (ex: "Mecânica Geral - Turma A").
     */
    protected static function generateClassName(int $courseMapId): string
    {
        $map = CourseMap::with('course')->find($courseMapId);

        if (! $map || ! $map->course) {
            return 'Nova Turma';
        }

        $existingCount = StudentClass::where('course_map_id', $courseMapId)->count();
        $letter = chr(65 + $existingCount);

        return static::shortCourseCode($map->course->name) . "-{$letter}";
    }

    protected static function shortCourseCode(?string $courseName): string
    {
        $name = Str::of($courseName ?? 'Turma')
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9\s]/', ' ')
            ->replaceMatches('/\b(CURSO|DE|DA|DO|DAS|DOS|E|A|O|AS|OS|EM|PARA)\b/', ' ')
            ->squish();

        $letters = preg_replace('/[^A-Z0-9]/', '', (string) $name);

        if (blank($letters)) {
            return 'TRM';
        }

        return substr($letters, 0, 4);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::studentClassFormSchema());
    }

    protected static function studentClassFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Grid::make(2)
                ->schema([
            Forms\Components\Select::make('academic_year_id')
                ->label('Ano Lectivo')
                ->options(fn (): array => AcademicYear::query()
                    ->orderByDesc('year')
                    ->pluck('year', 'id')
                    ->toArray())
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('institution_id', null);
                    $set('course_id_helper', null);
                    $set('course_map_id', null);
                    $set('capacity', null);
                }),

            Forms\Components\Select::make('institution_id')
                ->label('Escola')
                ->options(fn (Get $get): array => static::institutionOptionsForAcademicYear($get('academic_year_id')))
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('course_id_helper', null);
                    $set('course_map_id', null);
                    $set('capacity', null);
                }),

            Forms\Components\Select::make('course_id_helper')
                ->label('Curso')
                ->options(fn (Get $get): array => static::courseOptionsForClassSelection(
                    $get('academic_year_id'),
                    $get('institution_id'),
                ))
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->dehydrated(false)
                ->afterStateHydrated(function (Forms\Components\Select $component, ?StudentClass $record): void {
                    if ($record?->courseMap) {
                        $component->state($record->courseMap->course_id);
                    }
                })
                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                    $map = static::findCourseMapForClassSelection(
                        $get('academic_year_id'),
                        $get('institution_id'),
                        $state,
                    );

                    if (! $map) {
                        $set('course_map_id', null);
                        return;
                    }

                    $set('course_map_id', $map->id);

                    if (blank($get('name'))) {
                        $set('name', static::generateClassName((int) $map->id));
                    }

                    if (blank($get('capacity')) && $map->max_students) {
                        $set('capacity', $map->max_students);
                    }
                }),

            Forms\Components\TextInput::make('name')
                ->label('Nome da Turma')
                ->placeholder('Gerado automaticamente se deixar em branco')
                ->helperText('Deixe em branco para gerar automaticamente.')
                ->maxLength(191),

            Forms\Components\TextInput::make('capacity')
                ->label('Capacidade')
                ->numeric()
                ->minValue(1)
                ->suffix('alunos')
                ->helperText('Número máximo de alunos nesta turma.')
                ->afterStateHydrated(function (Forms\Components\TextInput $component, ?StudentClass $record): void {
                    if (blank($component->getState()) && $record?->courseMap?->max_students) {
                        $component->state($record->courseMap->max_students);
                    }
                }),

            Forms\Components\Select::make('room_number')
                ->label('Número da Sala')
                ->options(static::roomNumberOptions())
                ->searchable()
                ->preload(),

            Forms\Components\Select::make('shift')
                ->label('Turno')
                ->options(static::shiftOptions())
                ->searchable()
                ->preload(),

            Forms\Components\Hidden::make('course_map_id')
                ->required(),
                ])
                ->columnSpanFull(),
        ];
    }

    public static function prepareClassData(array $data): array
    {
        if (
            blank($data['course_map_id'] ?? null)
            && filled($data['academic_year_id'] ?? null)
            && filled($data['institution_id'] ?? null)
            && filled($data['course_id_helper'] ?? null)
        ) {
            $data['course_map_id'] = static::findCourseMapForClassSelection(
                $data['academic_year_id'],
                $data['institution_id'],
                $data['course_id_helper'],
            )?->id;
        }

        unset($data['course_id_helper']);

        if (! empty($data['course_map_id'])) {
            $map = CourseMap::find($data['course_map_id']);

            if ($map) {
                $data['institution_id'] = $map->institution_id;
                $data['academic_year_id'] = $map->academic_year_id;

                if (blank($data['capacity'] ?? null) && $map->max_students) {
                    $data['capacity'] = $map->max_students;
                }
            }

            if (blank($data['name'] ?? null)) {
                $data['name'] = static::generateClassName((int) $data['course_map_id']);
            }
        }

        return $data;
    }

    protected static function roomNumberOptions(): array
    {
        return collect(range(1, 100))
            ->mapWithKeys(function (int $number): array {
                $room = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

                return [$room => $room];
            })
            ->toArray();
    }

    protected static function shiftOptions(): array
    {
        return [
            'Manhã' => 'Manhã',
            'Tarde' => 'Tarde',
            'Noite' => 'Noite',
            'Integral' => 'Integral',
            'Pós-Laboral' => 'Pós-Laboral',
        ];
    }

    protected static function institutionOptionsForAcademicYear(mixed $academicYearId): array
    {
        if (blank($academicYearId)) {
            return [];
        }

        return Institution::query()
            ->whereIn('id', CourseMap::query()
                ->where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->select('institution_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function courseOptionsForClassSelection(mixed $academicYearId, mixed $institutionId): array
    {
        if (blank($academicYearId) || blank($institutionId)) {
            return [];
        }

        return Course::query()
            ->whereIn('id', CourseMap::query()
                ->where('academic_year_id', $academicYearId)
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->select('course_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function findCourseMapForClassSelection(
        mixed $academicYearId,
        mixed $institutionId,
        mixed $courseId,
    ): ?CourseMap {
        if (blank($academicYearId) || blank($institutionId) || blank($courseId)) {
            return null;
        }

        return CourseMap::query()
            ->where('academic_year_id', $academicYearId)
            ->where('institution_id', $institutionId)
            ->where('course_id', $courseId)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('academicYear.year')
                    ->label('Ano Lectivo')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Escola')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('courseMap.course.name')
                    ->label('Curso')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Turma')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Ano Lectivo')
                    ->relationship('academicYear', 'year'),
                Tables\Filters\SelectFilter::make('course')
                    ->label('Curso')
                    ->options(fn (): array => Course::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(
                        fn (Builder $query, array $data): Builder => $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->whereHas(
                                'courseMap',
                                fn (Builder $courseMapQuery): Builder => $courseMapQuery->where('course_id', $value),
                            ),
                        ),
                    ),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth('5xl')
                    ->mutateFormDataUsing(fn (array $data): array => static::prepareClassData($data))
                    ->modalSubmitAction(fn (Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn (Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Turma criada com sucesso!'),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()
                        ->label('Visualizar')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading('Visualizar Turma')
                        ->modalWidth('5xl')
                        ->schema(static::studentClassFormSchema())
                        ->mutateRecordDataUsing(fn (array $data, StudentClass $record): array => [
                            ...$data,
                            'course_id_helper' => $record->courseMap?->course_id,
                        ])
                        ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger')),
                    Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('5xl')
                        ->mutateFormDataUsing(fn (array $data): array => static::prepareClassData($data))
                        ->modalSubmitAction(fn (Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Turma atualizada com sucesso!'),
                    Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListStudentClasses::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:StudentClass') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
