<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Concerns\HasCandidateRegimeForm;
use App\Filament\Escola\Resources\CandidateResource\Pages;
use App\Filament\Resources\CandidateResource\RelationManagers;
use App\Models\Candidate;
use App\Models\Institution;
use App\Models\StudentType;
use App\Models\CandidateTransferHistory;
use App\Services\RecruitmentPortalCandidateSyncService;
use App\Services\SmsService;
use App\Imports\CandidateImport;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;

class CandidateResource extends Resource
{
    use HasCandidateRegimeForm;

    protected static bool $shouldSkipAuthorization = false;

    protected static ?string $model = Candidate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-user-plus';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Formandos';
    protected static ?string $modelLabel = 'Formando';
    protected static ?string $pluralModelLabel = 'Formandos';


    // Mostrar candidatos do portal e cadastros diretos que ainda estão como Alistado/Formando.
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(\Filament\Facades\Filament::getTenant()?->id, fn (Builder $query, int $institutionId): Builder => $query->where('institution_id', $institutionId))
            ->whereIn('student_type', ['Alistado', 'Formando'])
            ->with(['recruitmentType', 'academicYear', 'institution', 'province', 'municipalityRelation', 'provenance']);
    }

    /**
     * Obter opções de tipos de aluno dinâmicas
     */
    public static function getStudentTypeOptions(): array
    {
        return StudentType::where('is_active', true)
            ->orderBy('order')
            ->pluck('name', 'name')
            ->toArray();
    }

    /**
     * Obter cores de tipos de aluno
     */
    public static function getStudentTypeColors(): array
    {
        return StudentType::where('is_active', true)
            ->pluck('color', 'name')
            ->toArray();
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::candidateFormSchema());
    }

    protected static function candidateFormSchema(): array
    {
        return [
            static::candidateIdentificationSection(),
            static::candidateClassificationSection([
                'Apurado' => 'Apurado',
                'Reprovado' => 'Reprovado',
            ], 'Apurado'),
        ];

        return [
                // Identificação Pessoal
                \Filament\Schemas\Components\Section::make('Identificação Pessoal')
                    ->icon('heroicon-o-user')
                    ->description('Dados pessoais do alistado')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(191)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Já existe um alistado com este nome.',
                            ]),
                        Forms\Components\TextInput::make('id_number')
                            ->label('Nº do BI')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(191)
                            ->validationMessages([
                                'unique' => 'Já existe um candidato com este Nº de BI.',
                            ]),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Data de Nascimento')
                            ->required(),
                        Forms\Components\Select::make('gender')
                            ->label('Género')
                            ->options([
                                'Masculino' => 'Masculino',
                                'Feminino' => 'Feminino',
                            ])
                            ->required(),
                        Forms\Components\Select::make('marital_status')
                            ->label('Estado Civil')
                            ->options([
                                'solteiro' => 'Solteiro(a)',
                                'casado' => 'Casado(a)',
                                'divorciado' => 'Divorciado(a)',
                                'viuvo' => 'Viúvo(a)',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('father_name')
                            ->label('Nome do Pai')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('mother_name')
                            ->label('Nome da Mãe')
                            ->maxLength(191),
                    ])->columns(3)->columnSpanFull(),

                // Localização e Contacto
                \Filament\Schemas\Components\Section::make('Localização e Contacto')
                    ->icon('heroicon-o-map-pin')
                    ->description('Endereço e contactos')
                    ->schema([
                        Forms\Components\Select::make('province_id')
                            ->label('Província')
                            ->options(\App\Models\Province::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn($set) => $set('municipality_id', null)),
                        Forms\Components\Select::make('municipality_id')
                            ->label('Município')
                            ->options(function ($get) {
                                $provinceId = $get('province_id');
                                if (!$provinceId) {
                                    return [];
                                }
                                return \App\Models\Municipality::where('province_id', $provinceId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Textarea::make('address')
                            ->label('Endereço')
                            ->rows(2),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->prefix('+244')
                            ->placeholder('9XX XXX XXX')
                            ->mask('999 999 999')
                            ->maxLength(191)
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(191)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Já existe um candidato com este e-mail.',
                            ]),
                    ])->columns(3)->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Classificação')
                    ->icon('heroicon-o-check-badge')
                    ->description('Tipo de registo e resultado do recrutamento')
                    ->schema([
                        Forms\Components\Select::make('student_type')
                            ->label('Tipo')
                            ->options([
                                'Alistado' => 'Alistado',
                                'Formando' => 'Formando',
                            ])
                            ->default('Alistado')
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Resultado')
                            ->options([
                                'Pendente' => 'Pendente',
                                'Apurado' => 'Apurado',
                                'Reprovado' => 'Reprovado',
                            ])
                            ->default('Pendente')
                            ->required()
                            ->native(false),
                    ])->columns(2)->columnSpanFull(),
            ];
    }

    public static function table(Table $table): Table
    {
        $typeColors = self::getStudentTypeColors();

        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome Completo')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('id_number')
                    ->label('BI/NIP')
                    ->getStateUsing(fn (Candidate $record): string => ($record->staff_type === 'regime_especial' || filled($record->nuri))
                        ? (string) ($record->nuri ?: '-')
                        : (string) ($record->id_number ?: '-'))
                    ->searchable(['id_number', 'nuri']),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable()
                    ->icon('heroicon-o-phone'),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Género')
                    ->formatStateUsing(fn($state) => $state === 'M' ? 'Masculino' : ($state === 'F' ? 'Feminino' : $state)),
                Tables\Columns\TextColumn::make('student_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn($state) => $typeColors[$state] ?? 'primary'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Resultado')
                    ->badge()
                    ->formatStateUsing(fn($state) => static::formatRecruitmentStatus($state))
                    ->color(fn($state) => static::recruitmentStatusColor($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data Registo')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('province_id')
                    ->label('Província')
                    ->options(\App\Models\Province::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('municipality_id')
                    ->label('Município')
                    ->options(function () {
                        return \App\Models\Municipality::orderBy('name')->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Instituição')
                    ->options(Institution::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->hidden(fn (): bool => filled(\Filament\Facades\Filament::getTenant()?->id)),
                Tables\Filters\SelectFilter::make('student_type')
                    ->label('Tipo')
                    ->options([
                        'Alistado' => 'Alistado',
                        'Formando' => 'Formando',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Resultado')
                    ->options([
                        'Pendente' => 'Pendente',
                        'Apurado' => 'Apurado',
                        'Reprovado' => 'Reprovado',
                    ]),
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'Masculino' => 'Masculino',
                        'Feminino' => 'Feminino',
                    ]),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->headerActions([
                // Botão de Importação Excel
                \Filament\Actions\Action::make('sincronizarPortal')
                    ->visible(false)
                    ->label('Sincronizar Portal')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->modalHeading('Sincronizar candidatos do portal')
                    ->modalDescription('Importa e atualiza todos os candidatos disponíveis no portal de recrutamento. No SIGEF entram como Alistados.')
                    ->form([
                        Forms\Components\TextInput::make('endpoint')
                            ->label('Endpoint')
                            ->default(config('services.recruitment_portal.candidates_url', 'http://10.110.2.18/api/candidates'))
                            ->required()
                            ->url(),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $stats = app(RecruitmentPortalCandidateSyncService::class)->sync($data['endpoint'] ?? null);

                            \Filament\Notifications\Notification::make()
                                ->title('Portal sincronizado')
                                ->body("Páginas: {$stats['pages']} | Recebidos: {$stats['received']} | Sincronizados: {$stats['synced']} | Apurados: {$stats['approved']} | Reprovados: {$stats['rejected']} | Pendentes: {$stats['pending']} | Outros: {$stats['other']} | Criados: {$stats['created']} | Atualizados: {$stats['updated']} | Ignorados: {$stats['skipped']}")
                                ->success()
                                ->duration(10000)
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Falha ao sincronizar portal')
                                ->body($e->getMessage())
                                ->danger()
                                ->duration(10000)
                                ->send();
                        }
                    }),
                \Filament\Actions\Action::make('importarExcel')
                    ->visible(false)
                    ->label('Importar Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->extraAttributes([
                        'style' => 'background-color: #11ba82 !important; border-color: #11ba82 !important; color: white !important;',
                    ])
                    ->modalHeading('Importar Formandos do Excel')
                    ->modalDescription(new \Illuminate\Support\HtmlString('<span style="color: white;">Faça upload de um arquivo Excel (.xlsx, .xls) com os dados dos formandos.</span>'))
                    ->modalIcon('heroicon-o-document-arrow-up')
                    ->modalIconColor('primary')
                    ->form([
                        Forms\Components\FileUpload::make('excel_file')
                            ->label('Arquivo Excel')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', '.xlsx', '.xls'])
                            ->directory('temp/imports')
                            ->required()
                            ->helperText('Formatos aceitos: .xlsx, .xls'),
                    ])
                    ->action(function (array $data): void {
                        $filePath = storage_path('app/public/' . $data['excel_file']);

                        if (!file_exists($filePath)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro')
                                ->body('Arquivo não encontrado.')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            $import = new CandidateImport();
                            Excel::import($import, $filePath);

                            $stats = $import->getImportStats();
                            $detailedErrors = $import->getDetailedErrors();

                            @unlink($filePath);

                            if ($stats['imported'] > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Importação Concluída')
                                    ->body("Importados: {$stats['imported']} formandos!")
                                    ->success()
                                    ->send();
                            }

                            if ($stats['skipped'] > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Registros Ignorados')
                                    ->body("{$stats['skipped']} já existiam.")
                                    ->warning()
                                    ->send();
                            }

                            if (count($detailedErrors) > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Problemas Encontrados')
                                    ->body(implode("\n", array_slice($detailedErrors, 0, 5)))
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro na Importação')
                                ->body('Erro: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Importar')->icon('heroicon-o-arrow-up-tray'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->icon('heroicon-o-x-mark')->color('danger')),
                // Botão para baixar modelo
                \Filament\Actions\Action::make('baixarModelo')
                    ->visible(false)
                    ->label('Baixar Modelo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->action(function () {
                        return Excel::download(new \App\Exports\CandidateTemplateExport(), 'modelo_importacao_formandos.xlsx');
                    }),
                \Filament\Actions\CreateAction::make()
                    ->visible(false)
                    ->icon('heroicon-o-plus')
                    ->modalWidth('6xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data = static::normalizeCandidateRegimeData($data);
                        static::guardAgainstDuplicateCandidate($data);
                        $data['status'] = 'Apurado';
                        $data['institution_id'] = \Filament\Facades\Filament::getTenant()?->id;

                        return $data;
                    })
                    ->successNotificationTitle('Formando criado com sucesso!')
                    ->after(function (Candidate $record) {
                        // Enviar SMS ao alistado após criar
                        $phone = $record->phone;

                        if (!empty($phone)) {
                            $candidateName = $record->full_name ?? 'Alistado';

                            // Buscar nome da instituição selecionada
                            $institutionName = 'Escola de Formação da Polícia Nacional';
                            if ($record->institution_id) {
                                $record->loadMissing('institution');
                                if ($record->institution) {
                                    $institutionName = $record->institution->name;
                                }
                            }

                            // Remover acentos do nome da instituição
                            $institutionName = strtr($institutionName, [
                                'ã' => 'a',
                                'á' => 'a',
                                'à' => 'a',
                                'â' => 'a',
                                'é' => 'e',
                                'ê' => 'e',
                                'í' => 'i',
                                'ó' => 'o',
                                'ô' => 'o',
                                'õ' => 'o',
                                'ú' => 'u',
                                'ç' => 'c',
                                'Ã' => 'A',
                                'Á' => 'A',
                                'À' => 'A',
                                'Â' => 'A',
                                'É' => 'E',
                                'Ê' => 'E',
                                'Í' => 'I',
                                'Ó' => 'O',
                                'Ô' => 'O',
                                'Õ' => 'O',
                                'Ú' => 'U',
                                'Ç' => 'C',
                            ]);

                            // Remover acentos do nome do candidato
                            $candidateName = strtr($candidateName, [
                                'ã' => 'a',
                                'á' => 'a',
                                'à' => 'a',
                                'â' => 'a',
                                'é' => 'e',
                                'ê' => 'e',
                                'í' => 'i',
                                'ó' => 'o',
                                'ô' => 'o',
                                'õ' => 'o',
                                'ú' => 'u',
                                'ç' => 'c',
                                'Ã' => 'A',
                                'Á' => 'A',
                                'À' => 'A',
                                'Â' => 'A',
                                'É' => 'E',
                                'Ê' => 'E',
                                'Í' => 'I',
                                'Ó' => 'O',
                                'Ô' => 'O',
                                'Õ' => 'O',
                                'Ú' => 'U',
                                'Ç' => 'C',
                            ]);

                            try {
                                $smsService = new SmsService();
                                $result = $smsService->sendAgentRegistrationNotification(
                                    $phone,
                                    $candidateName,
                                    $institutionName
                                );

                                if ($result['success']) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('SMS enviado')
                                        ->body("Notificação enviada para {$phone}")
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Falha ao enviar SMS')
                                        ->body("Não foi possível enviar SMS. Detalhes: " . ($result['message'] ?? 'Erro desconhecido'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Erro ao enviar SMS')
                                    ->body("Erro: " . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }
                    }),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                        ->label('Visualizar')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading('Visualizar Formando')
                        ->modalWidth('6xl')
                        ->schema(static::candidateFormSchema())
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger')),
                    static::imprimirFichaAction(),
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('6xl')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->mutateFormDataUsing(fn (array $data): array => static::normalizeCandidateRegimeData($data))
                        ->successNotificationTitle('Formando atualizado com sucesso!'),
                    \Filament\Actions\Action::make('moverAlistado')
                        ->label('Mover')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('warning')
                        ->visible(fn(Candidate $record): bool => !empty($record->institution_id))
                        ->requiresConfirmation()
                        ->modalHeading('Mover Alistado para Outra Instituição')
                        ->modalDescription(fn(Candidate $record) => 'Transferir "' . ($record->full_name ?? 'N/A') . '" para outra instituição.')
                        ->modalIcon('heroicon-o-building-office')
                        ->form([
                            Forms\Components\Placeholder::make('instituicao_atual')
                                ->label('Instituição Atual')
                                ->content(fn(Candidate $record) => $record->institution?->name ?? 'Sem instituição'),
                            Forms\Components\Select::make('new_institution_id')
                                ->label('Nova Instituição')
                                ->options(fn(Candidate $record) => Institution::where('id', '!=', $record->institution_id)->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload(),
                        ])
                        ->action(function (Candidate $record, array $data): void {
                            $oldInstitutionId = $record->institution_id;
                            $oldInstitution = $record->institution?->name ?? 'N/A';
                            $oldStudentType = $record->student_type ?: 'Sem tipo';
                            $candidateName = static::candidateAlertName($record);
                            $identifier = static::candidateAlertIdentifier($record);
                            $newInstitutionId = $data['new_institution_id'];
                            $newInstitution = Institution::find($newInstitutionId)?->name ?? 'N/A';

                            static::recordCandidateTransferHistory($record, $oldInstitutionId, (int) $newInstitutionId, $record->student_type);

                            // Atualizar a instituição do alistado
                            $record->update(['institution_id' => $newInstitutionId]);
                            $newStudentType = Institution::getDefaultStudentTypeForId($newInstitutionId);
                            $finalStudentType = $oldStudentType;

                            if ($newStudentType) {
                                $record->update(['student_type' => $newStudentType]);
                                $finalStudentType = $newStudentType;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Registo atualizado!')
                                ->body(static::candidateNotificationBody([
                                    "Registo: {$candidateName}",
                                    "BI/NIP: {$identifier}",
                                    "Instituição: {$oldInstitution} -> {$newInstitution}",
                                    'Tipo: '.static::candidateChangeText($oldStudentType, $finalStudentType),
                                ]))
                                ->success()
                                ->send();
                        })
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Transferir')->icon('heroicon-o-check')->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->icon('heroicon-o-x-mark')->color('danger')),
                    // Vincular alistado/formando e enviar para Gestão de Formandos.
                    \Filament\Actions\Action::make('vincularEConverterRecruta')
                        ->label('Enviar para uma Instituição')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->visible(fn(Candidate $record): bool => empty($record->institution_id))
                        ->requiresConfirmation()
                        ->modalHeading('Vincular à Instituição')
                        ->modalDescription('Alistados serão convertidos em Recruta. Formandos serão convertidos em Em Formação.')
                        ->modalIcon('heroicon-o-academic-cap')
                        ->form([
                            Forms\Components\Select::make('institution_id')
                                ->label('Instituição de Ensino')
                                ->options(Institution::orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->helperText('Selecione a escola onde o registo será formado.'),
                        ])
                        ->action(function (Candidate $record, array $data): void {
                            $institution = Institution::find($data['institution_id']);
                            $oldInstitutionId = $record->institution_id;
                            $oldStudentType = $record->student_type ?: 'Sem tipo';
                            $candidateName = static::candidateAlertName($record);
                            $identifier = static::candidateAlertIdentifier($record);
                            $studentType = static::linkCandidateToInstitutionAsStudent($record, (int) $data['institution_id']);

                            static::recordCandidateTransferHistory($record, $oldInstitutionId, (int) $data['institution_id'], $studentType);

                            // Vincular à instituição
                            // Criar ou atualizar Student
                            \Filament\Notifications\Notification::make()
                                ->title('Registo vinculado e convertido!')
                                ->body(static::candidateNotificationBody([
                                    "Registo: {$candidateName}",
                                    "BI/NIP: {$identifier}",
                                    'Instituição: '.($institution?->name ?? 'N/A'),
                                    'Tipo: '.static::candidateChangeText($oldStudentType, $studentType),
                                ]))
                                ->success()
                                ->send();
                        })
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Vincular e Converter')->icon('heroicon-o-check')->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->icon('heroicon-o-x-mark')->color('danger')),
                    \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações')
                    ->icon('heroicon-s-cog-6-tooth')
                    ->color('primary')
                    ->size('lg')
                    ->tooltip('Acções')
                    ->iconButton(),
            ])
            ->bulkActions([
                // Vincular Alistados à Instituição e transformar em Recruta
                \Filament\Actions\BulkAction::make('vincularETransformarRecrutas')
                    ->label('Enviar para uma Instituição')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->extraAttributes([
                        'style' => 'color: white !important;',
                        'class' => '[&>svg]:!text-white',
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Vincular Alistados/Formandos e Converter')
                    ->modalDescription('Alistados serão convertidos em Recruta. Formandos serão convertidos em Em Formação.')
                    ->modalIcon('heroicon-o-academic-cap')
                    ->form([
                        Forms\Components\Select::make('institution_id')
                            ->label('Instituição de Ensino')
                            ->options(Institution::orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Selecione a escola onde os registos serão formados.'),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        $institution = Institution::find($data['institution_id']);
                        $countRecrutas = 0;
                        $countEmFormacao = 0;
                        $changes = [];

                        foreach ($records as $candidate) {
                            $oldInstitutionId = $candidate->institution_id;
                            $oldStudentType = $candidate->student_type ?: 'Sem tipo';
                            $studentType = static::linkCandidateToInstitutionAsStudent($candidate, (int) $data['institution_id']);
                            static::recordCandidateTransferHistory($candidate, $oldInstitutionId, (int) $data['institution_id'], $studentType);

                            if ($studentType === 'Em Formação') {
                                $countEmFormacao++;
                            } else {
                                $countRecrutas++;
                            }

                            $changes[] = [
                                'name' => static::candidateAlertName($candidate),
                                'identifier' => static::candidateAlertIdentifier($candidate),
                                'from' => $oldStudentType,
                                'to' => $studentType,
                            ];
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Registos vinculados e convertidos!')
                            ->body(static::candidateBulkConversionNotificationBody(
                                $institution?->name ?? 'N/A',
                                $records->count(),
                                $countRecrutas,
                                $countEmFormacao,
                                $changes,
                            ))
                            ->success()
                            ->duration(10000)
                            ->send();
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Confirmar')->color('primary'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->color('danger'))
                    ->deselectRecordsAfterCompletion(),
                // Apenas atribuir escola (sem converter)
                \Filament\Actions\BulkAction::make('atribuirInstituicaoEmMassa')
                    ->label('Apenas Atribuir Escola')
                    ->icon('heroicon-o-building-library')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Atribuir Escola aos Alistados')
                    ->modalDescription('Os alistados serão atribuídos à escola, mas NÃO serão convertidos em Recrutas ainda.')
                    ->form([
                        Forms\Components\Select::make('institution_id')
                            ->label('Instituição de Ensino')
                            ->options(Institution::orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        $count = 0;
                        $institution = Institution::find($data['institution_id']);

                        foreach ($records as $record) {
                            $oldInstitutionId = $record->institution_id;
                            $record->update(['institution_id' => $data['institution_id']]);
                            static::recordCandidateTransferHistory(
                                $record,
                                $oldInstitutionId,
                                (int) $data['institution_id'],
                                $record->student_type,
                                'Escola atribuída sem conversão.'
                            );
                            $count++;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Escola Atribuída!')
                            ->body("{$count} alistados foram atribuídos à escola \"{$institution->name}\".")
                            ->success()
                            ->send();
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Confirmar')->color('primary'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->color('danger'))
                    ->deselectRecordsAfterCompletion(),
                // Enviar SMS em massa
                \Filament\Actions\BulkAction::make('enviarSmsEmMassa')
                    ->label('Enviar SMS de Apresentação')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('warning')
                    ->extraAttributes([
                        'style' => 'color: white !important;',
                        'class' => '[&>svg]:!text-white',
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Enviar SMS de Apresentação')
                    ->modalDescription('Será enviado um SMS aos alistados selecionados para se apresentarem na instituição. Apenas alistados com telefone e vinculados a uma escola receberão o SMS.')
                    ->modalIcon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Textarea::make('mensagem')
                            ->label('Mensagem')
                            ->default("Prezado(a) {nome}, informamos que deve apresentar-se na {escola} para obter informações sobre o aquartelamento. Compareça com documento de identificação. Polícia Nacional de Angola.")
                            ->helperText('Use {nome} para o nome do alistado e {escola} para o nome da escola.')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        $smsService = app(\App\Services\SmsService::class);
                        $enviados = 0;
                        $semTelefone = 0;
                        $semEscola = 0;
                        $falhas = 0;

                        foreach ($records as $record) {
                            $phone = $record->phone;
                            $escola = $record->institution?->name;
                            $nome = $record->full_name ?? 'Alistado';

                            if (empty($phone)) {
                                $semTelefone++;
                                continue;
                            }

                            if (empty($escola)) {
                                $semEscola++;
                                continue;
                            }

                            // Preparar mensagem
                            $mensagem = str_replace(
                                ['{nome}', '{escola}'],
                                [$nome, $escola],
                                $data['mensagem']
                            );

                            // Remover acentos
                            $mensagem = strtr($mensagem, [
                                'ã' => 'a',
                                'á' => 'a',
                                'à' => 'a',
                                'â' => 'a',
                                'é' => 'e',
                                'ê' => 'e',
                                'í' => 'i',
                                'ó' => 'o',
                                'ô' => 'o',
                                'õ' => 'o',
                                'ú' => 'u',
                                'ç' => 'c',
                                'Ã' => 'A',
                                'Á' => 'A',
                                'À' => 'A',
                                'Â' => 'A',
                                'É' => 'E',
                                'Ê' => 'E',
                                'Í' => 'I',
                                'Ó' => 'O',
                                'Ô' => 'O',
                                'Õ' => 'O',
                                'Ú' => 'U',
                                'Ç' => 'C',
                            ]);

                            try {
                                $result = $smsService->send($phone, $mensagem);
                                if ($result['success']) {
                                    $enviados++;
                                } else {
                                    $falhas++;
                                }
                            } catch (\Exception $e) {
                                $falhas++;
                            }
                        }

                        $msg = "{$enviados} SMS enviados com sucesso.";
                        if ($semTelefone > 0) $msg .= " {$semTelefone} sem telefone.";
                        if ($semEscola > 0) $msg .= " {$semEscola} sem escola.";
                        if ($falhas > 0) $msg .= " {$falhas} falharam.";

                        \Filament\Notifications\Notification::make()
                            ->title('Envio de SMS Concluído')
                            ->body($msg)
                            ->success()
                            ->duration(10000)
                            ->send();
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Confirmar')->color('primary'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->color('danger'))
                    ->deselectRecordsAfterCompletion(),
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }

    protected static function imprimirFichaAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('imprimirFicha')
            ->label('Imprimir Ficha')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->modalHeading('Pré-visualização da Ficha de Inscrição')
            ->modalDescription(null)
            ->modalWidth(\Filament\Support\Enums\Width::SixExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action
                ->icon('heroicon-o-x-mark')
                ->label('Fechar Pré-visualização')
                ->color('danger'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(function (Candidate $record) {
                $record->loadMissing(['institution']);

                $printUrl = route('candidates.sheet.print', ['candidate' => $record]);
                $candidateName = trim((string) ($record->full_name ?: 'Formando'));
                $identifierLabel = $record->staff_type === 'regime_especial' ? 'NIP' : 'N.o DO BI';
                $identifierNumber = trim((string) (
                    $record->staff_type === 'regime_especial'
                        ? ($record->nuri ?: 'FORM-'.$record->getKey())
                        : ($record->id_number ?: 'FORM-'.$record->getKey())
                ));
                $frameId = 'sigef-candidate-sheet-frame-'.$record->getKey();
                $viewerId = 'sigef-candidate-sheet-viewer-'.$record->getKey();

                return view('trainers.sheet-modal', [
                    'viewerId' => $viewerId,
                    'frameId' => $frameId,
                    'documentName' => 'Ficha de Inscrição - '.$candidateName,
                    'documentBadge' => $identifierLabel.': '.$identifierNumber,
                    'defaultOrientation' => 'vertical',
                    'embeddedHorizontalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=horizontal',
                    'embeddedVerticalUrl' => $printUrl.'?embedded=1&autoprint=0&orientation=vertical',
                    'fallbackPrintHorizontalUrl' => $printUrl.'?autoprint=1&orientation=horizontal',
                    'fallbackPrintVerticalUrl' => $printUrl.'?autoprint=1&orientation=vertical',
                ]);
            });
    }

    protected static function candidateAlertName(Candidate $candidate): string
    {
        return trim((string) ($candidate->full_name ?: 'Registo #'.$candidate->getKey()));
    }

    protected static function candidateAlertIdentifier(Candidate $candidate): string
    {
        $identifier = ($candidate->staff_type === 'regime_especial' || filled($candidate->nuri))
            ? $candidate->nuri
            : $candidate->id_number;

        return trim((string) ($identifier ?: 'Sem BI/NIP'));
    }

    protected static function candidateChangeText(?string $oldValue, ?string $newValue): string
    {
        $oldValue = trim((string) ($oldValue ?: 'Sem tipo'));
        $newValue = trim((string) ($newValue ?: 'Sem tipo'));

        return $oldValue === $newValue
            ? "{$oldValue} (sem alteração)"
            : "{$oldValue} -> {$newValue}";
    }

    protected static function candidateNotificationBody(array $lines): \Illuminate\Support\HtmlString
    {
        return new \Illuminate\Support\HtmlString(
            collect($lines)
                ->filter(fn (?string $line): bool => filled($line))
                ->map(fn (string $line): string => e($line))
                ->implode('<br>')
        );
    }

    protected static function candidateBulkConversionNotificationBody(
        string $institutionName,
        int $total,
        int $countRecrutas,
        int $countEmFormacao,
        array $changes,
    ): \Illuminate\Support\HtmlString {
        $lines = [
            "Instituição: {$institutionName}",
            "Total alterado: {$total}",
            "Convertidos para Recruta: {$countRecrutas}",
            "Convertidos para Em Formação: {$countEmFormacao}",
        ];

        if ($changes !== []) {
            $lines[] = 'Alterações realizadas:';

            foreach ($changes as $index => $change) {
                $number = $index + 1;
                $lines[] = "{$number}. {$change['name']} ({$change['identifier']}): ".static::candidateChangeText($change['from'], $change['to']);
            }
        }

        return static::candidateNotificationBody($lines);
    }

    protected static function studentTypeAfterInstitutionLink(?string $studentType): string
    {
        return str_contains(strtolower(trim((string) $studentType)), 'formando')
            ? 'Em Formação'
            : '1ª Fase - Recruta';
    }

    protected static function recordCandidateTransferHistory(
        Candidate $candidate,
        ?int $fromInstitutionId,
        int $toInstitutionId,
        ?string $studentType = null,
        ?string $notes = null,
    ): void {
        $candidate->loadMissing('province');

        CandidateTransferHistory::create([
            'candidate_id' => $candidate->id,
            'from_institution_id' => $fromInstitutionId,
            'to_institution_id' => $toInstitutionId,
            'transferred_by' => auth()->id(),
            'candidate_name' => $candidate->full_name,
            'bi_number' => $candidate->id_number,
            'student_type' => $studentType ?: $candidate->student_type ?: 'Alistado',
            'phone' => $candidate->phone,
            'province' => $candidate->province?->name,
            'status' => $candidate->status,
            'notes' => $notes,
            'transferred_at' => now(),
        ]);
    }

    protected static function linkCandidateToInstitutionAsStudent(Candidate $candidate, int $institutionId): string
    {
        $studentType = static::studentTypeAfterInstitutionLink($candidate->student_type);
        $studentTypeId = StudentType::getIdByName($studentType);

        $candidate->update([
            'institution_id' => $institutionId,
            'student_type' => $studentType,
        ]);

        $studentData = [
            'institution_id' => $institutionId,
            'provenance_id' => $candidate->provenance_id,
            'rank_id' => $candidate->current_rank_id,
            'student_type' => $studentType,
            'student_type_id' => $studentTypeId,
            'nuri' => $candidate->nuri ?: null,
            'phone' => $candidate->phone ?: null,
            'photo' => $candidate->photo ?: null,
            'bilhete_identidade' => $candidate->bilhete_identidade ?: $candidate->id_number,
            'certificado_doc' => $candidate->certificado_doc ?: null,
        ];

        $existingStudent = \App\Models\Student::where('candidate_id', $candidate->id)->first();

        if ($existingStudent) {
            if (blank($existingStudent->enrollment_date)) {
                $studentData['enrollment_date'] = now();
            }

            $existingStudent->update($studentData);
        } else {
            \App\Models\Student::create($studentData + [
                'candidate_id' => $candidate->id,
                'student_number' => 'ALT-' . $candidate->id,
                'enrollment_date' => now(),
            ]);
        }

        return $studentType;
    }

    protected static function formatRecruitmentStatus(?string $state): string
    {
        return match (strtolower((string) $state)) {
            'approved', 'aprovado', 'apurado', 'admitted', 'apto' => 'Apurado',
            'rejected', 'reprovado', 'failed', 'inapto' => 'Reprovado',
            'pending', 'pendente', '' => 'Pendente',
            default => (string) $state,
        };
    }

    protected static function guardAgainstDuplicateCandidate(array $data): void
    {
        $checks = [
            'id_number' => ['label' => 'Nº do BI', 'value' => $data['id_number'] ?? null],
            'email' => ['label' => 'e-mail', 'value' => $data['email'] ?? null],
        ];

        foreach ($checks as $field => $check) {
            if (! filled($check['value'])) {
                continue;
            }

            $duplicate = Candidate::withTrashed()
                ->where($field, $check['value'])
                ->first();

            if ($duplicate) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $field => "Já existe um formando com este {$check['label']}.",
                ]);
            }
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        $phoneDigits = preg_replace('/\D+/', '', $phone);

        if ($phone !== '' && $phoneDigits !== '') {
            $duplicate = Candidate::withTrashed()
                ->where('phone', $phone)
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', '') = ?", [$phoneDigits])
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', '') = ?", ['244' . $phoneDigits])
                ->first();

            if ($duplicate) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'phone' => 'Já existe um formando com este telefone.',
                ]);
            }
        }

        $fullName = trim((string) ($data['full_name'] ?? ''));
        $birthDate = $data['birth_date'] ?? null;

        if ($fullName !== '') {
            $duplicate = Candidate::withTrashed()
                ->where('full_name', $fullName)
                ->when($birthDate, fn (Builder $query) => $query->whereDate('birth_date', $birthDate))
                ->first();

            if ($duplicate) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'full_name' => $birthDate
                        ? 'Já existe um formando com este nome e data de nascimento.'
                        : 'Já existe um formando com este nome.',
                ]);
            }
        }
    }

    protected static function recruitmentStatusColor(?string $state): string
    {
        return match (strtolower((string) $state)) {
            'approved', 'aprovado', 'apurado', 'admitted', 'apto' => 'success',
            'rejected', 'reprovado', 'failed', 'inapto' => 'danger',
            'pending', 'pendente', '' => 'warning',
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
            'index' => Pages\ListCandidates::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        // Alistados NOT visible in INSTITUTO type institutions
        $tenant = \Filament\Facades\Filament::getTenant();
        if ($tenant && $tenant->institution_type_id === 3) {
            return false;
        }
        return auth()->user()?->can('ViewAny:Candidate') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
