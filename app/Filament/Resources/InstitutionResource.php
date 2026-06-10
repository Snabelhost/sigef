<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstitutionResource\Pages;
use App\Models\Institution;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DbSchema;
use Throwable;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-building-library';
    protected static string|\UnitEnum|null $navigationGroup = 'Instituições';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Instituições';
    protected static ?string $modelLabel = 'Instituição';
    protected static ?string $pluralModelLabel = 'Instituições';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['type']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('institution_type_id')
                    ->label('Tipo de Instituição')
                    ->relationship('type', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                Forms\Components\TextInput::make('acronym')
                    ->label('Sigla')
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                Forms\Components\TextInput::make('phone')
                    ->label('Telefone')
                    ->tel()
                    ->prefix('+244')
                    ->placeholder('9XX XXX XXX')
                    ->mask('999 999 999')
                    ->rule('regex:/^[9][0-9]{2}\s?[0-9]{3}\s?[0-9]{3}$/')
                    ->validationMessages([
                        'regex' => 'O número de telefone deve ter 9 dígitos e começar com 9.',
                    ])
                    ->maxLength(11),
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->rule('email:rfc,dns')
                    ->validationMessages([
                        'email' => 'Por favor, insira um endereço de e-mail válido.',
                    ])
                    ->suffixIcon('heroicon-o-envelope')
                    ->maxLength(191),
                Forms\Components\Select::make('province')
                    ->label('Província')
                    ->options(fn() => \App\Models\Province::orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Set $set) => $set('municipality', null)),
                Forms\Components\Select::make('municipality')
                    ->label('Município')
                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                        $provinceName = $get('province');
                        if (!$provinceName) {
                            return [];
                        }
                        $province = \App\Models\Province::where('name', $provinceName)->first();
                        if (!$province) {
                            return [];
                        }
                        return \App\Models\Municipality::where('province_id', $province->id)
                            ->orderBy('name')
                            ->pluck('name', 'name');
                    })
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('address')
                    ->label('Endereço')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('logo')
                    ->label('Logótipo')
                    ->image()
                    ->disk('public')
                    ->directory('institutions'),
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
                Tables\Columns\TextColumn::make('type.name')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('acronym')
                    ->label('Sigla')
                    ->searchable(),
                Tables\Columns\TextColumn::make('province')
                    ->label('Província')
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
                    ->label('Nova Instituição'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                \Filament\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Registo atualizado com sucesso!'),
                static::guardedDeleteAction(),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->action(function (\Filament\Actions\DeleteBulkAction $action, \Illuminate\Database\Eloquent\Collection $records): void {
                            $deleted = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    if (static::deleteInstitutionWithLinks($record)) {
                                        $deleted++;
                                    } else {
                                        $failed++;
                                    }
                                } catch (Throwable $exception) {
                                    report($exception);

                                    $failed++;
                                }
                            }

                            if ($failed > 0) {

                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title($failed.' instituição(ões) não foram excluída(s)')
                                    ->body('Algumas instituições não puderam ser excluídas por uma restrição inesperada da base de dados.')
                                    ->persistent()
                                    ->send();
                            }

                            if ($deleted > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title($deleted.' instituição(ões) excluída(s) com sucesso.')
                                    ->send();
                            }

                            if ($failed > 0 && $deleted === 0) {
                                $action->failure();

                                return;
                            }

                            $action->success();
                        }),
                ]),
            ]);
    }

    public static function guardedDeleteAction(): \Filament\Actions\DeleteAction
    {
        return \Filament\Actions\DeleteAction::make()
            ->icon('heroicon-o-trash')
            ->using(fn (Institution $record): bool => static::deleteInstitutionWithLinks($record));
    }

    protected static function deleteInstitutionWithLinks(Institution $record): bool
    {
        return DB::transaction(function () use ($record): bool {
            static::prepareInstitutionForDeletion($record);

            return (bool) $record->delete();
        });
    }

    protected static function prepareInstitutionForDeletion(Institution $record): void
    {
        $institutionId = (int) $record->getKey();
        $courseMapIds = static::tableIdsWhere('course_maps', 'institution_id', $institutionId);
        $classIds = static::tableIdsWhere('classes', 'institution_id', $institutionId);

        if (! empty($courseMapIds) && DbSchema::hasTable('classes') && DbSchema::hasColumn('classes', 'course_map_id')) {
            $classIds = array_values(array_unique(array_merge(
                $classIds,
                DB::table('classes')
                    ->whereIn('course_map_id', $courseMapIds)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
            )));
        }

        if (! empty($courseMapIds)) {
            static::nullWhereIn('students', 'course_map_id', $courseMapIds);
        }

        foreach ([
            'attendances',
            'student_subject_enrollments',
            'student_class_enrollments',
            'trainer_class_assignments',
        ] as $table) {
            static::deleteWhereIn($table, 'class_id', $classIds);
        }

        static::deleteWhereIn('classes', 'id', $classIds);
        static::deleteWhereIn('course_maps', 'id', $courseMapIds);

        foreach ([
            'users',
            'students',
            'trainers',
            'candidates',
            'courses',
            'subjects',
            'evaluations',
            'student_leaves',
            'equipment_assignments',
            'trainer_subject_authorizations',
            'effectives',
        ] as $table) {
            static::nullWhere($table, 'institution_id', $institutionId);
        }

        foreach ([
            'agent_transfer_histories',
            'candidate_transfer_histories',
            'student_transfer_histories',
        ] as $table) {
            static::nullWhere($table, 'from_institution_id', $institutionId);
            static::deleteWhere($table, 'to_institution_id', $institutionId);
        }
    }

    protected static function tableIdsWhere(string $table, string $column, int $value): array
    {
        if (! DbSchema::hasTable($table) || ! DbSchema::hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)
            ->where($column, $value)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    protected static function nullWhere(string $table, string $column, int $value): void
    {
        if (! DbSchema::hasTable($table) || ! DbSchema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->where($column, $value)
            ->update([$column => null]);
    }

    protected static function nullWhereIn(string $table, string $column, array $values): void
    {
        if (empty($values) || ! DbSchema::hasTable($table) || ! DbSchema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->whereIn($column, $values)
            ->update([$column => null]);
    }

    protected static function deleteWhere(string $table, string $column, int $value): void
    {
        if (! DbSchema::hasTable($table) || ! DbSchema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->where($column, $value)
            ->delete();
    }

    protected static function deleteWhereIn(string $table, string $column, array $values): void
    {
        if (empty($values) || ! DbSchema::hasTable($table) || ! DbSchema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->whereIn($column, $values)
            ->delete();
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
            'index' => Pages\ListInstitutions::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Institution') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
