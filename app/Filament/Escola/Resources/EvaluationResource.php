<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\EvaluationResource\Pages;
use App\Filament\Resources\EvaluationResource\RelationManagers;
use App\Models\Evaluation;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EvaluationResource extends Resource
{
    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $model = Evaluation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 9;
    protected static ?string $modelLabel = 'Avaliação';
    protected static ?string $pluralModelLabel = 'Avaliações';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student.candidate', 'subject', 'trainer', 'coursePhase']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Dados da Avaliação')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Formando')
                            ->options(function (): array {
                                $tiposPermitidos = [
                                    'Oficial',
                                    'Agente de 3ª Classe',
                                    'Recruta',
                                    '1ª Fase - Recruta',
                                    'Instruendo',
                                    '2ª Fase - Instruendo',
                                    'Em Formação',
                                ];

                                return \App\Models\Student::with('candidate')
                                    ->where(function ($q) use ($tiposPermitidos) {
                                        foreach ($tiposPermitidos as $tipo) {
                                            $q->orWhere('student_type', 'like', "%{$tipo}%");
                                        }
                                    })
                                    ->get()
                                    ->mapWithKeys(fn($s) => [
                                        $s->id => $s->candidate?->full_name ?? 'N/A'
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $student = \App\Models\Student::with('candidate')->find($value);
                                return $student?->candidate?->full_name ?? 'N/A';
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(callable $set) => $set('subject_id', null)),
                        Forms\Components\Select::make('subject_id')
                            ->label('Disciplina')
                            ->options(function (callable $get) {
                                $studentId = $get('student_id');
                                if (!$studentId) {
                                    return [];
                                }
                                // Buscar disciplinas vinculadas ao formando
                                return \App\Models\StudentSubjectEnrollment::where('student_id', $studentId)
                                    ->with('subject')
                                    ->get()
                                    ->mapWithKeys(fn($e) => [
                                        $e->subject_id => $e->subject?->name ?? 'N/A'
                                    ]);
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $subject = \App\Models\Subject::find($value);
                                return $subject?->name ?? 'N/A';
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    // Buscar formador autorizado para esta disciplina
                                    $authorization = \App\Models\TrainerSubjectAuthorization::where('subject_id', $state)
                                        ->with('trainer')
                                        ->first();

                                    if ($authorization?->trainer) {
                                        $set('evaluator_name', $authorization->trainer->full_name);
                                    }
                                }
                            }),
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
                        Forms\Components\Select::make('evaluator_name')
                            ->label('Formador')
                            ->options(function (callable $get): array {
                                $subjectId = $get('subject_id');
                                if (!$subjectId) {
                                    return [];
                                }

                                return \App\Models\TrainerSubjectAuthorization::where('subject_id', $subjectId)
                                    ->with('trainer')
                                    ->get()
                                    ->filter(fn($a) => $a->trainer !== null)
                                    ->mapWithKeys(fn($a) => [
                                        $a->trainer->full_name => $a->trainer->full_name
                                    ])
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
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
                Tables\Columns\TextColumn::make('student.id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
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
                    ->label('Avaliador')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('evaluation_type')
                    ->label('Tipo de Avaliação')
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
                    \Filament\Actions\Action::make('novaAvaliacao')
                        ->label('Nova Avaliação')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('subject_id')
                                ->label('Disciplina')
                                ->options(function ($record) {
                                    // Buscar todas as disciplinas do formando
                                    return \App\Models\StudentSubjectEnrollment::where('student_id', $record->student_id)
                                        ->with('subject')
                                        ->get()
                                        ->mapWithKeys(fn($e) => [
                                            $e->subject_id => $e->subject?->name ?? 'N/A'
                                        ]);
                                })
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
                            Forms\Components\TextInput::make('evaluator_name')
                                ->label('Avaliador')
                                ->required(),
                        ])
                        ->action(function ($record, array $data): void {
                            // Obter phase_id da disciplina
                            $subject = \App\Models\Subject::find($data['subject_id']);

                            \App\Models\Evaluation::create([
                                'student_id' => $record->student_id,
                                'institution_id' => $record->student?->institution_id,
                                'subject_id' => $data['subject_id'],
                                'course_phase_id' => $subject?->course_phase_id,
                                'evaluation_type' => $data['evaluation_type'],
                                'score' => $data['score'],
                                'evaluator_name' => $data['evaluator_name'],
                                'evaluated_at' => now(),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Avaliação Registada!')
                                ->success()
                                ->send();
                        })
                        ->modalHeading(fn($record) => 'Nova Avaliação - ' . ($record->student?->candidate?->full_name ?? 'N/A'))
                        ->modalWidth('2xl')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action
                            ->label('Registrar')
                            ->icon('heroicon-o-check')
                            ->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action
                            ->label('Cancelar')
                            ->icon('heroicon-o-x-mark')
                            ->color('danger')),
                    \Filament\Actions\Action::make('verDetalhes')
                        ->label('Ver Detalhes')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn($record) => 'Detalhes - ' . ($record->student?->candidate?->full_name ?? 'N/A'))
                        ->modalWidth('4xl')
                        ->modalContent(function ($record) {
                            $evaluations = \App\Models\Evaluation::where('student_id', $record->student_id)
                                ->with('subject')
                                ->orderBy('evaluated_at', 'desc')
                                ->get();

                            return view('filament.pages.evaluation-details', [
                                'record' => $record,
                                'evaluations' => $evaluations,
                            ]);
                        })
                        ->modalFooterActions([
                            \Filament\Actions\Action::make('fechar')
                                ->label('Fechar')
                                ->icon('heroicon-o-x-mark')
                                ->color('danger')
                                ->close(),
                        ]),
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
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
