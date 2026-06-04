<?php

namespace App\Filament\Pages;

use App\Models\AgentTransferHistory;
use App\Models\CandidateTransferHistory;
use App\Models\StudentTransferHistory;
use App\Models\Institution;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Livewire\Attributes\Url;

class TransferHistory extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-arrows-right-left';
    
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    
    protected static ?int $navigationSort = 7;
    
    protected static ?string $navigationLabel = 'Histórico de Transferências';
    
    protected static ?string $title = 'Histórico de Transferências';
    
    public function getView(): string
    {
        return 'filament.pages.transfer-history';
    }
    
    #[Url]
    public string $activeTab = 'candidates';
    
    public function getHeading(): string
    {
        return 'Histórico de Transferências';
    }
    
    public function getSubheading(): ?string
    {
        return 'Transferências de alistados e formandos';
    }
    
    public function table(Table $table): Table
    {
        return match ($this->activeTab) {
            'candidates' => $this->getCandidatesTable($table),
            'students' => $this->getStudentsTable($table),
            default => $this->getCandidatesTable($table),
        };
    }
    
    protected function getAgentsTable(Table $table): Table
    {
        return $table
            ->query(AgentTransferHistory::query())
            ->defaultSort('transferred_at', 'desc')
            ->columns([
                TextColumn::make('transferred_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('agent_name')
                    ->label('Agente')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('student_number')
                    ->label('Nº Ordem')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rank')
                    ->label('Patente')
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('fromInstitution.name')
                    ->label('De')
                    ->placeholder('N/A')
                    ->wrap()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-right'),
                TextColumn::make('toInstitution.name')
                    ->label('Para')
                    ->wrap()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
                TextColumn::make('transferredByUser.name')
                    ->label('Por')
                    ->placeholder('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('from_institution_id')
                    ->label('Instituição Anterior')
                    ->options(Institution::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('to_institution_id')
                    ->label('Instituição Destino')
                    ->options(Institution::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
            ])
            ->recordAction('view')
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->label('Visualizar')
                    ->modalHeading('Detalhes da Transferência de Agente')
                    ->infolist([
                        Section::make('Informações do Agente')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('agent_name')->label('Nome do Agente'),
                                    TextEntry::make('student_number')->label('Nº de Ordem'),
                                    TextEntry::make('rank')->label('Patente')->placeholder('N/A'),
                                ]),
                                Grid::make(3)->schema([
                                    TextEntry::make('provenance')->label('Proveniência')->placeholder('N/A'),
                                    TextEntry::make('phone')->label('Telefone')->placeholder('N/A'),
                                    TextEntry::make('status')->label('Estado')->badge()
                                        ->formatStateUsing(fn ($state) => match($state) {
                                            'pending' => 'Pendente',
                                            'em_formacao' => 'Em Formação',
                                            'active' => 'Activo',
                                            'inactive' => 'Inactivo',
                                            default => ucfirst(str_replace('_', ' ', $state ?? 'N/A')),
                                        }),
                                ]),
                            ])->columns(1),
                        Section::make('Detalhes da Transferência')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('fromInstitution.name')
                                        ->label('Instituição Anterior')
                                        ->icon('heroicon-o-building-office')
                                        ->placeholder('N/A')
                                        ->color('danger'),
                                    TextEntry::make('toInstitution.name')
                                        ->label('Instituição Actual')
                                        ->icon('heroicon-o-building-office-2')
                                        ->color('success'),
                                ]),
                                Grid::make(2)->schema([
                                    TextEntry::make('transferred_at')
                                        ->label('Data da Transferência')
                                        ->dateTime('d/m/Y H:i')
                                        ->icon('heroicon-o-calendar'),
                                    TextEntry::make('transferredByUser.name')
                                        ->label('Transferido por')
                                        ->icon('heroicon-o-user')
                                        ->placeholder('Sistema'),
                                ]),
                                TextEntry::make('notes')
                                    ->label('Observações')
                                    ->placeholder('Sem observações')
                                    ->columnSpanFull(),
                            ])->columns(1),
                    ])
                    ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action->label('Fechar')->color('danger')),
            ]);
    }
    
    protected function getCandidatesTable(Table $table): Table
    {
        return $table
            ->query(CandidateTransferHistory::query())
            ->defaultSort('transferred_at', 'desc')
            ->columns([
                TextColumn::make('transferred_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('candidate_name')
                    ->label('Alistado')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('bi_number')
                    ->label('Nº BI')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('student_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Agente' => 'primary',
                        'Oficial' => 'success',
                        'Sargento' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('fromInstitution.name')
                    ->label('De')
                    ->placeholder('N/A')
                    ->wrap()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-right'),
                TextColumn::make('toInstitution.name')
                    ->label('Para')
                    ->wrap()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
                TextColumn::make('transferredByUser.name')
                    ->label('Por')
                    ->placeholder('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('from_institution_id')
                    ->label('Instituição Anterior')
                    ->options(Institution::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('to_institution_id')
                    ->label('Instituição Destino')
                    ->options(Institution::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
            ])
            ->recordAction('view')
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->label('Visualizar')
                    ->modalHeading('Detalhes da Transferência de Alistado')
                    ->infolist([
                        Section::make('Informações do Alistado')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('candidate_name')->label('Nome do Alistado'),
                                    TextEntry::make('bi_number')->label('Nº BI')->placeholder('N/A'),
                                    TextEntry::make('student_type')->label('Tipo')->badge(),
                                ]),
                                Grid::make(3)->schema([
                                    TextEntry::make('phone')->label('Telefone')->placeholder('N/A'),
                                    TextEntry::make('province')->label('Província')->placeholder('N/A'),
                                    TextEntry::make('status')->label('Estado')->badge()
                                        ->formatStateUsing(fn ($state) => match($state) {
                                            'pending' => 'Pendente',
                                            'pendente' => 'Pendente',
                                            'aprovado' => 'Aprovado',
                                            'reprovado' => 'Reprovado',
                                            'active' => 'Activo',
                                            'inactive' => 'Inactivo',
                                            default => ucfirst(str_replace('_', ' ', $state ?? 'N/A')),
                                        }),
                                ]),
                            ])->columns(1),
                        Section::make('Detalhes da Transferência')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('fromInstitution.name')
                                        ->label('Instituição Anterior')
                                        ->icon('heroicon-o-building-office')
                                        ->placeholder('N/A')
                                        ->color('danger'),
                                    TextEntry::make('toInstitution.name')
                                        ->label('Instituição Actual')
                                        ->icon('heroicon-o-building-office-2')
                                        ->color('success'),
                                ]),
                                Grid::make(2)->schema([
                                    TextEntry::make('transferred_at')
                                        ->label('Data da Transferência')
                                        ->dateTime('d/m/Y H:i')
                                        ->icon('heroicon-o-calendar'),
                                    TextEntry::make('transferredByUser.name')
                                        ->label('Transferido por')
                                        ->icon('heroicon-o-user')
                                        ->placeholder('Sistema'),
                                ]),
                                TextEntry::make('notes')
                                    ->label('Observações')
                                    ->placeholder('Sem observações')
                                    ->columnSpanFull(),
                            ])->columns(1),
                    ])
                    ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action->label('Fechar')->color('danger')),
            ]);
    }
    
    protected function getStudentsTable(Table $table): Table
    {
        return $table
            ->query(StudentTransferHistory::query())
            ->defaultSort('transferred_at', 'desc')
            ->columns([
                TextColumn::make('transferred_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('student_name')
                    ->label('Formando')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('student_number')
                    ->label('Nº Aluno')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('student_type')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Recruta' => 'gray',
                        'Instruendo' => 'info',
                        'Formando Superior' => 'success',
                        'Em Formação' => 'warning',
                        default => 'primary',
                    })
                    ->sortable(),
                TextColumn::make('course')
                    ->label('Curso')
                    ->placeholder('N/A')
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('fromInstitution.name')
                    ->label('De')
                    ->placeholder('N/A')
                    ->wrap()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-right'),
                TextColumn::make('toInstitution.name')
                    ->label('Para')
                    ->wrap()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
                TextColumn::make('transferredByUser.name')
                    ->label('Por')
                    ->placeholder('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('student_type')
                    ->label('Estado')
                    ->options([
                        'Recruta' => 'Recruta',
                        'Instruendo' => 'Instruendo',
                        'Formando Superior' => 'Formando Superior',
                        'Em Formação' => 'Em Formação',
                    ]),
                SelectFilter::make('from_institution_id')
                    ->label('Instituição Anterior')
                    ->options(Institution::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('to_institution_id')
                    ->label('Instituição Destino')
                    ->options(Institution::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
            ])
            ->recordAction('view')
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->label('Visualizar')
                    ->modalHeading('Detalhes da Transferência de Formando')
                    ->infolist([
                        Section::make('Informações do Formando')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('student_name')->label('Nome do Formando'),
                                    TextEntry::make('student_number')->label('Nº Aluno'),
                                    TextEntry::make('bi_number')->label('Nº BI')->placeholder('N/A'),
                                ]),
                                Grid::make(4)->schema([
                                    TextEntry::make('student_type')->label('Estado')->badge()
                                        ->color(fn (?string $state): string => match ($state) {
                                            'Recruta' => 'gray',
                                            'Instruendo' => 'info',
                                            'Formando Superior' => 'success',
                                            'Em Formação' => 'warning',
                                            default => 'primary',
                                        }),
                                    TextEntry::make('rank')->label('Patente')->placeholder('N/A'),
                                    TextEntry::make('provenance')->label('Proveniência')->placeholder('N/A'),
                                    TextEntry::make('phone')->label('Telefone')->placeholder('N/A'),
                                ]),
                            ])->columns(1),
                        Section::make('Curso e Localização')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('course')->label('Curso')->placeholder('N/A'),
                                    TextEntry::make('student_class')->label('Turma')->placeholder('N/A'),
                                ]),
                                Grid::make(3)->schema([
                                    TextEntry::make('cia')->label('CIA')
                                        ->formatStateUsing(fn ($state) => $state ? "{$state}ª CIA" : '-'),
                                    TextEntry::make('platoon')->label('Pelotão')
                                        ->formatStateUsing(fn ($state) => $state ? "{$state}º PELOTÃO" : '-'),
                                    TextEntry::make('section')->label('Secção')
                                        ->formatStateUsing(fn ($state) => $state ? "{$state}ª SECÇÃO" : '-'),
                                ]),
                            ])->columns(1),
                        Section::make('Detalhes da Transferência')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('fromInstitution.name')
                                        ->label('Instituição Anterior')
                                        ->icon('heroicon-o-building-office')
                                        ->placeholder('N/A')
                                        ->color('danger'),
                                    TextEntry::make('toInstitution.name')
                                        ->label('Instituição Actual')
                                        ->icon('heroicon-o-building-office-2')
                                        ->color('success'),
                                ]),
                                Grid::make(2)->schema([
                                    TextEntry::make('transferred_at')
                                        ->label('Data da Transferência')
                                        ->dateTime('d/m/Y H:i')
                                        ->icon('heroicon-o-calendar'),
                                    TextEntry::make('transferredByUser.name')
                                        ->label('Transferido por')
                                        ->icon('heroicon-o-user')
                                        ->placeholder('Sistema'),
                                ]),
                                TextEntry::make('notes')
                                    ->label('Observações')
                                    ->placeholder('Sem observações')
                                    ->columnSpanFull(),
                            ])->columns(1),
                    ])
                    ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action->label('Fechar')->color('danger')),
            ]);
    }
    
    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['candidates', 'students'], true)) {
            $tab = 'candidates';
        }

        // Resetar ações marcadas para evitar conflitos de state path
        $this->mountedActions = [];
        $this->mountedActionsData = [];
        
        $this->activeTab = $tab;
        $this->resetTable();
    }
    
    public function getAgentsCount(): int
    {
        return AgentTransferHistory::count();
    }
    
    public function getCandidatesCount(): int
    {
        return CandidateTransferHistory::count();
    }
    
    public function getStudentsCount(): int
    {
        return StudentTransferHistory::count();
    }
}
