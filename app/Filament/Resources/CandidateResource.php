<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidateResource\Pages;
use App\Filament\Resources\CandidateResource\RelationManagers;
use App\Models\Candidate;
use App\Models\Institution;
use App\Models\StudentType;
use App\Models\CandidateTransferHistory;
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
    protected static ?string $model = Candidate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-user-plus';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Alistados';
    protected static ?string $modelLabel = 'Alistado';
    protected static ?string $pluralModelLabel = 'Alistados';


    // Filtrar apenas alistados cadastrados neste formulário
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('student_type', 'Alistado')
            ->with(['recruitmentType', 'academicYear']);
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
            ->schema([
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

                // Tipo de Aluno definido automaticamente como Alistado
                Forms\Components\Hidden::make('student_type')
                    ->default('Alistado'),
            ]);
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
                    ->label('Nº BI')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable()
                    ->icon('heroicon-o-phone'),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Género')
                    ->formatStateUsing(fn($state) => $state === 'M' ? 'Masculino' : ($state === 'F' ? 'Feminino' : $state)),
                Tables\Columns\TextColumn::make('student_type')
                    ->label('Estado')
                    ->badge()
                    ->color(fn($state) => $typeColors[$state] ?? 'primary'),
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
                    ->preload(),
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'Masculino' => 'Masculino',
                        'Feminino' => 'Feminino',
                    ]),
            ])
            ->headerActions([
                // Botão de Importação Excel
                \Filament\Actions\Action::make('importarExcel')
                    ->label('Importar Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->extraAttributes([
                        'style' => 'background-color: #11ba82 !important; border-color: #11ba82 !important; color: white !important;',
                    ])
                    ->modalHeading('Importar Alistados do Excel')
                    ->modalDescription(new \Illuminate\Support\HtmlString('<span style="color: white;">Faça upload de um arquivo Excel (.xlsx, .xls) com os dados dos alistados.</span>'))
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
                                    ->body("Importados: {$stats['imported']} alistados!")
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
                    ->label('Baixar Modelo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->action(function () {
                        return Excel::download(new \App\Exports\CandidateTemplateExport(), 'modelo_importacao_alistados.xlsx');
                    }),
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth('6xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Alistado criado com sucesso!')
                    ->after(function (Candidate $record) {
                        // Enviar SMS ao alistado após criar
                        $phone = $record->phone;

                        if (!empty($phone)) {
                            $candidateName = $record->full_name ?? 'Alistado';

                            // Buscar nome da instituição selecionada
                            $institutionName = 'Escola de Formacao da Policia Nacional';
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
                                        ->body("Notificacao enviada para {$phone}")
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Falha ao enviar SMS')
                                        ->body("Nao foi possivel enviar SMS. Detalhes: " . ($result['message'] ?? 'Erro desconhecido'))
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
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('6xl')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Alistado atualizado com sucesso!'),
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
                            $newInstitutionId = $data['new_institution_id'];
                            $newInstitution = Institution::find($newInstitutionId)?->name ?? 'N/A';

                            // Registrar no histórico de transferências
                            \App\Models\CandidateTransferHistory::create([
                                'candidate_id' => $record->id,
                                'from_institution_id' => $oldInstitutionId,
                                'to_institution_id' => $newInstitutionId,
                                'transferred_by' => auth()->id(),
                                'candidate_name' => $record->full_name,
                                'bi_number' => $record->id_number,
                                'student_type' => $record->student_type ?? 'Alistado',
                                'phone' => $record->phone,
                                'province' => $record->province?->name,
                                'status' => $record->status,
                                'notes' => null,
                                'transferred_at' => now(),
                            ]);

                            // Atualizar a instituição do alistado
                            $record->update(['institution_id' => $newInstitutionId]);

                            // Atualizar student_type conforme o tipo de instituição de destino
                            $newStudentType = Institution::getDefaultStudentTypeForId($newInstitutionId);
                            if ($newStudentType) {
                                $record->update(['student_type' => $newStudentType]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Alistado Movido!')
                                ->body("Transferido de \"{$oldInstitution}\" para \"{$newInstitution}\"." . ($newStudentType ? " Tipo alterado para \"{$newStudentType}\"." : ''))
                                ->success()
                                ->send();
                        })
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Transferir')->icon('heroicon-o-check')->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->icon('heroicon-o-x-mark')->color('danger')),
                    // Vincular Alistado e Converter em Recruta
                    \Filament\Actions\Action::make('vincularEConverterRecruta')
                        ->label('Enviar para uma Instituição')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->visible(fn(Candidate $record): bool => empty($record->institution_id))
                        ->requiresConfirmation()
                        ->modalHeading('Vincular Alistado e Converter em Recruta')
                        ->modalDescription(fn(Candidate $record) => 'O alistado "' . ($record->full_name ?? 'N/A') . '" será vinculado à escola selecionada e convertido em Recruta (1ª Fase).')
                        ->modalIcon('heroicon-o-academic-cap')
                        ->form([
                            Forms\Components\Select::make('institution_id')
                                ->label('Instituição de Ensino')
                                ->options(Institution::orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->helperText('Selecione a escola onde o alistado será formado'),
                        ])
                        ->action(function (Candidate $record, array $data): void {
                            $institution = Institution::find($data['institution_id']);

                            // Vincular à instituição
                            $record->update([
                                'institution_id' => $data['institution_id'],
                                'student_type' => '1ª Fase - Recruta',
                            ]);

                            // Criar ou atualizar Student
                            $existingStudent = \App\Models\Student::where('candidate_id', $record->id)->first();

                            if ($existingStudent) {
                                $existingStudent->update([
                                    'institution_id' => $data['institution_id'],
                                    'student_type' => '1ª Fase - Recruta',
                                ]);
                            } else {
                                \App\Models\Student::create([
                                    'candidate_id' => $record->id,
                                    'institution_id' => $data['institution_id'],
                                    'student_number' => 'ALT-' . $record->id,
                                    'student_type' => '1ª Fase - Recruta',
                                    'nuri' => $record->nuri ?? null,
                                    'enrollment_date' => now(),
                                ]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Alistado Vinculado e Convertido!')
                                ->body("Vinculado à escola \"{$institution->name}\" e convertido em Recruta (1ª Fase).")
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
                    ->modalHeading('Vincular Alistados e Converter em Recrutas')
                    ->modalDescription('Os alistados selecionados serão vinculados à escola escolhida e automaticamente convertidos em Recrutas (1ª Fase). Eles aparecerão na listagem de Gestão de Formandos.')
                    ->modalIcon('heroicon-o-academic-cap')
                    ->form([
                        Forms\Components\Select::make('institution_id')
                            ->label('Instituição de Ensino')
                            ->options(Institution::orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Selecione a escola onde os alistados serão formados'),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        $institution = Institution::find($data['institution_id']);
                        $countConverted = 0;

                        // Obter o ID do tipo de aluno dinamicamente
                        $studentTypeId = \App\Models\StudentType::getIdByName('1ª Fase - Recruta');

                        foreach ($records as $candidate) {
                            // Atualizar institution_id e student_type do Candidate
                            // Mudar para '1ª Fase - Recruta' para remover da listagem de Alistados
                            $candidate->update([
                                'institution_id' => $data['institution_id'],
                                'student_type' => '1ª Fase - Recruta',
                            ]);

                            // Verificar se já existe Student para este Candidate
                            $existingStudent = \App\Models\Student::where('candidate_id', $candidate->id)->first();

                            if ($existingStudent) {
                                // Já existe, apenas atualizar institution_id e student_type
                                $existingStudent->update([
                                    'institution_id' => $data['institution_id'],
                                    'student_type' => '1ª Fase - Recruta',
                                    'student_type_id' => $studentTypeId,
                                ]);
                            } else {
                                // Criar novo Student como Recruta
                                \App\Models\Student::create([
                                    'candidate_id' => $candidate->id,
                                    'institution_id' => $data['institution_id'],
                                    'student_number' => 'ALT-' . $candidate->id,
                                    'student_type' => '1ª Fase - Recruta',
                                    'student_type_id' => $studentTypeId,
                                    'nuri' => $candidate->nuri ?? null,
                                    'enrollment_date' => now(),
                                ]);
                            }
                            $countConverted++;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Alistados Convertidos em Recrutas!')
                            ->body("{$countConverted} alistados foram vinculados à escola \"{$institution->name}\" e convertidos em Recrutas. Eles foram removidos desta listagem e estão agora em Gestão de Formandos.")
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
                            $record->update(['institution_id' => $data['institution_id']]);
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
                            ->default("Prezado(a) {nome}, informamos que deve apresentar-se na {escola} para obter informacoes sobre o aquartelamento. Compareça com documento de identificacao. Policia Nacional de Angola.")
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
        return auth()->user()?->can('ViewAny:Candidate') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
