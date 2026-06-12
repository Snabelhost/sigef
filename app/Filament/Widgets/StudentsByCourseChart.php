<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardChartCache;
use App\Services\DashboardCourseStatsService;
use App\Support\ChartColors;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StudentsByCourseChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use UsesDashboardChartCache;

    protected string $view = 'filament.widgets.institution-students-chart';

    protected ?string $heading = 'Total de Alunos por Curso';

    protected ?string $description = 'Distribuicao de alunos por curso no sistema e na API SIGA';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '380px';

    protected static bool $isLazy = false;

    protected function getData(): array
    {
        $filters = $this->dashboardChartFilters();

        return $this->rememberDashboardChart(
            'students_by_course',
            $filters,
            fn (): array => $this->buildData($filters),
            $this->emptyBarChartData(),
        );
    }

    private function buildData(array $filters): array
    {
        $items = app(DashboardCourseStatsService::class)->studentsByCourse($filters);

        $labels = array_column($items, 'course_name');
        $systemValues = array_column($items, 'sistema_alunos');
        $apiValues = array_column($items, 'api_alunos');

        if ($labels === []) {
            $labels = ['Sem dados'];
            $systemValues = [0];
            $apiValues = [0];
        }

        $systemBackgroundColors = ChartColors::forLabels($labels, 0.86);
        $systemBorderColors = ChartColors::forLabels($labels, 1);
        $apiBackgroundColors = ChartColors::forLabels($labels, 0.38);
        $apiBorderColors = ChartColors::forLabels($labels, 0.95);

        return [
            'datasets' => [
                [
                    'label' => 'Sistema',
                    'data' => $systemValues,
                    'backgroundColor' => $systemBackgroundColors,
                    'borderColor' => $systemBorderColors,
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                    'barPercentage' => 0.78,
                    'categoryPercentage' => 0.72,
                ],
                [
                    'label' => 'API SIGA',
                    'data' => $apiValues,
                    'backgroundColor' => $apiBackgroundColors,
                    'borderColor' => $apiBorderColors,
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                    'barPercentage' => 0.78,
                    'categoryPercentage' => 0.72,
                ],
            ],
            'labels' => $labels,
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
    plugins: {
        legend: { display: true },
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
}
JS);
    }

}
