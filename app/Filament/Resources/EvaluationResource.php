<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResource\Pages;
use App\Filament\Resources\EvaluationResource\RelationManagers;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\TrainerClassAssignment;
use App\Models\TrainerSubjectAuthorization;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EvaluationResource extends Resource
{
    protected static ?string $model = Evaluation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 9;
    protected static ?string $navigationLabel = 'Avaliações de Apoio';
    protected static ?string $modelLabel = 'Avaliação de Apoio';
    protected static ?string $pluralModelLabel = 'Avaliações de Apoio';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student.candidate', 'subject', 'trainer', 'coursePhase']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Dados da Avaliação de Apoio')
                    ->schema([
                        Forms\Components\Hidden::make('institution_id'),
                        Forms\Components\Hidden::make('course_phase_id'),
                        Forms\Components\Select::make('student_id')
                            ->label('Formando')
                            ->options(fn (): array => static::studentOptions())
                            ->getOptionLabelUsing(function ($value): ?string {
                                $student = Student::with('candidate')->find($value);
                                return $student ? static::studentOptionLabel($student) : null;
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state): void {
                                $set('subject_id', null);
                                $set('evaluator_name', null);
                                $set('course_phase_id', null);
                                $set('institution_id', static::studentInstitutionId($state));
                            }),
                        Forms\Components\Select::make('subject_id')
                            ->label('Disciplina')
                            ->options(fn (callable $get): array => static::subjectOptionsForStudent($get('student_id')))
                            ->getOptionLabelUsing(function ($value): ?string {
                                $subject = Subject::find($value);
                                return $subject?->name ?? 'N/A';
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn (callable $get): bool => blank($get('student_id')))
                            ->live()
                            ->afterStateUpdated(function (callable $get, callable $set, $state): void {
                                $studentId = $get('student_id');

                                $set('course_phase_id', static::coursePhaseIdForStudentSubject($studentId, $state));
                                $set('evaluator_name', static::preferredTrainerNameForStudentSubject($studentId, $state));
                            }),
                        Forms\Components\Select::make('evaluation_type')
                            ->label('Tipo de Avaliação de Apoio')
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
                        Forms\Components\Select::make('evaluator_name')
                            ->label('Formador')
                            ->options(fn (callable $get): array => static::trainerOptionsForStudentSubject($get('student_id'), $get('subject_id')))
                            ->required()
                            ->searchable()
                            ->disabled(fn (callable $get): bool => blank($get('subject_id')))
                            ->preload(),
                        Forms\Components\DateTimePicker::make('evaluated_at')
                            ->label('Data e Hora')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('observations')
                            ->label('Observações')
                            ->columnSpanFull(),
                    ])->columns(2)->columnSpanFull(),
            ]);
    }

    protected static function studentOptions(): array
    {
        return Student::with('candidate')
            ->where(function (Builder $query): void {
                foreach (static::studentTypesShownInTrainingManagement() as $type) {
                    $query->orWhere('student_type', 'like', "%{$type}%");
                }
            })
            ->orderBy('nuri')
            ->get()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => static::studentOptionLabel($student),
            ])
            ->toArray();
    }

    protected static function studentTypesShownInTrainingManagement(): array
    {
        return [
            'Oficial',
            'Agente de 3ª Classe',
            'Recruta',
            '1ª Fase - Recruta',
            'Instruendo',
            '2ª Fase - Instruendo',
            'Em Formação',
            'Formando Concluído',
            'Formando Concluido',
        ];
    }

    protected static function studentOptionLabel(Student $student): string
    {
        $name = trim((string) ($student->candidate?->full_name ?: 'N/A'));
        $identifier = trim((string) ($student->nuri ?: $student->student_number ?: ''));

        return $identifier !== '' ? "{$name} - {$identifier}" : $name;
    }

    protected static function studentInstitutionId(mixed $studentId): ?int
    {
        if (blank($studentId)) {
            return null;
        }

        return Student::query()->whereKey($studentId)->value('institution_id');
    }

    protected static function subjectOptionsForStudent(mixed $studentId): array
    {
        if (blank($studentId)) {
            return [];
        }

        return StudentSubjectEnrollment::query()
            ->with('subject')
            ->where('student_id', $studentId)
            ->where('is_active', true)
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
        if (blank($studentId) || blank($subjectId)) {
            return null;
        }

        return StudentSubjectEnrollment::query()
            ->with('studentClass.courseMap')
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->latest('updated_at')
            ->first()
            ?? StudentSubjectEnrollment::query()
                ->with('studentClass.courseMap')
                ->where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->latest('updated_at')
                ->first();
    }

    protected static function coursePhaseIdForStudentSubject(mixed $studentId, mixed $subjectId): ?int
    {
        if (blank($subjectId)) {
            return null;
        }

        return static::currentSubjectEnrollment($studentId, $subjectId)?->course_phase_id
            ?: Subject::query()->whereKey($subjectId)->value('course_phase_id');
    }

    protected static function courseIdForStudentSubject(mixed $studentId, mixed $subjectId): ?int
    {
        $enrollment = static::currentSubjectEnrollment($studentId, $subjectId);

        if ($enrollment?->studentClass?->courseMap?->course_id) {
            return $enrollment->studentClass->courseMap->course_id;
        }

        return Student::query()
            ->with('courseMap')
            ->whereKey($studentId)
            ->first()
            ?->courseMap
            ?->course_id;
    }

    protected static function trainerOptionsForStudentSubject(mixed $studentId, mixed $subjectId): array
    {
        if (blank($subjectId)) {
            return [];
        }

        $enrollment = static::currentSubjectEnrollment($studentId, $subjectId);

        if ($enrollment?->class_id) {
            $options = TrainerClassAssignment::query()
                ->with('trainer')
                ->where('class_id', $enrollment->class_id)
                ->where('subject_id', $subjectId)
                ->where('is_active', true)
                ->get()
                ->filter(fn (TrainerClassAssignment $assignment): bool => filled($assignment->trainer?->full_name) && $assignment->trainer?->is_active !== false)
                ->mapWithKeys(fn (TrainerClassAssignment $assignment): array => [
                    $assignment->trainer->full_name => $assignment->trainer->full_name,
                ])
                ->toArray();

            if ($options !== []) {
                return $options;
            }
        }

        $student = blank($studentId) ? null : Student::query()->find($studentId);
        $courseId = static::courseIdForStudentSubject($studentId, $subjectId);
        $institutionId = $student?->institution_id ?? $enrollment?->studentClass?->institution_id;

        foreach ([true, false] as $strict) {
            $query = TrainerSubjectAuthorization::query()
                ->with('trainer')
                ->where('subject_id', $subjectId)
                ->when($strict && $courseId, fn (Builder $query): Builder => $query->where('course_id', $courseId))
                ->when($strict && $institutionId, fn (Builder $query): Builder => $query->where('institution_id', $institutionId));

            $options = $query
                ->get()
                ->filter(fn (TrainerSubjectAuthorization $authorization): bool => filled($authorization->trainer?->full_name) && $authorization->trainer?->is_active !== false)
                ->mapWithKeys(fn (TrainerSubjectAuthorization $authorization): array => [
                    $authorization->trainer->full_name => $authorization->trainer->full_name,
                ])
                ->toArray();

            if ($options !== []) {
                return $options;
            }
        }

        return [];
    }

    protected static function preferredTrainerNameForStudentSubject(mixed $studentId, mixed $subjectId): ?string
    {
        $options = static::trainerOptionsForStudentSubject($studentId, $subjectId);

        return $options === [] ? null : (string) array_key_first($options);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->modifyQueryUsing(function ($query) {
                // Mostrar apenas a última avaliação de cada formando
                $subquery = \App\Models\Evaluation::selectRaw('MAX(id) as id')
                    ->groupBy('student_id');

                return $query->whereIn('id', $subquery);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('student.candidate.photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->size(42)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->student?->candidate?->full_name ?? 'NA') . '&background=0D4C8B&color=fff&size=100'),
                Tables\Columns\TextColumn::make('student.candidate.full_name')
                    ->label('Formando')
                    ->sortable()
                    ->searchable()
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
                    ->color(fn(string $state): string => $state < 10 ? 'danger' : 'success')
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
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('cia')
                    ->label('CIA')
                    ->options(fn() => \App\Models\Student::whereNotNull('cia')->distinct()->pluck('cia', 'cia')->toArray())
                    ->query(fn($query, array $data) => $query->when($data['value'], fn($q) => $q->whereHas('student', fn($sq) => $sq->where('cia', $data['value'])))),
                Tables\Filters\SelectFilter::make('platoon')
                    ->label('Pelotão')
                    ->options(fn() => \App\Models\Student::whereNotNull('platoon')->distinct()->pluck('platoon', 'platoon')->toArray())
                    ->query(fn($query, array $data) => $query->when($data['value'], fn($q) => $q->whereHas('student', fn($sq) => $sq->where('platoon', $data['value'])))),
                Tables\Filters\SelectFilter::make('section')
                    ->label('Secção')
                    ->options(fn() => \App\Models\Student::whereNotNull('section')->distinct()->pluck('section', 'section')->toArray())
                    ->query(fn($query, array $data) => $query->when($data['value'], fn($q) => $q->whereHas('student', fn($sq) => $sq->where('section', $data['value'])))),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth('4xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Registo criado com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('verDetalhes')
                        ->label('Ver Detalhes')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn($record) => 'Detalhes - ' . ($record->student?->candidate?->full_name ?? 'N/A'))
                        ->modalWidth('6xl')
                        ->infolist(fn ($record): array => static::evaluationDetailsInfolist($record))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Fechar')->icon('heroicon-o-x-mark')->color('danger')),
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('4xl')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
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

    protected static function evaluationDetailsInfolist(\App\Models\Evaluation $record): array
    {
        $evaluations = \App\Models\Evaluation::query()
            ->with(['subject', 'phase'])
            ->where('student_id', $record->student_id)
            ->orderByDesc('evaluated_at')
            ->orderByDesc('id')
            ->get();

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
                            ->getStateUsing(fn (\App\Models\Evaluation $record): string => static::studentIdentifierValue($record->student))
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
            \Filament\Schemas\Components\Section::make('Resumo da Avaliação de Apoio')
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

    protected static function studentIdentifierValue(?\App\Models\Student $student): string
    {
        $identifier = trim((string) (
            $student?->nuri
            ?: $student?->candidate?->nuri
            ?: $student?->student_number
            ?: ''
        ));

        return $identifier !== '' ? $identifier : 'N/A';
    }

    protected static function formatStudentUnit($state, string $label, string $suffix): string
    {
        $value = trim((string) $state);

        if ($value === '') {
            return 'N/A';
        }

        return str_contains(mb_strtolower($value), mb_strtolower($label))
            ? $value
            : "{$value}{$suffix} {$label}";
    }

    protected static function evaluationTypeLabel($state): string
    {
        return match ((string) $state) {
            'frequencia' => 'Frequência',
            'exame' => 'Exame',
            'pratico' => 'Prático',
            'comportamental' => 'Comportamental',
            default => filled($state) ? ucfirst((string) $state) : 'N/A',
        };
    }

    protected static function evaluationTypeColor($state): string
    {
        return match ((string) $state) {
            'frequencia' => 'info',
            'exame' => 'primary',
            'pratico' => 'success',
            'comportamental' => 'warning',
            default => 'gray',
        };
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
