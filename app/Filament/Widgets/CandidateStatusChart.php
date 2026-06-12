<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Candidate;
use App\Filament\Widgets\Concerns\UsesDashboardChartCache;
use App\Services\SigaDashboardStatsService;
use App\Support\ChartColors;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class CandidateStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use UsesDashboardChartCache;
    
    protected ?string $heading = 'Estado de Formandos';
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = null;
    protected static bool $isLazy = false;
    
    protected function getData(): array
    {
        $filters = $this->dashboardChartFilters();

        return $this->rememberDashboardChart(
            'candidate_status',
            $filters,
            fn (): array => $this->buildData($filters),
            $this->emptyPieChartData(),
        );
    }

    private function buildData(array $filters): array
    {
        // Obter filtros do dashboard
        $institutionId = $filters['institution_id'] ?? null;
        $courseId = $filters['course_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        
        // Converter datas se existirem
        $startDate = $startDate ? Carbon::parse($startDate) : null;
        $endDate = $endDate ? Carbon::parse($endDate) : null;
        
        // Definir os estados que queremos mostrar com cores fixas
        $estados = [
            'Alistado' => [
                'color' => ChartColors::forLabel('Alistado', 0.9),
                'count' => 0,
            ],
            'Recruta' => [
                'color' => ChartColors::forLabel('Recruta', 0.9),
                'count' => 0,
            ],
            'Instruendo' => [
                'color' => ChartColors::forLabel('Instruendo', 0.9),
                'count' => 0,
            ],
            'Formando Superior' => [
                'color' => ChartColors::forLabel('Formando Superior', 0.9),
                'count' => 0,
            ],
            'Em Formação' => [
                'color' => ChartColors::forLabel('Em Formação', 0.9),
                'count' => 0,
            ],
        ];
        
        // Contar Alistados (de Candidates)
        $alistadosQuery = Candidate::query();
        if ($institutionId) {
            $alistadosQuery->where('institution_id', $institutionId);
        }
        if ($courseId) {
            $alistadosQuery->whereRaw('1 = 0');
        }
        if ($startDate) {
            $alistadosQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $alistadosQuery->whereDate('created_at', '<=', $endDate);
        }
        $estados['Alistado']['count'] = (clone $alistadosQuery)->where('student_type', 'Alistado')->count();
        
        // Contar os outros estados (de Students)
        $studentsQuery = Student::query()->whereNotNull('institution_id');
        if ($institutionId) {
            $studentsQuery->where('institution_id', $institutionId);
        }
        if ($courseId) {
            $studentsQuery->whereHas('courseMap', fn($query) => $query->where('course_id', $courseId));
        }
        if ($startDate) {
            $studentsQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $studentsQuery->whereDate('created_at', '<=', $endDate);
        }
        
        // Recrutas
        $estados['Recruta']['count'] = (clone $studentsQuery)
            ->where('student_type', 'like', '%Recruta%')
            ->count();
        
        // Instruendos
        $estados['Instruendo']['count'] = (clone $studentsQuery)
            ->where('student_type', 'like', '%Instruendo%')
            ->count();
        
        // Formando Superior
        $estados['Formando Superior']['count'] = (clone $studentsQuery)
            ->where('student_type', 'like', '%Formando Superior%')
            ->count();
        
        // Em Formação
        $estados['Em Formação']['count'] = (clone $studentsQuery)
            ->where('student_type', 'like', '%Em Formação%')
            ->whereNot('student_type', 'like', '%Superior%')
            ->count();
        
        foreach (app(SigaDashboardStatsService::class)->studentTrainingTypes($filters) as $row) {
            $label = (string) ($row['label'] ?? '');
            $count = (int) ($row['total_alunos'] ?? 0);

            if ($label === '' || $count <= 0) {
                continue;
            }

            $estados[$label] = [
                'color' => ChartColors::forLabel($label, 0.9),
                'count' => $count,
            ];
        }

        // Filtrar apenas estados com contagem > 0
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
        
        // Se não houver dados
        if (empty($values)) {
            $labels = ['Sem dados'];
            $values = [1];
            $colors = [ChartColors::forLabel('Sem dados', 0.8)];
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
