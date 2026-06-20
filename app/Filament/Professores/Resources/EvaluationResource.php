<?php

namespace App\Filament\Professores\Resources;

use App\Filament\Professores\Resources\EvaluationResource\Pages;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\Trainer;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EvaluationResource extends \App\Filament\Resources\EvaluationResource
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Avaliações de Apoio';
    protected static ?string $modelLabel = 'Avaliação de Apoio';
    protected static ?string $pluralModelLabel = 'Avaliações de Apoio';
    protected static bool $shouldSkipAuthorization = true;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return static::scopeEvaluationQueryToProfessor($query);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('id', static::latestEvaluationRecordIdsQuery()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('student.candidate.photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->size(42)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->student?->candidate?->full_name ?? 'NA') . '&background=0D4C8B&color=fff&size=100'),
                Tables\Columns\TextColumn::make('student.candidate.full_name')
                    ->label('Nome')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('student_identifier')
                    ->label('NIP/NURI')
                    ->getStateUsing(fn (Evaluation $record): string => static::studentIdentifierValue($record->student))
                    ->searchable(query: fn (Builder $query, string $search): Builder => static::applyStudentIdentifierSearch($query, $search))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('evaluation_type')
                    ->label('Tipo')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Nota')
                    ->numeric()
                    ->sortable()
                    ->color(fn (string $state): string => $state < 10 ? 'danger' : 'success')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('evaluator_name')
                    ->label('Formador')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('evaluation_type')
                    ->label('Tipo de Avaliação de Apoio')
                    ->options([
                        'frequencia' => 'Frequência',
                        'exame' => 'Exame',
                        'pratico' => 'Prático',
                        'comportamental' => 'Comportamental',
                    ]),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Disciplina')
                    ->options(fn (): array => static::subjectFilterOptions())
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('student_identifier')
                    ->label('NIP/NURI')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label('NIP/NURI')
                            ->placeholder('Pesquisar NIP/NURI'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = trim((string) ($data['value'] ?? ''));

                        return $search === ''
                            ? $query
                            : static::applyStudentIdentifierSearch($query, $search);
                    }),
                Tables\Filters\SelectFilter::make('cia')
                    ->label('CIA')
                    ->options(fn () => static::tenantStudentQuery()->whereNotNull('cia')->distinct()->pluck('cia', 'cia')->toArray())
                    ->query(fn ($query, array $data) => $query->when($data['value'], fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('cia', $data['value'])))),
                Tables\Filters\SelectFilter::make('platoon')
                    ->label('Pelotão')
                    ->options(fn () => static::tenantStudentQuery()->whereNotNull('platoon')->distinct()->pluck('platoon', 'platoon')->toArray())
                    ->query(fn ($query, array $data) => $query->when($data['value'], fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('platoon', $data['value'])))),
                Tables\Filters\SelectFilter::make('section')
                    ->label('Secção')
                    ->options(fn () => static::tenantStudentQuery()->whereNotNull('section')->distinct()->pluck('section', 'section')->toArray())
                    ->query(fn ($query, array $data) => $query->when($data['value'], fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('section', $data['value'])))),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Criar Avaliação de Apoio')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(fn (array $data): array => static::mutateEvaluationFormData($data))
                    ->modalSubmitAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Registo criado com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('verDetalhes')
                        ->label('Ver Detalhes')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn ($record) => 'Detalhes - ' . ($record->student?->candidate?->full_name ?? 'N/A'))
                        ->modalWidth('6xl')
                        ->infolist(fn ($record): array => static::evaluationDetailsInfolist($record))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action->label('Fechar')->icon('heroicon-o-x-mark')->color('danger')),
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('4xl')
                        ->mutateFormDataUsing(fn (array $data): array => static::mutateEvaluationFormData($data))
                        ->modalSubmitAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Registo atualizado com sucesso!'),
                    \Filament\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->using(fn (Evaluation $record): bool => static::deleteEvaluationGroup($record) > 0),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->action(function (\Filament\Actions\DeleteBulkAction $action, \Illuminate\Database\Eloquent\Collection $records): void {
                            $deleted = 0;

                            foreach ($records as $record) {
                                $deleted += static::deleteEvaluationGroup($record);
                            }

                            $deleted > 0 ? $action->success() : $action->failure();
                        }),
                ]),
            ]);
    }

    protected static function latestEvaluationRecordIdsQuery(): Builder
    {
        $query = Evaluation::query()
            ->selectRaw('MAX(evaluations.id) as id');

        static::scopeEvaluationQueryToProfessor($query);

        return $query->groupBy('evaluations.student_id');
    }

    public static function mutateEvaluationFormData(array $data): array
    {
        $studentId = $data['student_id'] ?? null;
        $subjectId = $data['subject_id'] ?? null;

        if (blank($data['institution_id'] ?? null) && filled($studentId)) {
            $data['institution_id'] = static::studentInstitutionId($studentId);
        }

        if (blank($data['course_phase_id'] ?? null) && filled($studentId) && filled($subjectId)) {
            $data['course_phase_id'] = static::coursePhaseIdForStudentSubject($studentId, $subjectId);
        }

        if (! static::currentUserIsProfessorAdmin() && ($trainer = static::currentTrainer())) {
            $data['evaluator_name'] = $trainer->full_name;
            $data['evaluated_by'] = $trainer->getKey();
        }

        return $data;
    }

    protected static function deleteEvaluationGroup(Evaluation $record): int
    {
        $query = Evaluation::query()
            ->where('student_id', $record->student_id)
            ->when(
                $record->institution_id,
                fn (Builder $query, int $institutionId): Builder => $query->where('institution_id', $institutionId),
                fn (Builder $query): Builder => $query->whereNull('institution_id'),
            );

        static::scopeEvaluationQueryToProfessor($query);

        return $query->delete();
    }

    protected static function evaluationDetailsInfolist(Evaluation $record): array
    {
        $evaluations = static::evaluationHistoryQuery($record)->get();

        return [
            \Filament\Schemas\Components\Section::make('Informações do Formando')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    \Filament\Schemas\Components\Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 5,
                    ])->schema([
                        \Filament\Infolists\Components\TextEntry::make('student.candidate.full_name')
                            ->label('Nome completo')
                            ->icon('heroicon-o-user')
                            ->placeholder('N/A'),
                        \Filament\Infolists\Components\TextEntry::make('student_identifier')
                            ->label('NIP/NURI')
                            ->getStateUsing(fn (Evaluation $record): string => static::studentIdentifierValue($record->student))
                            ->icon('heroicon-o-identification')
                            ->badge()
                            ->color('primary'),
                        \Filament\Infolists\Components\TextEntry::make('student.cia')
                            ->label('CIA')
                            ->formatStateUsing(fn ($state): string => static::formatStudentUnit($state, 'CIA', 'ª'))
                            ->placeholder('N/A'),
                        \Filament\Infolists\Components\TextEntry::make('student.platoon')
                            ->label('Pelotão')
                            ->formatStateUsing(fn ($state): string => static::formatStudentUnit($state, 'Pelotão', 'º'))
                            ->placeholder('N/A'),
                        \Filament\Infolists\Components\TextEntry::make('student.section')
                            ->label('Secção')
                            ->formatStateUsing(fn ($state): string => static::formatStudentUnit($state, 'Secção', 'ª'))
                            ->placeholder('N/A'),
                    ]),
                ]),
            \Filament\Schemas\Components\Section::make('Resumo da Avaliação')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    \Filament\Schemas\Components\Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 5,
                    ])->schema([
                        \Filament\Infolists\Components\TextEntry::make('subject.name')
                            ->label('Disciplina')
                            ->icon('heroicon-o-book-open')
                            ->placeholder('N/A'),
                        \Filament\Infolists\Components\TextEntry::make('evaluation_type')
                            ->label('Tipo')
                            ->formatStateUsing(fn ($state): string => static::evaluationTypeLabel($state))
                            ->badge()
                            ->color(fn ($state): string => static::evaluationTypeColor($state)),
                        \Filament\Infolists\Components\TextEntry::make('score')
                            ->label('Nota')
                            ->formatStateUsing(fn ($state): string => is_numeric($state) ? number_format((float) $state, 1, ',', '.') : 'N/A')
                            ->badge()
                            ->color(fn ($state): string => is_numeric($state) && (float) $state >= 10 ? 'success' : 'danger'),
                        \Filament\Infolists\Components\TextEntry::make('evaluator_name')
                            ->label('Formador')
                            ->icon('heroicon-o-academic-cap')
                            ->placeholder('N/A'),
                        \Filament\Infolists\Components\TextEntry::make('evaluated_at')
                            ->label('Data')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-o-calendar-days')
                            ->placeholder('N/A'),
                    ]),
                    \Filament\Infolists\Components\TextEntry::make('observations')
                        ->label('Observações')
                        ->placeholder('Sem observações registadas.')
                        ->columnSpanFull(),
                ]),
            \Filament\Schemas\Components\Section::make('Histórico de Avaliações')
                ->icon('heroicon-o-table-cells')
                ->schema([
                    \Filament\Infolists\Components\ViewEntry::make('evaluations_history')
                        ->label('')
                        ->view('filament.pages.evaluation-details')
                        ->viewData([
                            'record' => $record,
                            'evaluations' => $evaluations,
                        ])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function evaluationHistoryQuery(Evaluation $record): Builder
    {
        $query = Evaluation::query()
            ->with(['subject', 'phase'])
            ->where('student_id', $record->student_id)
            ->when(
                $record->institution_id,
                fn (Builder $query, int $institutionId): Builder => $query->where('institution_id', $institutionId),
            )
            ->orderByDesc('evaluated_at')
            ->orderByDesc('id');

        return static::scopeEvaluationQueryToProfessor($query);
    }

    protected static function tenantStudentQuery(): Builder
    {
        $query = Student::query();

        if (static::currentUserIsProfessorAdmin()) {
            return $query;
        }

        $trainer = static::currentTrainer();

        if (! $trainer) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereExists(function ($subquery) use ($trainer): void {
            $subquery
                ->selectRaw('1')
                ->from('student_subject_enrollments')
                ->join('classes', 'classes.id', '=', 'student_subject_enrollments.class_id')
                ->leftJoin('course_maps', 'course_maps.id', '=', 'classes.course_map_id')
                ->whereColumn('student_subject_enrollments.student_id', 'students.id')
                ->where('student_subject_enrollments.is_active', true)
                ->whereExists(function ($activeEnrollmentQuery): void {
                    $activeEnrollmentQuery
                        ->selectRaw('1')
                        ->from('student_class_enrollments')
                        ->whereColumn('student_class_enrollments.student_id', 'student_subject_enrollments.student_id')
                        ->whereColumn('student_class_enrollments.class_id', 'student_subject_enrollments.class_id')
                        ->where('student_class_enrollments.is_active', true);
                })
                ->where(function ($query) use ($trainer): void {
                    static::whereTrainerCanEvaluateEnrollment($query, $trainer);
                });
        });
    }

    protected static function subjectOptionsForStudent(mixed $studentId): array
    {
        if (static::currentUserIsProfessorAdmin()) {
            return parent::subjectOptionsForStudent($studentId);
        }

        if (blank($studentId)) {
            return [];
        }

        return static::professorStudentSubjectEnrollmentQuery()
            ->with('subject')
            ->where('student_subject_enrollments.student_id', $studentId)
            ->get()
            ->filter(fn (StudentSubjectEnrollment $enrollment): bool => filled($enrollment->subject?->name))
            ->sortBy(fn (StudentSubjectEnrollment $enrollment): string => (string) $enrollment->subject?->name)
            ->mapWithKeys(fn (StudentSubjectEnrollment $enrollment): array => [
                $enrollment->subject_id => $enrollment->subject?->name,
            ])
            ->toArray();
    }

    protected static function currentSubjectEnrollment(mixed $studentId, mixed $subjectId): ?StudentSubjectEnrollment
    {
        if (static::currentUserIsProfessorAdmin()) {
            return parent::currentSubjectEnrollment($studentId, $subjectId);
        }

        if (blank($studentId) || blank($subjectId)) {
            return null;
        }

        return static::professorStudentSubjectEnrollmentQuery()
            ->with('studentClass.courseMap')
            ->where('student_subject_enrollments.student_id', $studentId)
            ->where('student_subject_enrollments.subject_id', $subjectId)
            ->latest('student_subject_enrollments.updated_at')
            ->first();
    }

    protected static function trainerOptionsForStudentSubject(mixed $studentId, mixed $subjectId): array
    {
        if (static::currentUserIsProfessorAdmin()) {
            return parent::trainerOptionsForStudentSubject($studentId, $subjectId);
        }

        $trainer = static::currentTrainer();

        if (! $trainer || ! static::currentSubjectEnrollment($studentId, $subjectId)) {
            return [];
        }

        return [$trainer->full_name => $trainer->full_name];
    }

    protected static function subjectFilterOptions(): array
    {
        if (static::currentUserIsProfessorAdmin()) {
            return Subject::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        }

        return static::professorStudentSubjectEnrollmentQuery()
            ->with('subject')
            ->get()
            ->filter(fn (StudentSubjectEnrollment $enrollment): bool => filled($enrollment->subject?->name))
            ->sortBy(fn (StudentSubjectEnrollment $enrollment): string => (string) $enrollment->subject?->name)
            ->mapWithKeys(fn (StudentSubjectEnrollment $enrollment): array => [
                $enrollment->subject_id => $enrollment->subject?->name,
            ])
            ->unique()
            ->toArray();
    }

    protected static function professorStudentSubjectEnrollmentQuery(): Builder
    {
        $query = StudentSubjectEnrollment::query()
            ->select('student_subject_enrollments.*')
            ->join('classes', 'classes.id', '=', 'student_subject_enrollments.class_id')
            ->leftJoin('course_maps', 'course_maps.id', '=', 'classes.course_map_id')
            ->where('student_subject_enrollments.is_active', true)
            ->whereExists(function ($subquery): void {
                $subquery
                    ->selectRaw('1')
                    ->from('student_class_enrollments')
                    ->whereColumn('student_class_enrollments.student_id', 'student_subject_enrollments.student_id')
                    ->whereColumn('student_class_enrollments.class_id', 'student_subject_enrollments.class_id')
                    ->where('student_class_enrollments.is_active', true);
            });

        if (static::currentUserIsProfessorAdmin()) {
            return $query;
        }

        $trainer = static::currentTrainer();

        if (! $trainer) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($query) use ($trainer): void {
            static::whereTrainerCanEvaluateEnrollment($query, $trainer);
        });
    }

    protected static function scopeEvaluationQueryToProfessor(Builder $query): Builder
    {
        if (static::currentUserIsProfessorAdmin()) {
            return $query;
        }

        $trainer = static::currentTrainer();

        if (! $trainer) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereExists(function ($subquery) use ($trainer): void {
            $subquery
                ->selectRaw('1')
                ->from('student_subject_enrollments')
                ->join('classes', 'classes.id', '=', 'student_subject_enrollments.class_id')
                ->leftJoin('course_maps', 'course_maps.id', '=', 'classes.course_map_id')
                ->whereColumn('student_subject_enrollments.student_id', 'evaluations.student_id')
                ->whereColumn('student_subject_enrollments.subject_id', 'evaluations.subject_id')
                ->where('student_subject_enrollments.is_active', true)
                ->whereExists(function ($activeEnrollmentQuery): void {
                    $activeEnrollmentQuery
                        ->selectRaw('1')
                        ->from('student_class_enrollments')
                        ->whereColumn('student_class_enrollments.student_id', 'student_subject_enrollments.student_id')
                        ->whereColumn('student_class_enrollments.class_id', 'student_subject_enrollments.class_id')
                        ->where('student_class_enrollments.is_active', true);
                })
                ->where(function ($query) use ($trainer): void {
                    static::whereTrainerCanEvaluateEnrollment($query, $trainer);
                });
        });
    }

    protected static function whereTrainerCanEvaluateEnrollment($query, Trainer $trainer): void
    {
        $query
            ->whereExists(function ($assignmentQuery) use ($trainer): void {
                $assignmentQuery
                    ->selectRaw('1')
                    ->from('trainer_class_assignments')
                    ->where('trainer_class_assignments.trainer_id', $trainer->getKey())
                    ->where('trainer_class_assignments.is_active', true)
                    ->whereColumn('trainer_class_assignments.class_id', 'student_subject_enrollments.class_id')
                    ->whereColumn('trainer_class_assignments.subject_id', 'student_subject_enrollments.subject_id');
            })
            ->orWhereExists(function ($authorizationQuery) use ($trainer): void {
                $authorizationQuery
                    ->selectRaw('1')
                    ->from('trainer_subject_authorizations')
                    ->where('trainer_subject_authorizations.trainer_id', $trainer->getKey())
                    ->whereColumn('trainer_subject_authorizations.subject_id', 'student_subject_enrollments.subject_id')
                    ->whereColumn('trainer_subject_authorizations.institution_id', 'classes.institution_id')
                    ->whereColumn('trainer_subject_authorizations.course_id', 'course_maps.course_id');
            });
    }

    protected static function currentTrainer(): ?Trainer
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return $user->trainer
            ?: Trainer::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $user->email)])
                ->first();
    }

    protected static function currentUserIsProfessorAdmin(): bool
    {
        return (bool) Auth::user()?->hasRole('professores_admin');
    }

    protected static function canUseProfessorEvaluations(): bool
    {
        return (bool) Auth::user()?->can('AccessPanel:Professores');
    }

    protected static function recordBelongsToProfessorScope(Model $record): bool
    {
        if (! $record instanceof Evaluation) {
            return false;
        }

        if (static::currentUserIsProfessorAdmin()) {
            return true;
        }

        return static::scopeEvaluationQueryToProfessor(
            Evaluation::query()->whereKey($record->getKey())
        )->exists();
    }

    public static function canAccess(): bool
    {
        return static::canUseProfessorEvaluations();
    }

    public static function canViewAny(): bool
    {
        return static::canUseProfessorEvaluations();
    }

    public static function canCreate(): bool
    {
        return static::canUseProfessorEvaluations();
    }

    public static function canView(Model $record): bool
    {
        return static::recordBelongsToProfessorScope($record);
    }

    public static function canEdit(Model $record): bool
    {
        return static::recordBelongsToProfessorScope($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::recordBelongsToProfessorScope($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::canUseProfessorEvaluations();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluations::route('/'),
        ];
    }
}
