<?php

namespace App\Filament\Escola\Resources;

use App\Filament\Escola\Resources\CourseResource\Pages;
use App\Models\Course;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static bool $shouldSkipAuthorization = false;

    protected static ?string $model = Course::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-academic-cap';
    protected static ?string $navigationLabel = 'Cursos';
    protected static ?string $modelLabel = 'Curso';
    protected static ?string $pluralModelLabel = 'Cursos';
    protected static ?int $navigationSort = 1;
    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';

    // Desabilitar tenancy automática
    protected static bool $isScopedToTenant = false;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Informação do Curso')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Curso')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('duration_months')
                            ->label('Duração (meses)')
                            ->numeric()
                            ->suffix('meses'),
                        Forms\Components\Toggle::make('has_phases')
                            ->label('Possui Fases')
                            ->default(false),
                    ])->columns(2)->columnSpanFull(),
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
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_months')
                    ->label('Duração')
                    ->suffix(' meses'),
                Tables\Columns\IconColumn::make('has_phases')
                    ->label('Fases')
                    ->boolean(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalWidth('xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Curso criado com sucesso!')
                    ->label('Novo Curso'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->modalWidth('xl')
                        ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                        ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                        ->successNotificationTitle('Curso atualizado com sucesso!'),
                    \Filament\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->before(function (Course $record, \Filament\Actions\DeleteAction $action) {
                            $courseMapsCount = \App\Models\CourseMap::where('course_id', $record->id)->count();
                            $phasesCount = \App\Models\CoursePhase::where('course_id', $record->id)->count();
                            $plansCount = \App\Models\CoursePlan::where('course_id', $record->id)->count();

                            if ($courseMapsCount > 0 || $phasesCount > 0 || $plansCount > 0) {
                                $messages = [];
                                if ($courseMapsCount > 0) $messages[] = "$courseMapsCount mapa(s) de curso";
                                if ($phasesCount > 0) $messages[] = "$phasesCount fase(s)";
                                if ($plansCount > 0) $messages[] = "$plansCount plano(s)";

                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Não é possível excluir')
                                    ->body('Este curso está vinculado a: ' . implode(', ', $messages) . '. Remova as dependências primeiro.')
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),
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
            'index' => Pages\ListCourses::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Course') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
