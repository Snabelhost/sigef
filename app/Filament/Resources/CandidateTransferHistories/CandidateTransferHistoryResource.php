<?php

namespace App\Filament\Resources\CandidateTransferHistories;

use App\Filament\Resources\CandidateTransferHistories\Pages\ManageCandidateTransferHistories;
use App\Models\CandidateTransferHistory;
use App\Models\Institution;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class CandidateTransferHistoryResource extends Resource
{
    protected static ?string $model = CandidateTransferHistory::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão do Centro';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Histórico Alistados';
    protected static ?string $modelLabel = 'Transferência Alistado';
    protected static ?string $pluralModelLabel = 'Histórico de Transferências de Alistados';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'candidate_name';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:CandidateTransferHistory') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(Filament::getCurrentPanel()?->getId() === 'escola' && Filament::getTenant()?->id, function (Builder $query): Builder {
                return $query->where(function (Builder $query): void {
                    $query
                        ->where('from_institution_id', Filament::getTenant()->id)
                        ->orWhere('to_institution_id', Filament::getTenant()->id);
                });
            });
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Alistado')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('candidate_name')
                                ->label('Nome do Alistado'),
                            TextEntry::make('bi_number')
                                ->label('Nº BI')
                                ->placeholder('N/A'),
                            TextEntry::make('student_type')
                                ->label('Tipo')
                                ->badge()
                                ->color(fn(?string $state): string => match ($state) {
                                    'Agente' => 'primary',
                                    'Oficial' => 'success',
                                    'Sargento' => 'warning',
                                    default => 'gray',
                                }),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('phone')
                                ->label('Telefone')
                                ->placeholder('N/A'),
                            TextEntry::make('province')
                                ->label('Província')
                                ->placeholder('N/A'),
                            TextEntry::make('status')
                                ->label('Estado')
                                ->badge()
                                ->color(fn(?string $state): string => match ($state) {
                                    'ativo' => 'success',
                                    'pendente' => 'warning',
                                    'inativo' => 'danger',
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
            ->recordTitleAttribute('candidate_name')
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
                    ->toggleable(),
                TextColumn::make('student_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
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
            'index' => ManageCandidateTransferHistories::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
