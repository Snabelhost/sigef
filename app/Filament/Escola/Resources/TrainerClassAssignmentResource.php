<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\TrainerClassAssignmentResource\Pages;
use App\Models\TrainerSubjectAuthorization;
use App\Models\Trainer;
use App\Models\StudentClass;
use App\Models\Subject;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class TrainerClassAssignmentResource extends Resource
{
    protected static bool $shouldSkipAuthorization = false;

    protected static ?string $model = TrainerSubjectAuthorization::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-link';
    protected static ?string $navigationLabel = 'Atribuição de Turmas';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?int $navigationSort = 5;

    // Desabilitar tenancy automática para este recurso
    protected static bool $isScopedToTenant = false;

    public static function getModelLabel(): string
    {
        return 'Atribuição';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Atribuição de Turmas';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['trainer', 'subject', 'studentClass']);

        if ($tenant = Filament::getTenant()) {
            $query->whereHas('trainer', fn($q) => $q->where('institution_id', $tenant->id));
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('trainer_id')
                    ->label('Formador')
                    ->options(function () {
                        if ($tenant = Filament::getTenant()) {
                            return Trainer::where('institution_id', $tenant->id)->pluck('name', 'id');
                        }
                        return Trainer::pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('subject_id')
                    ->label('Disciplina')
                    ->relationship('subject', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('student_class_id')
                    ->label('Turma')
                    ->options(function () {
                        if ($tenant = Filament::getTenant()) {
                            return StudentClass::whereHas('courseMap', fn($q) => $q->where('institution_id', $tenant->id))->pluck('name', 'id');
                        }
                        return StudentClass::pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('trainer.name')
                    ->label('Formador')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('studentClass.name')
                    ->label('Turma')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Atribuir'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Atribuição criada com sucesso!'),
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
            'index' => Pages\ListTrainerClassAssignments::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:TrainerClassAssignment') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
