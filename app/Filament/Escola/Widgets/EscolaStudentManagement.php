<?php

namespace App\Filament\Escola\Widgets;

use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class EscolaStudentManagement extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Últimos Formandos';

    public static function canView(): bool
    {
        return auth()->user()?->can('View:EscolaStudentManagement') ?? false;
    }

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
        $tenant = Filament::getTenant();
        $institutionId = $tenant?->id;

        return $table
            ->query(
                Student::query()
                    ->with(['institution', 'classEnrollments.studentClass', 'candidate', 'studentTypeRelation'])
                    ->where('institution_id', $institutionId)
                    ->whereNotNull('institution_id')
                    ->where(function ($query) {
                        $query->whereNotIn('student_type', ['Alistado', 'Formando'])
                            ->orWhereNull('student_type');
                    })
                    ->whereDoesntHave('studentTypeRelation', function ($q) {
                        $q->whereIn('name', ['Alistado', 'Formando']);
                    })
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
            ->emptyStateHeading('Sem formandos')
            ->emptyStateDescription('Não existem formandos registados nesta instituição.')
            ->emptyStateIcon('heroicon-s-academic-cap');
    }
}
