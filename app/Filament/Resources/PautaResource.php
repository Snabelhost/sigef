<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PautaResource\Pages;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Evaluation;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PautaResource extends Resource
{
    protected static ?string $model = StudentClass::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-table-cells';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão do Centro';
    protected static ?string $navigationLabel = 'Mini Pautas';
    protected static ?string $modelLabel = 'Mini Pauta';
    protected static ?string $pluralModelLabel = 'Mini Pautas';
    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['courseMap.course', 'academicYear', 'students', 'institution']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Instituição')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Turma')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('courseMap.course.name')
                    ->label('Curso')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('academicYear.year')
                    ->label('Ano Académico')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('students_count')
                    ->label('Nº Alunos')
                    ->counts('students')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Instituição')
                    ->relationship('institution', 'name'),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('miniPauta')
                    ->label('Mini Pauta')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(fn() => static::getUrl('mini-pauta')),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('pautaGeral')
                        ->label('Pauta Geral')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('success')
                        ->url(fn(StudentClass $record) => static::getUrl('pauta-geral', ['record' => $record])),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\MiniPauta::route('/'),
            'list' => Pages\ListPautas::route('/list'),
            'pauta-geral' => Pages\PautaGeral::route('/pauta-geral'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Pauta') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
