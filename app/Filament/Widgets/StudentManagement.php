<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class StudentManagement extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Gestão de Formandos';

    // Adicionar espaçamento acima do widget
    protected function getTableContentFooter(): ?\Illuminate\Contracts\View\View
    {
        return null;
    }

    public static function canView(): bool
    {
        return true;
    }

    // Override para adicionar margem superior
    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }

    protected function getTableWrapperAttributes(): array
    {
        return [
            'class' => 'mt-6',
        ];
    }

    public function table(Table $table): Table
    {
        $institutionId = $this->filters['institution_id'] ?? null;
        $courseId = $this->filters['course_id'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        return $table
            ->query(
                Student::query()
                    ->with(['institution', 'classEnrollments.studentClass', 'candidate', 'studentTypeRelation'])
                    ->whereNotNull('institution_id')
                    // Excluir Alistado e Formando
                    ->where(function ($query) {
                        $query->whereNotIn('student_type', ['Alistado', 'Formando'])
                            ->orWhereNull('student_type');
                    })
                    ->whereDoesntHave('studentTypeRelation', function ($q) {
                        $q->whereIn('name', ['Alistado', 'Formando']);
                    })
                    ->when($institutionId, fn($query) => $query->where('institution_id', $institutionId))
                    ->when($courseId, fn($query) => $query->whereHas('courseMap', fn($courseQuery) => $courseQuery->where('course_id', $courseId)))
                    ->when($startDate, fn($query) => $query->whereDate('created_at', '>=', $startDate))
                    ->when($endDate, fn($query) => $query->whereDate('created_at', '<=', $endDate))
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Nome')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Instituição')
                    ->badge()
                    ->color('primary')
                    ->limit(25),
                Tables\Columns\TextColumn::make('classEnrollments.studentClass.name')
                    ->label('Turma(s)')
                    ->badge()
                    ->color('info')
                    ->limit(30),
                Tables\Columns\TextColumn::make('student_type')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(?string $state): string => match (true) {
                        str_contains($state ?? '', 'Recruta') => 'warning',
                        str_contains($state ?? '', 'Instruendo') => 'info',
                        str_contains($state ?? '', 'Em Formação') => 'primary',
                        str_contains($state ?? '', 'Oficial') => 'success',
                        str_contains($state ?? '', 'Agente') => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('Data Inscrição')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Instituição')
                    ->relationship('institution', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('student_type_id')
                    ->label('Tipo de Aluno')
                    ->relationship('studentTypeRelation', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->emptyStateHeading('Sem formandos')
            ->emptyStateDescription('Não existem formandos com instituição atribuída.')
            ->emptyStateIcon('heroicon-s-academic-cap');
    }
}
