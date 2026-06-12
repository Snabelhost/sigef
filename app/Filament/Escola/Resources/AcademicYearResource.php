<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\AcademicYearResource\Pages;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AcademicYearResource extends Resource
{
    protected static bool $shouldSkipAuthorization = false;

    protected static ?string $model = AcademicYear::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-calendar';
    protected static ?string $navigationLabel = 'Anos Académicos';
    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';
    protected static ?int $navigationSort = 0;

    // Desabilitar tenancy automática
    protected static bool $isScopedToTenant = false;

    public static function getModelLabel(): string
    {
        return 'Ano Académico';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Anos Académicos';
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
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
                        'unique' => 'Ja existe um ano académico com este ano.',
                    ]),
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(191)
                    ->placeholder('Ex: 2024/2025'),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Data de Início')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Data de Fim')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('start_date'),
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
            ->defaultSort('start_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('Ano')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
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
                    ->successNotificationTitle('Ano Académico criado com sucesso!'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make()->icon('heroicon-o-pencil-square'),
                    \Filament\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->before(function (AcademicYear $record, \Filament\Actions\DeleteAction $action): void {
                            $dependencies = static::academicYearDependencies($record);

                            if ($dependencies === []) {
                                return;
                            }

                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Não é possível excluir')
                                ->body('Este ano académico está vinculado a: ' . implode(', ', $dependencies) . '. Remova ou altere essas dependências primeiro.')
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
                                    ->title('Alguns anos académicos não foram excluídos')
                                    ->body($blocked->count() . ' ano(s) académico(s) possuem dados vinculados. Remova ou altere essas dependências primeiro.')
                                    ->persistent()
                                    ->send();
                            }

                            if ($deletable->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title($deletable->count() . ' ano(s) académico(s) excluído(s) com sucesso.')
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

    protected static function academicYearDependencies(AcademicYear $record): array
    {
        $tables = [
            'course_maps' => 'mapa(s) de curso',
            'course_plans' => 'plano(s) de curso',
            'candidates' => 'formando(s)',
            'classes' => 'turma(s)',
            'student_class_enrollments' => 'inscrição(ões) em turmas',
            'trainer_class_assignments' => 'atribuição(ões) de formador',
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
        return false;
    }
}
