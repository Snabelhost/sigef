<?php

namespace App\Filament\Escola\Widgets;

use App\Models\Student;
use App\Models\Candidate;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class EscolaCandidateStatusChart extends ChartWidget
{
    protected ?string $heading = 'Estado de Formandos';
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = null;
    protected static bool $isLazy = true;

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        $institutionId = $tenant?->id;

        $estados = [
            'Alistado' => [
                'color' => 'rgba(59, 130, 246, 0.9)',
                'count' => 0,
            ],
            'Recruta' => [
                'color' => 'rgba(16, 185, 129, 0.9)',
                'count' => 0,
            ],
            'Instruendo' => [
                'color' => 'rgba(245, 158, 11, 0.9)',
                'count' => 0,
            ],
            'Formando Superior' => [
                'color' => 'rgba(139, 92, 246, 0.9)',
                'count' => 0,
            ],
            'Em Formação' => [
                'color' => 'rgba(236, 72, 153, 0.9)',
                'count' => 0,
            ],
        ];

        // Alistados (Candidates)
        $estados['Alistado']['count'] = Candidate::where('institution_id', $institutionId)
            ->where('student_type', 'like', '%Alistado%')
            ->count();

        // Students
        $studentsQuery = Student::where('institution_id', $institutionId);

        $estados['Recruta']['count'] = (clone $studentsQuery)
            ->where('student_type', 'like', '%Recruta%')
            ->count();

        $estados['Instruendo']['count'] = (clone $studentsQuery)
            ->where('student_type', 'like', '%Instruendo%')
            ->count();

        $estados['Formando Superior']['count'] = (clone $studentsQuery)
            ->where('student_type', 'like', '%Formando Superior%')
            ->count();

        $estados['Em Formação']['count'] = (clone $studentsQuery)
            ->where('student_type', 'like', '%Em Forma%')
            ->whereNot('student_type', 'like', '%Superior%')
            ->count();

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($estados as $nome => $info) {
            if ($info['count'] > 0) {
                $labels[] = $nome;
                $values[] = $info['count'];
                $colors[] = $info['color'];
            }
        }

        if (empty($values)) {
            $labels = ['Sem dados'];
            $values = [1];
            $colors = ['rgba(156, 163, 175, 0.8)'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Formandos',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
