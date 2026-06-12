<?php

namespace App\Filament\Escola\Widgets;

use App\Models\Student;
use App\Support\ChartColors;
use Filament\Facades\Filament;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class EscolaStudentsByCourseChart extends ChartWidget
{
    protected ?string $heading = 'Formandos por Curso';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '360px';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->can('View:EscolaStudentsByCourseChart') ?? false;
    }

    protected function getData(): array
    {
        $institutionId = Filament::getTenant()?->id;

        $items = Student::query()
            ->selectRaw('COALESCE(courses.name, "Sem curso") as course_name, COUNT(DISTINCT students.id) as total')
            ->leftJoin('student_class_enrollments', 'student_class_enrollments.student_id', '=', 'students.id')
            ->leftJoin('classes', 'classes.id', '=', 'student_class_enrollments.class_id')
            ->leftJoin('course_maps', 'course_maps.id', '=', 'classes.course_map_id')
            ->leftJoin('courses', 'courses.id', '=', 'course_maps.course_id')
            ->when($institutionId, fn ($query) => $query->where('students.institution_id', $institutionId))
            ->whereNotNull('students.institution_id')
            ->groupBy('course_name')
            ->orderBy('course_name')
            ->get();

        $labels = $items->pluck('course_name')->map(fn ($label) => (string) $label)->all();
        $values = $items->pluck('total')->map(fn ($value) => (int) $value)->all();

        if ($labels === []) {
            $labels = ['Sem dados'];
            $values = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Formandos',
                    'data' => $values,
                    'backgroundColor' => ChartColors::forLabels($labels, 0.86),
                    'borderColor' => ChartColors::forLabels($labels, 1),
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                    'barPercentage' => 0.76,
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
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (context) => `${context.parsed.y || 0} formando(s)`,
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: 'rgba(15, 23, 42, 0.08)' },
        },
        x: {
            ticks: {
                autoSkip: false,
                maxRotation: 18,
                minRotation: 0,
                callback: function(value) {
                    const label = this.getLabelForValue(value) || '';
                    return label.length > 34 ? label.slice(0, 34) + '...' : label;
                },
            },
            grid: { display: false },
        },
    },
}
JS);
    }
}
