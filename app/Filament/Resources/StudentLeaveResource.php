<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentLeaveResource\Pages;
use App\Filament\Resources\StudentLeaveResource\RelationManagers;
use App\Models\Candidate;
use App\Models\Institution;
use App\Models\StudentLeave;
use App\Models\Student;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class StudentLeaveResource extends Resource
{
    protected static ?string $model = StudentLeave::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-clock';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 8;
    protected static ?string $modelLabel = 'Dispensa/Falta';
    protected static ?string $pluralModelLabel = 'Dispensas e Faltas';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Formando')
                    ->options(function (?StudentLeave $record): array {
                        $tiposPermitidos = [
                            'Oficial',
                            'Agente de 3ª Classe',
                            'Recruta',
                            '1ª Fase - Recruta',
                            'Instruendo',
                            '2ª Fase - Instruendo',
                            'Em Formação',
                        ];

                        return Student::with('candidate')
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
                        $student = Student::with('candidate')->find($value);
                        return $student?->candidate?->full_name ?? 'N/A';
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                Forms\Components\Select::make('leave_type')
                    ->label('Tipo de Ocorrência')
                    ->options([
                        'dispensa_saude' => 'Dispensa - Saúde',
                        'dispensa_pessoal' => 'Dispensa - Pessoal',
                        'dispensa_servico' => 'Dispensa - Serviço',
                        'dispensa_falecimento' => 'Dispensa - Falecimento Familiar',
                        'dispensa_outro' => 'Dispensa - Outro',
                        'falta_justificada' => 'Falta Justificada',
                        'falta_injustificada' => 'Falta Injustificada',
                        'reprovado_faltas' => 'Reprovado por Faltas',
                        'reprovado_desistencia' => 'Reprovado por Desistência',
                        'baixa_curso' => 'Baixa de curso',
                    ])
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendente',
                        'approved' => 'Aprovada',
                        'rejected' => 'Rejeitada',
                    ])
                    ->required()
                    ->default('pending')
                    ->native(false),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Data de Início')
                    ->required()
                    ->default(now()),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Data de Fim')
                    ->required()
                    ->default(now()),
                Forms\Components\Textarea::make('reason')
                    ->label('Motivo/Justificação')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->modifyQueryUsing(function ($query) {
                // Pega apenas o último registo de cada student_id
                $subquery = StudentLeave::selectRaw('MAX(id) as id')
                    ->groupBy('student_id');

                return $query->whereIn('id', $subquery);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('student.student_number')
                    ->label('Nº Ordem')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('student_photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->size(40)
                    ->getStateUsing(fn (StudentLeave $record): ?string => $record->student?->photo ?: $record->student?->candidate?->photo)
                    ->defaultImageUrl(fn (StudentLeave $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->student?->candidate?->full_name ?? 'NA') . '&background=0D4C8B&color=fff&size=100'),
                Tables\Columns\TextColumn::make('student.candidate.full_name')
                    ->label('Nome')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('student_identifier')
                    ->label('NIP/NURI')
                    ->getStateUsing(fn (StudentLeave $record): string => static::studentIdentifier($record->student))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('student', function (Builder $studentQuery) use ($search): void {
                        $studentQuery
                            ->where('nuri', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%")
                            ->orWhereHas('candidate', fn (Builder $candidateQuery): Builder => $candidateQuery
                                ->where('nuri', 'like', "%{$search}%")
                                ->orWhere('student_number', 'like', "%{$search}%"));
                    }))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('student.cia')
                    ->label('Cia')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('student.platoon')
                    ->label('Pelotão')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('student.section')
                    ->label('Secção')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_ocorrencias')
                    ->label('Total Ocorrências')
                    ->getStateUsing(fn($record) => StudentLeave::where('student_id', $record->student_id)->count())
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Última Ocorrência')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'dispensa_saude' => 'Dispensa - Saúde',
                        'dispensa_pessoal' => 'Dispensa - Pessoal',
                        'dispensa_servico' => 'Dispensa - Serviço',
                        'dispensa_falecimento' => 'Dispensa - Falecimento',
                        'dispensa_outro' => 'Dispensa - Outro',
                        'falta_justificada' => 'Falta Justificada',
                        'falta_injustificada' => 'Falta Injustificada',
                        'reprovado_faltas' => 'Reprovado Faltas',
                        'reprovado_desistencia' => 'Reprovado Desistência',
                        'baixa_curso' => 'Baixa de curso',
                        default => $state,
                    })
                    ->color(fn($state) => match ($state) {
                        'dispensa_saude', 'dispensa_pessoal', 'dispensa_servico', 'dispensa_falecimento', 'dispensa_outro' => 'info',
                        'falta_justificada' => 'warning',
                        'falta_injustificada' => 'danger',
                        'reprovado_faltas', 'reprovado_desistencia', 'baixa_curso' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending' => 'Pendente',
                        'approved' => 'Aprovada',
                        'rejected' => 'Rejeitada',
                        default => $state,
                    })
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Última Ação')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('leave_type')
                    ->label('Tipo')
                    ->options([
                        'dispensa_saude' => 'Dispensa - Saúde',
                        'dispensa_pessoal' => 'Dispensa - Pessoal',
                        'dispensa_servico' => 'Dispensa - Serviço',
                        'dispensa_falecimento' => 'Dispensa - Falecimento',
                        'dispensa_outro' => 'Dispensa - Outro',
                        'falta_justificada' => 'Falta Justificada',
                        'falta_injustificada' => 'Falta Injustificada',
                        'reprovado_faltas' => 'Reprovado por Faltas',
                        'reprovado_desistencia' => 'Reprovado por Desistência',
                        'baixa_curso' => 'Baixa de curso',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendente',
                        'approved' => 'Aprovada',
                        'rejected' => 'Rejeitada',
                    ]),
                Tables\Filters\SelectFilter::make('cia')
                    ->label('Cia')
                    ->options(fn() => Student::whereNotNull('cia')->distinct()->pluck('cia', 'cia')->toArray())
                    ->query(fn($query, array $data) => $query->when($data['value'], fn($q) => $q->whereHas('student', fn($sq) => $sq->where('cia', $data['value'])))),
                Tables\Filters\SelectFilter::make('platoon')
                    ->label('Pelotão')
                    ->options(fn() => Student::whereNotNull('platoon')->distinct()->pluck('platoon', 'platoon')->toArray())
                    ->query(fn($query, array $data) => $query->when($data['value'], fn($q) => $q->whereHas('student', fn($sq) => $sq->where('platoon', $data['value'])))),
                Tables\Filters\SelectFilter::make('section')
                    ->label('Secção')
                    ->options(fn() => Student::whereNotNull('section')->distinct()->pluck('section', 'section')->toArray())
                    ->query(fn($query, array $data) => $query->when($data['value'], fn($q) => $q->whereHas('student', fn($sq) => $sq->where('section', $data['value'])))),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('createOccurrence')
                    ->label('Nova Ocorrência')
                    ->icon('heroicon-o-plus')
                    ->form(static::occurrenceHeaderFormSchema())
                    ->action(fn (array $data): StudentLeave => static::registerOccurrenceFromHeader($data))
                    ->modalHeading('Registar Nova Ocorrência')
                    ->modalWidth('5xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Registar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('novaOcorrencia')
                        ->label('Nova Ocorrência')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->hidden()
                        ->form([
                            Forms\Components\Select::make('leave_type')
                                ->label('Tipo de Ocorrência')
                                ->options([
                                    'dispensa_saude' => 'Dispensa - Saúde',
                                    'dispensa_pessoal' => 'Dispensa - Pessoal',
                                    'dispensa_servico' => 'Dispensa - Serviço',
                                    'dispensa_falecimento' => 'Dispensa - Falecimento Familiar',
                                    'dispensa_outro' => 'Dispensa - Outro',
                                    'falta_justificada' => 'Falta Justificada',
                                    'falta_injustificada' => 'Falta Injustificada',
                                    'reprovado_faltas' => 'Reprovado por Faltas',
                                    'reprovado_desistencia' => 'Reprovado por Desistência',
                                    'baixa_curso' => 'Baixa de curso',
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\Select::make('status')
                                ->label('Estado')
                                ->options([
                                    'pending' => 'Pendente',
                                    'approved' => 'Aprovada',
                                    'rejected' => 'Rejeitada',
                                ])
                                ->required()
                                ->default('pending')
                                ->native(false),
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Data de Início')
                                ->required()
                                ->default(now()),
                            Forms\Components\DatePicker::make('end_date')
                                ->label('Data de Fim')
                                ->required()
                                ->default(now()),
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo/Justificação')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->action(function ($record, array $data): void {
                            StudentLeave::create([
                                'student_id' => $record->student_id,
                                'institution_id' => $record->student?->institution_id ?? null,
                                'leave_type' => $data['leave_type'],
                                'start_date' => $data['start_date'],
                                'end_date' => $data['end_date'],
                                'reason' => $data['reason'] ?? null,
                                'status' => $data['status'],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Ocorrência Registada!')
                                ->success()
                                ->send();
                        })
                        ->modalHeading(fn($record) => 'Nova Ocorrência - ' . ($record->student?->candidate?->full_name ?? 'N/A'))
                        ->modalWidth('3xl')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action
                            ->label('Registrar')
                            ->icon('heroicon-o-check')
                            ->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action
                            ->label('Cancelar')
                            ->icon('heroicon-o-x-mark')
                            ->color('danger')),
                    \Filament\Actions\Action::make('fichaDispensa')
                        ->label('Ficha de Dispensa')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->modalHeading('Pre-visualizacao da Ficha de Dispensa')
                        ->modalDescription(null)
                        ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action
                            ->icon('heroicon-o-x-mark')
                            ->label('Fechar Pre-visualizacao')
                            ->color('danger'))
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->closeModalByClickingAway(false)
                        ->modalContent(function (StudentLeave $record) {
                            $record->loadMissing('student.candidate');
                            $student = $record->student;
                            $studentName = trim((string) ($student?->candidate?->full_name ?: $student?->full_name ?: 'Formando'));
                            $identifierNumber = trim((string) ($student?->nuri ?: $student?->candidate?->nuri ?: $student?->student_number ?: 'DISP-'.$record->getKey()));
                            $printUrl = route('student-leaves.sheet.print', ['studentLeave' => $record]);
                            $frameId = 'sigef-student-leave-sheet-frame-'.$record->getKey();
                            $viewerId = 'sigef-student-leave-sheet-viewer-'.$record->getKey();

                            return view('trainers.sheet-modal', [
                                'viewerId' => $viewerId,
                                'frameId' => $frameId,
                                'documentName' => 'Ficha de Dispensa - '.$studentName,
                                'documentBadge' => 'NIP/NURI: '.$identifierNumber,
                                'defaultOrientation' => 'vertical',
                                'embeddedHorizontalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=horizontal',
                                'embeddedVerticalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=vertical',
                                'fallbackPrintHorizontalUrl' => $printUrl.'?autoprint=1&orientation=horizontal',
                                'fallbackPrintVerticalUrl' => $printUrl.'?autoprint=1&orientation=vertical',
                                'documentType' => 'ficha de dispensa',
                                'loadingText' => 'A preparar ficha de dispensa...',
                                'hintText' => 'Pre-visualize a ficha de dispensa em A4 antes de imprimir.',
                            ]);
                        }),
                    \Filament\Actions\Action::make('verDetalhes')
                        ->label('Ver Histórico')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn($record) => 'Histórico de Ocorrências - ' . ($record->student?->candidate?->full_name ?? 'N/A'))
                        ->modalWidth('5xl')
                        ->infolist(function ($record) {
                            $ocorrencias = StudentLeave::where('student_id', $record->student_id)->get();
                            $totalDispensas = $ocorrencias->filter(fn($o) => str_starts_with($o->leave_type, 'dispensa_'))->count();
                            $faltasJustificadas = $ocorrencias->where('leave_type', 'falta_justificada')->count();
                            $faltasInjustificadas = $ocorrencias->where('leave_type', 'falta_injustificada')->count();
                            $totalGeral = $ocorrencias->count();

                            return [
                                \Filament\Schemas\Components\Section::make('Informações do Formando')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(5)->schema([
                                            \Filament\Infolists\Components\TextEntry::make('student.candidate.full_name')
                                                ->label('Nome completo')
                                                ->icon('heroicon-o-user'),
                                            \Filament\Infolists\Components\TextEntry::make('student_identifier')
                                                ->label('NIP/NURI')
                                                ->getStateUsing(fn ($record): string => trim((string) (
                                                    $record->student?->nuri
                                                    ?: $record->student?->candidate?->nuri
                                                    ?: $record->student?->student_number
                                                    ?: '-'
                                                )))
                                                ->icon('heroicon-o-identification'),
                                            \Filament\Infolists\Components\TextEntry::make('student.cia')
                                                ->label('CIA')
                                                ->icon('heroicon-o-building-office')
                                                ->formatStateUsing(fn ($state): string => filled($state)
                                                    ? (str_contains(strtoupper((string) $state), 'CIA') ? (string) $state : "{$state}ª CIA")
                                                    : '-'),
                                            \Filament\Infolists\Components\TextEntry::make('student.platoon')
                                                ->label('PELOTÃO')
                                                ->icon('heroicon-o-users')
                                                ->formatStateUsing(fn ($state): string => filled($state)
                                                    ? (str_contains(strtoupper((string) $state), 'PELOT') ? (string) $state : "{$state}º PELOTÃO")
                                                    : '-'),
                                            \Filament\Infolists\Components\TextEntry::make('student.section')
                                                ->label('SECÇÃO')
                                                ->icon('heroicon-o-user-group')
                                                ->formatStateUsing(fn ($state): string => filled($state)
                                                    ? (str_contains(strtoupper((string) $state), 'SEC') ? (string) $state : "{$state}ª SECÇÃO")
                                                    : '-'),
                                        ]),
                                    ]),
                                \Filament\Schemas\Components\Section::make('Resumo de Ocorrências')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(4)->schema([
                                            \Filament\Infolists\Components\TextEntry::make('total_dispensas')
                                                ->label('Total Dispensas')
                                                ->getStateUsing(fn() => $totalDispensas)
                                                ->badge()
                                                ->color('info')
                                                ->icon('heroicon-o-clock'),
                                            \Filament\Infolists\Components\TextEntry::make('faltas_justificadas')
                                                ->label('Faltas Justificadas')
                                                ->getStateUsing(fn() => $faltasJustificadas)
                                                ->badge()
                                                ->color('warning')
                                                ->icon('heroicon-o-exclamation-triangle'),
                                            \Filament\Infolists\Components\TextEntry::make('faltas_injustificadas')
                                                ->label('Faltas Injustificadas')
                                                ->getStateUsing(fn() => $faltasInjustificadas)
                                                ->badge()
                                                ->color('danger')
                                                ->icon('heroicon-o-x-circle'),
                                            \Filament\Infolists\Components\TextEntry::make('total_geral')
                                                ->label('Total Geral')
                                                ->getStateUsing(fn() => $totalGeral)
                                                ->badge()
                                                ->color('primary')
                                                ->icon('heroicon-o-document-text'),
                                        ]),
                                    ]),
                                \Filament\Schemas\Components\Section::make('Histórico de Ocorrências')
                                    ->schema([
                                        \Filament\Infolists\Components\ViewEntry::make('ocorrencias_table')
                                            ->view('filament.pages.student-leaves-table')
                                            ->viewData(['studentId' => $record->student_id])
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ];
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Fechar')->icon('heroicon-o-x-mark')->color('danger')),
                    \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function occurrenceHeaderFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Grid::make(2)->schema([
            Forms\Components\Select::make('student_id')
                ->label('Formando')
                ->options(fn (): array => static::studentOptionsForLeaves())
                ->getOptionLabelUsing(fn ($value): ?string => static::studentOptionLabel(Student::with('candidate')->find($value)))
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('student_identifier')
                ->label('NIP/NURI')
                ->maxLength(191),
            Forms\Components\Select::make('institution_id')
                ->label('Instituicao')
                ->options(fn (): array => Institution::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->default(fn (): ?int => auth()->user()?->institution_id)
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('cia')
                ->label('CIA')
                ->options(collect(range(1, 15))->mapWithKeys(fn (int $number): array => [$number => "{$number}ª CIA"]))
                ->searchable(),
            Forms\Components\Select::make('platoon')
                ->label('Pelotao')
                ->options(collect(range(1, 15))->mapWithKeys(fn (int $number): array => [$number => "{$number}º Pelotao"]))
                ->searchable(),
            Forms\Components\Select::make('section')
                ->label('Seccao')
                ->options(collect(range(1, 15))->mapWithKeys(fn (int $number): array => [$number => "{$number}ª Seccao"]))
                ->searchable(),
            Forms\Components\Select::make('leave_type')
                ->label('Tipo de Ocorrencia')
                ->options(static::leaveTypeOptions())
                ->required()
                ->native(false),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options(static::leaveStatusOptions())
                ->required()
                ->default('pending')
                ->native(false),
            Forms\Components\DatePicker::make('start_date')
                ->label('Data de Inicio')
                ->required()
                ->default(now()),
            Forms\Components\DatePicker::make('end_date')
                ->label('Data de Fim')
                ->required()
                ->default(now()),
            Forms\Components\Textarea::make('reason')
                ->label('Motivo/Justificacao')
                ->rows(3)
                ->columnSpanFull(),
            ]),
        ];
    }

    protected static function registerOccurrenceFromHeader(array $data): StudentLeave
    {
        $leave = DB::transaction(function () use ($data): StudentLeave {
            $student = static::resolveStudentForOccurrence($data);
            static::syncStudentOccurrenceData($student, $data);

            return StudentLeave::create([
                'student_id' => $student->id,
                'institution_id' => $data['institution_id'] ?? $student->institution_id,
                'leave_type' => $data['leave_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'] ?? null,
                'status' => $data['status'] ?? 'pending',
            ]);
        });

        \Filament\Notifications\Notification::make()
            ->title('Ocorrência registada com sucesso!')
            ->success()
            ->send();

        return $leave;
    }

    protected static function resolveStudentForOccurrence(array $data): Student
    {
        if (! empty($data['student_id'])) {
            $student = Student::with('candidate')->find($data['student_id']);

            if ($student) {
                return $student;
            }
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'student_id' => 'Selecione o formando para registar a ocorrencia.',
        ]);

        $name = trim((string) ($data['student_name'] ?? ''));
        $identifier = trim((string) ($data['student_identifier'] ?? ''));

        if ($student = static::findExistingStudentForOccurrence($name, $identifier)) {
            return $student;
        }

        $candidate = static::findExistingCandidateForOccurrence($name, $identifier);

        if (! $candidate) {
            if ($name === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'student_name' => 'Informe o nome do formando ou selecione um formando existente.',
                ]);
            }

            $candidate = Candidate::create([
                'full_name' => $name,
                'student_type' => 'Em Formação',
                'status' => 'approved',
                'nuri' => $identifier !== '' ? $identifier : null,
                'institution_id' => $data['institution_id'] ?? null,
            ]);
        }

        return Student::create([
            'candidate_id' => $candidate->id,
            'institution_id' => $data['institution_id'] ?? $candidate->institution_id,
            'student_number' => static::generateStudentNumber($identifier),
            'student_type' => 'Em Formação',
            'status' => 'em_formacao',
            'nuri' => $identifier !== '' ? $identifier : ($candidate->nuri ?? null),
            'cia' => $data['cia'] ?? null,
            'platoon' => $data['platoon'] ?? null,
            'section' => $data['section'] ?? null,
            'enrollment_date' => now(),
        ])->load('candidate');
    }

    protected static function findExistingStudentForOccurrence(string $name, string $identifier): ?Student
    {
        if ($identifier !== '') {
            $student = Student::with('candidate')
                ->where(function (Builder $query) use ($identifier): void {
                    $query
                        ->where('nuri', $identifier)
                        ->orWhere('student_number', $identifier)
                        ->orWhereHas('candidate', fn (Builder $candidateQuery): Builder => $candidateQuery
                            ->where('nuri', $identifier)
                            ->orWhere('student_number', $identifier)
                            ->orWhere('id_number', $identifier));
                })
                ->first();

            if ($student) {
                return $student;
            }
        }

        if ($name === '') {
            return null;
        }

        $normalizedName = static::normalizeStudentName($name);

        return Student::with('candidate')
            ->whereHas('candidate', fn (Builder $query): Builder => $query->whereRaw('LOWER(TRIM(full_name)) = ?', [$normalizedName]))
            ->first();
    }

    protected static function findExistingCandidateForOccurrence(string $name, string $identifier): ?Candidate
    {
        if ($identifier !== '') {
            $candidate = Candidate::query()
                ->where('nuri', $identifier)
                ->orWhere('student_number', $identifier)
                ->orWhere('id_number', $identifier)
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        if ($name === '') {
            return null;
        }

        return Candidate::query()
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [static::normalizeStudentName($name)])
            ->first();
    }

    protected static function syncStudentOccurrenceData(Student $student, array $data): void
    {
        $identifier = trim((string) ($data['student_identifier'] ?? ''));
        $studentUpdates = [];

        foreach (['institution_id', 'cia', 'platoon', 'section'] as $field) {
            if (filled($data[$field] ?? null)) {
                $studentUpdates[$field] = $data[$field];
            }
        }

        if ($identifier !== '') {
            $studentUpdates['nuri'] = $identifier;
        }

        if ($studentUpdates !== []) {
            $student->update($studentUpdates);
            $student->refresh();
        }

        if ($student->candidate) {
            $candidateUpdates = [];

            if ($identifier !== '') {
                $candidateUpdates['nuri'] = $identifier;
            }

            if (filled($data['institution_id'] ?? null)) {
                $candidateUpdates['institution_id'] = $data['institution_id'];
            }

            if ($candidateUpdates !== []) {
                $student->candidate->update($candidateUpdates);
            }
        }
    }

    protected static function studentOptionsForLeaves(): array
    {
        return static::allowedStudentsForLeavesQuery()
            ->with('candidate')
            ->get()
            ->sortBy(fn (Student $student): string => $student->candidate?->full_name ?? '')
            ->mapWithKeys(fn (Student $student): array => [$student->id => static::studentOptionLabel($student)])
            ->toArray();
    }

    protected static function allowedStudentsForLeavesQuery(): Builder
    {
        $types = ['Oficial', 'Agente', 'Recruta', 'Instruendo', 'Em Forma', 'Formando', '1ª Fase', '2ª Fase'];

        return Student::query()
            ->where(function (Builder $query) use ($types): void {
                foreach ($types as $type) {
                    $query->orWhere('student_type', 'like', "%{$type}%");
                }
            });
    }

    protected static function studentOptionLabel(?Student $student): ?string
    {
        if (! $student) {
            return null;
        }

        $name = $student->candidate?->full_name ?? 'N/A';
        $identifier = static::studentIdentifier($student);

        return $identifier !== '-' ? "{$name} ({$identifier})" : $name;
    }

    protected static function studentIdentifier(?Student $student): string
    {
        $identifier = trim((string) (
            $student?->nuri
            ?: $student?->candidate?->nuri
            ?: $student?->student_number
            ?: ''
        ));

        return $identifier !== '' ? $identifier : '-';
    }

    protected static function leaveTypeOptions(): array
    {
        return [
            'dispensa_saude' => 'Dispensa - Saúde',
            'dispensa_pessoal' => 'Dispensa - Pessoal',
            'dispensa_servico' => 'Dispensa - Serviço',
            'dispensa_falecimento' => 'Dispensa - Falecimento Familiar',
            'dispensa_outro' => 'Dispensa - Outro',
            'falta_justificada' => 'Falta Justificada',
            'falta_injustificada' => 'Falta Injustificada',
            'reprovado_faltas' => 'Reprovado por Faltas',
            'reprovado_desistencia' => 'Reprovado por Desistência',
            'baixa_curso' => 'Baixa de curso',
        ];
    }

    protected static function leaveStatusOptions(): array
    {
        return [
            'pending' => 'Pendente',
            'approved' => 'Aprovada',
            'rejected' => 'Rejeitada',
        ];
    }

    protected static function normalizeStudentName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }

    protected static function generateStudentNumber(string $identifier = ''): string
    {
        $identifier = strtoupper(preg_replace('/\s+/', '', trim($identifier)));

        if ($identifier !== '' && ! Student::withTrashed()->where('student_number', $identifier)->exists()) {
            return $identifier;
        }

        do {
            $studentNumber = 'ALT-' . now()->format('YmdHis') . '-' . random_int(100, 999);
        } while (Student::withTrashed()->where('student_number', $studentNumber)->exists());

        return $studentNumber;
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
            'index' => Pages\ListStudentLeaves::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:StudentLeave') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
