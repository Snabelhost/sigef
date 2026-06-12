<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicYearResource\Pages;
use App\Filament\Resources\AcademicYearResource\RelationManagers;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicYearResource extends Resource
{
    protected static ?string $model = AcademicYear::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-calendar-date-range';
    protected static ?string $navigationLabel = 'Anos Académicos';

    public static function getModelLabel(): string
    {
        return 'Ano Académico';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Anos Académicos';
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';
    protected static ?int $navigationSort = 0;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::academicYearFormSchema());
    }

    protected static function academicYearFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('year')
                ->label('Ano')
                ->required()
                ->placeholder('Ex: 2026/2027')
                ->maxLength(9)
                ->unique(ignoreRecord: true)
                ->mutateStateForValidationUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                ->rules(['regex:/^\d{4}\/\d{4}$/'])
                ->validationMessages([
                    'regex' => 'O formato deve ser AAAA/AAAA (ex: 2026/2027)',
                    'unique' => 'Ja existe um ano academico com este ano.',
                ]),
            Forms\Components\TextInput::make('name')
                ->label('Descrição')
                ->required()
                ->maxLength(191),
            Forms\Components\DatePicker::make('start_date')
                ->label('Data de Início')
                ->required(),
            Forms\Components\DatePicker::make('end_date')
                ->label('Data de Término')
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Activo')
                ->default(true)
                ->required(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('Ano')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Descrição')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Início')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Término')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Registo criado com sucesso!')
                    ->label('Novo Ano Académico'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                        ->label('Visualizar')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading('Visualizar Ano Académico')
                        ->schema(static::academicYearFormSchema())
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Fechar')->color('danger')),
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Registo atualizado com sucesso!'),
                    \Filament\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->before(function (AcademicYear $record, \Filament\Actions\DeleteAction $action): void {
                            $dependencies = static::academicYearDependencies($record);

                            if ($dependencies === []) {
                                return;
                            }

                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Nao e possivel excluir')
                                ->body('Este ano academico esta vinculado a: ' . implode(', ', $dependencies) . '. Remova ou altere essas dependencias primeiro.')
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->action(function (\Filament\Actions\DeleteBulkAction $action, \Illuminate\Support\Collection $records): void {
                            $blocked = $records->filter(fn (AcademicYear $record): bool => static::academicYearDependencies($record) !== []);
                            $deletable = $records->reject(fn (AcademicYear $record): bool => static::academicYearDependencies($record) !== []);

                            $deletable->each(fn (AcademicYear $record): bool|null => $record->delete());

                            if ($blocked->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Alguns anos academicos nao foram excluidos')
                                    ->body($blocked->count() . ' ano(s) academico(s) possuem dados vinculados. Remova ou altere essas dependencias primeiro.')
                                    ->persistent()
                                    ->send();
                            }

                            if ($deletable->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title($deletable->count() . ' ano(s) academico(s) excluido(s) com sucesso.')
                                    ->send();
                            }

                            if ($blocked->isNotEmpty() && $deletable->isEmpty()) {
                                $action->failure();

                                return;
                            }

                            $action->success();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    protected static function academicYearDependencies(AcademicYear $record): array
    {
        $tables = [
            'course_maps' => 'mapa(s) de curso',
            'course_plans' => 'plano(s) de curso',
            'candidates' => 'formando(s)',
            'classes' => 'turma(s)',
            'student_class_enrollments' => 'inscricao(oes) em turmas',
            'trainer_class_assignments' => 'atribuicao(oes) de formador',
        ];

        $dependencies = [];

        foreach ($tables as $table => $label) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table) || ! \Illuminate\Support\Facades\Schema::hasColumn($table, 'academic_year_id')) {
                continue;
            }

            $count = \Illuminate\Support\Facades\DB::table($table)
                ->where('academic_year_id', $record->getKey())
                ->count();

            if ($count > 0) {
                $dependencies[] = "{$count} {$label}";
            }
        }

        return $dependencies;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicYears::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:AcademicYear') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
