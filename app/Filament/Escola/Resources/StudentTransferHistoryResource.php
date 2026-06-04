<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\StudentTransferHistoryResource\Pages\ManageStudentTransferHistories;
use App\Models\StudentTransferHistory;
use App\Models\Institution;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class StudentTransferHistoryResource extends Resource
{
    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $model = StudentTransferHistory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Histórico de Transferências';
    protected static ?string $modelLabel = 'Transferência';
    protected static ?string $pluralModelLabel = 'Histórico de Transferências';
    protected static ?string $slug = 'student-transfer-history';
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where(function ($query) use ($tenant) {
                $query->where('from_institution_id', $tenant?->id)
                    ->orWhere('to_institution_id', $tenant?->id);
            });
    }

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
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    ViewAction::make(),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
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

    public static function canAccess(): bool
    {
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
