<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipmentAssignmentResource\Pages;
use App\Filament\Resources\EquipmentAssignmentResource\RelationManagers;
use App\Models\EquipmentAssignment;
use App\Models\Student;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentAssignmentResource extends Resource
{
    // Mudamos o modelo base para Student para listar todos os formandos inscritos
    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-cube';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 6;
    protected static ?string $modelLabel = 'Atribuição de Meio';
    protected static ?string $pluralModelLabel = 'Atribuição de Meios';
    protected static ?string $slug = 'equipment-assignments';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Dados da Atribuição')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Formando')
                            ->options(function (?EquipmentAssignment $record) {
                                $assignedStudentIds = EquipmentAssignment::distinct()->pluck('student_id');

                                $query = Student::with(['candidate', 'classEnrollments'])
                                    ->whereHas('classEnrollments');

                                if (!$record) {
                                    $query->whereNotIn('id', $assignedStudentIds);
                                }

                                return $query->get()
                                    ->mapWithKeys(fn($s) => [
                                        $s->id => "{$s->student_number} - " . ($s->candidate?->full_name ?? 'N/A') . " ({$s->student_type})"
                                    ]);
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull()
                            ->helperText('Selecione o formando para atribuição de meios'),
                        Forms\Components\Select::make('equipment_name')
                            ->label('Equipamento/Meio')
                            ->required()
                            ->searchable()
                            ->options(self::getEquipmentOptions()),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantidade')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        Forms\Components\Select::make('condition')
                            ->label('Estado/Condição')
                            ->required()
                            ->options([
                                'Novo' => 'Novo',
                                'Bom Estado' => 'Bom Estado',
                                'Usado' => 'Usado',
                                'Razoável' => 'Razoável',
                                'Danificado' => 'Danificado',
                                'Necessita Reparação' => 'Necessita Reparação',
                            ])
                            ->default('Novo'),
                        Forms\Components\DateTimePicker::make('assigned_at')
                            ->label('Data de Atribuição')
                            ->required()
                            ->default(now()),
                        Forms\Components\DateTimePicker::make('returned_at')
                            ->label('Data de Devolução'),
                        Forms\Components\TextInput::make('assigned_by_name')
                            ->label('Atribuído por')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('Nome do responsável')
                            ->columnSpanFull(),
                    ])->columns(2)->columnSpanFull(),
            ]);
    }

    public static function getEquipmentOptions(): array
    {
        return [
            'Fardamento' => [
                'Farda Camuflada' => 'Farda Camuflada',
                'Farda de Cerimónia' => 'Farda de Cerimónia',
                'Farda de Instrução' => 'Farda de Instrução',
                'Calça Camuflada' => 'Calça Camuflada',
                'Camisa Camuflada' => 'Camisa Camuflada',
                'Boina' => 'Boina',
                'Gorro' => 'Gorro',
                'Cinto Tático' => 'Cinto Tático',
                'Botas Militares' => 'Botas Militares',
                'Camisola Interior' => 'Camisola Interior',
                'Calças Interiores' => 'Calças Interiores',
                'Meias' => 'Meias (par)',
            ],
            'Equipamento de Cama' => [
                'Colchão' => 'Colchão',
                'Travesseiro' => 'Travesseiro',
                'Lençol' => 'Lençol',
                'Cobertor' => 'Cobertor',
                'Fronha' => 'Fronha',
            ],
            'Equipamento de Higiene' => [
                'Kit de Higiene' => 'Kit de Higiene',
                'Toalha' => 'Toalha',
                'Balde' => 'Balde',
            ],
            'Equipamento Tático' => [
                'Mochila Militar' => 'Mochila Militar',
                'Cantil' => 'Cantil',
                'Capacete' => 'Capacete',
                'Colete Tático' => 'Colete Tático',
                'Bastão' => 'Bastão',
                'Algemas' => 'Algemas',
            ],
            'Material Didático' => [
                'Caderno de Apontamentos' => 'Caderno de Apontamentos',
                'Manual de Instrução' => 'Manual de Instrução',
                'Caneta' => 'Caneta',
            ],
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->modifyQueryUsing(function ($query) {
                // Mesma lógica da Gestão de Formandos
                // Tipos de alunos permitidos (Exclui: Alistado, Formando)
                $tiposPermitidos = [
                    'Oficial',
                    'Agente de 3ª Classe',
                    'Recruta',
                    '1ª Fase - Recruta',
                    'Instruendo',
                    '2ª Fase - Instruendo',
                    'Em Formação',
                ];

                return $query->with(['candidate', 'institution', 'courseMap', 'equipmentAssignments'])
                    ->where(function ($q) use ($tiposPermitidos) {
                        foreach ($tiposPermitidos as $tipo) {
                            $q->orWhere('student_type', 'like', "%{$tipo}%");
                        }
                    });
            })
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->size(40)
                    ->getStateUsing(fn ($record): ?string => $record->photo ?: $record->candidate?->photo)
                    ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->candidate?->full_name ?? 'NA') . '&background=0D4C8B&color=fff&size=100'),
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Nome')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Instituição')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cia')
                    ->label('CIA')
                    ->formatStateUsing(fn($state): string => static::formatCia($state))
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('platoon')
                    ->label('Pelotão')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('section')
                    ->label('Secção')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ViewColumn::make('equipamentos_lista')
                    ->label('Equipamentos Atribuídos')
                    ->view('filament.tables.columns.equipment-badges')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_equipamentos')
                    ->label('Pendentes')
                    ->getStateUsing(fn($record) => EquipmentAssignment::where('student_id', $record->id)->whereNull('returned_at')->count())
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'primary' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('estado_meios')
                    ->label('Estado')
                    ->getStateUsing(function ($record) {
                        $assignments = EquipmentAssignment::where('student_id', $record->id);
                        $total = $assignments->count();
                        if ($total === 0) return 'Sem Meios';
                        $pendentes = (clone $assignments)->whereNull('returned_at')->count();
                        return $pendentes > 0 ? 'Atribuído' : 'Devolvido';
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Atribuído' => 'warning',
                        'Devolvido' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn($state) => match ($state) {
                        'Atribuído' => 'heroicon-o-cube',
                        'Devolvido' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-x-circle',
                    })
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ultima_atribuicao')
                    ->label('Última Atribuição')
                    ->getStateUsing(function ($record) {
                        $ultima = EquipmentAssignment::where('student_id', $record->id)
                            ->orderBy('assigned_at', 'desc')
                            ->first();
                        return $ultima ? $ultima->assigned_at->format('d/m/Y') : '-';
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('student_identifier')
                    ->form([
                        Forms\Components\TextInput::make('identifier')
                            ->label('NIP/NURI')
                            ->placeholder('Pesquisar NIP/NURI'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $identifier = trim((string) ($data['identifier'] ?? ''));

                        return $query->when($identifier !== '', function (Builder $query) use ($identifier): Builder {
                            return $query->where(function (Builder $query) use ($identifier): void {
                                $query
                                    ->where('nuri', 'like', "%{$identifier}%")
                                    ->orWhere('student_number', 'like', "%{$identifier}%")
                                    ->orWhereHas('candidate', function (Builder $candidateQuery) use ($identifier): void {
                                        $candidateQuery
                                            ->where('nuri', 'like', "%{$identifier}%")
                                            ->orWhere('student_number', 'like', "%{$identifier}%")
                                            ->orWhere('id_number', 'like', "%{$identifier}%");
                                    });
                            });
                        });
                    }),
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Instituição')
                    ->relationship('institution', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('cia')
                    ->label('CIA')
                    ->options(function () {
                        return Student::whereNotNull('cia')
                            ->where('cia', '!=', '')
                            ->distinct()
                            ->pluck('cia', 'cia')
                            ->map(fn($cia): string => static::formatCia($cia))
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('platoon')
                    ->label('Pelotão')
                    ->options(function () {
                        return Student::whereNotNull('platoon')
                            ->distinct()
                            ->pluck('platoon', 'platoon')
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('section')
                    ->label('Secção')
                    ->options(function () {
                        return Student::whereNotNull('section')
                            ->distinct()
                            ->pluck('section', 'section')
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('equipment_status')
                    ->label('Estado de Equipamento')
                    ->options([
                        'pending' => 'Atribuido / Pendente',
                        'returned' => 'Devolvido',
                        'none' => 'Sem Meios',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'pending' => $query->whereHas('equipmentAssignments', fn (Builder $equipmentQuery): Builder => $equipmentQuery->whereNull('returned_at')),
                        'returned' => $query
                            ->whereHas('equipmentAssignments')
                            ->whereDoesntHave('equipmentAssignments', fn (Builder $equipmentQuery): Builder => $equipmentQuery->whereNull('returned_at')),
                        'none' => $query->whereDoesntHave('equipmentAssignments'),
                        default => $query,
                    }),
            ])
            ->filtersFormColumns(6)
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->persistFiltersInSession()
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('verEquipamentos')
                        ->label('Ver Detalhes')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn($record) => 'Detalhes de Atribuição - ' . ($record->candidate?->full_name ?? 'N/A'))
                        ->modalWidth('4xl')
                        ->infolist(function ($record) {
                            $equipamentos = EquipmentAssignment::where('student_id', $record->id)->get();
                            $devolvidos = $equipamentos->whereNotNull('returned_at')->count();
                            $pendentes = $equipamentos->whereNull('returned_at')->count();

                            return [
                                Section::make('Informações do Formando')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextEntry::make('candidate.full_name')
                                                ->label('Nome do Formando')
                                                ->icon('heroicon-o-user'),
                                            TextEntry::make('student_identifier')
                                                ->label(fn(Student $record): string => static::studentIdentifierLabel($record))
                                                ->getStateUsing(fn(Student $record): string => static::studentIdentifierValue($record))
                                                ->icon('heroicon-o-identification'),
                                            TextEntry::make('student_type')
                                                ->label('Estado')
                                                ->badge()
                                                ->color('info'),
                                        ]),
                                    ]),
                                Section::make('Resumo de Equipamentos')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextEntry::make('total')
                                                ->label('Total Atribuído')
                                                ->getStateUsing(fn() => $equipamentos->count())
                                                ->badge()
                                                ->color('primary')
                                                ->icon('heroicon-o-cube'),
                                            TextEntry::make('devolvidos')
                                                ->label('Devolvidos')
                                                ->getStateUsing(fn() => $devolvidos)
                                                ->badge()
                                                ->color('success')
                                                ->icon('heroicon-o-check-circle'),
                                            TextEntry::make('pendentes')
                                                ->label('Pendentes')
                                                ->getStateUsing(fn() => $pendentes)
                                                ->badge()
                                                ->color($pendentes > 0 ? 'warning' : 'gray')
                                                ->icon('heroicon-o-clock'),
                                        ]),
                                    ]),
                                Section::make('Lista de Equipamentos')
                                    ->schema([
                                        \Filament\Infolists\Components\ViewEntry::make('equipamentos_lista')
                                            ->view('filament.pages.equipment-list')
                                            ->viewData(['equipamentos' => $equipamentos])
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull()
                                    ->visible(fn() => $equipamentos->count() > 0),
                            ];
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Fechar')->icon('heroicon-o-x-mark')->color('danger')),
                    \Filament\Actions\Action::make('devolucao')
                        ->label('Devolução')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Registar Devolução de Equipamentos')
                        ->modalDescription(fn($record) => 'Tem certeza que deseja registar a devolução de todos os equipamentos de ' . ($record->candidate?->full_name ?? 'N/A') . '?')
                        ->modalSubmitActionLabel('Confirmar Devolução')
                        ->form([
                            Forms\Components\DateTimePicker::make('returned_at')
                                ->label('Data de Devolução')
                                ->required()
                                ->default(now()),
                        ])
                        ->action(function ($record, array $data): void {
                            $count = EquipmentAssignment::where('student_id', $record->id)
                                ->whereNull('returned_at')
                                ->update([
                                    'returned_at' => $data['returned_at'],
                                ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Devolução Registada!')
                                ->body("$count equipamento(s) devolvido(s) com sucesso.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => EquipmentAssignment::where('student_id', $record->id)->whereNull('returned_at')->exists())
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->label('Cancelar')->color('danger')),
                    \Filament\Actions\Action::make('atribuirMeios')
                        ->label('Atribuir Meios')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->modalHeading(fn($record) => 'Atribuir Equipamentos - ' . ($record->candidate?->full_name ?? 'N/A'))
                        ->modalWidth('4xl')
                        ->form(function ($record) {
                            return [
                                Forms\Components\Placeholder::make('formando_info')
                                    ->label('Formando')
                                    ->content($record->candidate?->full_name . ' (Nº ' . $record->student_number . ')'),
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('assigned_at')
                                            ->label('Data de Atribuição')
                                            ->default(now())
                                            ->required(),
                                        Forms\Components\TextInput::make('assigned_by_name')
                                            ->label('Atribuído Por')
                                            ->default(auth()->user()?->name)
                                            ->required(),
                                    ]),
                                Forms\Components\Repeater::make('equipamentos')
                                    ->label('Equipamentos')
                                    ->schema([
                                        Forms\Components\Select::make('equipment_name')
                                            ->label('Equipamento')
                                            ->options(self::getEquipmentOptions())
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantidade')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->minValue(1),
                                        Forms\Components\Select::make('condition')
                                            ->label('Condição')
                                            ->options([
                                                'Novo' => 'Novo',
                                                'Bom Estado' => 'Bom Estado',
                                                'Usado' => 'Usado',
                                                'Razoável' => 'Razoável',
                                                'Danificado' => 'Danificado',
                                            ])
                                            ->default('Novo')
                                            ->required(),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->addActionLabel('Adicionar Equipamento')
                                    ->reorderable(false)
                                    ->defaultItems(1)
                                    ->minItems(1),
                            ];
                        })
                        ->action(function ($record, array $data): void {
                            $count = 0;
                            foreach ($data['equipamentos'] as $equip) {
                                EquipmentAssignment::create([
                                    'student_id' => $record->id,
                                    'institution_id' => $record->institution_id,
                                    'equipment_name' => $equip['equipment_name'],
                                    'quantity' => $equip['quantity'],
                                    'condition' => $equip['condition'],
                                    'assigned_at' => $data['assigned_at'],
                                    'assigned_by' => auth()->id(),
                                    'assigned_by_name' => $data['assigned_by_name'],
                                ]);
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Atribuição Concluída!')
                                ->body("$count equipamento(s) atribuído(s) com sucesso.")
                                ->success()
                                ->send();
                        })
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Atribuir')->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger')),
                ])->icon('heroicon-s-cog-6-tooth')->color('primary')->size('lg')->tooltip('Acções')->iconButton(),
            ])
            ->bulkActions([
                // Atribuição de Meios em Massa
                \Filament\Actions\BulkAction::make('atribuirMeiosEmMassa')
                    ->label('Atribuir Meios em Massa')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->deselectRecordsAfterCompletion()
                    ->modalHeading('Atribuir Equipamentos em Massa')
                    ->modalDescription('Os equipamentos selecionados serão atribuídos a todos os formandos seleccionados.')
                    ->modalWidth('4xl')
                    ->form([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('assigned_at')
                                    ->label('Data de Atribuição')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\TextInput::make('assigned_by_name')
                                    ->label('Atribuído Por')
                                    ->default(auth()->user()?->name)
                                    ->required(),
                            ]),
                        Forms\Components\Repeater::make('equipamentos')
                            ->label('Equipamentos a Atribuir')
                            ->schema([
                                Forms\Components\Select::make('equipment_name')
                                    ->label('Equipamento')
                                    ->options(self::getEquipmentOptions())
                                    ->searchable()
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1),
                                Forms\Components\Select::make('condition')
                                    ->label('Condição')
                                    ->options([
                                        'Novo' => 'Novo',
                                        'Bom Estado' => 'Bom Estado',
                                        'Usado' => 'Usado',
                                        'Razoável' => 'Razoável',
                                        'Danificado' => 'Danificado',
                                    ])
                                    ->default('Novo')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->addActionLabel('Adicionar Equipamento')
                            ->reorderable(false)
                            ->defaultItems(1)
                            ->minItems(1),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        $totalStudents = 0;
                        $totalEquipments = 0;

                        foreach ($records as $student) {
                            foreach ($data['equipamentos'] as $equip) {
                                EquipmentAssignment::create([
                                    'student_id' => $student->id,
                                    'institution_id' => $student->institution_id,
                                    'equipment_name' => $equip['equipment_name'],
                                    'quantity' => $equip['quantity'],
                                    'condition' => $equip['condition'],
                                    'assigned_at' => $data['assigned_at'],
                                    'assigned_by' => auth()->id(),
                                    'assigned_by_name' => $data['assigned_by_name'],
                                ]);
                                $totalEquipments++;
                            }
                            $totalStudents++;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Atribuição em Massa Concluída!')
                            ->body("{$totalEquipments} equipamento(s) atribuído(s) a {$totalStudents} formando(s).")
                            ->success()
                            ->send();
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Atribuir a Todos')->color('primary'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger')),

                // Devolução de Meios em Massa
                \Filament\Actions\BulkAction::make('devolucaoEmMassa')
                    ->label('Devolução em Massa')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Registar Devolução em Massa')
                    ->modalDescription('Todos os equipamentos pendentes de devolução dos formandos seleccionados serão marcados como devolvidos.')
                    ->modalIcon('heroicon-o-arrow-uturn-left')
                    ->modalIconColor('danger')
                    ->form([
                        Forms\Components\DateTimePicker::make('returned_at')
                            ->label('Data de Devolução')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        $totalStudents = 0;
                        $totalEquipments = 0;

                        foreach ($records as $student) {
                            $count = EquipmentAssignment::where('student_id', $student->id)
                                ->whereNull('returned_at')
                                ->update([
                                    'returned_at' => $data['returned_at'],
                                ]);

                            if ($count > 0) {
                                $totalStudents++;
                                $totalEquipments += $count;
                            }
                        }

                        if ($totalEquipments > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Devolução em Massa Concluída!')
                                ->body("{$totalEquipments} equipamento(s) devolvido(s) de {$totalStudents} formando(s).")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Nenhuma Devolução Registada')
                                ->body('Os formandos seleccionados não tinham equipamentos pendentes de devolução.')
                                ->warning()
                                ->send();
                        }
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Confirmar Devolução')->color('primary'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger')),
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
            'index' => Pages\ListEquipmentAssignments::route('/'),
        ];
    }

    private static function formatCia(mixed $state): string
    {
        $value = trim((string) $state);

        if ($value === '') {
            return '-';
        }

        return str_contains(strtoupper($value), 'CIA')
            ? $value
            : $value.'ª CIA';
    }

    private static function studentIdentifierLabel(Student $student): string
    {
        $studentType = mb_strtolower(trim((string) $student->student_type));

        return str_contains($studentType, 'em forma')
            || str_contains($studentType, 'formando')
            || str_contains($studentType, 'oficial')
            ? 'NIP'
            : 'NURI';
    }

    private static function studentIdentifierValue(Student $student): string
    {
        $identifier = trim((string) ($student->nuri ?: $student->candidate?->nuri ?: ''));

        return $identifier !== '' ? $identifier : '-';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:EquipmentAssignment') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
