<?php

namespace App\Filament\Resources\StudentTransferHistories;

use App\Filament\Resources\StudentTransferHistories\Pages\ManageStudentTransferHistories;
use App\Models\StudentTransferHistory;
use App\Models\Institution;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class StudentTransferHistoryResource extends Resource
{
    protected static ?string $model = StudentTransferHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Histórico Formandos';
    protected static ?string $modelLabel = 'Transferência Formando';
    protected static ?string $pluralModelLabel = 'Histórico de Transferências de Formandos';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'student_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Formando')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('student_name')
                                ->label('Nome do Formando'),
                            TextEntry::make('student_number')
                                ->label('Nº Aluno'),
                            TextEntry::make('bi_number')
                                ->label('Nº BI')
                                ->placeholder('N/A'),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('student_type')
                                ->label('Estado')
                                ->badge()
                                ->color(fn(?string $state): string => match ($state) {
                                    'Recruta' => 'gray',
                                    'Instruendo' => 'info',
                                    'Formando Superior' => 'success',
                                    'Em Formação' => 'warning',
                                    default => 'primary',
                                }),
                            TextEntry::make('rank')
                                ->label('Patente')
                                ->placeholder('N/A'),
                            TextEntry::make('provenance')
                                ->label('Proveniência')
                                ->placeholder('N/A'),
                            TextEntry::make('phone')
                                ->label('Telefone')
                                ->placeholder('N/A'),
                        ]),
                    ])
                    ->columns(1),

                Section::make('Curso e Localização')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('course')
                                ->label('Curso')
                                ->placeholder('N/A'),
                            TextEntry::make('student_class')
                                ->label('Turma')
                                ->placeholder('N/A'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('cia')
                                ->label('CIA')
                                ->formatStateUsing(fn($state) => $state ? "{$state}ª CIA" : '-'),
                            TextEntry::make('platoon')
                                ->label('Pelotão')
                                ->formatStateUsing(fn($state) => $state ? "{$state}º PELOTÃO" : '-'),
                            TextEntry::make('section')
                                ->label('Secção')
                                ->formatStateUsing(fn($state) => $state ? "{$state}ª SECÇÃO" : '-'),
                        ]),
                    ])
                    ->columns(1),

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
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student_name')
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
                    ->sortable(),
                TextColumn::make('student_type')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
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
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    ViewAction::make(),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentTransferHistories::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
