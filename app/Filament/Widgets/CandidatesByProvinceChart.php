<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Institution;
use App\Services\SigaDashboardStatsService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CandidatesByProvinceChart extends ChartWidget
{
    use InteractsWithPageFilters;
    
    protected string $view = 'filament.widgets.institution-students-chart';
    protected ?string $heading = 'Alunos por Instituição de Ensino';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';
    protected ?string $maxHeight = '380px';
    protected static bool $isLazy = true;

    protected function getData(): array
    {
        // Obter filtros do dashboard
        $institutionId = $this->filters['institution_id'] ?? null;
        $courseId = $this->filters['course_id'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        
        // Se uma instituição específica foi selecionada
        if ($institutionId) {
            $institutions = Institution::where('id', $institutionId)->get();
        } else {
            $institutions = Institution::all();
        }
        
        $combined = [];
        
        foreach ($institutions as $institution) {
            // Query estudantes
            $studentQuery = Student::where('institution_id', $institution->id);
            if ($courseId) {
                $studentQuery->whereHas('courseMap', fn($query) => $query->where('course_id', $courseId));
            }
            if ($startDate) {
                $studentQuery->whereDate('created_at', '>=', Carbon::parse($startDate));
            }
            if ($endDate) {
                $studentQuery->whereDate('created_at', '<=', Carbon::parse($endDate));
            }
            $studentCount = $studentQuery->count();
            
            if ($studentCount > 0) {
                $this->addInstitutionTotal($combined, $institution->name, $studentCount, 0);
            }
        }

        foreach (app(SigaDashboardStatsService::class)->institutionStudents([
            'institution_id' => $institutionId,
            'course_id' => $courseId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]) as $row) {
            $this->addInstitutionTotal(
                $combined,
                $row['institution_name'] ?? 'Faculdade SIGA',
                0,
                (int) ($row['total_alunos'] ?? 0),
            );
        }

        if ($combined === []) {
            $combined = [
                'sem-dados' => [
                    'label' => 'Sem dados',
                    'sistema' => 0,
                    'api' => 0,
                    'total' => 0,
                ],
            ];
        }

        usort($combined, fn (array $first, array $second): int => $second['total'] <=> $first['total']);
        $combined = array_slice(array_values($combined), 0, 10);
        $barCount = count($combined);
        $systemBackgroundColors = $this->chartPalette($barCount, 0.86);
        $systemBorderColors = $this->chartPalette($barCount, 1);
        $apiBackgroundColors = $this->chartPalette($barCount, 0.38);
        $apiBorderColors = $this->chartPalette($barCount, 0.95);
        
        return [
            'datasets' => [
                [
                    'label' => 'Sistema',
                    'data' => array_column($combined, 'sistema'),
                    'backgroundColor' => $systemBackgroundColors,
                    'borderColor' => $systemBorderColors,
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                    'barPercentage' => 0.78,
                    'categoryPercentage' => 0.72,
                ],
                [
                    'label' => 'API SIGA',
                    'data' => array_column($combined, 'api'),
                    'backgroundColor' => $apiBackgroundColors,
                    'borderColor' => $apiBorderColors,
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                    'barPercentage' => 0.78,
                    'categoryPercentage' => 0.72,
                ],
            ],
            'labels' => array_column($combined, 'label'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
{
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    scales: {
        y: {
            beginAtZero: true,
            stacked: false,
            ticks: {
                precision: 0,
            },
            grid: {
                color: 'rgba(15, 23, 42, 0.08)',
            },
        },
        x: {
            stacked: false,
            ticks: {
                autoSkip: false,
                maxRotation: 22,
                minRotation: 0,
                callback: function(value) {
                    const label = this.getLabelForValue(value) || '';
                    return label.length > 34 ? label.slice(0, 34) + '...' : label;
                },
            },
            grid: {
                display: false,
            },
        },
    },
    plugins: {
        legend: {
            display: true,
        },
        tooltip: {
            callbacks: {
                label: (context) => `${context.dataset.label}: ${context.parsed.y || 0} aluno(s)`,
                footer: (items) => {
                    const total = items.reduce((sum, item) => sum + (item.parsed.y || 0), 0);
                    return `Total: ${total} aluno(s)`;
                },
            },
        },
    },
}
JS);
    }

    private function chartPalette(int $count, float $alpha, int $offset = 0): array
    {
        $palette = [
            [37, 99, 235],
            [16, 185, 129],
            [245, 158, 11],
            [239, 68, 68],
            [139, 92, 246],
            [14, 165, 233],
            [236, 72, 153],
            [34, 197, 94],
            [249, 115, 22],
            [100, 116, 139],
        ];

        $colors = [];

        for ($index = 0; $index < $count; $index++) {
            [$red, $green, $blue] = $palette[($index + $offset) % count($palette)];
            $colors[] = "rgba({$red}, {$green}, {$blue}, {$alpha})";
        }

        return $colors;
    }

    private function addInstitutionTotal(array &$combined, string $label, int $systemTotal, int $apiTotal): void
    {
        $key = Str::of($label)->ascii()->lower()->trim()->squish()->toString();

        if (! isset($combined[$key])) {
            $combined[$key] = [
                'label' => $label,
                'sistema' => 0,
                'api' => 0,
                'total' => 0,
            ];
        }

        $combined[$key]['sistema'] += $systemTotal;
        $combined[$key]['api'] += $apiTotal;
        $combined[$key]['total'] = $combined[$key]['sistema'] + $combined[$key]['api'];
    }
}
