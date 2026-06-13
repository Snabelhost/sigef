<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\StudentClassEnrollmentResource\Pages;
use App\Filament\Resources\Concerns\StudentEnrollmentEditForm;
use App\Models\StudentClassEnrollment;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\CoursePhase;
use App\Models\AcademicYear;
use App\Models\StudentSubjectEnrollment;
use App\Models\StudentType;
use App\Models\StudentTransferHistory;
use App\Services\GradeCalculator;
use App\Services\StudentCardService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentClassEnrollmentResource extends Resource
{
    use StudentEnrollmentEditForm;

    protected static bool $shouldSkipAuthorization = false;

    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-user-group';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Gestão de Formandos';
    protected static ?string $modelLabel = 'Formando';
    protected static ?string $pluralModelLabel = 'Gestão de Formandos';
    protected static ?string $slug = 'student-class-enrollments';
    protected static bool $isScopedToTenant = false;

    /**
     * Obter opções de tipos de alunos da base de dados
     */
    public static function getStudentTypeOptions(): array
    {
        return StudentType::where('is_active', true)
            ->orderBy('order')
            ->pluck('name', 'name')
            ->toArray();
    }

    /**
     * Obter cores dos tipos de alunos
     */
    public static function getStudentTypeColors(): array
    {
        return StudentType::where('is_active', true)
            ->pluck('color', 'name')
            ->toArray();
    }

    public static function getEloquentQuery(): Builder
    {
        // Tipos de alunos que devem aparecer na Gestão de Formandos
        // (Exclui: Alistado, Formando)
        $tiposPermitidos = [
            'Oficial',
            'Agente de 3ª Classe',
            'Recruta',
            '1ª Fase - Recruta',
            'Instruendo',
            '2ª Fase - Instruendo',
            'Em Formação',
            'Formando Concluído',
        ];

        return Student::query()
            ->when(\Filament\Facades\Filament::getTenant()?->id, fn (Builder $query, int $institutionId): Builder => $query->where('institution_id', $institutionId))
            ->where(function ($query) use ($tiposPermitidos) {
                foreach ($tiposPermitidos as $tipo) {
                    $query->orWhere('student_type', 'like', "%{$tipo}%");
                }
            })
            ->with(['candidate', 'institution', 'currentPhase', 'studentTypeRelation', 'classEnrollments.studentClass.courseMap.course', 'classEnrollments.coursePhase'])
            ->withCount(['classEnrollments', 'subjectEnrollments'])
            ->withMax('classEnrollments', 'enrolled_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('nuri', 'asc')
            ->recordAction(null)
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->headerActions([])
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions());
    }

    /**
     * Definição das colunas da tabela
     */
    private static function getTableColumns(): array
    {
        $typeColors = self::getStudentTypeColors();

        return [
            Tables\Columns\ImageColumn::make('candidate.photo')
                ->label('Foto')
                ->disk('public')
                ->circular()
                ->size(40)
                ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->candidate?->full_name ?? 'NA') . '&background=0D4C8B&color=fff&size=100'),
            Tables\Columns\TextColumn::make('candidate.full_name')
                ->label('Nome do Aluno')
                ->searchable()
                ->sortable()
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('nuri')
                ->label('NIP/NURI')
                ->searchable()
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
                    return $query->orderByRaw('nuri IS NULL OR nuri = "" OR nuri = "-", nuri + 0 ' . $direction);
                })
                ->toggleable()
                ->placeholder('-'),
            Tables\Columns\TextColumn::make('student_type')
                ->label('Estado')
                ->badge()
                ->color(fn($state) => $typeColors[$state] ?? 'gray')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('cia')
                ->label('CIA')
                ->formatStateUsing(fn($state) => $state ? "{$state}ª CIA" : null)
                ->sortable()
                ->searchable()
                ->placeholder('-')
                ->toggleable(),
            Tables\Columns\TextColumn::make('platoon')
                ->label('Pelotão')
                ->formatStateUsing(fn($state) => $state ? "{$state}º Pelotão" : null)
                ->sortable()
                ->placeholder('-')
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('section')
                ->label('Secção')
                ->formatStateUsing(fn($state) => $state ? "{$state}ª Secção" : null)
                ->sortable()
                ->placeholder('-')
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('classEnrollments.studentClass.courseMap.course.name')
                ->label('Curso')
                ->searchable()
                ->wrap()
                ->placeholder('-')
                ->toggleable(),
            Tables\Columns\TextColumn::make('institution.name')
                ->label('Instituição')
                ->wrap()
                ->placeholder('-')
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('subject_enrollments_count')
                ->label('Total Disc')
                ->badge()
                ->color('primary')
                ->sortable()
                ->toggleable(),
        ];
    }

    /**
     * Definição dos filtros da tabela
     */
    private static function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('nuri_filter')
                ->form([
                    Forms\Components\TextInput::make('nuri')
                        ->label('Procurar por NURI / NIP'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['nuri'],
                        fn($query, $nuri) => $query->where('nuri', 'like', "%{$nuri}%"),
                    );
                }),
            Tables\Filters\SelectFilter::make('student_type')
                ->label('Estado do Aluno')
                ->options(fn() => self::getStudentTypeOptions()),
            Tables\Filters\SelectFilter::make('institution_id')
                ->label('Instituição')
                ->relationship('institution', 'name')
                ->searchable()
                ->hidden(fn (): bool => filled(\Filament\Facades\Filament::getTenant()?->id)),
            Tables\Filters\SelectFilter::make('cia')
                ->label('CIA')
                ->options(fn() => Student::whereNotNull('cia')->distinct()->pluck('cia', 'cia'))
                ->searchable(),
            Tables\Filters\SelectFilter::make('platoon')
                ->label('Pelotão')
                ->options(fn() => Student::whereNotNull('platoon')->distinct()->pluck('platoon', 'platoon'))
                ->searchable(),
            Tables\Filters\SelectFilter::make('section')
                ->label('Secção')
                ->options(fn() => Student::whereNotNull('section')->distinct()->pluck('section', 'section'))
                ->searchable(),
            Tables\Filters\SelectFilter::make('gender')
                ->label('Género')
                ->options([
                    'Masculino' => 'Masculino',
                    'Feminino' => 'Feminino',
                ])
                ->query(function ($query, array $data) {
                    if (!empty($data['value'])) {
                        return $query->whereHas('candidate', function ($q) use ($data) {
                            $q->where('gender', $data['value']);
                        });
                    }
                    return $query;
                }),
            Tables\Filters\SelectFilter::make('province_id')
                ->label('Província')
                ->options(\App\Models\Province::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->query(function ($query, array $data) {
                    if (!empty($data['value'])) {
                        return $query->whereHas('candidate', function ($q) use ($data) {
                            $q->where('province_id', $data['value']);
                        });
                    }
                    return $query;
                }),
            Tables\Filters\SelectFilter::make('municipality_id')
                ->label('Município')
                ->options(\App\Models\Municipality::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->query(function ($query, array $data) {
                    if (!empty($data['value'])) {
                        return $query->whereHas('candidate', function ($q) use ($data) {
                            $q->where('municipality_id', $data['value']);
                        });
                    }
                    return $query;
                }),
        ];
    }

    /**
     * Definição das acções de linha (row actions)
     */
    private static function getTableActions(): array
    {
        return [
            \Filament\Actions\ActionGroup::make([
                static::editarFormandoAction(),
                static::visualizarAction(),
                static::inscricaoAction(),
                static::gerarCartaoAction(),
                static::moverInstituicaoAction(),
                static::baixarFichaAction(),
                static::imprimirCertificadoAction(),
                \Filament\Actions\DeleteAction::make()
                    ->label('Excluir')
                    ->icon('heroicon-o-trash'),
            ])->icon('heroicon-s-cog-6-tooth')->color('primary')->size('lg')->tooltip('Acções')->iconButton(),
        ];
    }

    /**
     * Definição das acções em massa (bulk actions)
     */
    private static function getTableBulkActions(): array
    {
        return [
            static::recrutaParaInstruendoBulkAction(),
            static::emFormacaoParaConcluidoBulkAction(),
            static::atribuirEmMassaBulkAction(),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // ROW ACTIONS
    // ──────────────────────────────────────────────────────────────

    private static function editarFormandoAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('editarFormando')
            ->label('Editar')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->modalHeading(fn(Student $record) => 'Editar - ' . ($record->candidate?->full_name ?? 'N/A'))
            ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
            ->form([
                \Filament\Schemas\Components\Section::make('Identificação Pessoal')
                    ->description('Dados pessoais do formando')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('candidate_id_number')
                            ->label('Nº do BI')
                            ->default(fn(Student $record) => $record->candidate?->id_number)
                            ->maxLength(191),
                        Forms\Components\TextInput::make('candidate_full_name')
                            ->label('Nome Completo')
                            ->default(fn(Student $record) => $record->candidate?->full_name)
                            ->required()
                            ->maxLength(191),
                        Forms\Components\DatePicker::make('candidate_birth_date')
                            ->label('Data de Nascimento')
                            ->default(fn(Student $record) => $record->candidate?->birth_date)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\Select::make('candidate_gender')
                            ->label('Género')
                            ->options([
                                'Masculino' => 'Masculino',
                                'Feminino' => 'Feminino',
                            ])
                            ->default(fn(Student $record) => $record->candidate?->gender),
                        Forms\Components\Select::make('candidate_marital_status')
                            ->label('Estado Civil')
                            ->options([
                                'solteiro' => 'Solteiro(a)',
                                'casado' => 'Casado(a)',
                                'divorciado' => 'Divorciado(a)',
                                'viuvo' => 'Viúvo(a)',
                            ])
                            ->default(fn(Student $record) => $record->candidate?->marital_status),
                        Forms\Components\TextInput::make('candidate_father_name')
                            ->label('Nome do Pai')
                            ->default(fn(Student $record) => $record->candidate?->father_name)
                            ->maxLength(191),
                        Forms\Components\TextInput::make('candidate_mother_name')
                            ->label('Nome da Mãe')
                            ->default(fn(Student $record) => $record->candidate?->mother_name)
                            ->maxLength(191)
                            ->columnSpan(1),
                        Forms\Components\FileUpload::make('candidate_photo')
                            ->label('Foto')
                            ->image()
                            ->disk('public')
                            ->directory('candidates/photos')
                            ->openable()
                            ->previewable()
                            ->default(fn(Student $record) => $record->candidate?->photo),
                    ])->columns(3),
                \Filament\Schemas\Components\Section::make('Localização e Contacto')
                    ->description('Endereço e contactos')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Select::make('candidate_province_id')
                            ->label('Província')
                            ->options(\App\Models\Province::orderBy('name')->pluck('name', 'id'))
                            ->default(fn(Student $record) => $record->candidate?->province_id)
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('candidate_municipality_id')
                            ->label('Município')
                            ->options(\App\Models\Municipality::orderBy('name')->pluck('name', 'id'))
                            ->default(fn(Student $record) => $record->candidate?->municipality_id)
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('candidate_address')
                            ->label('Endereço')
                            ->default(fn(Student $record) => $record->candidate?->address)
                            ->maxLength(191),
                        Forms\Components\TextInput::make('candidate_phone')
                            ->label('Telefone')
                            ->tel()
                            ->default(fn(Student $record) => $record->candidate?->phone)
                            ->maxLength(191),
                        Forms\Components\TextInput::make('candidate_email')
                            ->label('E-mail')
                            ->email()
                            ->default(fn(Student $record) => $record->candidate?->email)
                            ->maxLength(191),
                    ])->columns(3),
                \Filament\Schemas\Components\Section::make('Documentos')
                    ->description('Documentos do formando')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\FileUpload::make('candidate_bilhete_identidade')
                            ->label('Bilhete de Identidade')
                            ->disk('public')
                            ->directory('candidates/documents')
                            ->default(fn(Student $record) => $record->candidate?->bilhete_identidade)
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->openable()
                            ->previewable(),
                        Forms\Components\FileUpload::make('candidate_certificado_doc')
                            ->label('Certificado')
                            ->disk('public')
                            ->directory('candidates/documents')
                            ->default(fn(Student $record) => $record->candidate?->certificado_doc)
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->openable()
                            ->previewable(),
                        Forms\Components\FileUpload::make('candidate_curriculum')
                            ->label('Curriculum')
                            ->disk('public')
                            ->directory('candidates/documents')
                            ->default(fn(Student $record) => $record->candidate?->curriculum)
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->openable()
                            ->previewable(),
                    ])->columns(3),
            ])
            ->form(static::enrollmentEditFormSchema())
            ->action(function (Student $record, array $data): void {
                if ($record->candidate) {
                    $record->candidate->update([
                        'id_number' => $data['candidate_id_number'] ?? null,
                        'full_name' => $data['candidate_full_name'],
                        'birth_date' => $data['candidate_birth_date'] ?? null,
                        'gender' => $data['candidate_gender'] ?? null,
                        'marital_status' => $data['candidate_marital_status'] ?? null,
                        'father_name' => $data['candidate_father_name'] ?? null,
                        'mother_name' => $data['candidate_mother_name'] ?? null,
                        'province_id' => $data['candidate_province_id'] ?? null,
                        'municipality_id' => $data['candidate_municipality_id'] ?? null,
                        'address' => $data['candidate_address'] ?? null,
                        'phone' => $data['candidate_phone'] ?? null,
                        'email' => $data['candidate_email'] ?? null,
                        'photo' => $data['candidate_photo'] ?? null,
                        'bilhete_identidade' => $data['candidate_bilhete_identidade'] ?? null,
                        'certificado_doc' => $data['candidate_certificado_doc'] ?? null,
                        'curriculum' => $data['candidate_curriculum'] ?? null,
                    ]);
                }

                \Filament\Notifications\Notification::make()
                    ->title('Formando atualizado com sucesso!')
                    ->success()
                    ->send();
            })
            ->action(function (Student $record, array $data): void {
                static::updateEnrollmentFromEditForm($record, $data);
            })
            ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar')->color('primary'))
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'));
    }

    private static function visualizarAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('visualizar')
            ->label('Visualizar')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->modalHeading(fn(Student $record) => 'Visualizar - ' . ($record->candidate?->full_name ?? 'N/A'))
            ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
            ->form(static::enrollmentEditFormSchema())
            ->disabledSchema()
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action
                ->label('Fechar')
                ->icon('heroicon-o-x-mark')
                ->color('danger'));
    }

    private static function visualizarResumoAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('visualizar')
            ->label('Visualizar')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->modalHeading(fn(Student $record) => 'Detalhes - ' . ($record->candidate?->full_name ?? 'N/A'))
            ->modalWidth('4xl')
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Fechar')->icon('heroicon-o-x-mark')->color('danger'))
            ->infolist(function (Student $record) {
                return [
                    \Filament\Schemas\Components\Section::make('Dados Pessoais')
                        ->headerActions([
                            static::baixarFichaAction(),
                            \Filament\Actions\Action::make('moverAluno')
                                ->label('Mover Aluno')
                                ->icon('heroicon-s-arrow-right-circle')
                                ->color('success')
                                ->button()
                                ->extraAttributes([
                                    'style' => 'background-color: #10b981 !important; color: white !important; --c-400: white; --c-500: white;',
                                ])
                                ->requiresConfirmation()
                                ->modalHeading('Mover Aluno para Outra Instituição')
                                ->modalDescription(fn() => 'O aluno "' . ($record->candidate?->full_name ?? 'N/A') . '" será transferido para outra instituição mantendo todas as suas informações, inscrições e disciplinas.')
                                ->modalIcon('heroicon-o-building-office')
                                ->form([
                                    Forms\Components\Placeholder::make('current_institution')
                                        ->label('Instituição Actual')
                                        ->content(fn() => $record->institution?->name ?? 'Sem instituição definida'),
                                    Forms\Components\Select::make('new_institution_id')
                                        ->label('Nova Instituição')
                                        ->options(fn() => \App\Models\Institution::where('id', '!=', $record->institution_id)->pluck('name', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Selecione a instituição de destino'),
                                ])
                                ->action(function (array $data) use ($record): void {
                                    $oldInstitutionId = $record->institution_id;
                                    $oldInstitution = $record->institution?->name ?? 'N/A';
                                    $newInstitutionId = $data['new_institution_id'];
                                    $newInstitution = \App\Models\Institution::find($newInstitutionId)?->name ?? 'N/A';

                                    $enrollment = $record->classEnrollments->first();
                                    $courseName = $enrollment?->studentClass?->courseMap?->course?->name;
                                    $className = $enrollment?->studentClass?->name;

                                    StudentTransferHistory::create([
                                        'student_id' => $record->id,
                                        'from_institution_id' => $oldInstitutionId,
                                        'to_institution_id' => $newInstitutionId,
                                        'transferred_by' => auth()->id(),
                                        'student_name' => $record->candidate?->full_name,
                                        'student_number' => $record->student_number,
                                        'bi_number' => $record->candidate?->bi_number,
                                        'student_type' => $record->student_type,
                                        'rank' => $record->rank?->name,
                                        'provenance' => $record->provenance?->name,
                                        'phone' => $record->candidate?->phone,
                                        'course' => $courseName,
                                        'student_class' => $className,
                                        'cia' => $record->cia,
                                        'platoon' => $record->platoon,
                                        'section' => $record->section,
                                        'notes' => $data['notes'] ?? null,
                                        'transferred_at' => now(),
                                    ]);

                                    // Atualizar instituição
                                    $record->update(['institution_id' => $newInstitutionId]);

                                    // Atualizar student_type conforme o tipo de instituição de destino
                                    $newStudentType = \App\Models\Institution::getDefaultStudentTypeForId($newInstitutionId);
                                    if ($newStudentType) {
                                        $newStudentTypeId = \App\Models\StudentType::getIdByName($newStudentType);
                                        $record->update([
                                            'student_type' => $newStudentType,
                                            'student_type_id' => $newStudentTypeId,
                                        ]);
                                    }

                                    if ($record->candidate) {
                                        $updateData = ['institution_id' => $newInstitutionId];
                                        if ($newStudentType) {
                                            $updateData['student_type'] = $newStudentType;
                                        }
                                        $record->candidate->update($updateData);
                                    }

                                    \Filament\Notifications\Notification::make()
                                        ->title('Aluno Movido com Sucesso!')
                                        ->body("Transferido de \"{$oldInstitution}\" para \"{$newInstitution}\"." . ($newStudentType ? " Tipo alterado para \"{$newStudentType}\"." : '') . ' Todas as inscrições e disciplinas foram mantidas.')
                                        ->success()
                                        ->duration(5000)
                                        ->send();
                                })
                                ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Confirmar Transferência')->color('primary'))
                                ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->color('danger')),
                        ])
                        ->schema([
                            \Filament\Schemas\Components\Grid::make(['default' => 1, 'md' => 6])
                                ->schema([
                                    \Filament\Infolists\Components\ImageEntry::make('candidate.photo')
                                        ->hiddenLabel()
                                        ->circular()
                                        ->size(100)
                                        ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->candidate?->full_name ?? 'NA') . '&background=0D4C8B&color=fff&size=200')
                                        ->columnSpan(1),
                                    \Filament\Schemas\Components\Grid::make(3)
                                        ->schema([
                                            \Filament\Infolists\Components\TextEntry::make('student_number')->label('Nº de Ordem'),
                                            \Filament\Infolists\Components\TextEntry::make('candidate.full_name')->label('Nome Completo'),
                                            \Filament\Infolists\Components\TextEntry::make('candidate.id_number')->label('Nº BI'),
                                            \Filament\Infolists\Components\TextEntry::make('candidate.phone')->label('Telefone'),
                                            \Filament\Infolists\Components\TextEntry::make('student_type')->label('Estado')->badge(),
                                            \Filament\Infolists\Components\TextEntry::make('institution.name')->label('Instituição'),
                                        ])
                                        ->extraAttributes(['style' => 'gap: 0.5rem;'])
                                        ->columnSpan(5),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Section::make('Localização')
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('nuri')
                                ->label(fn($record) => in_array(strtolower($record->student_type ?? ''), ['em formação', 'oficial']) ? 'NIP' : 'NURI')
                                ->placeholder('-'),
                            \Filament\Infolists\Components\TextEntry::make('cia')
                                ->label('CIA')
                                ->formatStateUsing(fn($state) => $state ? "{$state}ª CIA" : '-'),
                            \Filament\Infolists\Components\TextEntry::make('platoon')
                                ->label('Pelotão')
                                ->formatStateUsing(fn($state) => $state ? "{$state}º PELOTÃO" : '-'),
                            \Filament\Infolists\Components\TextEntry::make('section')
                                ->label('Secção')
                                ->formatStateUsing(fn($state) => $state ? "{$state}ª SECÇÃO" : '-'),
                        ])->columns(4),
                    \Filament\Schemas\Components\Section::make('Curso e Disciplinas')
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('classEnrollments')
                                ->label('Curso')
                                ->getStateUsing(fn() => $record->classEnrollments
                                    ->map(fn($e) => $e->studentClass?->courseMap?->course?->name)
                                    ->filter()->unique()->implode(', ') ?: '-'),
                            \Filament\Infolists\Components\TextEntry::make('subject_enrollments_count')
                                ->label('Total de Disciplinas')
                                ->badge()
                                ->color('primary'),
                        ])->columns(2),
                ];
            });
    }

    private static function inscricaoAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('inscricao')
            ->label('Inscrição')
            ->icon('heroicon-o-academic-cap')
            ->color('success')
            ->modalHeading(fn(Student $record) => 'Inscrição - ' . ($record->candidate?->full_name ?? 'N/A'))
            ->modalDescription('Atribua ano lectivo, escola, turma, NURI/NIP, CIA, pelotão e secção.')
            ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
            ->form(function (Student $record) {
                $lastEnrollment = $record->classEnrollments()->with('studentClass.courseMap.course')->latest()->first();
                $defaultCourseId = $lastEnrollment?->studentClass?->courseMap?->course_id;

                $existingSubjectIds = [];
                if ($lastEnrollment) {
                    $existingSubjectIds = \App\Models\StudentSubjectEnrollment::where('student_id', $record->id)
                        ->where('class_id', $lastEnrollment->class_id)
                        ->where('is_active', true)
                        ->pluck('subject_id')
                        ->toArray();
                }

                $studentType = strtolower($record->student_type ?? '');
                $isNip = in_array($studentType, ['em formação', 'oficial']) ||
                    str_contains($studentType, 'em formação') ||
                    str_contains($studentType, 'oficial');

                return [
                    \Filament\Schemas\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('course_id')
                                ->label('Curso')
                                ->options(\App\Models\Course::pluck('name', 'id'))
                                ->default($defaultCourseId)
                                ->required()
                                ->live()
                                ->searchable()
                                ->afterStateUpdated(function ($set) {
                                    $set('class_id', null);
                                    $set('course_phase_id', null);
                                    $set('subject_ids', []);
                                }),
                            Forms\Components\Select::make('class_id')
                                ->label('Turma')
                                ->options(function ($get) {
                                    $courseId = $get('course_id');
                                    if (!$courseId) return [];

                                    $courseMapIds = \App\Models\CourseMap::where('course_id', $courseId)->pluck('id');
                                    return StudentClass::whereIn('course_map_id', $courseMapIds)->pluck('name', 'id');
                                })
                                ->default($lastEnrollment?->class_id)
                                ->required()
                                ->live()
                                ->searchable(),
                        ]),
                    \Filament\Schemas\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('phase_filter')
                                ->label('Fase')
                                ->options([
                                    '1ª Fase' => '1ª Fase',
                                    '2ª Fase' => '2ª Fase',
                                ])
                                ->default(fn() => $lastEnrollment?->course_phase_id
                                    ? (CoursePhase::find($lastEnrollment->course_phase_id)?->name)
                                    : null)
                                ->live()
                                ->searchable(),
                            Forms\Components\TextInput::make('classroom')
                                ->label('Sala')
                                ->maxLength(50)
                                ->placeholder('Ex: Sala 1, Sala A')
                                ->default($lastEnrollment?->classroom),
                        ]),
                    \Filament\Schemas\Components\Grid::make(4)
                        ->schema([
                            Forms\Components\TextInput::make('nuri')
                                ->label($isNip ? 'NIP' : 'NURI')
                                ->default($record->nuri)
                                ->maxLength(9),
                            Forms\Components\Select::make('cia')
                                ->label('CIA')
                                ->options(collect(range(1, 15))->mapWithKeys(fn($n) => [$n => "{$n}ª CIA"]))
                                ->default($record->cia)
                                ->searchable(),
                            Forms\Components\Select::make('platoon')
                                ->label('Pelotão')
                                ->options(collect(range(1, 15))->mapWithKeys(fn($n) => [$n => "{$n}º Pelotão"]))
                                ->default($record->platoon)
                                ->searchable(),
                            Forms\Components\Select::make('section')
                                ->label('Secção')
                                ->options(collect(range(1, 15))->mapWithKeys(fn($n) => [$n => "{$n}ª Secção"]))
                                ->default($record->section)
                                ->searchable(),
                        ]),
                    Forms\Components\Select::make('subject_ids')
                        ->label('Disciplinas')
                        ->options(function ($get) {
                            $courseId = $get('course_id');
                            $phaseName = $get('phase_filter');
                            $selectedIds = $get('subject_ids') ?? [];

                            if (!$courseId) return [];

                            $coursePhaseIds = CoursePhase::where('course_id', $courseId)->pluck('id');

                            $alreadySelected = collect();
                            if (!empty($selectedIds)) {
                                $alreadySelected = Subject::whereIn('id', $selectedIds)->pluck('name', 'id');
                            }

                            if ($phaseName) {
                                $phaseSubjects = Subject::query()
                                    ->where(function (Builder $query) use ($courseId, $coursePhaseIds): void {
                                        $query
                                            ->where('course_id', $courseId)
                                            ->orWhereIn('course_phase_id', $coursePhaseIds);
                                    })
                                    ->where(function (Builder $query) use ($phaseName): void {
                                        $query
                                            ->where(function (Builder $blankPhaseQuery): void {
                                                $blankPhaseQuery
                                                    ->whereNull('phases')
                                                    ->orWhereJsonLength('phases', 0);
                                            })
                                            ->orWhereJsonContains('phases', $phaseName);
                                    })
                                    ->pluck('name', 'id');
                                return $alreadySelected->union($phaseSubjects)->all();
                            }

                            $allSubjects = Subject::query()
                                ->where(function (Builder $query) use ($courseId, $coursePhaseIds): void {
                                    $query
                                        ->where('course_id', $courseId)
                                        ->orWhereIn('course_phase_id', $coursePhaseIds);
                                })
                                ->pluck('name', 'id');
                            return $alreadySelected->union($allSubjects)->all();
                        })
                        ->default($existingSubjectIds)
                        ->multiple()
                        ->required()
                        ->searchable(),
                ];
            })
            ->form(fn (Student $record): array => static::enrollmentInscriptionFormSchema($record))
            ->action(function (Student $record, array $data): void {
                $record->update([
                    'nuri' => $data['nuri'] ?? $record->nuri,
                    'cia' => $data['cia'] ?? null,
                    'platoon' => $data['platoon'] ?? null,
                    'section' => $data['section'] ?? null,
                ]);

                $phaseName = $data['phase_filter'] ?? null;
                $coursePhaseId = null;
                if ($phaseName) {
                    $courseMapId = \App\Models\StudentClass::find($data['class_id'])?->course_map_id;
                    $courseId = \App\Models\CourseMap::find($courseMapId)?->course_id;
                    if ($courseId) {
                        $coursePhaseId = CoursePhase::firstOrCreate(
                            ['course_id' => $courseId, 'name' => $phaseName],
                            ['order' => CoursePhase::where('course_id', $courseId)->max('order') + 1]
                        )->id;
                    }
                }

                $academicYearId = \App\Models\AcademicYear::where('is_active', true)->first()?->id;

                $enrollment = \App\Models\StudentClassEnrollment::updateOrCreate(
                    [
                        'student_id' => $record->id,
                        'class_id' => $data['class_id'],
                    ],
                    [
                        'course_phase_id' => $coursePhaseId,
                        'academic_year_id' => $academicYearId,
                        'student_type' => $record->student_type,
                        'classroom' => $data['classroom'] ?? null,
                        'is_active' => true,
                        'enrolled_at' => now(),
                        'enrolled_by' => auth()->id(),
                    ]
                );

                $courseMapId = \App\Models\StudentClass::find($data['class_id'])?->course_map_id;
                $record->update(['course_map_id' => $courseMapId]);

                $subjectIds = $data['subject_ids'] ?? [];

                $deactivateQuery = StudentSubjectEnrollment::where('student_id', $record->id)
                    ->where('class_id', $data['class_id'])
                    ->whereNotIn('subject_id', $subjectIds);

                if ($phaseName) {
                    $phaseSubjectIds = Subject::whereJsonContains('phases', $phaseName)->pluck('id');
                    $deactivateQuery->whereIn('subject_id', $phaseSubjectIds);
                }

                $deactivateQuery->update(['is_active' => false]);

                foreach ($subjectIds as $subjectId) {
                    StudentSubjectEnrollment::updateOrCreate(
                        [
                            'student_id' => $record->id,
                            'subject_id' => $subjectId,
                            'class_id' => $data['class_id'],
                        ],
                        [
                            'course_phase_id' => $data['course_phase_id'] ?? null,
                            'is_active' => true,
                        ]
                    );
                }
            })
            ->action(function (Student $record, array $data): void {
                static::updateEnrollmentInscription($record, $data);
            })
            ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar Inscrição')->color('primary'))
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
            ->successNotificationTitle('Inscrição actualizada com sucesso!');
    }

    private static function gerarCartaoAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('gerarCartao')
            ->label('Imprimir Cartão')
            ->icon('heroicon-o-identification')
            ->color('info')
            ->modalHeading('Pré-visualização do Cartão')
            ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
            ->modalContent(function (Student $record) {
                $data = app(StudentCardService::class)->build($record);
                $template = $data['template'];
                $printUrl = route('cartoes.preview', ['student' => $record]);
                $cacheBuster = (string) max(
                    (int) ($record->updated_at?->timestamp ?: 0),
                    (int) ($template->updated_at?->timestamp ?: 0),
                    time(),
                );
                $embeddedUrl = fn (string $face): string => $printUrl.'?'.http_build_query([
                    'embedded' => 1,
                    'autoprint' => 0,
                    'face' => $face,
                    'v' => $cacheBuster,
                ]);

                return view('cards.print-modal', $data + [
                    'viewerId' => 'sigef-student-card-viewer-'.$record->getKey(),
                    'frameId' => 'sigef-student-card-frame-'.$record->getKey(),
                    'printUrl' => $printUrl,
                    'embeddedFrontUrl' => $embeddedUrl('front'),
                    'embeddedBackUrl' => $embeddedUrl('back'),
                    'entityLabel' => 'Formandos',
                    'documentName' => 'Formandos - '.($data['payload']['name'] ?? 'Formando'),
                    'statusLabel' => $record->status ?: ($record->student_type ?: 'ACTIVO'),
                    'statusColor' => 'success',
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar Pré-visualização')->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false);
    }

    private static function moverInstituicaoAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('moverInstituicao')
            ->label('Mover Instituição')
            ->icon('heroicon-o-building-office-2')
            ->color('warning')
            ->modalHeading('Mover Aluno para Outra Instituição')
            ->modalDescription(fn(Student $record) => 'Mover "' . ($record->candidate?->full_name ?? 'N/A') . '" para outra instituição')
            ->modalWidth('md')
            ->form([
                Forms\Components\Placeholder::make('instituicao_atual')
                    ->label('Instituição Atual')
                    ->content(fn(Student $record) => $record->institution?->name ?? 'Sem instituição'),
                Forms\Components\Select::make('institution_id')
                    ->label('Nova Instituição')
                    ->options(fn(Student $record) => \App\Models\Institution::where('id', '!=', $record->institution_id)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Observações')
                    ->placeholder('Motivo da transferência (opcional)')
                    ->rows(2),
            ])
            ->action(function (Student $record, array $data): void {
                $oldInstitutionId = $record->institution_id;
                $oldInstitution = $record->institution?->name ?? 'Sem instituição';
                $newInstitutionId = $data['institution_id'];
                $newInstitution = \App\Models\Institution::find($newInstitutionId)?->name ?? 'N/A';

                \App\Models\StudentTransferHistory::create([
                    'student_id' => $record->id,
                    'from_institution_id' => $oldInstitutionId,
                    'to_institution_id' => $newInstitutionId,
                    'transferred_by' => auth()->id(),
                    'student_name' => $record->candidate?->full_name ?? 'N/A',
                    'student_number' => $record->student_number ?? $record->nuri,
                    'bi_number' => $record->candidate?->id_number,
                    'student_type' => $record->student_type,
                    'rank' => $record->rank?->name,
                    'provenance' => $record->provenance?->name,
                    'phone' => $record->candidate?->phone,
                    'course' => $record->classEnrollments()?->latest()?->first()?->studentClass?->courseMap?->course?->name,
                    'student_class' => $record->classEnrollments()?->latest()?->first()?->studentClass?->name,
                    'cia' => $record->cia,
                    'platoon' => $record->platoon,
                    'section' => $record->section,
                    'notes' => $data['notes'] ?? null,
                    'transferred_at' => now(),
                ]);

                // Atualizar instituição
                $record->update(['institution_id' => $newInstitutionId]);

                // Atualizar student_type conforme o tipo de instituição de destino
                $newStudentType = \App\Models\Institution::getDefaultStudentTypeForId($newInstitutionId);
                if ($newStudentType) {
                    $newStudentTypeId = \App\Models\StudentType::getIdByName($newStudentType);
                    $record->update([
                        'student_type' => $newStudentType,
                        'student_type_id' => $newStudentTypeId,
                    ]);
                }

                \Filament\Notifications\Notification::make()
                    ->title('Aluno Movido!')
                    ->body("Transferido de \"{$oldInstitution}\" para \"{$newInstitution}\"")
                    ->success()
                    ->send();
            })
            ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Confirmar Transferência')->icon('heroicon-o-check')->color('primary'))
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->icon('heroicon-o-x-mark')->color('danger'));
    }

    private static function baixarFichaAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('imprimirFicha')
            ->label('Imprimir Ficha')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->modalHeading('Pré-visualização da Ficha de Inscrição')
            ->modalDescription(null)
            ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action
                ->icon('heroicon-o-x-mark')
                ->label('Fechar Pré-visualização')
                ->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(function (Student $record) {
                $printUrl = route('students.sheet.print', ['student' => $record]);
                $studentName = trim((string) ($record->candidate?->full_name ?: $record->full_name ?: 'Formando'));
                $identifierNumber = trim((string) ($record->nuri ?: $record->student_number ?: 'FORM-'.$record->getKey()));
                $frameId = 'sigef-student-sheet-frame-'.$record->getKey();
                $viewerId = 'sigef-student-sheet-viewer-'.$record->getKey();

                return view('trainers.sheet-modal', [
                    'viewerId' => $viewerId,
                    'frameId' => $frameId,
                    'documentName' => 'Ficha de Inscrição - '.$studentName,
                    'documentBadge' => 'FORMANDO: '.$identifierNumber,
                    'defaultOrientation' => 'vertical',
                    'embeddedHorizontalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=horizontal',
                    'embeddedVerticalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=vertical',
                    'fallbackPrintHorizontalUrl' => $printUrl.'?autoprint=1&orientation=horizontal',
                    'fallbackPrintVerticalUrl' => $printUrl.'?autoprint=1&orientation=vertical',
                ]);
            });
    }

    // ──────────────────────────────────────────────────────────────
    // BULK ACTIONS
    // ──────────────────────────────────────────────────────────────

    private static function imprimirCertificadoAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('imprimirCertificado')
            ->label('Imprimir Certificado')
            ->icon('heroicon-o-trophy')
            ->color('warning')
            ->visible(fn (Student $record): bool => static::canPrintCertificate($record))
            ->modalHeading('Pré-visualização do Certificado')
            ->modalDescription(null)
            ->modalWidth(\Filament\Support\Enums\Width::Screen)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action
                ->icon('heroicon-o-x-mark')
                ->label('Fechar Pré-visualização')
                ->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(function (Student $record) {
                $printUrl = route('certificados.individual', ['student' => $record]);
                $studentName = trim((string) ($record->candidate?->full_name ?: $record->full_name ?: 'Formando'));
                $identifierNumber = trim((string) ($record->nuri ?: $record->student_number ?: 'FORM-'.$record->getKey()));
                $frameId = 'sigef-student-certificate-frame-'.$record->getKey();
                $viewerId = 'sigef-student-certificate-viewer-'.$record->getKey();

                return view('trainers.sheet-modal', [
                    'viewerId' => $viewerId,
                    'frameId' => $frameId,
                    'documentName' => 'Certificado - '.$studentName,
                    'documentBadge' => 'APROVADO: '.$identifierNumber,
                    'defaultOrientation' => 'horizontal',
                    'embeddedHorizontalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=horizontal',
                    'embeddedVerticalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=horizontal',
                    'fallbackPrintHorizontalUrl' => $printUrl.'?autoprint=1&orientation=horizontal',
                    'fallbackPrintVerticalUrl' => $printUrl.'?autoprint=1&orientation=horizontal',
                    'documentType' => 'certificado',
                    'showOrientationSelector' => false,
                    'loadingText' => 'A preparar certificado...',
                    'hintText' => 'Pre-visualize o certificado em A4 antes de imprimir.',
                ]);
            });
    }

    private static function canPrintCertificate(Student $record): bool
    {
        $record->loadMissing([
            'evaluations',
            'classEnrollments.studentClass',
        ]);

        static::filterCertificateEvaluationsByInstitution($record);

        return GradeCalculator::result($record) === 'Aprovado';
    }

    private static function filterCertificateEvaluationsByInstitution(Student $record): void
    {
        $enrollment = $record->classEnrollments->firstWhere('is_active', true)
            ?? $record->classEnrollments->sortByDesc('enrolled_at')->first();

        $institutionId = $enrollment?->studentClass?->institution_id;

        if (! $institutionId || ! $record->relationLoaded('evaluations')) {
            return;
        }

        $record->setRelation(
            'evaluations',
            $record->evaluations->where('institution_id', $institutionId)->values()
        );
    }

    private static function recrutaParaInstruendoBulkAction(): \Filament\Actions\BulkAction
    {
        return \Filament\Actions\BulkAction::make('recrutaParaInstruendo')
            ->label('Recrutas → Instruendo')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('info')
            ->deselectRecordsAfterCompletion()
            ->requiresConfirmation()
            ->modalHeading('Promover Recrutas para Instruendo')
            ->modalIcon('heroicon-o-arrow-up-circle')
            ->modalIconColor('info')
            ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                $newType = '2ª Fase - Instruendo';
                $studentTypeId = StudentType::getIdByName($newType);
                $count = 0;
                $skipped = 0;

                foreach ($records as $student) {
                    if (! static::studentTypeContains($student->student_type, 'recruta')) {
                        $skipped++;
                        continue;
                    }

                    $student->update([
                        'student_type' => $newType,
                        'student_type_id' => $studentTypeId,
                    ]);

                    $count++;
                }

                $msg = "$count recrutas promovidos para 2ª Fase - Instruendo";
                if ($skipped > 0) {
                    $msg .= " ($skipped ignorados por não serem recrutas)";
                }

                \Filament\Notifications\Notification::make()
                    ->title('Promoção concluída!')
                    ->body($msg)
                    ->success()
                    ->send();
            })
            ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Promover')->color('primary'))
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'));
    }

    private static function emFormacaoParaConcluidoBulkAction(): \Filament\Actions\BulkAction
    {
        return \Filament\Actions\BulkAction::make('emFormacaoParaConcluido')
            ->label('Em Formação → Concluído')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->deselectRecordsAfterCompletion()
            ->requiresConfirmation()
            ->modalHeading('Concluir Formandos em Formação')
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                $newType = 'Formando Concluído';
                $studentTypeId = StudentType::getIdByName($newType);
                $count = 0;
                $skipped = 0;

                foreach ($records as $student) {
                    if (! static::studentTypeContains($student->student_type, 'em forma')) {
                        $skipped++;
                        continue;
                    }

                    $student->update([
                        'student_type' => $newType,
                        'student_type_id' => $studentTypeId,
                        'status' => 'concluiu',
                        'conclusion_date' => now(),
                    ]);

                    if ($student->candidate) {
                        $student->candidate->update([
                            'student_type' => $newType,
                        ]);
                    }

                    $count++;
                }

                $msg = "$count formandos concluídos";
                if ($skipped > 0) {
                    $msg .= " ($skipped ignorados por não estarem Em Formação)";
                }

                \Filament\Notifications\Notification::make()
                    ->title('Conclusão concluída!')
                    ->body($msg)
                    ->success()
                    ->send();
            })
            ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Concluir')->color('primary'))
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'));
    }

    private static function studentTypeContains(?string $studentType, string $needle): bool
    {
        return str_contains(strtolower(trim((string) $studentType)), strtolower($needle));
    }

    private static function atribuirEmMassaBulkAction(): \Filament\Actions\BulkAction
    {
        return \Filament\Actions\BulkAction::make('atribuirEmMassa')
            ->label('Inscrições em Massa')
            ->icon('heroicon-o-academic-cap')
            ->color('primary')
            ->modalHeading('Inscrições em Massa')
            ->modalDescription('Atribua turma, disciplinas, CIA, pelotão e secção.')
            ->modalWidth('4xl')
            ->form([
                \Filament\Schemas\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('course_id')
                        ->label('Curso')
                        ->options(\App\Models\Course::orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn($set) => $set('subject_ids', [])),
                    Forms\Components\Select::make('class_id')
                        ->label('Turma')
                        ->options(fn() => StudentClass::with('courseMap')->get()->mapWithKeys(fn($c) => [$c->id => $c->name]))
                        ->required()
                        ->searchable(),
                ]),
                \Filament\Schemas\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('cia')
                        ->label('CIA')
                        ->options(collect(range(1, 15))->mapWithKeys(fn($n) => [$n => "{$n}ª CIA"]))
                        ->searchable(),
                    Forms\Components\Select::make('platoon')
                        ->label('Pelotão')
                        ->options(collect(range(1, 15))->mapWithKeys(fn($n) => [$n => "{$n}º Pelotão"]))
                        ->searchable(),
                    Forms\Components\Select::make('section')
                        ->label('Secção')
                        ->options(collect(range(1, 15))->mapWithKeys(fn($n) => [$n => "{$n}ª Secção"]))
                        ->searchable(),
                ]),
                Forms\Components\Select::make('subject_ids')
                    ->label('Disciplinas')
                    ->options(function ($get) {
                        $courseId = $get('course_id');
                        $selectedIds = $get('subject_ids') ?? [];

                        if (!$courseId) return [];

                        $coursePhaseIds = CoursePhase::where('course_id', $courseId)->pluck('id');

                        $alreadySelected = collect();
                        if (!empty($selectedIds)) {
                            $alreadySelected = Subject::whereIn('id', $selectedIds)->pluck('name', 'id');
                        }

                        $allSubjects = Subject::query()
                            ->where(function (Builder $query) use ($courseId, $coursePhaseIds): void {
                                $query
                                    ->where('course_id', $courseId)
                                    ->orWhereIn('course_phase_id', $coursePhaseIds);
                            })
                            ->pluck('name', 'id');
                        return $alreadySelected->union($allSubjects)->all();
                    })
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->columnSpanFull(),
            ])
            ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                $updated = 0;
                $enrolledInClass = 0;
                $enrolledInSubjects = 0;
                $skippedClass = 0;
                $skippedSubjects = 0;

                $academicYearId = \App\Models\AcademicYear::where('is_active', true)->first()?->id;

                foreach ($records as $student) {
                    $wasUpdated = false;

                    $upd = [];
                    if (!empty($data['cia']) && empty($student->cia)) {
                        $upd['cia'] = $data['cia'];
                    }
                    if (!empty($data['platoon']) && empty($student->platoon)) {
                        $upd['platoon'] = $data['platoon'];
                    }
                    if (!empty($data['section']) && empty($student->section)) {
                        $upd['section'] = $data['section'];
                    }

                    if (!empty($upd)) {
                        $student->update($upd);
                        $wasUpdated = true;
                    }

                    if (!empty($data['class_id'])) {
                        $phaseName = static::phaseNameForStudentType($student->student_type);
                        $coursePhaseId = static::resolveCoursePhaseIdForInscription((int) ($data['course_id'] ?? 0), $phaseName);

                        $enrollment = StudentClassEnrollment::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'class_id' => $data['class_id'],
                            ],
                            [
                                'course_phase_id' => $coursePhaseId,
                                'academic_year_id' => $academicYearId,
                                'student_type' => $student->student_type,
                                'is_active' => true,
                                'enrolled_at' => now(),
                                'enrolled_by' => auth()->id(),
                            ]
                        );

                        $courseMapId = \App\Models\StudentClass::find($data['class_id'])?->course_map_id;
                        $student->update(['course_map_id' => $courseMapId]);

                        $enrolledInClass++;
                        $wasUpdated = true;
                    }

                    if (!empty($data['subject_ids']) && !empty($data['class_id'])) {
                        $classId = $data['class_id'];

                        $addedSubjects = false;
                        foreach ($data['subject_ids'] as $subjectId) {
                            $exists = StudentSubjectEnrollment::where([
                                'student_id' => $student->id,
                                'subject_id' => $subjectId,
                                'class_id' => $classId,
                            ])->exists();

                            if (!$exists) {
                                $subject = Subject::find($subjectId);
                                StudentSubjectEnrollment::create([
                                    'student_id' => $student->id,
                                    'subject_id' => $subjectId,
                                    'class_id' => $classId,
                                    'course_phase_id' => $subject?->course_phase_id,
                                    'enrolled_at' => now(),
                                    'is_active' => true,
                                ]);
                                $addedSubjects = true;
                            }
                        }

                        if ($addedSubjects) {
                            $enrolledInSubjects++;
                        } else {
                            $skippedSubjects++;
                        }
                    }

                    if ($wasUpdated) $updated++;
                }

                $msg = [];
                if ($updated > 0) $msg[] = "{$updated} formandos atualizados";
                if ($enrolledInClass > 0) $msg[] = "{$enrolledInClass} inscritos em turma";
                if ($enrolledInSubjects > 0) $msg[] = "{$enrolledInSubjects} inscritos em disciplinas";
                if ($skippedClass > 0) $msg[] = "{$skippedClass} já tinham turma (ignorados)";
                if ($skippedSubjects > 0) $msg[] = "{$skippedSubjects} já tinham disciplinas (ignorados)";

                \Filament\Notifications\Notification::make()
                    ->title('Inscrições em Massa Concluída!')
                    ->body(implode(', ', $msg) ?: 'Nenhuma alteração feita')
                    ->success()
                    ->duration(10000)
                    ->send();
            })
            ->deselectRecordsAfterCompletion()
            ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar Inscrição')->color('primary'))
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'));
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
            'index' => Pages\ListStudentClassEnrollments::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->can('ViewAny:StudentClassEnrollment') || $user?->can('ViewAny:Student')) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
