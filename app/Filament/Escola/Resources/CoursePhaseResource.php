<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\CoursePhaseResource\Pages;
use App\Models\CoursePhase;
use App\Models\Course;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class CoursePhaseResource extends Resource
{
    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $model = CoursePhase::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-bars-3-bottom-left';
    protected static ?string $navigationLabel = 'Fases do Curso';
    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';
    protected static ?int $navigationSort = 2;

    // Desabilitar tenancy automática - filtragem manual em getEloquentQuery
    protected static bool $isScopedToTenant = false;

    public static function getModelLabel(): string
    {
        return 'Fase do Curso';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Fases do Curso';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['course']);

        // Filtrar fases de cursos que têm mapas na instituição do tenant
        if ($tenant = Filament::getTenant()) {
            $courseIds = \App\Models\CourseMap::where('institution_id', $tenant->id)->pluck('course_id');
            $query->whereIn('course_id', $courseIds);
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->label('Curso')
                    ->options(function () {
                        if ($tenant = Filament::getTenant()) {
                            $courseIds = \App\Models\CourseMap::where('institution_id', $tenant->id)->pluck('course_id');
                            return Course::whereIn('id', $courseIds)->pluck('name', 'id');
                        }
                        return Course::pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('Nome da Fase')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(1),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Curso')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Fase')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Ordem')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Fase do Curso criada com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make()->icon('heroicon-o-pencil-square'),
                    \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoursePhases::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
