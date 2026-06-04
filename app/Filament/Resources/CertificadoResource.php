<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificadoResource\Pages;
use App\Services\GradeCalculator;
use App\Models\Student;
use App\Models\StudentClass;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificadoResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-trophy';
    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Escolar';
    protected static ?string $navigationLabel = 'Certificados';
    protected static ?string $modelLabel = 'Certificado';
    protected static ?string $pluralModelLabel = 'Certificados';
    protected static ?int $navigationSort = 12;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['candidate', 'classes.institution', 'classes.courseMap.course', 'classEnrollments'])
            ->with(['evaluations' => function ($q) {
                // Filtrar avaliações pela instituição da turma activa do aluno
                $q->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('student_class_enrollments as sce')
                        ->join('classes as sc', 'sc.id', '=', 'sce.class_id')
                        ->whereColumn('sce.student_id', 'evaluations.student_id')
                        ->whereColumn('sc.institution_id', 'evaluations.institution_id')
                        ->where('sce.is_active', true);
                });
            }])
            ->whereHas('evaluations');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('id', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Nome do Aluno')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cia')
                    ->label('CIA')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('platoon')
                    ->label('Pelotão')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('section')
                    ->label('Secção')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('turma')
                    ->label('Turma')
                    ->getStateUsing(function (Student $record) {
                        $enrollment = $record->classEnrollments->where('is_active', true)->first();
                        if ($enrollment) {
                            return \App\Models\StudentClass::find($enrollment->class_id)?->name;
                        }
                        return '-';
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('media_geral')
                    ->label('Média Geral')
                    ->getStateUsing(fn(Student $record) => static::calculateGeneralAverage($record))
                    ->badge()
                    ->color(fn($state) => $state !== '-' && floatval($state) >= 10 ? 'success' : 'danger')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('resultado')
                    ->label('Resultado')
                    ->getStateUsing(fn(Student $record) => static::getResult($record))
                    ->badge()
                    ->color(fn($state) => $state === 'Aprovado' ? 'success' : ($state === 'Pendente' ? 'gray' : 'danger'))
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cia')
                    ->label('CIA')
                    ->options(fn() => Student::whereNotNull('cia')->distinct()->pluck('cia', 'cia')->toArray()),
                Tables\Filters\SelectFilter::make('platoon')
                    ->label('Pelotão')
                    ->options(fn() => Student::whereNotNull('platoon')->distinct()->pluck('platoon', 'platoon')->toArray()),
                Tables\Filters\SelectFilter::make('section')
                    ->label('Secção')
                    ->options(fn() => Student::whereNotNull('section')->distinct()->pluck('section', 'section')->toArray()),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Turma')
                    ->relationship('classes', 'name'),
            ])
            ->headerActions([])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('gerarCertificado')
                        ->label('Certificado')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->url(fn(Student $record) => route('certificados.individual', ['student' => $record->id]))
                        ->openUrlInNewTab()
                        ->visible(fn(Student $record) => static::getResult($record) === 'Aprovado'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('gerarCertificados')
                    ->label('Gerar Certificados')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, \Filament\Actions\BulkAction $action) {
                        // Filtrar apenas aprovados
                        $approvedIds = $records->filter(function (Student $student) {
                            return static::getResult($student) === 'Aprovado';
                        })->pluck('id');

                        if ($approvedIds->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Nenhum aluno aprovado')
                                ->body('Nenhum dos alunos seleccionados está aprovado para gerar certificado.')
                                ->send();
                            return;
                        }

                        $url = route('certificados.bulk', ['ids' => $approvedIds->implode(',')]);

                        $action->getLivewire()->js("window.open('{$url}', '_blank')");
                    }),
            ]);
    }

    protected static function getSubjectFinalAverage(Student $student, int $subjectId): string
    {
        return GradeCalculator::subjectAverage($student, $subjectId);
    }

    protected static function calculateGeneralAverage(Student $student): string
    {
        return GradeCalculator::generalAverage($student);
    }

    protected static function getResult(Student $student): string
    {
        return GradeCalculator::result($student);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificados::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Certificado') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
