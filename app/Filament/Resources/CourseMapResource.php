<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseMapResource\Pages;
use App\Filament\Resources\CourseMapResource\RelationManagers;
use App\Models\CourseMap;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CourseMapResource extends Resource
{
    protected static ?string $model = CourseMap::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-puzzle-piece';
    protected static ?string $navigationLabel = 'Mapas de Curso';

    public static function getModelLabel(): string
    {
        return 'Mapa de Curso';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Mapas de Curso';
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['course', 'institution', 'academicYear']);
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
                Forms\Components\Select::make('institution_id')
                    ->label('Instituição')
                    ->relationship('institution', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('organ')
                    ->label('Órgão')
                    ->options(fn() => \App\Models\Provenance::orderBy('name')->get()->mapWithKeys(fn($p) => [$p->name => $p->acronym ? "{$p->name} ({$p->acronym})" : $p->name])->toArray())
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('academic_year_id')
                    ->label('Ano Académico')
                    ->relationship('academicYear', 'year')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('max_students')
                    ->label('Capacidade/Vagas')
                    ->required()
                    ->default(0)
                    ->formatStateUsing(fn($state) => $state ? number_format((int)$state, 0, ',', '.') : '0')
                    ->dehydrateStateUsing(fn($state) => (int) str_replace(['.', ','], '', (string) $state))
                    ->extraInputAttributes([
                        'x-on:input' => "
                            let val = \$el.value.replace(/[^0-9]/g, '');
                            if (val) {
                                \$el.value = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            }
                        ",
                        'inputmode' => 'numeric',
                    ]),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Instituição')
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('academicYear.year')
                    ->label('Ano')
                    ->sortable(),
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
                    ->numeric(thousandsSeparator: '.')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->suffix(' vagas'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('organ')
                    ->label('Órgão')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth('3xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Mapa de curso criado com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Mapa de curso atualizado com sucesso!'),
                    \Filament\Actions\Action::make('criarPlano')
                        ->label('Criar Plano de Curso')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->modalHeading('Criar Plano de Curso')
                        ->modalWidth('3xl')
                        ->form([
                            Forms\Components\Select::make('subjects')
                                ->label('Disciplinas')
                                ->options(\App\Models\Subject::orderBy('name')->pluck('name', 'id'))
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->helperText('Selecione as disciplinas que farão parte deste plano'),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Activo')
                                ->default(true),
                        ])
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar Plano')->color('primary'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->action(function (array $data, CourseMap $record): void {
                            $plan = \App\Models\CoursePlan::create([
                                'course_id' => $record->course_id,
                                'academic_year_id' => $record->academic_year_id,
                                'is_active' => $data['is_active'] ?? true,
                            ]);

                            if (!empty($data['subjects'])) {
                                $subjects = collect($data['subjects'])->mapWithKeys(fn($id, $index) => [
                                    $id => ['order' => $index + 1]
                                ]);
                                $plan->subjects()->attach($subjects);
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Plano de Curso criado com sucesso!')
                                ->body("Plano criado para {$record->course?->name}")
                                ->send();
                        }),
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
            'index' => Pages\ListCourseMaps::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:CourseMap') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
