<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentClassResource\Pages;
use App\Filament\Resources\StudentClassResource\RelationManagers;
use App\Models\StudentClass;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentClassResource extends Resource
{
    protected static ?string $model = StudentClass::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-users';
    public static function getModelLabel(): string
    {
        return 'Turma';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Turmas';
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';
    protected static ?int $navigationSort = 6;

    /**
     * Gera automaticamente o nome da turma (ex: "Mecânica Geral - Turma A").
     */
    protected static function generateClassName(int $courseMapId): string
    {
        $map = \App\Models\CourseMap::with('course')->find($courseMapId);
        if (!$map || !$map->course) {
            return 'Nova Turma';
        }

        $existingCount = StudentClass::where('course_map_id', $courseMapId)->count();
        $letter = chr(65 + $existingCount); // A, B, C, ...

        return "{$map->course->name} - Turma {$letter}";
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_map_id')
                    ->label('Mapa de Curso')
                    ->options(function () {
                        return \App\Models\CourseMap::with(['course', 'institution', 'academicYear'])
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn($map) => [
                                $map->id => ($map->course?->name ?? '?') . ' — ' . ($map->institution?->name ?? '?') . ' (' . ($map->academicYear?->year ?? '?') . ')'
                            ]);
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                        if ($state) {
                            $map = \App\Models\CourseMap::find($state);
                            if ($map) {
                                $set('institution_id', $map->institution_id);
                                $set('academic_year_id', $map->academic_year_id);
                                $set('name', static::generateClassName((int) $state));
                            }
                        } else {
                            $set('institution_id', null);
                            $set('academic_year_id', null);
                            $set('name', '');
                        }
                    })
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('Nome da Turma')
                    ->helperText('Gerado automaticamente. Pode editar se preferir.')
                    ->required()
                    ->maxLength(191)
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('institution_id'),
                Forms\Components\Hidden::make('academic_year_id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Turma')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Escola')
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicYear.year')
                    ->label('Ano Lectivo')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('courseMap.max_students')
                    ->label('Capacidade')
                    ->sortable()
                    ->numeric(thousandsSeparator: '.')
                    ->badge()
                    ->color('success')
                    ->suffix(' vagas'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Ano Lectivo')
                    ->relationship('academicYear', 'year'),
                Tables\Filters\SelectFilter::make('course')
                    ->label('Curso')
                    ->options(fn() => \App\Models\Course::pluck('name', 'id'))
                    ->query(
                        fn(Builder $query, array $data) =>
                        $query->when(
                            $data['value'],
                            fn($q, $v) =>
                            $q->whereHas('courseMap', fn($q2) => $q2->where('course_id', $v))
                        )
                    ),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth('3xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Turma criada com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Turma atualizada com sucesso!'),
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
            'index' => Pages\ListStudentClasses::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:StudentClass') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
