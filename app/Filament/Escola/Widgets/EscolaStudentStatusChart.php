<?php

namespace App\Filament\Escola\Widgets;

use App\Models\Student;
use App\Services\GradeCalculator;
use App\Support\ChartColors;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EscolaStudentStatusChart extends ChartWidget
{
    protected ?string $heading = 'Formandos Aprovados e Reprovados';
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->can('View:EscolaStudentStatusChart') ?? false;
    }

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        $institutionId = $tenant?->id;

        // Load students with evaluations for proper calculation
        $students = Student::with(['evaluations'])
            ->where('institution_id', $institutionId)
            ->whereHas('evaluations')
            ->get();

        $aprovados = 0;
        $reprovadosNotas = 0;
        $pendentes = 0;

        foreach ($students as $student) {
            $result = GradeCalculator::result($student);
            if ($result === 'Aprovado') {
                $aprovados++;
            } elseif ($result === 'Reprovado') {
                $reprovadosNotas++;
            } else {
                $pendentes++;
            }
        }

        // Reprovados por Desistência
        $reprovadosDesistencia = DB::table('student_leaves')
            ->join('students', 'student_leaves.student_id', '=', 'students.id')
            ->where('student_leaves.leave_type', 'reprovado_desistencia')
            ->where('students.institution_id', $institutionId)
            ->distinct('student_leaves.student_id')
            ->count('student_leaves.student_id');

        // Reprovados por Faltas
        $reprovadosFaltas = DB::table('student_leaves')
            ->join('students', 'student_leaves.student_id', '=', 'students.id')
            ->where('student_leaves.leave_type', 'reprovado_faltas')
            ->where('students.institution_id', $institutionId)
            ->distinct('student_leaves.student_id')
            ->count('student_leaves.student_id');

        // Baixa de Curso
        $baixaCurso = DB::table('student_leaves')
            ->join('students', 'student_leaves.student_id', '=', 'students.id')
            ->where('student_leaves.leave_type', 'baixa_curso')
            ->where('students.institution_id', $institutionId)
            ->distinct('student_leaves.student_id')
            ->count('student_leaves.student_id');

        // Build data - only include categories with values > 0
        $labels = [];
        $values = [];
        $colors = [];

        $categories = [
            ['label' => 'Aprovados (Pauta)', 'value' => $aprovados, 'color' => ChartColors::forLabel('Aprovados (Pauta)', 0.9)],
            ['label' => 'Pendentes', 'value' => $pendentes, 'color' => ChartColors::forLabel('Pendentes', 0.9)],
            ['label' => 'Reprovados por Notas', 'value' => $reprovadosNotas, 'color' => ChartColors::forLabel('Reprovados por Notas', 0.9)],
            ['label' => 'Reprovados por Faltas', 'value' => $reprovadosFaltas, 'color' => ChartColors::forLabel('Reprovados por Faltas', 0.9)],
            ['label' => 'Reprovados/Desistências', 'value' => $reprovadosDesistencia, 'color' => ChartColors::forLabel('Reprovados/Desistências', 0.9)],
            ['label' => 'Baixa de curso', 'value' => $baixaCurso, 'color' => ChartColors::forLabel('Baixa de curso', 0.9)],
        ];

        foreach ($categories as $cat) {
            if ($cat['value'] > 0) {
                $labels[] = $cat['label'];
                $values[] = $cat['value'];
                $colors[] = $cat['color'];
            }
        }

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
        return 'pie';
    }
}
