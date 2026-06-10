<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\TrainerResource\Pages;
use App\Filament\Resources\TrainerResource\RelationManagers;
use App\Models\Trainer;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Closure;

class TrainerResource extends Resource
{
    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $model = Trainer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-presentation-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 0;
    protected static ?string $modelLabel = 'Formador';
    protected static ?string $pluralModelLabel = 'Formadores';
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['rank', 'institution']);

        // Filter by current tenant: check both institution_id column AND pivot table
        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            $query->where(function ($q) use ($tenant) {
                $q->where('institution_id', $tenant->id)
                    ->orWhereHas('institutions', fn($sub) => $sub->where('institutions.id', $tenant->id))
                    ->orWhereHas('subjectAuthorizations', fn($sub) => $sub->where('institution_id', $tenant->id));
            });
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Wizard::make([
                    \Filament\Schemas\Components\Wizard\Step::make('Tipo')
                        ->description('Selecione o tipo de formador')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Forms\Components\Select::make('trainer_type')
                                ->label('Tipo de Formador')
                                ->options([
                                    'Fardado' => 'REGIME ESPECIAL',
                                    'Civil' => 'REGIME GERAL',
                                ])
                                ->default('Fardado')
                                ->required()
                                ->live()
                                ->columnSpanFull(),
                        ]),

                    \Filament\Schemas\Components\Wizard\Step::make('Identificação')
                        ->description('Dados pessoais do formador')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Forms\Components\FileUpload::make('photo')
                                ->label('Foto')
                                ->image()
                                ->avatar()
                                ->directory('trainers')
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('full_name')
                                ->label('Nome Completo')
                                ->required()
                                ->maxLength(191)
                                ->unique(ignoreRecord: true)
                                ->validationMessages([
                                    'unique' => 'Já existe um formador com este nome.',
                                ]),
                            Forms\Components\Select::make('gender')
                                ->label('Género')
                                ->options([
                                    'Masculino' => 'Masculino',
                                    'Feminino' => 'Feminino',
                                ])
                                ->required(),

                            // Campos para Fardado
                            \Filament\Schemas\Components\Fieldset::make('Dados Pessoais')
                                ->schema([
                                    Forms\Components\TextInput::make('nip')
                                        ->label('NIP')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(191),
                                    Forms\Components\Select::make('rank_id')
                                        ->label('Patente')
                                        ->relationship('rank', 'name')
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Select::make('organ')
                                        ->label('Órgão/Unidade')
                                        ->options(fn() => \App\Models\Provenance::orderBy('name')->get()->mapWithKeys(fn($p) => [$p->name => $p->acronym ? "{$p->name} ({$p->acronym})" : $p->name])->toArray())
                                        ->searchable()
                                        ->preload(),
                                ])->columns(3)
                                ->columnSpanFull()
                                ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') === 'Fardado'),

                            // Campos para Civil
                            \Filament\Schemas\Components\Fieldset::make('Dados Pessoais')
                                ->schema([
                                    Forms\Components\TextInput::make('bilhete')
                                        ->label('Bilhete de Identidade')
                                        ->maxLength(191)
                                        ->columnSpanFull(),
                                ])->columns(1)
                                ->columnSpanFull()
                                ->visible(fn(\Filament\Schemas\Components\Utilities\Get $get): bool => $get('trainer_type') === 'Civil'),
                        ])->columns(2),

                    \Filament\Schemas\Components\Wizard\Step::make('Disciplinas')
                        ->description('Atribuir disciplinas ao formador')
                        ->icon('heroicon-o-academic-cap')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Disciplinas Autorizadas')
                                ->description('Selecione as disciplinas que o formador pode leccionar em cada instituição')
                                ->schema([
                                    Forms\Components\Repeater::make('subjectAuthorizations')
                                        ->label('Autorizações de Disciplinas')
                                        ->relationship()
                                        ->schema([
                                            Forms\Components\Select::make('institution_id')
                                                ->label('Instituição')
                                                ->options(fn() => \App\Models\Institution::orderBy('name')->pluck('name', 'id'))
                                                ->getOptionLabelUsing(fn($value): ?string => \App\Models\Institution::find($value)?->name)
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->live(debounce: 0)
                                                ->afterStateUpdated(function ($set) {
                                                    $set('course_id', null);
                                                    $set('subject_id', null);
                                                }),
                                            Forms\Components\Select::make('course_id')
                                                ->label('Curso')
                                                ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                                    $institutionId = $get('institution_id');
                                                    if (!$institutionId) {
                                                        return [];
                                                    }
                                                    return \App\Models\CourseMap::where('course_maps.institution_id', $institutionId)
                                                        ->join('courses', 'course_maps.course_id', '=', 'courses.id')
                                                        ->orderBy('courses.name')
                                                        ->pluck('courses.name', 'course_maps.course_id')
                                                        ->toArray();
                                                })
                                                ->getOptionLabelUsing(fn($value): ?string => \App\Models\Course::find($value)?->name)
                                                ->searchable()
                                                ->required()
                                                ->live(debounce: 0)
                                                ->afterStateUpdated(fn($set) => $set('subject_id', null)),
                                            Forms\Components\Select::make('subject_id')
                                                ->label('Disciplina')
                                                ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                                    $courseId = $get('course_id');
                                                    if (!$courseId) {
                                                        return [];
                                                    }
                                                    $coursePlan = \App\Models\CoursePlan::where('course_id', $courseId)
                                                        ->where('is_active', true)
                                                        ->first();

                                                    if (!$coursePlan) {
                                                        return \App\Models\Subject::orderBy('name')->pluck('name', 'id')->toArray();
                                                    }

                                                    return $coursePlan->subjects()->orderBy('name')->pluck('name', 'subjects.id')->toArray();
                                                })
                                                ->getOptionLabelUsing(fn($value): ?string => \App\Models\Subject::find($value)?->name)
                                                ->searchable()
                                                ->required(),
                                        ])
                                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record): ?array {
                                            $trainerId = $record?->id;
                                            $institutionId = $data['institution_id'] ?? null;
                                            $courseId = $data['course_id'] ?? null;
                                            $subjectId = $data['subject_id'] ?? null;

                                            // Verificar se já existe esta combinação para ESTE formador
                                            $existsForThis = \App\Models\TrainerSubjectAuthorization::where([
                                                'trainer_id' => $trainerId,
                                                'institution_id' => $institutionId,
                                                'course_id' => $courseId,
                                                'subject_id' => $subjectId,
                                            ])->exists();

                                            if ($existsForThis && $trainerId) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Disciplina já atribuída!')
                                                    ->body('Esta combinação de instituição, curso e disciplina já existe para este formador.')
                                                    ->danger()
                                                    ->duration(5000)
                                                    ->send();
                                                return null; // Não criar
                                            }

                                            // Verificar se OUTRO formador já tem esta disciplina
                                            $existingTrainer = \App\Models\TrainerSubjectAuthorization::getExistingTrainer(
                                                $institutionId,
                                                $courseId,
                                                $subjectId,
                                                $trainerId
                                            );

                                            if ($existingTrainer) {
                                                $trainerName = $existingTrainer->candidate?->full_name ?? 'Outro formador';
                                                \Filament\Notifications\Notification::make()
                                                    ->title('⚠️ Atenção: Disciplina já atribuída a outro formador!')
                                                    ->body("A disciplina já está atribuída a \"{$trainerName}\". Se continuar, haverá dois formadores para a mesma disciplina.")
                                                    ->warning()
                                                    ->duration(8000)
                                                    ->send();
                                                // Permite criar mas mostra aviso
                                            }

                                            $data['authorized_by'] = auth()->id();
                                            return $data;
                                        })
                                        ->columns(3)
                                        ->columnSpanFull()
                                        ->addActionLabel('Adicionar Disciplina')
                                        ->reorderable(false)
                                        ->defaultItems(0),
                                ]),
                        ]),

                    \Filament\Schemas\Components\Wizard\Step::make('Finalização')
                        ->description('Informações adicionais')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            Forms\Components\Select::make('education_level')
                                ->label('Nível Académico')
                                ->options([
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
                                ])
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('phone')
                                ->label('Telefone')
                                ->tel()
                                ->prefix('+244')
                                ->placeholder('9XX XXX XXX')
                                ->mask('999 999 999')
                                ->maxLength(191)
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Activo')
                                ->default(true)
                                ->required(),
                        ])->columns(5),
                ])
                    ->skippable()
                    ->persistStepInQueryString()
                    ->columnSpanFull(),
            ]);
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
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name ?? 'F') . '&background=0D47A1&color=fff&size=128'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('bilhete')
                    ->label('Bilhete')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rank.name')
                    ->label('Patente')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Escola')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('trainer_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'Fardado' => 'REGIME ESPECIAL',
                        'Civil' => 'REGIME GERAL',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'Fardado',
                        'success' => 'Civil',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trainer_type')
                    ->label('Tipo')
                    ->options([
                        'Fardado' => 'REGIME ESPECIAL',
                        'Civil' => 'REGIME GERAL',
                    ]),
                Tables\Filters\SelectFilter::make('rank_id')
                    ->label('Patente')
                    ->relationship('rank', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado'),
            ])
            ->headerActions([
                // Botão Importar Excel
                \Filament\Actions\Action::make('importarExcel')
                    ->label('Importar Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->extraAttributes([
                        'style' => 'background-color: #11ba82 !important; border-color: #11ba82 !important; color: white !important;',
                    ])
                    ->modalHeading('Importar Formadores do Excel')
                    ->modalDescription(new \Illuminate\Support\HtmlString('<span style="color: white;">Faça upload de um arquivo Excel (.xlsx, .xls) com os dados dos formadores.</span>'))
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
                            $import = new \App\Imports\TrainerImport();
                            \Maatwebsite\Excel\Facades\Excel::import($import, $filePath);

                            $stats = $import->getImportStats();
                            $detailedErrors = $import->getDetailedErrors();

                            @unlink($filePath);

                            if ($stats['imported'] > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Importação Concluída')
                                    ->body("Importados: {$stats['imported']} formadores!")
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
                // Botão Baixar Modelo
                \Filament\Actions\Action::make('baixarModelo')
                    ->label('Baixar Modelo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->action(function () {
                        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\TrainerTemplateExport(), 'modelo_importacao_formadores.xlsx');
                    }),
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth('6xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Registo criado com sucesso!')
                    ->label('Novo Formador'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->modalHeading('Detalhes do Formador')
                        ->schema([
                            \Filament\Schemas\Components\Section::make()
                                ->schema([
                                    \Filament\Schemas\Components\Grid::make(3)
                                        ->schema([
                                            \Filament\Schemas\Components\Group::make([
                                                Infolists\Components\TextEntry::make('full_name')
                                                    ->label('Nome Completo')
                                                    ->weight('bold')
                                                    ->size('lg'),
                                                Infolists\Components\TextEntry::make('trainer_type')
                                                    ->label('Tipo de Formador')
                                                    ->badge()
                                                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                                                        'Fardado' => 'REGIME ESPECIAL',
                                                        'Civil' => 'REGIME GERAL',
                                                        default => $state ?? '-',
                                                    })
                                                    ->color(fn(?string $state): string => match ($state) {
                                                        'Fardado' => 'primary',
                                                        'Civil' => 'success',
                                                        default => 'gray',
                                                    }),
                                                Infolists\Components\TextEntry::make('gender')
                                                    ->label('Género'),
                                                Infolists\Components\TextEntry::make('phone')
                                                    ->label('Telefone')
                                                    ->icon('heroicon-o-phone')
                                                    ->placeholder('Não informado'),
                                            ])->columnSpan(1),
                                            \Filament\Schemas\Components\Group::make([
                                                Infolists\Components\TextEntry::make('nip')
                                                    ->label('NIP')
                                                    ->placeholder('N/A'),
                                                Infolists\Components\TextEntry::make('bilhete')
                                                    ->label('Bilhete de Identidade')
                                                    ->placeholder('N/A'),
                                                Infolists\Components\TextEntry::make('rank.name')
                                                    ->label('Patente')
                                                    ->placeholder('N/A'),
                                                Infolists\Components\TextEntry::make('organ')
                                                    ->label('Órgão/Unidade')
                                                    ->placeholder('N/A'),
                                                Infolists\Components\TextEntry::make('education_level')
                                                    ->label('Nível Académico')
                                                    ->placeholder('Não informado'),
                                            ])->columnSpan(1),
                                            \Filament\Schemas\Components\Group::make([
                                                Infolists\Components\ImageEntry::make('photo')
                                                    ->label('Foto')
                                                    ->circular()
                                                    ->size(120)
                                                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name ?? 'F') . '&background=0D47A1&color=fff&size=128'),
                                                Infolists\Components\TextEntry::make('institution.name')
                                                    ->label('Instituição (Escola)')
                                                    ->icon('heroicon-o-building-library'),
                                                Infolists\Components\IconEntry::make('is_active')
                                                    ->label('Estado')
                                                    ->boolean()
                                                    ->trueIcon('heroicon-o-check-circle')
                                                    ->falseIcon('heroicon-o-x-circle'),
                                            ])->columnSpan(1),
                                        ]),
                                ]),
                            \Filament\Schemas\Components\Section::make('Disciplinas que Lecciona')
                                ->icon('heroicon-o-book-open')
                                ->collapsible()
                                ->schema([
                                    Infolists\Components\RepeatableEntry::make('subjectAuthorizations')
                                        ->label('')
                                        ->schema([
                                            Infolists\Components\TextEntry::make('course.name')
                                                ->label('Curso')
                                                ->badge()
                                                ->color('primary'),
                                            Infolists\Components\TextEntry::make('subject.name')
                                                ->label('Disciplina')
                                                ->badge()
                                                ->color('info'),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    \Filament\Actions\Action::make('atribuirDisciplina')
                        ->label('Atribuir Disciplina')
                        ->icon('heroicon-o-book-open')
                        ->color('primary')
                        ->modalHeading(fn($record) => 'Atribuir Disciplina a ' . $record->full_name)
                        ->modalWidth('6xl')
                        ->form([
                            \Filament\Schemas\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('institution_id')
                                    ->label('Instituição')
                                    ->options(\App\Models\Institution::orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('course_id')
                                    ->label('Curso')
                                    ->options(\App\Models\Course::orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                            Forms\Components\Select::make('subject_ids')
                                ->label('Disciplinas')
                                ->options(fn() => \App\Models\Subject::orderBy('name')->pluck('name', 'id'))
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->action(function (\App\Models\Trainer $record, array $data): void {
                            $created = 0;
                            $skipped = 0;
                            $institutionId = $data['institution_id'];
                            $courseId = $data['course_id'];

                            // Vincular instituição
                            $record->institutions()->syncWithoutDetaching([$institutionId]);

                            foreach ($data['subject_ids'] as $subjectId) {
                                $exists = \App\Models\TrainerSubjectAuthorization::where([
                                    'trainer_id' => $record->id,
                                    'institution_id' => $institutionId,
                                    'course_id' => $courseId,
                                    'subject_id' => $subjectId,
                                ])->exists();

                                if ($exists) {
                                    $skipped++;
                                    continue;
                                }

                                \App\Models\TrainerSubjectAuthorization::create([
                                    'trainer_id' => $record->id,
                                    'institution_id' => $institutionId,
                                    'course_id' => $courseId,
                                    'subject_id' => $subjectId,
                                    'authorized_by' => auth()->id(),
                                ]);
                                $created++;
                            }

                            $msg = [];
                            if ($created > 0) $msg[] = "{$created} disciplinas atribuídas";
                            if ($skipped > 0) $msg[] = "{$skipped} já existiam";

                            \Filament\Notifications\Notification::make()
                                ->title('Atribuição Concluída!')
                                ->body(implode(', ', $msg) ?: 'Nenhuma alteração feita')
                                ->success()
                                ->duration(8000)
                                ->send();
                        })
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Atribuir')->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger')),
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('6xl')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Registo atualizado com sucesso!'),
                    \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                // Botão directo: Atribuir Disciplinas
                \Filament\Actions\BulkAction::make('atribuirDisciplinas')
                    ->label('Atribuir Disciplinas')
                    ->icon('heroicon-o-book-open')
                    ->color('primary')
                    ->modalHeading('Atribuição de Disciplinas aos Formadores')
                    ->modalDescription('Atribua instituições, cursos e disciplinas aos formadores selecionados.')
                    ->modalWidth('6xl')
                    ->form([
                        \Filament\Schemas\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('institution_ids')
                                ->label('Instituições')
                                ->options(\App\Models\Institution::orderBy('name')->pluck('name', 'id'))
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required(),
                            Forms\Components\Select::make('course_ids')
                                ->label('Cursos')
                                ->options(\App\Models\Course::orderBy('name')->pluck('name', 'id'))
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        \Filament\Schemas\Components\Grid::make(1)->schema([
                            Forms\Components\Select::make('subject_ids')
                                ->label('Disciplinas')
                                ->options(fn() => \App\Models\Subject::orderBy('name')->pluck('name', 'id'))
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        $created = 0;
                        $skipped = 0;

                        $institutionIds = $data['institution_ids'] ?? [];
                        $courseIds = $data['course_ids'] ?? [];
                        $subjectIds = $data['subject_ids'] ?? [];

                        foreach ($records as $trainer) {
                            foreach ($institutionIds as $institutionId) {
                                $trainer->institutions()->syncWithoutDetaching([$institutionId]);

                                foreach ($courseIds as $courseId) {
                                    foreach ($subjectIds as $subjectId) {
                                        $exists = \App\Models\TrainerSubjectAuthorization::where([
                                            'trainer_id' => $trainer->id,
                                            'institution_id' => $institutionId,
                                            'course_id' => $courseId,
                                            'subject_id' => $subjectId,
                                        ])->exists();

                                        if ($exists) {
                                            $skipped++;
                                            continue;
                                        }

                                        \App\Models\TrainerSubjectAuthorization::create([
                                            'trainer_id' => $trainer->id,
                                            'institution_id' => $institutionId,
                                            'course_id' => $courseId,
                                            'subject_id' => $subjectId,
                                            'authorized_by' => auth()->id(),
                                        ]);
                                        $created++;
                                    }
                                }
                            }
                        }

                        $msg = [];
                        if ($created > 0) $msg[] = "{$created} atribuições criadas";
                        if ($skipped > 0) $msg[] = "{$skipped} já existiam (ignoradas)";

                        \Filament\Notifications\Notification::make()
                            ->title('Atribuição Concluída!')
                            ->body(implode(', ', $msg) ?: 'Nenhuma alteração feita')
                            ->success()
                            ->duration(10000)
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Atribuir')->color('primary'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger')),
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
            'index' => Pages\ListTrainers::route('/'),
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
