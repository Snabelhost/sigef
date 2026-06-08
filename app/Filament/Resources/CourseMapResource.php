<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseMapResource\Pages;
use App\Models\CourseMap;
use App\Models\CoursePlan;
use App\Models\Subject;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class CourseMapResource extends Resource
{
    protected static ?string $model = CourseMap::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-puzzle-piece';

    protected static ?string $navigationLabel = 'Mapas e Planos de Curso';

    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Mapa e Plano de Curso';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Mapas e Planos de Curso';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['course', 'institution', 'academicYear']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::courseMapFormSchema());
    }

    protected static function courseMapFormSchema(): array
    {
        return [
                \Filament\Schemas\Components\Section::make('Mapa de Curso')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Curso')
                            ->relationship('course', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set): mixed => $set('plan_subject_ids', [])),
                        Forms\Components\Select::make('institution_id')
                            ->label('Instituição')
                            ->relationship('institution', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('organ')
                            ->label('Órgão')
                            ->options(fn () => \App\Models\Provenance::orderBy('name')->get()->mapWithKeys(fn ($p) => [$p->name => $p->acronym ? "{$p->name} ({$p->acronym})" : $p->name])->toArray())
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('academic_year_id')
                            ->label('Ano Académico')
                            ->relationship('academicYear', 'year')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('max_students')
                            ->label('Capacidade/Vagas')
                            ->required()
                            ->default(0)
                            ->formatStateUsing(fn ($state) => $state ? number_format((int) $state, 0, ',', '.') : '0')
                            ->dehydrateStateUsing(fn ($state) => (int) str_replace(['.', ','], '', (string) $state))
                            ->extraInputAttributes([
                                'x-on:input' => "
                                    let val = \$el.value.replace(/[^0-9]/g, '');
                                    if (val) {
                                        \$el.value = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                    }
                                ",
                                'inputmode' => 'numeric',
                            ]),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data de Início')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Data do Fim')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('start_date'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Mapa activo')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Plano de Curso')
                    ->description('Seleccione as disciplinas que pertencem ao curso deste mapa.')
                    ->schema([
                        Forms\Components\Select::make('plan_subject_ids')
                            ->label('Disciplinas')
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get): array => static::subjectOptionsForCourse($get('course_id')))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Seleccione primeiro o curso do mapa; aqui aparecem apenas as disciplinas desse curso.')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('plan_is_active')
                            ->label('Plano activo')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->persistColumnsInSession(false)
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Curso')
                    ->sortable(),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Instituição')
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('academicYear.year')
                    ->label('Ano')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan_subjects')
                    ->label('Disciplinas')
                    ->getStateUsing(fn (CourseMap $record): ?string => static::getPlanSubjectNames($record))
                    ->placeholder('Sem disciplinas')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('organ')
                    ->label('Órgão')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('max_students')
                    ->label('Vagas')
                    ->numeric(thousandsSeparator: '.')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->suffix(' vagas'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                static::makeUnifiedCreateAction('Novo Mapa e Plano'),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    static::makeUnifiedViewAction(),
                    static::makeUnifiedEditAction(),
                    Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function makeUnifiedCreateAction(?string $label = null): Actions\CreateAction
    {
        return Actions\CreateAction::make()
            ->label($label ?? 'Novo Mapa e Plano')
            ->icon('heroicon-o-plus')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(fn (Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
            ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
            ->createAnotherAction(fn (Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
            ->createAnother(true)
            ->mutateDataUsing(fn (array $data): array => static::mapDataOnly($data))
            ->after(function (Actions\Action $action): void {
                $record = $action->getRecord();

                if ($record instanceof CourseMap) {
                    static::syncCoursePlan($record, $action->getRawData());
                }
            })
            ->successNotificationTitle('Mapa e plano de curso criados com sucesso!');
    }

    public static function makeUnifiedViewAction(): Actions\ViewAction
    {
        return Actions\ViewAction::make()
            ->label('Visualizar')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->modalHeading('Visualizar Mapa e Plano de Curso')
            ->modalWidth(Width::FiveExtraLarge)
            ->schema(static::courseMapFormSchema())
            ->mutateRecordDataUsing(fn (array $data, CourseMap $record): array => static::withPlanData($record, $data))
            ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger'));
    }

    public static function makeUnifiedEditAction(): Actions\EditAction
    {
        return Actions\EditAction::make()
            ->icon('heroicon-o-pencil-square')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(fn (Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
            ->modalCancelAction(fn (Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
            ->mutateRecordDataUsing(fn (array $data, CourseMap $record): array => static::withPlanData($record, $data))
            ->mutateDataUsing(fn (array $data): array => static::mapDataOnly($data))
            ->after(function (Actions\Action $action): void {
                $record = $action->getRecord();

                if ($record instanceof CourseMap) {
                    static::syncCoursePlan($record, $action->getRawData());
                }
            })
            ->successNotificationTitle('Mapa e plano de curso atualizados com sucesso!');
    }

    protected static function mapDataOnly(array $data): array
    {
        return Arr::except($data, ['plan_subject_ids', 'plan_is_active']);
    }

    protected static function withPlanData(CourseMap $record, array $data): array
    {
        $plan = static::findCoursePlan($record);

        return [
            ...$data,
            'plan_subject_ids' => $plan
                ? $plan->subjects()->orderBy('course_plan_subjects.order')->orderBy('subjects.name')->pluck('subjects.id')->all()
                : [],
            'plan_is_active' => $plan?->is_active ?? true,
        ];
    }

    protected static function subjectOptionsForCourse(mixed $courseId): array
    {
        if (blank($courseId)) {
            return [];
        }

        return Subject::query()
            ->whereHas('phase', fn (Builder $query): Builder => $query->where('course_id', $courseId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function syncCoursePlan(CourseMap $record, array $data): void
    {
        $plan = static::findCoursePlan($record) ?? new CoursePlan([
            'course_id' => $record->course_id,
            'academic_year_id' => $record->academic_year_id,
        ]);

        $plan->course_id = $record->course_id;
        $plan->academic_year_id = $record->academic_year_id;
        $plan->is_active = (bool) ($data['plan_is_active'] ?? true);
        $plan->save();

        $subjects = collect($data['plan_subject_ids'] ?? [])
            ->filter(fn ($id): bool => filled($id))
            ->values()
            ->mapWithKeys(fn ($id, int $index): array => [(int) $id => ['order' => $index + 1]])
            ->all();

        $plan->subjects()->sync($subjects);
    }

    protected static function findCoursePlan(CourseMap $record): ?CoursePlan
    {
        if (! $record->course_id || ! $record->academic_year_id) {
            return null;
        }

        return CoursePlan::query()
            ->where('course_id', $record->course_id)
            ->where('academic_year_id', $record->academic_year_id)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();
    }

    protected static function getPlanSubjectNames(CourseMap $record): ?string
    {
        $plan = static::findCoursePlan($record);

        if (! $plan) {
            return null;
        }

        $subjects = $plan->subjects()
            ->orderBy('course_plan_subjects.order')
            ->orderBy('subjects.name')
            ->pluck('subjects.name')
            ->all();

        return filled($subjects) ? implode(', ', $subjects) : null;
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
            'index' => Pages\ListCourseMaps::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:CourseMap') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
