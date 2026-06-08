<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoursePlanResource\Pages;
use App\Filament\Resources\CoursePlanResource\RelationManagers;
use App\Models\CoursePlan;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CoursePlanResource extends Resource
{
    protected static ?string $model = CoursePlan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Planos de Curso';
    protected static ?string $modelLabel = 'Plano de Curso';
    protected static ?string $pluralModelLabel = 'Planos de Curso';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_map_id')
                    ->label('Curso')
                    ->options(fn() => \App\Models\CourseMap::with(['course', 'academicYear'])
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn($m) => [$m->id => $m->course?->name . ' (' . ($m->academicYear?->year ?? 'S/A') . ')'])
                        ->toArray())
                    ->required()
                    ->searchable()
                    ->preload()
                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                        if ($state) {
                            $map = \App\Models\CourseMap::find($state);
                            if ($map) {
                                $set('course_id', $map->course_id);
                                $set('academic_year_id', $map->academic_year_id);
                            }
                        }
                    })
                    ->live()
                    ->dehydrated(false),
                Forms\Components\Hidden::make('course_id'),
                Forms\Components\Hidden::make('academic_year_id'),
                Forms\Components\Select::make('subjects')
                    ->label('Disciplinas')
                    ->relationship('subjects', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Selecione as disciplinas que farão parte deste plano')
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('subjects.name')
                    ->label('Disciplinas')
                    ->badge()
                    ->color(fn(string $state): string => match (crc32($state) % 6) {
                        0 => 'primary',
                        1 => 'success',
                        2 => 'warning',
                        3 => 'danger',
                        4 => 'info',
                        5 => 'gray',
                        default => 'primary',
                    })
                    ->separator(', ')
                    ->limitList(3)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('academicYear.year')
                    ->label('Ano')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Curso')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Ano Académico')
                    ->relationship('academicYear', 'year')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Registo criado com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Registo atualizado com sucesso!'),
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
            'index' => Pages\ListCoursePlans::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:CoursePlan') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
