<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\StudentLeaveResource\Pages;
use App\Filament\Resources\StudentLeaveResource\RelationManagers;
use App\Models\StudentLeave;
use App\Models\Student;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentLeaveResource extends Resource
{
    protected static bool $shouldSkipAuthorization = true;

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
                Tables\Columns\TextColumn::make('student.candidate.full_name')
                    ->label('Formando')
                    ->sortable()
                    ->searchable()
                    ->wrap()
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
                \Filament\Actions\CreateAction::make()
                    ->label('Adicionar Formando')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Registar Nova Ocorrência')
                    ->modalWidth('4xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Ocorrência registada com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('novaOcorrencia')
                        ->label('Nova Ocorrência')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
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
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
