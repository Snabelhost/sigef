<?php

namespace App\Filament\Resources\Concerns;

use App\Models\AcademicYear;
use App\Models\CoursePlan;
use App\Models\CoursePhase;
use App\Models\Effective;
use App\Models\Provenance;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubjectEnrollment;
use App\Models\StudentType;
use App\Models\Subject;
use App\Support\PublicStorage;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

trait StudentEnrollmentEditForm
{
    public static function enrollmentInscriptionFormSchema(Student $record): array
    {
        $lastEnrollment = static::currentEnrollmentForEdit($record);
        $defaultAcademicYearId = static::currentAcademicYearIdForEdit($record);
        $defaultInstitutionId = $lastEnrollment?->studentClass?->institution_id
            ?? $lastEnrollment?->studentClass?->courseMap?->institution_id
            ?? $record->institution_id
            ?? $record->candidate?->institution_id;
        $defaultCourseId = static::currentCourseIdForEdit($record);
        $defaultNipNuri = static::defaultEnrollmentNipNuri($record);

        return [
            \Filament\Schemas\Components\Grid::make([
                'default' => 1,
                'md' => 3,
            ])->schema([
                Forms\Components\Select::make('academic_year_id')
                    ->label('Ano Lectivo')
                    ->options(fn (): array => AcademicYear::query()
                        ->orderByDesc('year')
                        ->get()
                        ->mapWithKeys(fn (AcademicYear $year): array => [$year->id => $year->year ?: $year->name ?: (string) $year->id])
                        ->toArray())
                    ->default($defaultAcademicYearId)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set): void {
                        $set('course_id', null);
                        $set('class_id', null);
                    }),
                Forms\Components\Select::make('institution_id')
                    ->label('Escola')
                    ->options(fn (): array => \App\Models\Institution::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->default($defaultInstitutionId)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set): void {
                        $set('course_id', null);
                        $set('class_id', null);
                    }),
                Forms\Components\Select::make('course_id')
                    ->label('Curso')
                    ->options(fn ($get): array => static::inscriptionCourseOptions($get('academic_year_id'), $get('institution_id')))
                    ->default($defaultCourseId)
                    ->required()
                    ->live()
                    ->searchable()
                    ->preload()
                    ->afterStateUpdated(function (callable $set): void {
                        $set('class_id', null);
                    }),
            ]),
            \Filament\Schemas\Components\Grid::make([
                'default' => 1,
                'md' => 3,
            ])->schema([
                Forms\Components\Select::make('class_id')
                    ->label('Turma')
                    ->options(fn ($get): array => static::inscriptionClassOptions($get('academic_year_id'), $get('institution_id'), $get('course_id')))
                    ->default($lastEnrollment?->class_id)
                    ->required()
                    ->live()
                    ->searchable()
                    ->preload()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        $class = StudentClass::query()->with('courseMap')->find($state);

                        if (! $class) {
                            return;
                        }

                        $set('institution_id', $class->institution_id ?? $class->courseMap?->institution_id);
                        $set('academic_year_id', $class->academic_year_id ?? $class->courseMap?->academic_year_id);
                        $set('course_id', $class->courseMap?->course_id);
                    }),
                Forms\Components\TextInput::make('nuri')
                    ->label('NIP/NURI')
                    ->default($defaultNipNuri)
                    ->maxLength(9),
                Forms\Components\Select::make('cia')
                    ->label('CIA')
                    ->options(collect(range(1, 15))->mapWithKeys(fn (int $number): array => [$number => "{$number}ª CIA"]))
                    ->default($record->cia)
                    ->searchable(),
            ]),
            \Filament\Schemas\Components\Grid::make([
                'default' => 1,
                'md' => 3,
            ])->schema([
                Forms\Components\Select::make('platoon')
                    ->label('Pelotão')
                    ->options(collect(range(1, 15))->mapWithKeys(fn (int $number): array => [$number => "{$number}º Pelotão"]))
                    ->default($record->platoon)
                    ->searchable(),
                Forms\Components\Select::make('section')
                    ->label('Secção')
                    ->options(collect(range(1, 15))->mapWithKeys(fn (int $number): array => [$number => "{$number}ª Secção"]))
                    ->default($record->section)
                    ->searchable(),
            ]),
        ];
    }

    public static function updateEnrollmentInscription(Student $record, array $data): void
    {
        $studentClass = StudentClass::query()
            ->with(['courseMap', 'coursePlan'])
            ->find($data['class_id'] ?? null);

        $courseId = (int) ($data['course_id'] ?? $studentClass?->courseMap?->course_id ?? 0);
        $academicYearId = $data['academic_year_id']
            ?? $studentClass?->academic_year_id
            ?? $studentClass?->courseMap?->academic_year_id
            ?? AcademicYear::query()->where('is_active', true)->value('id');
        $currentEnrollment = static::currentEnrollmentForEdit($record);
        $phaseName = $currentEnrollment?->coursePhase?->name
            ?: static::phaseNameForStudentType($record->student_type);
        $coursePhaseId = static::resolveCoursePhaseIdForInscription($courseId, $phaseName);
        $nuri = filled($data['nuri'] ?? null)
            ? trim((string) $data['nuri'])
            : static::defaultEnrollmentNipNuri($record);

        $record->update([
            'institution_id' => $data['institution_id'] ?? $studentClass?->institution_id ?? $studentClass?->courseMap?->institution_id ?? $record->institution_id,
            'course_map_id' => $studentClass?->course_map_id ?? $record->course_map_id,
            'current_phase_id' => $coursePhaseId ?? $record->current_phase_id,
            'nuri' => $nuri,
            'cia' => $data['cia'] ?? null,
            'platoon' => $data['platoon'] ?? null,
            'section' => $data['section'] ?? null,
        ]);

        if (! $studentClass) {
            return;
        }

        StudentClassEnrollment::query()->updateOrCreate(
            [
                'student_id' => $record->id,
                'class_id' => $studentClass->id,
            ],
            [
                'course_phase_id' => $coursePhaseId,
                'academic_year_id' => $academicYearId,
                'student_type' => $record->student_type,
                'is_active' => true,
                'enrolled_at' => now(),
                'enrolled_by' => auth()->id(),
            ],
        );

        $subjectIds = static::automaticSubjectIdsForInscription(
            studentClass: $studentClass,
            courseId: $courseId,
            academicYearId: $academicYearId ? (int) $academicYearId : null,
            phaseName: $phaseName,
            coursePhaseId: $coursePhaseId,
        );

        if ($subjectIds === []) {
            \Filament\Notifications\Notification::make()
                ->title('Inscrição salva, mas o plano do curso não tem disciplinas.')
                ->warning()
                ->send();

            return;
        }

        $deactivateQuery = StudentSubjectEnrollment::query()
            ->where('student_id', $record->id)
            ->where('class_id', $studentClass->id)
            ->whereNotIn('subject_id', $subjectIds);

        if ($coursePhaseId) {
            $deactivateQuery->where('course_phase_id', $coursePhaseId);
        }

        $deactivateQuery->update(['is_active' => false]);

        Subject::query()
            ->whereIn('id', $subjectIds)
            ->get(['id', 'course_phase_id'])
            ->each(function (Subject $subject) use ($record, $studentClass, $coursePhaseId): void {
                StudentSubjectEnrollment::query()->updateOrCreate(
                    [
                        'student_id' => $record->id,
                        'subject_id' => $subject->id,
                        'class_id' => $studentClass->id,
                        'course_phase_id' => $coursePhaseId ?: $subject->course_phase_id,
                    ],
                    [
                        'is_active' => true,
                    ],
                );
            });
    }

    public static function enrollmentEditFormSchema(bool $viewMode = false): array
    {
        $photoControls = [
            Forms\Components\FileUpload::make('candidate_photo')
                ->label('Foto')
                ->hiddenLabel()
                ->image()
                ->disk('public')
                ->directory('candidates/photos')
                ->visibility('public')
                ->acceptedFileTypes(['image/*'])
                ->extraInputAttributes([
                    'accept' => 'image/*',
                    'data-sigef-photo-input' => 'true',
                ])
                ->extraAttributes([
                    'class' => 'sigef-trainer-photo-upload',
                    'data-sigef-photo-upload' => 'student-enrollment',
                ])
                ->imagePreviewHeight('10rem')
                ->panelAspectRatio('1:1')
                ->panelLayout('integrated')
                ->placeholder(static::studentEnrollmentPhotoUploadPlaceholder())
                ->default(fn (Student $record) => PublicStorage::existingPath($record->candidate?->photo))
                ->openable()
                ->previewable()
                ->maxSize(4096),
        ];

        $photoControls[] = $viewMode
            ? \Filament\Schemas\Components\Html::make(fn (?Student $record): HtmlString => static::studentEnrollmentPhotoPreviewTrigger($record))
            : \Filament\Schemas\Components\Html::make(static::studentEnrollmentPhotoUploadActions());

        return [
            \Filament\Schemas\Components\Section::make('Dados da Matrícula')
                ->schema([
                    \Filament\Schemas\Components\Grid::make([
                        'default' => 1,
                        'lg' => 12,
                    ])->schema([
                        \Filament\Schemas\Components\Group::make($photoControls)
                            ->extraAttributes([
                                'class' => 'sigef-trainer-photo-view-group',
                            ])
                            ->columnSpan(['default' => 1, 'lg' => 3]),

                        \Filament\Schemas\Components\Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            Forms\Components\Select::make('academic_year_id')
                                ->label('Ano lectivo')
                                ->options(fn (): array => AcademicYear::query()->orderByDesc('year')->pluck('year', 'id')->toArray())
                                ->default(fn (Student $record) => static::currentAcademicYearIdForEdit($record))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (callable $set): void {
                                    $set('course_id', null);
                                    $set('class_id', null);
                                }),
                            Forms\Components\Select::make('institution_id')
                                ->label('Escola')
                                ->options(fn (): array => \App\Models\Institution::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->default(fn (Student $record) => static::currentInstitutionIdForEdit($record))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (callable $set): void {
                                    $set('course_id', null);
                                    $set('class_id', null);
                                }),
                            Forms\Components\TextInput::make('candidate_full_name')
                                ->label('Nome completo')
                                ->default(fn (Student $record) => $record->candidate?->full_name)
                                ->required()
                                ->maxLength(191),
                            Forms\Components\Select::make('candidate_gender')
                                ->label('Sexo')
                                ->options([
                                    'Masculino' => 'Masculino',
                                    'Feminino' => 'Feminino',
                                ])
                                ->default(fn (Student $record) => $record->candidate?->gender)
                                ->native(false),
                            Forms\Components\DatePicker::make('candidate_birth_date')
                                ->label('Data de Nascimento')
                                ->default(fn (Student $record) => $record->candidate?->birth_date)
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                        ])->columnSpan(['default' => 1, 'lg' => 9]),

                        \Filament\Schemas\Components\Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])->schema([
                            Forms\Components\Select::make('course_id')
                                ->label('Curso')
                                ->options(fn ($get): array => static::inscriptionCourseOptions($get('academic_year_id'), $get('institution_id')))
                                ->default(fn (Student $record) => static::currentCourseIdForEdit($record))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (callable $set): void {
                                    $set('class_id', null);
                                }),
                            Forms\Components\Select::make('class_id')
                                ->label('Turma')
                                ->options(fn ($get): array => static::inscriptionClassOptions($get('academic_year_id'), $get('institution_id'), $get('course_id')))
                                ->default(fn (Student $record) => static::currentEnrollmentForEdit($record)?->class_id)
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    $class = StudentClass::query()->with('courseMap')->find($state);

                                    if (! $class) {
                                        return;
                                    }

                                    $set('institution_id', $class->institution_id ?? $class->courseMap?->institution_id);
                                    $set('academic_year_id', $class->academic_year_id ?? $class->courseMap?->academic_year_id);
                                    $set('course_id', $class->courseMap?->course_id);
                                }),
                            Forms\Components\TextInput::make('nuri')
                                ->label('NURI / NIP')
                                ->default(fn (Student $record) => $record->nuri)
                                ->maxLength(191),
                            Forms\Components\Select::make('cia')
                                ->label('CIA')
                                ->options(collect(range(1, 15))->mapWithKeys(fn (int $number): array => [$number => "{$number}ª CIA"]))
                                ->default(fn (Student $record) => $record->cia)
                                ->searchable(),
                            Forms\Components\Select::make('platoon')
                                ->label('Pelotão')
                                ->options(collect(range(1, 15))->mapWithKeys(fn (int $number): array => [$number => "{$number}º Pelotão"]))
                                ->default(fn (Student $record) => $record->platoon)
                                ->searchable(),
                            Forms\Components\Select::make('section')
                                ->label('Secção')
                                ->options(collect(range(1, 15))->mapWithKeys(fn (int $number): array => [$number => "{$number}ª Secção"]))
                                ->default(fn (Student $record) => $record->section)
                                ->searchable(),
                            Forms\Components\Select::make('student_status')
                                ->label('Estado académico')
                                ->options(fn (?Student $record = null): array => static::academicStatusOptions($record))
                                ->default(fn (Student $record) => static::defaultAcademicStatusForEdit($record))
                                ->native(false)
                                ->searchable(),
                        ])->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),

            \Filament\Schemas\Components\Tabs::make('matricula_tabs')
                ->columnSpanFull()
                ->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('Identidade')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Dados de Identidade')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('candidate_country')
                                        ->label('País')
                                        ->options(['Angola' => 'Angola'])
                                        ->default(fn (Student $record) => $record->candidate?->country ?: 'Angola')
                                        ->searchable()
                                        ->native(false),
                                    Forms\Components\Select::make('candidate_province_id')
                                        ->label('Província')
                                        ->options(fn (): array => \App\Models\Province::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                        ->default(fn (Student $record) => $record->candidate?->province_id)
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(fn (callable $set) => $set('candidate_municipality_id', null)),
                                    Forms\Components\Select::make('candidate_municipality_id')
                                        ->label('Naturalidade (município)')
                                        ->options(function ($get): array {
                                            $provinceId = $get('candidate_province_id');

                                            return \App\Models\Municipality::query()
                                                ->when($provinceId, fn (Builder $query) => $query->where('province_id', $provinceId))
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->default(fn (Student $record) => $record->candidate?->municipality_id)
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Select::make('identity_document_type')
                                        ->label('Tipo de Documento')
                                        ->options(['Bilhete de Identidade' => 'Bilhete de Identidade'])
                                        ->default('Bilhete de Identidade')
                                        ->native(false)
                                        ->dehydrated(false),
                                    Forms\Components\TextInput::make('candidate_id_number')
                                        ->label('Nº do Documento')
                                        ->default(fn (Student $record) => $record->candidate?->id_number)
                                        ->maxLength(191),
                                    Forms\Components\TextInput::make('identity_issued_place')
                                        ->label('Local Emitido')
                                        ->dehydrated(false)
                                        ->maxLength(191),
                                    Forms\Components\DatePicker::make('identity_issued_at')
                                        ->label('Data Emissão')
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->dehydrated(false),
                                    Forms\Components\DatePicker::make('identity_expires_at')
                                        ->label('Data Validade')
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->dehydrated(false),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('Agregado Familiar')
                        ->icon('heroicon-o-users')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Agregado Familiar')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('candidate_father_name')
                                        ->label('Nome do Pai')
                                        ->default(fn (Student $record) => $record->candidate?->father_name)
                                        ->maxLength(191),
                                    Forms\Components\TextInput::make('candidate_mother_name')
                                        ->label('Nome da Mãe')
                                        ->default(fn (Student $record) => $record->candidate?->mother_name)
                                        ->maxLength(191),
                                    Forms\Components\Select::make('candidate_marital_status')
                                        ->label('Estado civil')
                                        ->options([
                                            'solteiro' => 'Solteiro(a)',
                                            'casado' => 'Casado(a)',
                                            'divorciado' => 'Divorciado(a)',
                                            'viuvo' => 'Viúvo(a)',
                                        ])
                                        ->default(fn (Student $record) => $record->candidate?->marital_status)
                                        ->native(false),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('Contactos')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Contactos')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('candidate_phone')
                                        ->label('Telefone')
                                        ->tel()
                                        ->default(fn (Student $record) => $record->candidate?->phone ?? $record->phone)
                                        ->maxLength(191),
                                    Forms\Components\TextInput::make('candidate_email')
                                        ->label('E-mail')
                                        ->email()
                                        ->default(fn (Student $record) => $record->candidate?->email)
                                        ->maxLength(191),
                                    Forms\Components\TextInput::make('candidate_address')
                                        ->label('Endereço')
                                        ->default(fn (Student $record) => $record->candidate?->address)
                                        ->maxLength(191)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('Escola de Proveniência')
                        ->icon('heroicon-o-academic-cap')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Escola de Proveniência')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('candidate_institution_id')
                                        ->label('Instituição')
                                        ->options(fn (): array => \App\Models\Institution::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                        ->default(fn (Student $record) => $record->candidate?->institution_id ?? $record->institution_id)
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Select::make('candidate_provenance_id')
                                        ->label('Órgão de Proveniência')
                                        ->options(fn (): array => static::studentProvenanceOptions())
                                        ->default(fn (Student $record) => $record->candidate?->provenance_id ?? $record->provenance_id)
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\TextInput::make('previous_school')
                                        ->label('Escola anterior')
                                        ->dehydrated(false)
                                        ->maxLength(191),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('Dados Profissionais')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Dados Profissionais')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('candidate_current_rank_id')
                                        ->label('Posto')
                                        ->options(fn (): array => \App\Models\Rank::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                        ->default(fn (Student $record) => $record->candidate?->current_rank_id ?? $record->rank_id)
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Select::make('candidate_education_level')
                                        ->label('Grau académico')
                                        ->options(static::studentEducationLevelOptions())
                                        ->default(fn (Student $record) => $record->candidate?->education_level)
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\TextInput::make('candidate_education_area')
                                        ->label('Área de formação')
                                        ->default(fn (Student $record) => $record->candidate?->education_area)
                                        ->maxLength(191),
                                    Forms\Components\DatePicker::make('candidate_pna_entry_date')
                                        ->label('Data de entrada na PNA')
                                        ->default(fn (Student $record) => $record->candidate?->pna_entry_date)
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('Estado de Saúde')
                        ->icon('heroicon-o-heart')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Estado de Saúde')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('candidate_blood_type')
                                        ->label('Grupo sanguíneo')
                                        ->options(Effective::bloodTypeOptions())
                                        ->default(fn (Student $record) => $record->candidate?->blood_type)
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Textarea::make('health_notes')
                                        ->label('Observações')
                                        ->rows(3)
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('Arquivos')
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Arquivos')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\FileUpload::make('candidate_bilhete_identidade')
                                        ->label('Bilhete de Identidade')
                                        ->disk('public')
                                        ->directory('candidates/documents')
                                        ->default(fn (Student $record) => PublicStorage::existingPath($record->candidate?->bilhete_identidade))
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->openable()
                                        ->previewable(),
                                    Forms\Components\FileUpload::make('candidate_certificado_doc')
                                        ->label('Certificado')
                                        ->disk('public')
                                        ->directory('candidates/documents')
                                        ->default(fn (Student $record) => PublicStorage::existingPath($record->candidate?->certificado_doc))
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->openable()
                                        ->previewable(),
                                    Forms\Components\FileUpload::make('candidate_curriculum')
                                        ->label('Curriculum')
                                        ->disk('public')
                                        ->directory('candidates/documents')
                                        ->default(fn (Student $record) => PublicStorage::existingPath($record->candidate?->curriculum))
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->openable()
                                        ->previewable(),
                                ]),
                        ]),
                ]),
        ];
    }

    public static function updateEnrollmentFromEditForm(Student $record, array $data): void
    {
        $candidate = $record->candidate;
        $studentType = static::studentTypeForEditPayload($record, $data);
        $studentTypeId = $studentType ? static::studentTypeIdForName($studentType) : $record->student_type_id;

        if ($candidate) {
            $candidate->update([
                'academic_year_id' => $data['academic_year_id'] ?? $candidate->academic_year_id,
                'institution_id' => $data['institution_id'] ?? $data['candidate_institution_id'] ?? $candidate->institution_id,
                'provenance_id' => $data['candidate_provenance_id'] ?? $candidate->provenance_id,
                'current_rank_id' => $data['candidate_current_rank_id'] ?? $candidate->current_rank_id,
                'id_number' => $data['candidate_id_number'] ?? null,
                'full_name' => $data['candidate_full_name'] ?? $candidate->full_name,
                'birth_date' => $data['candidate_birth_date'] ?? null,
                'gender' => $data['candidate_gender'] ?? null,
                'blood_type' => $data['candidate_blood_type'] ?? $candidate->blood_type,
                'country' => $data['candidate_country'] ?? $candidate->country,
                'student_type' => $studentType ?? $candidate->student_type,
                'marital_status' => $data['candidate_marital_status'] ?? null,
                'father_name' => $data['candidate_father_name'] ?? null,
                'mother_name' => $data['candidate_mother_name'] ?? null,
                'province_id' => $data['candidate_province_id'] ?? null,
                'municipality_id' => $data['candidate_municipality_id'] ?? null,
                'address' => $data['candidate_address'] ?? null,
                'phone' => $data['candidate_phone'] ?? null,
                'email' => $data['candidate_email'] ?? null,
                'photo' => $data['candidate_photo'] ?? null,
                'education_level' => $data['candidate_education_level'] ?? null,
                'education_area' => $data['candidate_education_area'] ?? null,
                'pna_entry_date' => $data['candidate_pna_entry_date'] ?? null,
                'bilhete_identidade' => $data['candidate_bilhete_identidade'] ?? null,
                'certificado_doc' => $data['candidate_certificado_doc'] ?? null,
                'curriculum' => $data['candidate_curriculum'] ?? null,
            ]);
        }

        $studentClass = ! empty($data['class_id'])
            ? StudentClass::query()->with('courseMap')->find($data['class_id'])
            : null;

        $record->update([
            'institution_id' => $data['institution_id'] ?? $data['candidate_institution_id'] ?? $studentClass?->institution_id ?? $studentClass?->courseMap?->institution_id ?? $record->institution_id,
            'provenance_id' => $data['candidate_provenance_id'] ?? $record->provenance_id,
            'rank_id' => $data['candidate_current_rank_id'] ?? $record->rank_id,
            'course_map_id' => $studentClass?->course_map_id ?? $record->course_map_id,
            'current_phase_id' => $data['course_phase_id'] ?? $record->current_phase_id,
            'student_type' => $studentType ?? $record->student_type,
            'student_type_id' => $studentTypeId,
            'nuri' => $data['nuri'] ?? $data['student_nuri'] ?? $record->nuri,
            'cia' => $data['cia'] ?? $record->cia,
            'platoon' => $data['platoon'] ?? $record->platoon,
            'section' => $data['section'] ?? $record->section,
            'phone' => $data['candidate_phone'] ?? $record->phone,
            'photo' => $data['candidate_photo'] ?? $record->photo,
            'bilhete_identidade' => $data['candidate_bilhete_identidade'] ?? $record->bilhete_identidade,
            'certificado_doc' => $data['candidate_certificado_doc'] ?? $record->certificado_doc,
        ]);

        if ($studentClass) {
            $enrollment = static::currentEnrollmentForEdit($record);
            $coursePhaseId = $data['course_phase_id']
                ?? $enrollment?->course_phase_id
                ?? $record->current_phase_id;

            $payload = [
                'class_id' => $studentClass->id,
                'course_phase_id' => $coursePhaseId,
                'academic_year_id' => $data['academic_year_id'] ?? $studentClass->academic_year_id,
                'student_type' => $record->student_type,
                'classroom' => $enrollment?->classroom,
                'is_active' => true,
                'enrolled_at' => $enrollment?->enrolled_at ?? now(),
                'enrolled_by' => $enrollment?->enrolled_by ?? auth()->id(),
            ];

            if ($enrollment) {
                $enrollment->update($payload);
            } else {
                StudentClassEnrollment::query()->create([
                    'student_id' => $record->id,
                    ...$payload,
                ]);
            }
        }

        \Filament\Notifications\Notification::make()
            ->title('Formando atualizado com sucesso!')
            ->success()
            ->send();
    }

    protected static function inscriptionCourseOptions($academicYearId = null, $institutionId = null): array
    {
        $academicYearId = $academicYearId ? (int) $academicYearId : null;
        $institutionId = $institutionId ? (int) $institutionId : null;

        if (! $academicYearId && ! $institutionId) {
            return \App\Models\Course::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        }

        $courseIds = \App\Models\CourseMap::query()
            ->when($academicYearId, fn (Builder $query) => $query->where('academic_year_id', $academicYearId))
            ->when($institutionId, fn (Builder $query) => $query->where('institution_id', $institutionId))
            ->pluck('course_id')
            ->filter()
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return [];
        }

        return \App\Models\Course::query()
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function inscriptionClassOptions($academicYearId = null, $institutionId = null, $courseId = null, $shift = null): array
    {
        $academicYearId = $academicYearId ? (int) $academicYearId : null;
        $institutionId = $institutionId ? (int) $institutionId : null;
        $courseId = $courseId ? (int) $courseId : null;
        $shift = filled($shift) ? (string) $shift : null;

        return StudentClass::query()
            ->with(['courseMap.course', 'academicYear'])
            ->when($courseId, fn (Builder $query) => $query->whereHas('courseMap', fn (Builder $courseMapQuery) => $courseMapQuery->where('course_id', $courseId)))
            ->when($shift, fn (Builder $query) => $query->where('shift', $shift))
            ->when($academicYearId, function (Builder $query) use ($academicYearId): void {
                $query->where(function (Builder $yearQuery) use ($academicYearId): void {
                    $yearQuery
                        ->where('academic_year_id', $academicYearId)
                        ->orWhereHas('courseMap', fn (Builder $courseMapQuery) => $courseMapQuery->where('academic_year_id', $academicYearId));
                });
            })
            ->when($institutionId, function (Builder $query) use ($institutionId): void {
                $query->where(function (Builder $schoolQuery) use ($institutionId): void {
                    $schoolQuery
                        ->where('institution_id', $institutionId)
                        ->orWhereHas('courseMap', fn (Builder $courseMapQuery) => $courseMapQuery->where('institution_id', $institutionId));
                });
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (StudentClass $studentClass): array {
                $parts = array_filter([
                    $studentClass->name,
                    $studentClass->shift,
                    $studentClass->room_number ? 'Sala '.$studentClass->room_number : null,
                ]);

                return [$studentClass->id => implode(' - ', $parts)];
            })
            ->toArray();
    }

    protected static function inscriptionPhaseOptions($courseId = null): array
    {
        $courseId = $courseId ? (int) $courseId : null;

        $options = CoursePhase::query()
            ->when($courseId, fn (Builder $query) => $query->where('course_id', $courseId))
            ->orderBy('order')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();

        return $options ?: [
            '1ª Fase' => '1ª Fase',
            '2ª Fase' => '2ª Fase',
        ];
    }

    protected static function phaseNameForStudentType(?string $studentType): ?string
    {
        $studentType = strtolower(trim((string) $studentType));

        if ($studentType === '') {
            return null;
        }

        if (str_contains($studentType, '2') || str_contains($studentType, 'instruendo')) {
            return '2ª Fase';
        }

        if (str_contains($studentType, '1') || str_contains($studentType, 'recruta')) {
            return '1ª Fase';
        }

        return null;
    }

    protected static function resolveCoursePhaseIdForInscription(int $courseId, ?string $phaseName): ?int
    {
        $phaseName = trim((string) $phaseName);

        if ($courseId <= 0 || $phaseName === '') {
            return null;
        }

        return CoursePhase::query()->firstOrCreate(
            ['course_id' => $courseId, 'name' => $phaseName],
            ['order' => ((int) CoursePhase::query()->where('course_id', $courseId)->max('order')) + 1],
        )->id;
    }

    protected static function automaticSubjectIdsForInscription(
        StudentClass $studentClass,
        int $courseId,
        ?int $academicYearId,
        ?string $phaseName,
        ?int $coursePhaseId,
    ): array {
        $phaseName = trim((string) $phaseName);

        $coursePlan = $studentClass->course_plan_id
            ? CoursePlan::query()->find($studentClass->course_plan_id)
            : null;

        if (! $coursePlan && $courseId > 0 && $academicYearId) {
            $coursePlan = CoursePlan::query()
                ->where('course_id', $courseId)
                ->where('academic_year_id', $academicYearId)
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();
        }

        if (! $coursePlan && $courseId > 0) {
            $coursePlan = CoursePlan::query()
                ->where('course_id', $courseId)
                ->orderByDesc('is_active')
                ->orderByDesc('academic_year_id')
                ->orderBy('id')
                ->first();
        }

        if ($coursePlan) {
            $planSubjectIds = $coursePlan->subjects()
                ->when($phaseName !== '' || $coursePhaseId, function (Builder $query) use ($phaseName, $coursePhaseId): void {
                    $query->where(function (Builder $subjectQuery) use ($phaseName, $coursePhaseId): void {
                        $subjectQuery->where(function (Builder $blankPhaseQuery): void {
                            $blankPhaseQuery
                                ->whereNull('subjects.phases')
                                ->orWhereJsonLength('subjects.phases', 0);
                        });

                        if ($phaseName !== '') {
                            $subjectQuery->orWhereJsonContains('subjects.phases', $phaseName);
                        }

                        if ($coursePhaseId) {
                            $subjectQuery->orWhere('subjects.course_phase_id', $coursePhaseId);
                        }
                    });
                })
                ->orderBy('course_plan_subjects.order')
                ->orderBy('subjects.name')
                ->pluck('subjects.id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($planSubjectIds !== []) {
                return $planSubjectIds;
            }
        }

        if ($courseId <= 0) {
            return [];
        }

        $coursePhaseIds = CoursePhase::query()
            ->where('course_id', $courseId)
            ->pluck('id');

        return Subject::query()
            ->where(function (Builder $query) use ($courseId, $coursePhaseIds): void {
                $query
                    ->where('course_id', $courseId)
                    ->orWhereIn('course_phase_id', $coursePhaseIds);
            })
            ->when($phaseName !== '' || $coursePhaseId, function (Builder $query) use ($phaseName, $coursePhaseId): void {
                $query->where(function (Builder $subjectQuery) use ($phaseName, $coursePhaseId): void {
                    $subjectQuery->where(function (Builder $blankPhaseQuery): void {
                        $blankPhaseQuery
                            ->whereNull('phases')
                            ->orWhereJsonLength('phases', 0);
                    });

                    if ($phaseName !== '') {
                        $subjectQuery->orWhereJsonContains('phases', $phaseName);
                    }

                    if ($coursePhaseId) {
                        $subjectQuery->orWhere('course_phase_id', $coursePhaseId);
                    }
                });
            })
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected static function defaultEnrollmentNipNuri(Student $record): ?string
    {
        foreach ([$record->nuri, $record->candidate?->nuri] as $value) {
            $value = trim((string) $value);

            if ($value !== '' && $value !== '-') {
                return $value;
            }
        }

        return null;
    }

    protected static function currentEnrollmentForEdit(Student $student): ?StudentClassEnrollment
    {
        static $cache = [];

        return $cache[$student->getKey()] ??= $student->classEnrollments()
            ->with(['studentClass.courseMap', 'coursePhase', 'academicYear'])
            ->latest('enrolled_at')
            ->latest('id')
            ->first();
    }

    protected static function currentCourseIdForEdit(Student $student): ?int
    {
        $enrollment = static::currentEnrollmentForEdit($student);

        return $enrollment?->studentClass?->courseMap?->course_id
            ?? $student->courseMap?->course_id;
    }

    protected static function currentInstitutionIdForEdit(Student $student): ?int
    {
        $enrollment = static::currentEnrollmentForEdit($student);

        return $enrollment?->studentClass?->institution_id
            ?? $enrollment?->studentClass?->courseMap?->institution_id
            ?? $student->institution_id
            ?? $student->candidate?->institution_id;
    }

    protected static function currentAcademicYearIdForEdit(Student $student): ?int
    {
        $enrollment = static::currentEnrollmentForEdit($student);

        return $enrollment?->academic_year_id
            ?? $enrollment?->studentClass?->academic_year_id
            ?? $enrollment?->studentClass?->courseMap?->academic_year_id
            ?? $student->candidate?->academic_year_id
            ?? AcademicYear::query()->where('is_active', true)->value('id');
    }

    protected static function classOptionsForEnrollmentEdit($academicYearId, $courseId): array
    {
        $academicYearId = $academicYearId ? (int) $academicYearId : null;
        $courseId = $courseId ? (int) $courseId : null;

        return StudentClass::query()
            ->with(['courseMap.course', 'academicYear'])
            ->when($courseId, fn (Builder $query) => $query->whereHas('courseMap', fn (Builder $courseMapQuery) => $courseMapQuery->where('course_id', $courseId)))
            ->when($academicYearId, function (Builder $query) use ($academicYearId): void {
                $query->where(function (Builder $yearQuery) use ($academicYearId): void {
                    $yearQuery
                        ->where('academic_year_id', $academicYearId)
                        ->orWhereHas('courseMap', fn (Builder $courseMapQuery) => $courseMapQuery->where('academic_year_id', $academicYearId));
                });
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (StudentClass $studentClass): array {
                $parts = array_filter([
                    $studentClass->name,
                    $studentClass->shift,
                ]);

                return [$studentClass->id => implode(' - ', $parts)];
            })
            ->toArray();
    }

    protected static function coursePhaseOptionsForEnrollmentEdit($courseId): array
    {
        $courseId = $courseId ? (int) $courseId : null;

        return CoursePhase::query()
            ->when($courseId, fn (Builder $query) => $query->where('course_id', $courseId))
            ->orderBy('order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function studentEducationLevelOptions(): array
    {
        return [
            'Ensino Primário' => 'Ensino Primário',
            '7ª Classe' => '7ª Classe',
            '8ª Classe' => '8ª Classe',
            '9ª Classe' => '9ª Classe',
            '10ª Classe' => '10ª Classe',
            '11ª Classe' => '11ª Classe',
            '12ª Classe' => '12ª Classe',
            'Ensino Médio Técnico' => 'Ensino Médio Técnico',
            'Bacharelato' => 'Bacharelato',
            'Licenciatura' => 'Licenciatura',
            'Pós-Graduação' => 'Pós-Graduação',
            'Mestrado' => 'Mestrado',
            'Doutoramento' => 'Doutoramento',
        ];
    }

    protected static function studentProvenanceOptions(): array
    {
        return Provenance::query()
            ->orderBy('name')
            ->get(['id', 'name', 'acronym'])
            ->mapWithKeys(fn (Provenance $provenance): array => [
                $provenance->id => $provenance->acronym
                    ? "{$provenance->name} ({$provenance->acronym})"
                    : $provenance->name,
            ])
            ->toArray();
    }

    protected static function academicStatusOptions(?Student $record = null): array
    {
        $options = StudentType::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();

        $current = static::defaultAcademicStatusForEdit($record);

        if ($current !== null && ! array_key_exists($current, $options)) {
            $options[$current] = $current;
        }

        return $options;
    }

    protected static function defaultAcademicStatusForEdit(?Student $record): ?string
    {
        if (! $record) {
            return null;
        }

        $studentType = trim((string) ($record->student_type ?? ''));

        if ($studentType !== '' && $studentType !== '-') {
            return $studentType;
        }

        $status = trim((string) ($record->status ?? ''));

        return $status !== '' && ! in_array($status, static::hiddenAcademicStatuses(), true)
            ? $status
            : null;
    }

    protected static function studentTypeForEditPayload(Student $record, array $data): ?string
    {
        $studentType = trim((string) ($data['student_status'] ?? ''));

        if ($studentType !== '') {
            return $studentType;
        }

        return static::defaultAcademicStatusForEdit($record);
    }

    protected static function studentTypeIdForName(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return StudentType::getIdByName($name);
    }

    protected static function hiddenAcademicStatuses(): array
    {
        return ['matriculado', 'reconfirmado'];
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

    protected static function studentEnrollmentPhotoInlinePreview(?Student $record): HtmlString
    {
        $photoUrl = static::studentEnrollmentPhotoUrl($record);

        if ($photoUrl === null) {
            return new HtmlString('');
        }

        $name = e(trim((string) ($record?->candidate?->full_name ?: 'Formando')));
        $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0D4C8B&color=fff&size=200';

        return new HtmlString(
            '<div style="text-align:center;margin-bottom:0.5rem;">'
            . '<img src="' . e($photoUrl) . '" alt="' . $name . '"'
            . ' onerror="this.src=\'' . $fallback . '\'"'
            . ' style="width:120px;height:120px;object-fit:cover;border-radius:50%;border:3px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
            . '</div>'
        );
    }

    protected static function studentEnrollmentPhotoUploadPlaceholder(): string
    {
        return '<span class="sigef-photo-idle">'
            . '<span class="sigef-photo-camera" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 4.5 7.6 7H5.5A2.5 2.5 0 0 0 3 9.5v8A2.5 2.5 0 0 0 5.5 20h13a2.5 2.5 0 0 0 2.5-2.5v-8A2.5 2.5 0 0 0 18.5 7h-2.1L15 4.5H9Zm3 13a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Zm0-2a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg></span>'
            . '</span>';
    }

    protected static function studentEnrollmentPhotoUploadActions(): HtmlString
    {
        return new HtmlString(
            '<div class="sigef-photo-actions" data-sigef-photo-actions="student-enrollment">'
            . '<button type="button" class="sigef-photo-action sigef-photo-action-primary" data-sigef-photo-action="capture"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-9A2.5 2.5 0 0 0 3 6.5v11A2.5 2.5 0 0 0 5.5 20h13a2.5 2.5 0 0 0 2.5-2.5v-8A2.5 2.5 0 0 0 18.5 7H17l-2.5-3Z"/><circle cx="12" cy="13" r="3"/></svg><span>Capturar</span></button>'
            . '<button type="button" class="sigef-photo-action sigef-photo-action-secondary" data-sigef-photo-action="upload"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg><span>Carregar</span></button>'
            . '</div>'
        );
    }

    protected static function studentEnrollmentPhotoPreviewTrigger(?Student $record): HtmlString
    {
        $photoUrl = static::studentEnrollmentPhotoUrl($record);

        if ($photoUrl === null) {
            return new HtmlString('');
        }

        $name = trim((string) ($record?->candidate?->full_name ?: $record?->full_name ?: 'Formando'));

        return new HtmlString(
            '<button type="button" class="sigef-photo-preview-trigger"'
            . ' data-sigef-photo-preview="true"'
            . ' data-sigef-photo-url="' . e($photoUrl) . '"'
            . ' data-sigef-photo-name="' . e($name) . '"'
            . ' aria-label="Visualizar foto de ' . e($name) . '">'
            . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>'
            . '</button>'
        );
    }

    protected static function studentEnrollmentPhotoUrl(?Student $record): ?string
    {
        $photo = trim((string) ($record?->candidate?->photo ?: $record?->photo ?: ''));

        if ($photo === '') {
            return null;
        }

        return PublicStorage::url($photo, requireExisting: true);
    }
}
