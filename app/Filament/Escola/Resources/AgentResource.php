<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\AgentResource\Pages;
use App\Models\Student;
use App\Models\Candidate;
use App\Models\Institution;
use App\Models\Provenance;
use App\Models\Rank;
use App\Models\StudentType;
use App\Models\AgentTransferHistory;
use App\Services\SmsService;
use App\Services\PnaAgentService;
use App\Imports\AgentImport;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;


class AgentResource extends Resource
{
    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-shield-check';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Cadetes';
    protected static ?string $modelLabel = 'Cadete';
    protected static ?string $pluralModelLabel = 'Cadetes';


    // Filtrar agentes (Em Formação ou Formação Concluída) que estão no Curso Superior
    public static function getEloquentQuery(): Builder
    {
        // Buscar IDs dos tipos de aluno que são "Formando" (inclui "Formando" e "Formando Superior")
        $agentTypeIds = StudentType::where('name', 'like', '%Formando%')
            ->pluck('id')
            ->toArray();

        return parent::getEloquentQuery()
            ->whereIn('status', ['em_formacao', 'concluiu'])
            ->where(function ($query) use ($agentTypeIds) {
                // Usar student_type_id se disponível, senão fallback para student_type string
                $query->whereIn('student_type_id', $agentTypeIds)
                    ->orWhere('student_type', 'Formando')
                    ->orWhere('student_type', 'Formando Superior')
                    ->orWhere('student_type', 'like', '%Formando%');
            })
            ->with(['candidate', 'institution', 'currentPhase', 'provenance', 'rank', 'studentTypeRelation']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // Foto do cadete
                Forms\Components\FileUpload::make('photo')
                    ->label('Foto do Cadete')
                    ->image()
                    ->avatar()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                    ->directory('agents/photos')
                    ->columnSpanFull(),

                // Campos ocultos para controle
                Forms\Components\Hidden::make('dados_da_api')
                    ->default(fn($record) => $record?->created_via_api ?? false)
                    ->afterStateHydrated(function ($state, $set, $record) {
                        if ($record && $record->created_via_api) {
                            $set('dados_da_api', true);
                        }
                    }),
                Forms\Components\Hidden::make('created_via_api')
                    ->default(false)
                    ->dehydrated(true),
                Forms\Components\Hidden::make('candidate_id'),

                // NIP - com busca automática na API PIIPS
                Forms\Components\TextInput::make('nuri')
                    ->label('NIP')
                    ->maxLength(9)
                    ->regex('/^[0-9]+$/')
                    ->validationMessages([
                        'regex' => 'O campo NIP deve conter apenas números.',
                    ])
                    ->required()
                    ->unique(table: 'students', column: 'nuri', ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Já existe um agente com este NIP.',
                    ])
                    ->default(fn($record) => $record?->nuri)
                    ->dehydrated(true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state && strlen($state) >= 5 && !$get('dados_da_api')) {
                            $existingAgent = \App\Models\Student::where('nuri', $state)->first();
                            if ($existingAgent) {
                                \Filament\Notifications\Notification::make()
                                    ->title('NIP Já Cadastrado')
                                    ->body('Já existe um agente com o NIP ' . $state . ' no sistema: ' . $existingAgent->full_name)
                                    ->danger()
                                    ->duration(8000)
                                    ->send();
                                return;
                            }

                            $piipsService = app(\App\Services\PnaAgentService::class);

                            try {
                                $agentePiips = $piipsService->buscarPorNip($state);

                                if ($agentePiips) {
                                    $set('dados_da_api', true);
                                    $set('created_via_api', true);

                                    $nome = $agentePiips['nome_completo'] ?? null;
                                    if ($nome) {
                                        $nome = mb_convert_encoding($nome, 'UTF-8', 'UTF-8');
                                        $nome = preg_replace('/\?+/', '', $nome);
                                        $nome = trim(preg_replace('/\s+/', ' ', $nome));
                                        $set('full_name_manual', $nome);
                                    }

                                    $patente = $agentePiips['patente'] ?? null;
                                    if (is_array($patente)) $patente = $patente['nome'] ?? null;
                                    if ($patente) {
                                        $patente = mb_convert_encoding($patente, 'UTF-8', 'UTF-8');
                                        $patente = str_replace(['??', '?ª', 'ª?'], 'ª', $patente);
                                        $patente = preg_replace('/\?+/', 'ª', $patente);
                                        $patenteNorm = preg_replace('/[^\w\s]/u', '', $patente);
                                        $rank = Rank::where('name', 'like', '%' . $patenteNorm . '%')->first();
                                        if (!$rank) $rank = Rank::create(['name' => strtoupper($patente)]);
                                        $set('rank_id', $rank->id);
                                    }

                                    $colocacao = $agentePiips['colocacao'] ?? null;
                                    if (is_array($colocacao)) $colocacao = $colocacao['sigla'] ?? $colocacao['nome'] ?? null;
                                    if ($colocacao) {
                                        $provenance = Provenance::where('name', 'like', '%' . $colocacao . '%')->first();
                                        if (!$provenance) $provenance = Provenance::create(['name' => strtoupper($colocacao)]);
                                        $set('provenance_id', $provenance->id);
                                    }

                                    $telefones = $agentePiips['telefones'] ?? null;
                                    if ($telefones) {
                                        $phone = $piipsService->formatarTelefone($telefones);
                                        if ($phone) {
                                            $phone = preg_replace('/^\+?244/', '', $phone);
                                            $set('phone', $phone);
                                        }
                                    }

                                    \Filament\Notifications\Notification::make()
                                        ->title('Dados encontrados no PIIPS')
                                        ->body($nome ?? 'Agente encontrado')
                                        ->success()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::warning('PIIPS API offline', ['error' => $e->getMessage()]);
                            }
                        }
                    })
                    ->helperText('Digite o NIP para buscar dados automaticamente'),

                // Nome Completo
                Forms\Components\TextInput::make('full_name_manual')
                    ->label('Nome Completo')
                    ->required()
                    ->maxLength(191)
                    ->dehydrated(true)
                    ->live()
                    ->disabled(fn($get, $record) => ($get('dados_da_api') === true || $get('dados_da_api') === 'true') || ($record && $record->created_via_api))
                    ->default(fn($record) => $record?->candidate?->full_name)
                    ->afterStateHydrated(function ($state, $set, $record) {
                        if ($record && !$state) {
                            $set('full_name_manual', $record->candidate?->full_name);
                        }
                    })
                    ->helperText('Nome completo do cadete'),

                Forms\Components\Select::make('provenance_id')
                    ->label('Proveniência (Órgão/Unidade)')
                    ->options(Provenance::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->disabled(fn($get, $record) => ($get('dados_da_api') === true || $get('dados_da_api') === 'true') || ($record && $record->created_via_api))
                    ->dehydrated(true)
                    ->required(),

                Forms\Components\Select::make('rank_id')
                    ->label('Patente')
                    ->options(Rank::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->disabled(fn($get, $record) => ($get('dados_da_api') === true || $get('dados_da_api') === 'true') || ($record && $record->created_via_api))
                    ->dehydrated(true)
                    ->required(),

                Forms\Components\TextInput::make('phone')
                    ->label('Telefone')
                    ->tel()
                    ->prefix('+244')
                    ->placeholder('9XX XXX XXX')
                    ->mask('999 999 999')
                    ->maxLength(20)
                    ->live()
                    ->disabled(fn($get, $record) => ($get('dados_da_api') === true || $get('dados_da_api') === 'true') || ($record && $record->created_via_api))
                    ->dehydrated(true),

                // Campos ocultos
                Forms\Components\Hidden::make('student_type')
                    ->default('Formando'),
                Forms\Components\Hidden::make('status')
                    ->default('em_formacao'),
            ])->columns(2);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(function ($record) {
                        $name = $record->candidate?->full_name ?? 'Cadete';
                        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0D47A1&color=fff&size=128&bold=true';
                    }),
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('nuri')
                    ->label('NIP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('studentTypeRelation.name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn($record) => $record->studentTypeRelation?->color ?? 'gray')
                    ->default(fn($record) => $record->student_type),
                Tables\Columns\TextColumn::make('rank.name')
                    ->label('Patente')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('created_via_api')
                    ->label('Modo de Cadastro')
                    ->options([
                        '1' => 'Via API (PIIPS)',
                        '0' => 'Manual',
                    ])
                    ->placeholder('Todos'),
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Instituição de Ensino')
                    ->relationship('institution', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('provenance_id')
                    ->label('Proveniência')
                    ->relationship('provenance', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('rank_id')
                    ->label('Patente')
                    ->relationship('rank', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                // Botão de Importação Excel
                \Filament\Actions\Action::make('importarExcel')
                    ->label('Importar Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->extraAttributes([
                        'style' => 'background-color: #11ba82 !important; border-color: #11ba82 !important; color: white !important;',
                        'class' => 'text-white',
                    ])
                    ->modalHeading('Importar Cadetes do Excel')
                    ->modalDescription(new \Illuminate\Support\HtmlString('<span style="color: white;">Faça upload de um arquivo Excel (.xlsx, .xls) com os dados dos cadetes. O arquivo deve conter as colunas: Nome, NIP, Telefone, Patente, Proveniência, Nº Ordem</span>'))
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
                            $import = new AgentImport();
                            Excel::import($import, $filePath);

                            $stats = $import->getImportStats();
                            $failures = $import->failures();
                            $detailedErrors = $import->getDetailedErrors();

                            // Limpar arquivo temporário
                            @unlink($filePath);

                            // Construir mensagem de resultado
                            if ($stats['imported'] > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Importação Concluída')
                                    ->body("Importados: {$stats['imported']} registros com sucesso!")
                                    ->success()
                                    ->duration(8000)
                                    ->send();
                            }

                            if ($stats['skipped'] > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Registros Ignorados')
                                    ->body("{$stats['skipped']} registros já existiam no sistema.")
                                    ->warning()
                                    ->duration(8000)
                                    ->send();
                            }

                            // Exibir erros detalhados
                            if (count($detailedErrors) > 0) {
                                $errorList = implode("\n", array_slice($detailedErrors, 0, 5));
                                if (count($detailedErrors) > 5) {
                                    $errorList .= "\n...e mais " . (count($detailedErrors) - 5) . " erros.";
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Problemas Encontrados')
                                    ->body($errorList)
                                    ->danger()
                                    ->duration(15000)
                                    ->send();
                            }

                            // Exibir erros de validação do Excel
                            if (count($failures) > 0) {
                                $failureMessages = [];
                                foreach (array_slice($failures, 0, 5) as $failure) {
                                    $failureMessages[] = "Linha {$failure->row()}: " . implode(', ', $failure->errors());
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Erros de Validação')
                                    ->body(implode("\n", $failureMessages))
                                    ->danger()
                                    ->duration(15000)
                                    ->send();
                            }

                            // Se nenhum registro foi importado e houve erros
                            if ($stats['imported'] === 0 && (count($detailedErrors) > 0 || count($failures) > 0)) {
                                \Illuminate\Support\Facades\Log::warning('Nenhum registro importado', [
                                    'errors' => $detailedErrors,
                                    'failures' => count($failures),
                                ]);
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Erro na importação de agentes', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Erro na Importação')
                                ->body('Ocorreu um erro: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalSubmitAction(
                        fn(\Filament\Actions\Action $action) => $action
                            ->label('Importar')
                            ->icon('heroicon-o-arrow-up-tray')
                    )
                    ->modalCancelAction(
                        fn(\Filament\Actions\Action $action) => $action
                            ->label('Cancelar')
                            ->icon('heroicon-o-x-mark')
                            ->color('danger')
                    ),
                // Botão para baixar modelo de planilha
                \Filament\Actions\Action::make('baixarModelo')
                    ->label('Baixar Modelo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->action(function () {
                        return Excel::download(new \App\Exports\AgentTemplateExport(), 'modelo_importacao_agentes.xlsx');
                    }),
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        // DEBUG: Log para verificar os dados recebidos
                        \Illuminate\Support\Facades\Log::info('AgentResource - mutateFormDataUsing', [
                            'full_name_manual' => $data['full_name_manual'] ?? 'VAZIO',
                            'nuri' => $data['nuri'] ?? 'VAZIO',
                            'candidate_id' => $data['candidate_id'] ?? 'VAZIO',
                        ]);

                        // Obter o primeiro tipo de recrutamento disponível
                        $recruitmentTypeId = \App\Models\RecruitmentType::first()?->id;

                        // Nome do agente (prioriza full_name_manual se disponível)
                        $fullName = $data['full_name_manual'] ?? null;
                        $nip = $data['nuri'] ?? $data['nuri_manual'] ?? null;

                        // Se já tem candidate_id válido, usar esse
                        if (!empty($data['candidate_id'])) {
                            // Verificar se o candidato existe
                            $existingCandidate = Candidate::find($data['candidate_id']);
                            if ($existingCandidate) {
                                // Usar o candidato existente
                                unset($data['cadastro_mode']);
                                unset($data['full_name_manual']);
                                unset($data['nuri_manual']);
                                unset($data['candidate_name_display']);
                                return $data;
                            }
                        }

                        // Se não tem candidate_id, precisamos criar um candidato
                        if (empty($data['candidate_id']) && !empty($fullName)) {
                            if ($nip) {
                                // Se tem NIP, buscar ou criar candidato
                                $candidate = Candidate::firstOrCreate(
                                    ['id_number' => $nip],
                                    [
                                        'full_name' => $fullName,
                                        'institution_id' => $data['institution_id'] ?? null,
                                        'provenance_id' => $data['provenance_id'] ?? null,
                                        'current_rank_id' => $data['rank_id'] ?? null,
                                        'recruitment_type_id' => $recruitmentTypeId,
                                        'student_type' => 'Formando', // Marcar como agente
                                        'status' => 'aprovado',
                                        'phone' => $data['phone'] ?? null,
                                        'photo' => $data['photo'] ?? null,
                                    ]
                                );
                                // Atualizar phone e photo se o candidato já existia
                                if (!$candidate->wasRecentlyCreated) {
                                    $candidate->update([
                                        'phone' => $data['phone'] ?? $candidate->phone,
                                        'photo' => $data['photo'] ?? $candidate->photo,
                                    ]);
                                }
                            } else {
                                // Se não tem NIP, criar novo candidato
                                $candidate = Candidate::create([
                                    'full_name' => $fullName,
                                    'id_number' => null,
                                    'institution_id' => $data['institution_id'] ?? null,
                                    'provenance_id' => $data['provenance_id'] ?? null,
                                    'current_rank_id' => $data['rank_id'] ?? null,
                                    'recruitment_type_id' => $recruitmentTypeId,
                                    'student_type' => 'Formando', // Marcar como agente
                                    'status' => 'aprovado',
                                    'phone' => $data['phone'] ?? null,
                                    'photo' => $data['photo'] ?? null,
                                ]);
                            }

                            $data['candidate_id'] = $candidate->id;
                            $data['nuri'] = $nip;
                        } elseif (!empty($data['candidate_id'])) {
                            // Se já tem candidate_id, atualizar o candidato com phone e photo
                            $candidate = Candidate::find($data['candidate_id']);
                            if ($candidate) {
                                $candidate->update([
                                    'phone' => $data['phone'] ?? $candidate->phone,
                                    'photo' => $data['photo'] ?? $candidate->photo,
                                ]);
                            }
                        }

                        // Se ainda não tem candidate_id, exibir erro e parar
                        if (empty($data['candidate_id'])) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro ao criar agente')
                                ->body('É necessário preencher o nome do agente para criar o cadastro.')
                                ->danger()
                                ->send();
                            throw new \Filament\Support\Exceptions\Halt();
                        }

                        // Remover campos temporários
                        unset($data['cadastro_mode']);
                        unset($data['full_name_manual']);
                        unset($data['nuri_manual']);
                        unset($data['candidate_name_display']);

                        // Garantir valores padrão para campos obrigatórios
                        $data['enrollment_date'] = $data['enrollment_date'] ?? now();

                        // Auto-gerar student_number se não existir
                        if (empty($data['student_number'])) {
                            $prefix = 'CAD-' . now()->format('Ymd') . '-';
                            $lastNumber = \App\Models\Student::where('student_number', 'like', $prefix . '%')
                                ->orderByDesc('student_number')
                                ->value('student_number');
                            $nextSeq = $lastNumber ? (intval(substr($lastNumber, -4)) + 1) : 1;
                            $data['student_number'] = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
                        }

                        // Definir student_type_id se não existir
                        if (empty($data['student_type_id'])) {
                            $data['student_type_id'] = \App\Models\StudentType::where('name', 'like', '%Formando%')->first()?->id;
                        }

                        // Definir institution_id padrão se não existir
                        if (empty($data['institution_id'])) {
                            $data['institution_id'] = auth()->user()?->institution_id;
                        }

                        return $data;
                    })
                    ->after(function ($record) {
                        // Obter telefone do Student ou do Candidate
                        $phone = $record->phone ?? $record->candidate?->phone;

                        // Enviar SMS ao agente após a criação
                        if (!empty($phone)) {
                            try {
                                $smsService = app(SmsService::class);

                                // Obter o nome do agente
                                $agentName = $record->candidate?->full_name ?? 'Agente';

                                // Obter o nome da escola
                                $schoolName = $record->institution?->name ?? 'Instituição de Ensino da Polícia Nacional';

                                // Enviar SMS de notificação
                                $result = $smsService->sendAgentRegistrationNotification(
                                    $phone,
                                    $agentName,
                                    $schoolName
                                );

                                if ($result['success']) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('SMS Enviado')
                                        ->body("SMS de notificação enviado para {$phone}")
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Falha ao enviar SMS')
                                        ->body('Não foi possível enviar SMS. Detalhes: ' . ($result['message'] ?? 'Erro desconhecido'))
                                        ->warning()
                                        ->duration(8000)
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Erro ao enviar SMS para agente', [
                                    'agent_id' => $record->id,
                                    'phone' => $phone,
                                    'error' => $e->getMessage(),
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Erro ao enviar SMS')
                                    ->body('Ocorreu um erro ao tentar enviar o SMS: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('SMS não enviado')
                                ->body('Nenhum número de telefone disponível para enviar SMS.')
                                ->warning()
                                ->send();
                        }
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Cadete criado com sucesso!')
                    ->label('Novo Cadete'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->mutateFormDataUsing(function (array $data, $record): array {
                            // Preservar o candidate_id original se não foi alterado
                            if (empty($data['candidate_id']) && $record->candidate_id) {
                                $data['candidate_id'] = $record->candidate_id;
                            }

                            // Atualizar o candidato com nome, phone e photo
                            if ($record->candidate_id) {
                                $candidate = \App\Models\Candidate::find($record->candidate_id);
                                if ($candidate) {
                                    $fullName = $data['full_name_manual'] ?? null;
                                    $updateData = [];

                                    if ($fullName && $candidate->full_name !== $fullName) {
                                        $updateData['full_name'] = $fullName;
                                    }
                                    if (isset($data['phone'])) {
                                        $updateData['phone'] = $data['phone'];
                                    }
                                    if (isset($data['photo'])) {
                                        $updateData['photo'] = $data['photo'];
                                    }

                                    if (!empty($updateData)) {
                                        $candidate->update($updateData);
                                    }
                                }
                            }

                            // Limpar campos temporários
                            unset($data['cadastro_mode']);
                            unset($data['full_name_manual']);
                            unset($data['nuri_manual']);
                            unset($data['candidate_name_display']);

                            // Garantir enrollment_date
                            $data['enrollment_date'] = $data['enrollment_date'] ?? $record->enrollment_date ?? now()->format('Y-m-d');

                            return $data;
                        })
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Cadete atualizado com sucesso!'),
                    \Filament\Actions\Action::make('vincularEIniciarFormacao')
                        ->label('Vincular e Iniciar Formação')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Vincular e Iniciar Formação')
                        ->modalDescription(fn(Student $record) => 'O cadete "' . ($record->candidate?->full_name ?? 'N/A') . '" será vinculado à escola escolhida e o estado será alterado para "Em Formação".')
                        ->modalIcon('heroicon-o-academic-cap')
                        ->form([
                            Forms\Components\Select::make('institution_id')
                                ->label('Instituição de Ensino')
                                ->options(Institution::orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->helperText('Selecione a escola onde o agente será formado'),
                        ])
                        ->action(function (Student $record, array $data): void {
                            $institution = Institution::find($data['institution_id']);
                            $studentTypeId = \App\Models\StudentType::getIdByName('Em Formação');

                            $record->update([
                                'institution_id' => $data['institution_id'],
                                'student_type' => 'Em Formação',
                                'student_type_id' => $studentTypeId,
                            ]);

                            if ($record->candidate) {
                                $record->candidate->update([
                                    'institution_id' => $data['institution_id'],
                                    'student_type' => 'Em Formação',
                                ]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Formação Iniciada!')
                                ->body("O agente foi vinculado à escola \"{$institution->name}\" e iniciou formação.")
                                ->success()
                                ->send();
                        })
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Confirmar')->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->color('danger')),

                    \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações')
                    ->icon('heroicon-s-cog-6-tooth')
                    ->color('primary')
                    ->size('lg')
                    ->tooltip('Acções')
                    ->iconButton(),
            ])
            ->bulkActions([
                // Vincular e Iniciar Formação - botão directo
                \Filament\Actions\BulkAction::make('vincularEIniciarFormacao')
                    ->label('Vincular e Iniciar Formação')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->extraAttributes([
                        'style' => 'background-color: #059669 !important; border-color: #059669 !important; color: white !important; --c-400: 255,255,255; --c-500: 255,255,255;',
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Vincular e Iniciar Formação')
                    ->modalDescription('Os agentes selecionados serão vinculados à escola escolhida e o estado será alterado para "Em Formação".')
                    ->modalIcon('heroicon-o-academic-cap')
                    ->form([
                        Forms\Components\Select::make('institution_id')
                            ->label('Instituição de Ensino')
                            ->options(Institution::orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Selecione a escola onde os agentes serão formados'),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        $institution = Institution::find($data['institution_id']);
                        $count = 0;
                        $studentTypeId = \App\Models\StudentType::getIdByName('Em Formação');

                        foreach ($records as $record) {
                            $record->update([
                                'institution_id' => $data['institution_id'],
                                'student_type' => 'Em Formação',
                                'student_type_id' => $studentTypeId,
                            ]);
                            if ($record->candidate) {
                                $record->candidate->update([
                                    'institution_id' => $data['institution_id'],
                                    'student_type' => 'Em Formação',
                                ]);
                            }
                            $count++;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Formação Iniciada!')
                            ->body("{$count} agentes foram vinculados à escola \"{$institution->name}\" e iniciaram formação.")
                            ->success()
                            ->duration(10000)
                            ->send();
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Confirmar')->color('primary'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->color('danger'))
                    ->deselectRecordsAfterCompletion(),
                // Apenas Atribuir Escola - botão directo
                \Filament\Actions\BulkAction::make('atribuirInstituicaoEmMassa')
                    ->label('Apenas Atribuir Escola')
                    ->icon('heroicon-o-building-library')
                    ->color('info')
                    ->extraAttributes([
                        'style' => 'background-color: #2563EB !important; border-color: #2563EB !important; color: white !important; --c-400: 255,255,255; --c-500: 255,255,255;',
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Atribuir Escola aos Agentes Selecionados')
                    ->modalDescription('Os agentes serão atribuídos à escola, mas NÃO iniciarão formação ainda.')
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
                            if ($record->candidate) {
                                $record->candidate->update(['institution_id' => $data['institution_id']]);
                            }
                            $count++;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Escola Atribuída!')
                            ->body("{$count} agentes foram atribuídos à escola \"{$institution->name}\".")
                            ->success()
                            ->send();
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->label('Confirmar')->color('primary'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->color('danger'))
                    ->deselectRecordsAfterCompletion(),
                // Enviar SMS - botão directo
                \Filament\Actions\BulkAction::make('enviarSmsEmMassa')
                    ->label('Enviar SMS')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('warning')
                    ->extraAttributes([
                        'style' => 'background-color: #D97706 !important; border-color: #D97706 !important; color: white !important; --c-400: 255,255,255; --c-500: 255,255,255;',
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Enviar SMS de Apresentação')
                    ->modalDescription('Será enviado um SMS aos agentes selecionados.')
                    ->modalIcon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Textarea::make('mensagem')
                            ->label('Mensagem')
                            ->default("Prezado(a) {nome}, informamos que deve apresentar-se na {escola} para obter informacoes sobre o aquartelamento. Compareça com documento de identificacao. Policia Nacional de Angola.")
                            ->helperText('Use {nome} para o nome do agente e {escola} para o nome da escola.')
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
                            $phone = $record->phone ?? $record->candidate?->phone;
                            $escola = $record->institution?->name;
                            $nome = $record->candidate?->full_name ?? 'Agente';

                            if (empty($phone)) {
                                $semTelefone++;
                                continue;
                            }
                            if (empty($escola)) {
                                $semEscola++;
                                continue;
                            }

                            $mensagem = str_replace(['{nome}', '{escola}'], [$nome, $escola], $data['mensagem']);
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
                // Delete fica dentro do grupo para segurança
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
            'index' => Pages\ListAgents::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        // Cadetes only visible in INSTITUTO type institutions
        $tenant = \Filament\Facades\Filament::getTenant();
        if ($tenant && $tenant->institution_type_id !== 3) {
            return false;
        }
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
