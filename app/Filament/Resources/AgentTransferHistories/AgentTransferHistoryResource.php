<?php

namespace App\Filament\Resources\AgentTransferHistories;

use App\Filament\Resources\AgentTransferHistories\Pages\ManageAgentTransferHistories;
use App\Models\AgentTransferHistory;
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

class AgentTransferHistoryResource extends Resource
{
    protected static ?string $model = AgentTransferHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão do Centro';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Histórico de Transferências';
    protected static ?string $modelLabel = 'Transferência';
    protected static ?string $pluralModelLabel = 'Histórico de Transferências';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'agent_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Cadete')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('agent_name')
                                ->label('Nome do Cadete'),
                            TextEntry::make('student_number')
                                ->label('Nº de Ordem'),
                            TextEntry::make('rank')
                                ->label('Patente')
                                ->placeholder('N/A'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('provenance')
                                ->label('Proveniência')
                                ->placeholder('N/A'),
                            TextEntry::make('phone')
                                ->label('Telefone')
                                ->placeholder('N/A'),
                            TextEntry::make('status')
                                ->label('Estado')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'em_formacao' => 'success',
                                    'frequenta' => 'info',
                                    'concluiu' => 'primary',
                                    'desistiu' => 'danger',
                                    default => 'gray',
                                }),
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
            ->recordTitleAttribute('agent_name')
            ->defaultSort('transferred_at', 'desc')
            ->columns([
                TextColumn::make('transferred_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('agent_name')
                    ->label('Cadete')
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
            'index' => ManageAgentTransferHistories::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
