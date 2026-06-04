<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Currículo';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Curso';
    protected static ?string $pluralModelLabel = 'Cursos';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Detalhes do Curso')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Curso')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191),
                        Forms\Components\TextInput::make('duration_months')
                            ->label('Duração (Meses)')
                            ->numeric()
                            ->suffix('meses'),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
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
                    ->suffix(' meses')
                    ->sortable(),
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
                    ->successNotificationTitle('Curso criado com sucesso!')
                    ->label('Novo Curso'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                \Filament\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->modalWidth('3xl')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Curso atualizado com sucesso!'),
                \Filament\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->before(function (Course $record, \Filament\Actions\DeleteAction $action) {
                        $courseMapsCount = \App\Models\CourseMap::where('course_id', $record->id)->count();
                        $plansCount = \App\Models\CoursePlan::where('course_id', $record->id)->count();

                        if ($courseMapsCount > 0 || $plansCount > 0) {
                            $messages = [];
                            if ($courseMapsCount > 0) $messages[] = "$courseMapsCount mapa(s) de curso";
                            if ($plansCount > 0) $messages[] = "$plansCount plano(s)";

                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Não é possível excluir')
                                ->body('Este curso está vinculado a: ' . implode(', ', $messages) . '. Remova as dependências primeiro.')
                                ->persistent()
                                ->send();

                            $action->cancel();
                            return;
                        }

                        // Excluir fases vinculadas automaticamente
                        \App\Models\CoursePhase::where('course_id', $record->id)->delete();
                    }),
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
            'index' => Pages\ListCourses::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Course') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
