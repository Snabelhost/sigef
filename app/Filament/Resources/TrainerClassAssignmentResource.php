<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainerClassAssignmentResource\Pages;
use App\Models\TrainerClassAssignment;
use App\Models\Trainer;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrainerClassAssignmentResource extends Resource
{
    protected static ?string $model = Trainer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-academic-cap';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão do Centro';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Atribuição de Turmas';
    protected static ?string $modelLabel = 'Atribuição';
    protected static ?string $pluralModelLabel = 'Atribuições de Turmas';
    protected static ?string $slug = 'trainer-class-assignments';

    public static function getEloquentQuery(): Builder
    {
        return Trainer::query()
            ->where('is_active', true)
            ->withCount('classAssignments')
            ->with(['classAssignments.subject', 'classAssignments.studentClass.institution', 'institution']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Atribuir Turmas e Disciplinas ao Formador')
                    ->description('Seleccione o formador, a turma e as disciplinas que ele irá leccionar.')
                    ->schema([
                        Forms\Components\Select::make('trainer_id')
                            ->label('Formador')
                            ->options(Trainer::where('is_active', true)->pluck('full_name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                        Forms\Components\Select::make('class_id')
                            ->label('Turma')
                            ->options(
                                StudentClass::with('institution')
                                    ->get()
                                    ->mapWithKeys(fn($class) => [$class->id => $class->name . ' - ' . ($class->institution->name ?? '')])
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('academic_year_id')
                            ->label('Ano Académico')
                            ->options(AcademicYear::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('subject_ids')
                            ->label('Disciplinas')
                            ->options(Subject::pluck('name', 'id'))
                            ->required()
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('full_name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Formador')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Instituição')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('classAssignments')
                    ->label('Turmas')
                    ->getStateUsing(
                        fn(Trainer $record) =>
                        $record->classAssignments
                            ->pluck('studentClass.name')
                            ->unique()
                            ->filter()
                            ->implode(', ')
                    )
                    ->wrap()
                    ->limit(50),
                Tables\Columns\TextColumn::make('subjects')
                    ->label('Disciplinas')
                    ->getStateUsing(
                        fn(Trainer $record) =>
                        $record->classAssignments
                            ->pluck('subject.name')
                            ->unique()
                            ->filter()
                            ->implode(', ')
                    )
                    ->wrap()
                    ->limit(80),
                Tables\Columns\TextColumn::make('class_assignments_count')
                    ->label('Total')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\Action::make('novaAtribuicao')
                    ->icon('heroicon-o-plus')
                    ->label('Nova Atribuição')
                    ->modalHeading('Atribuir Turmas e Disciplinas')
                    ->form([
                        Forms\Components\Select::make('trainer_id')
                            ->label('Formador')
                            ->options(Trainer::where('is_active', true)->pluck('full_name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('class_id')
                            ->label('Turma')
                            ->options(
                                StudentClass::with('institution')
                                    ->get()
                                    ->mapWithKeys(fn($class) => [$class->id => $class->name . ' - ' . ($class->institution->name ?? '')])
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('subject_ids')
                            ->label('Disciplinas')
                            ->options(Subject::pluck('name', 'id'))
                            ->required()
                            ->multiple()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('academic_year_id')
                            ->label('Ano Académico')
                            ->options(AcademicYear::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (array $data): void {
                        $subjectIds = $data['subject_ids'] ?? [];

                        foreach ($subjectIds as $subjectId) {
                            TrainerClassAssignment::updateOrCreate(
                                [
                                    'trainer_id' => $data['trainer_id'],
                                    'class_id' => $data['class_id'],
                                    'subject_id' => $subjectId,
                                ],
                                [
                                    'academic_year_id' => $data['academic_year_id'] ?? null,
                                    'is_active' => true,
                                    'assigned_at' => now(),
                                    'assigned_by' => auth()->id(),
                                ]
                            );
                        }
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Atribuir'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Atribuições criadas com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('editarAtribuicoes')
                        ->label('Editar')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->modalHeading(fn(Trainer $record) => 'Editar Atribuições - ' . $record->full_name)
                        ->form(function (Trainer $record) {
                            $assignments = $record->classAssignments()->with(['subject', 'studentClass'])->get();

                            return [
                                Forms\Components\Repeater::make('assignments')
                                    ->label('Atribuições')
                                    ->schema([
                                        Forms\Components\Select::make('class_id')
                                            ->label('Turma')
                                            ->options(
                                                StudentClass::with('institution')
                                                    ->get()
                                                    ->mapWithKeys(fn($class) => [$class->id => $class->name . ' - ' . ($class->institution->name ?? '')])
                                            )
                                            ->required()
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\Select::make('subject_id')
                                            ->label('Disciplina')
                                            ->options(Subject::pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Activo')
                                            ->default(true),
                                    ])
                                    ->columns(3)
                                    ->default($assignments->map(fn($a) => [
                                        'id' => $a->id,
                                        'class_id' => $a->class_id,
                                        'subject_id' => $a->subject_id,
                                        'is_active' => $a->is_active,
                                    ])->toArray())
                                    ->addActionLabel('Adicionar Atribuição')
                                    ->reorderable(false)
                                    ->collapsible(),
                            ];
                        })
                        ->action(function (Trainer $record, array $data): void {
                            // Remover atribuições antigas
                            $record->classAssignments()->delete();

                            // Criar novas atribuições
                            foreach ($data['assignments'] ?? [] as $assignment) {
                                TrainerClassAssignment::create([
                                    'trainer_id' => $record->id,
                                    'class_id' => $assignment['class_id'],
                                    'subject_id' => $assignment['subject_id'],
                                    'is_active' => $assignment['is_active'] ?? true,
                                    'assigned_at' => now(),
                                    'assigned_by' => auth()->id(),
                                ]);
                            }
                        })
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Atribuições atualizadas com sucesso!'),
                    \Filament\Actions\Action::make('gerenciarDisciplinas')
                        ->label('Disciplinas')
                        ->icon('heroicon-o-academic-cap')
                        ->color('primary')
                        ->modalHeading(fn(Trainer $record) => 'Disciplinas - ' . $record->full_name)
                        ->modalContent(fn(Trainer $record) => view('filament.resources.trainer-class-assignment-resource.modal-disciplines', [
                            'trainer' => $record,
                            'assignments' => $record->classAssignments()->with(['subject', 'studentClass'])->get(),
                        ]))
                        ->modalSubmitAction(false),
                    \Filament\Actions\Action::make('adicionarDisciplina')
                        ->label('Adicionar')
                        ->icon('heroicon-o-plus')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('class_id')
                                ->label('Turma')
                                ->options(
                                    StudentClass::with('institution')
                                        ->get()
                                        ->mapWithKeys(fn($class) => [$class->id => $class->name . ' - ' . ($class->institution->name ?? '')])
                                )
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('subject_ids')
                                ->label('Disciplinas')
                                ->options(Subject::pluck('name', 'id'))
                                ->required()
                                ->multiple()
                                ->searchable()
                                ->preload(),
                        ])
                        ->action(function (Trainer $record, array $data): void {
                            $subjectIds = $data['subject_ids'] ?? [];

                            foreach ($subjectIds as $subjectId) {
                                TrainerClassAssignment::updateOrCreate(
                                    [
                                        'trainer_id' => $record->id,
                                        'class_id' => $data['class_id'],
                                        'subject_id' => $subjectId,
                                    ],
                                    [
                                        'is_active' => true,
                                        'assigned_at' => now(),
                                        'assigned_by' => auth()->id(),
                                    ]
                                );
                            }
                        })
                        ->successNotificationTitle('Disciplinas adicionadas com sucesso!'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListTrainerClassAssignments::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        // Recurso desabilitado - funcionalidade movida para TrainerResource
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Não mostrar na navegação
        return false;
    }
}
