<?php

namespace App\Filament\Pages;

use App\Models\AgentTransferHistory;
use App\Models\CandidateTransferHistory;
use App\Models\StudentTransferHistory;
use App\Models\Institution;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\StudentType;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
        return 'Listagem única com todos os históricos de transferências';
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getUnifiedTransferQuery())
            ->defaultSort('transferred_at', 'desc')
            ->columns([
                ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->person_name ?: 'NA').'&background=0D4C8B&color=fff&size=100'),
                TextColumn::make('person_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('registration_number')
                    ->label('NIP/NURI')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status_label')
                    ->label('Estado/Tipo')
                    ->badge()
                    ->color(fn (mixed $state): string => static::statusLabelBadgeColor($state))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('fromInstitution.name')
                    ->label('De')
                    ->placeholder('N/A')
                    ->wrap()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-right'),
                TextColumn::make('toInstitution.name')
                    ->label('Para')
                    ->placeholder('N/A')
                    ->wrap()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
                TextColumn::make('transferred_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('nuri_filter')
                    ->form([
                        Forms\Components\TextInput::make('nuri')
                            ->label('Procurar por NURI / NIP'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $nuri = trim((string) ($data['nuri'] ?? ''));

                        return $query->when(
                            $nuri !== '',
                            fn (Builder $query): Builder => $query->where('registration_number', 'like', "%{$nuri}%"),
                        );
                    }),
                SelectFilter::make('transfer_type')
                    ->label('Origem')
                    ->options([
                        'Alistado' => 'Alistado',
                        'Formando' => 'Formando',
                        'Agente' => 'Agente',
                    ])
                    ->searchable(),
                SelectFilter::make('student_type')
                    ->label('Estado do Aluno')
                    ->options(fn (): array => static::studentTypeFilterOptions())
                    ->searchable(),
                SelectFilter::make('institution_id')
                    ->label('Instituição')
                    ->options(fn (): array => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('from_institution_id')
                    ->label('Instituição Anterior')
                    ->options(fn (): array => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('cia')
                    ->label('CIA')
                    ->options(fn (): array => static::historyDistinctOptions('student_transfer_histories', 'cia'))
                    ->searchable(),
                SelectFilter::make('platoon')
                    ->label('Pelotão')
                    ->options(fn (): array => static::historyDistinctOptions('student_transfer_histories', 'platoon'))
                    ->searchable(),
                SelectFilter::make('section')
                    ->label('Secção')
                    ->options(fn (): array => static::historyDistinctOptions('student_transfer_histories', 'section'))
                    ->searchable(),
                SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'Masculino' => 'Masculino',
                        'Feminino' => 'Feminino',
                    ]),
                SelectFilter::make('province_id')
                    ->label('Província')
                    ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('municipality_id')
                    ->label('Município')
                    ->options(fn (): array => Municipality::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->recordAction('view')
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                    ->label('Visualizar')
                    ->modalHeading(fn (StudentTransferHistory $record): string => 'Detalhes da Transferência - '.($record->person_name ?: 'Registo'))
                    ->modalWidth('7xl')
                    ->schema(static::transferHistoryViewSchema())
                    ->fillForm(fn (StudentTransferHistory $record): array => static::transferHistoryViewData($record))
                    ->modalCancelAction(fn (\Filament\Actions\Action $action) => $action
                        ->label('Fechar')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ]);
    }

    protected static function transferHistoryViewSchema(): array
    {
        return [
            Section::make('Informações do Registo')
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('transfer_type')
                            ->label('Origem'),
                        Forms\Components\TextInput::make('person_name')
                            ->label('Nome'),
                        Forms\Components\TextInput::make('registration_number')
                            ->label('NIP/NURI'),
                    ]),
                    Grid::make(4)->schema([
                        Forms\Components\TextInput::make('bi_number')
                            ->label('Nº BI'),
                        Forms\Components\TextInput::make('status_label')
                            ->label('Estado/Tipo'),
                        Forms\Components\TextInput::make('rank_label')
                            ->label('Patente'),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone'),
                    ]),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Section::make('Curso e Localização')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('course')
                            ->label('Curso'),
                        Forms\Components\TextInput::make('student_class')
                            ->label('Turma'),
                    ]),
                    Grid::make(4)->schema([
                        Forms\Components\TextInput::make('provenance')
                            ->label('Proveniência'),
                        Forms\Components\TextInput::make('cia')
                            ->label('CIA'),
                        Forms\Components\TextInput::make('platoon')
                            ->label('Pelotão'),
                        Forms\Components\TextInput::make('section')
                            ->label('Secção'),
                    ]),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Section::make('Detalhes da Transferência')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('from_institution_name')
                            ->label('Instituição Anterior'),
                        Forms\Components\TextInput::make('to_institution_name')
                            ->label('Instituição Actual'),
                    ]),
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('transferred_at_label')
                            ->label('Data da Transferência'),
                        Forms\Components\TextInput::make('transferred_by_name')
                            ->label('Transferido por'),
                    ]),
                    Forms\Components\Textarea::make('notes')
                        ->label('Observações')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ];
    }

    protected static function transferHistoryViewData(StudentTransferHistory $record): array
    {
        return [
            'transfer_type' => static::displayValue($record->transfer_type),
            'person_name' => static::displayValue($record->person_name),
            'registration_number' => static::displayValue($record->registration_number),
            'bi_number' => static::displayValue($record->bi_number),
            'status_label' => static::displayValue($record->status_label),
            'rank_label' => static::displayValue($record->rank_label),
            'phone' => static::displayValue($record->phone),
            'course' => static::displayValue($record->course),
            'student_class' => static::displayValue($record->student_class),
            'provenance' => static::displayValue($record->provenance),
            'cia' => static::displayValue(static::formatCiaValue($record->cia)),
            'platoon' => static::displayValue(static::formatPlatoonValue($record->platoon)),
            'section' => static::displayValue(static::formatSectionValue($record->section)),
            'from_institution_name' => static::displayValue($record->fromInstitution?->name, 'N/A'),
            'to_institution_name' => static::displayValue($record->toInstitution?->name, 'N/A'),
            'transferred_at_label' => static::formatDateTimeValue($record->transferred_at),
            'transferred_by_name' => static::displayValue($record->transferredByUser?->name, 'Sistema'),
            'notes' => static::displayValue($record->notes, 'Sem observações'),
        ];
    }

    protected static function displayValue(mixed $value, string $fallback = '-'): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? $fallback : $value;
    }

    protected static function statusLabelBadgeColor(mixed $state): string
    {
        $state = trim((string) $state);

        if ($state === '') {
            return 'gray';
        }

        $colors = static::studentTypeColorMap();
        $normalizedState = static::normalizeStatusKey($state);

        if ($normalizedState === '') {
            return 'gray';
        }

        if (isset($colors[$state])) {
            return $colors[$state];
        }

        if (isset($colors[$normalizedState])) {
            return $colors[$normalizedState];
        }

        foreach ($colors as $type => $color) {
            if (! is_string($type) || $type === '') {
                continue;
            }

            if (str_contains($normalizedState, $type) || str_contains($type, $normalizedState)) {
                return $color;
            }
        }

        return match ($normalizedState) {
            'alistado' => 'gray',
            'formando', 'oficial', 'agente', 'agente de 3a classe', 'formado agente', 'formando concluido', 'concluiu', 'active', 'activo', 'ativo', 'aprovado' => 'success',
            'recruta', '1a fase recruta', 'pendente', 'pending' => 'warning',
            'instruendo', '2a fase instruendo', 'frequenta' => 'info',
            'em formacao', 'em formacao superior', 'em_formacao' => 'primary',
            'desistiu', 'inativo', 'inactive', 'reprovado' => 'danger',
            default => 'gray',
        };
    }

    protected static function studentTypeColorMap(): array
    {
        static $colors = null;

        if ($colors !== null) {
            return $colors;
        }

        $colors = [];

        foreach (StudentType::query()->where('is_active', true)->pluck('color', 'name') as $name => $color) {
            $name = trim((string) $name);
            $color = trim((string) $color) ?: 'gray';

            if ($name === '') {
                continue;
            }

            $colors[$name] = $color;
            $colors[static::normalizeStatusKey($name)] = $color;
        }

        return $colors;
    }

    protected static function normalizeStatusKey(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = strtr($value, [
            'Á' => 'A',
            'À' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'Ú' => 'U',
            'Ù' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'Ç' => 'C',
            'ç' => 'c',
            'ª' => 'a',
            'º' => 'o',
        ]);

        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            $value = $ascii === false ? $value : $ascii;
        }
        $value = strtolower($value);
        $value = str_replace(['_', '-', 'ª', 'º'], [' ', ' ', 'a', 'o'], $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    protected static function formatDateTimeValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y H:i');
        }

        $value = trim((string) $value);

        if ($value === '') {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $value;
        }
    }

    protected static function formatCiaValue(mixed $state): string
    {
        $value = trim((string) $state);

        if ($value === '') {
            return '-';
        }

        return str_contains(strtoupper($value), 'CIA')
            ? $value
            : $value.'ª CIA';
    }

    protected static function formatPlatoonValue(mixed $state): string
    {
        $value = trim((string) $state);

        if ($value === '') {
            return '-';
        }

        return str_contains(strtoupper($value), 'PELOT')
            ? $value
            : $value.'º PELOTÃO';
    }

    protected static function formatSectionValue(mixed $state): string
    {
        $value = trim((string) $state);

        if ($value === '') {
            return '-';
        }

        return str_contains(strtoupper($value), 'SEC')
            ? $value
            : $value.'ª SECÇÃO';
    }

    protected static function studentTypeFilterOptions(): array
    {
        $options = [];

        foreach (StudentType::query()->where('is_active', true)->orderBy('order')->pluck('name') as $type) {
            $type = trim((string) $type);

            if ($type !== '') {
                $options[$type] = $type;
            }
        }

        foreach (['Alistado', 'Formando', 'Agente'] as $type) {
            $options[$type] = $type;
        }

        foreach (['student_type', 'status'] as $column) {
            foreach (CandidateTransferHistory::query()->whereNotNull($column)->distinct()->pluck($column) as $type) {
                $type = trim((string) $type);

                if ($type !== '') {
                    $options[$type] = $type;
                }
            }
        }

        foreach (StudentTransferHistory::query()->whereNotNull('student_type')->distinct()->pluck('student_type') as $type) {
            $type = trim((string) $type);

            if ($type !== '') {
                $options[$type] = $type;
            }
        }

        foreach (AgentTransferHistory::query()->whereNotNull('status')->distinct()->pluck('status') as $type) {
            $type = trim((string) $type);

            if ($type !== '') {
                $options[$type] = $type;
            }
        }

        ksort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    protected static function historyDistinctOptions(string $table, string $column): array
    {
        return DB::table($table)
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }

    protected function getUnifiedTransferQuery(): Builder
    {
        $candidateStudentIdentifiers = DB::table('students')
            ->selectRaw('candidate_id, max(nuri) as nuri')
            ->whereNotNull('candidate_id')
            ->groupBy('candidate_id');

        $candidates = DB::table('candidate_transfer_histories')
            ->leftJoin('candidates as transfer_candidates', 'candidate_transfer_histories.candidate_id', '=', 'transfer_candidates.id')
            ->leftJoinSub($candidateStudentIdentifiers, 'transfer_candidate_students', function ($join): void {
                $join->on('candidate_transfer_histories.candidate_id', '=', 'transfer_candidate_students.candidate_id');
            })
            ->selectRaw("
                candidate_transfer_histories.id + 100000000 as id,
                'Alistado' as transfer_type,
                'candidate' as source_table,
                candidate_transfer_histories.id as source_id,
                candidate_transfer_histories.candidate_id as entity_id,
                candidate_transfer_histories.candidate_name as person_name,
                transfer_candidates.photo as photo,
                transfer_candidate_students.nuri as registration_number,
                candidate_transfer_histories.bi_number as bi_number,
                coalesce(candidate_transfer_histories.student_type, candidate_transfer_histories.status) as status_label,
                coalesce(candidate_transfer_histories.student_type, candidate_transfer_histories.status) as student_type,
                candidate_transfer_histories.to_institution_id as institution_id,
                transfer_candidates.gender as gender,
                transfer_candidates.province_id as province_id,
                transfer_candidates.municipality_id as municipality_id,
                null as rank_label,
                candidate_transfer_histories.province as provenance,
                candidate_transfer_histories.phone as phone,
                null as course,
                null as student_class,
                null as cia,
                null as platoon,
                null as section,
                candidate_transfer_histories.notes as notes,
                candidate_transfer_histories.from_institution_id as from_institution_id,
                candidate_transfer_histories.to_institution_id as to_institution_id,
                candidate_transfer_histories.transferred_by as transferred_by,
                candidate_transfer_histories.transferred_at as transferred_at,
                candidate_transfer_histories.created_at as created_at,
                candidate_transfer_histories.updated_at as updated_at
            ");

        $students = DB::table('student_transfer_histories')
            ->leftJoin('students as transfer_students', 'student_transfer_histories.student_id', '=', 'transfer_students.id')
            ->leftJoin('candidates as transfer_student_candidates', 'transfer_students.candidate_id', '=', 'transfer_student_candidates.id')
            ->selectRaw("
                student_transfer_histories.id + 200000000 as id,
                'Formando' as transfer_type,
                'student' as source_table,
                student_transfer_histories.id as source_id,
                student_transfer_histories.student_id as entity_id,
                student_transfer_histories.student_name as person_name,
                coalesce(transfer_students.photo, transfer_student_candidates.photo) as photo,
                coalesce(transfer_students.nuri, student_transfer_histories.student_number) as registration_number,
                student_transfer_histories.bi_number as bi_number,
                student_transfer_histories.student_type as status_label,
                student_transfer_histories.student_type as student_type,
                student_transfer_histories.to_institution_id as institution_id,
                transfer_student_candidates.gender as gender,
                transfer_student_candidates.province_id as province_id,
                transfer_student_candidates.municipality_id as municipality_id,
                student_transfer_histories.rank as rank_label,
                student_transfer_histories.provenance as provenance,
                student_transfer_histories.phone as phone,
                student_transfer_histories.course as course,
                student_transfer_histories.student_class as student_class,
                student_transfer_histories.cia as cia,
                student_transfer_histories.platoon as platoon,
                student_transfer_histories.section as section,
                student_transfer_histories.notes as notes,
                student_transfer_histories.from_institution_id as from_institution_id,
                student_transfer_histories.to_institution_id as to_institution_id,
                student_transfer_histories.transferred_by as transferred_by,
                student_transfer_histories.transferred_at as transferred_at,
                student_transfer_histories.created_at as created_at,
                student_transfer_histories.updated_at as updated_at
            ");

        $agents = DB::table('agent_transfer_histories')
            ->leftJoin('students as transfer_agents', 'agent_transfer_histories.student_id', '=', 'transfer_agents.id')
            ->leftJoin('candidates as transfer_agent_candidates', 'transfer_agents.candidate_id', '=', 'transfer_agent_candidates.id')
            ->selectRaw("
                agent_transfer_histories.id + 300000000 as id,
                'Agente' as transfer_type,
                'agent' as source_table,
                agent_transfer_histories.id as source_id,
                agent_transfer_histories.student_id as entity_id,
                agent_transfer_histories.agent_name as person_name,
                coalesce(transfer_agents.photo, transfer_agent_candidates.photo) as photo,
                coalesce(transfer_agents.nuri, agent_transfer_histories.student_number) as registration_number,
                null as bi_number,
                agent_transfer_histories.status as status_label,
                agent_transfer_histories.status as student_type,
                agent_transfer_histories.to_institution_id as institution_id,
                transfer_agent_candidates.gender as gender,
                transfer_agent_candidates.province_id as province_id,
                transfer_agent_candidates.municipality_id as municipality_id,
                agent_transfer_histories.rank as rank_label,
                agent_transfer_histories.provenance as provenance,
                agent_transfer_histories.phone as phone,
                null as course,
                null as student_class,
                null as cia,
                null as platoon,
                null as section,
                agent_transfer_histories.notes as notes,
                agent_transfer_histories.from_institution_id as from_institution_id,
                agent_transfer_histories.to_institution_id as to_institution_id,
                agent_transfer_histories.transferred_by as transferred_by,
                agent_transfer_histories.transferred_at as transferred_at,
                agent_transfer_histories.created_at as created_at,
                agent_transfer_histories.updated_at as updated_at
            ");

        $union = $candidates->unionAll($students)->unionAll($agents);

        $model = (new StudentTransferHistory())->setTable('transfer_history_entries');

        return $model->newQuery()
            ->fromSub($union, 'transfer_history_entries')
            ->select('transfer_history_entries.*')
            ->with(['fromInstitution', 'toInstitution', 'transferredByUser']);
    }

    protected function formatCia(mixed $state): string
    {
        $value = trim((string) $state);

        if ($value === '') {
            return '-';
        }

        return str_contains(strtoupper($value), 'CIA')
            ? $value
            : $value.'ª CIA';
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
