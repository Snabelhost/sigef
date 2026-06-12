<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\CourseMapResource\Pages;
use App\Models\CourseMap;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class CourseMapResource extends Resource
{
    protected static bool $shouldSkipAuthorization = false;

    protected static ?string $model = CourseMap::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-puzzle-piece';
    protected static ?string $navigationLabel = 'Mapas de Curso';
    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';
    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'Mapa de Curso';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Mapas de Curso';
    }

    // Filtrar apenas mapas de cursos da instituição actual (tenant)
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['course', 'institution']);

        if ($tenant = Filament::getTenant()) {
            $query->where('institution_id', $tenant->id);
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->label('Curso')
                    ->relationship('course', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Hidden::make('institution_id')
                    ->default(fn() => Filament::getTenant()?->id),
                Forms\Components\Select::make('organ')
                    ->label('Órgão')
                    ->options([
                        'Comando Geral' => 'Comando Geral',
                        'Direcção de Pessoal e Quadros' => 'Direcção de Pessoal e Quadros',
                        'Direcção de Ordem Pública' => 'Direcção de Ordem Pública',
                        'Direcção de Investigação Criminal' => 'Direcção de Investigação Criminal',
                        'Direcção de Trânsito' => 'Direcção de Trânsito',
                        'Direcção de Protecção Pública' => 'Direcção de Protecção Pública',
                        'Direcção de Fronteiras' => 'Direcção de Fronteiras',
                        'Direcção de Logística' => 'Direcção de Logística',
                        'Direcção de Finanças' => 'Direcção de Finanças',
                        'Direcção de Saúde' => 'Direcção de Saúde',
                        'Direcção de Instrução e Ensino' => 'Direcção de Instrução e Ensino',
                        'Direcção de Comunicações' => 'Direcção de Comunicações',
                    ])
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('max_students')
                    ->label('Capacidade/Vagas')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Data de Início')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Data do Fim')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('start_date'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Curso')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_students')
                    ->label('Vagas')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('organ')
                    ->label('Órgão')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['institution_id'] = Filament::getTenant()?->id;
                        return $data;
                    })
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Mapa de Curso criado com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Mapa de Curso atualizado com sucesso!'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseMaps::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:CourseMap') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
