<?php

namespace App\Filament\Widgets;

use App\Models\CourseMap;
use App\Models\Student;
use App\Services\GradeCalculator;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StudentStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Formandos Aprovados e Reprovados';
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getData(): array
    {
        // Obter filtros do dashboard
        $institutionId = $this->filters['institution_id'] ?? null;
        $courseId = $this->filters['course_id'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        $startDate = $startDate ? Carbon::parse($startDate) : null;
        $endDate = $endDate ? Carbon::parse($endDate) : null;

        // Load students with evaluations for proper calculation
        $query = Student::with(['evaluations'])
            ->whereHas('evaluations');

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        if ($courseId) {
            $query->whereHas('courseMap', fn($courseQuery) => $courseQuery->where('course_id', $courseId));
        }
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $students = $query->get();

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
        $desistenciaQuery = DB::table('student_leaves')
            ->join('students', 'student_leaves.student_id', '=', 'students.id')
            ->where('student_leaves.leave_type', 'reprovado_desistencia');
        if ($institutionId) {
            $desistenciaQuery->where('students.institution_id', $institutionId);
        }
        if ($courseId) {
            $desistenciaQuery->whereIn('students.course_map_id', CourseMap::query()->where('course_id', $courseId)->select('id'));
        }
        if ($startDate) {
            $desistenciaQuery->whereDate('student_leaves.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $desistenciaQuery->whereDate('student_leaves.created_at', '<=', $endDate);
        }
        $reprovadosDesistencia = $desistenciaQuery->distinct('student_leaves.student_id')->count('student_leaves.student_id');

        // Reprovados por Faltas
        $faltasQuery = DB::table('student_leaves')
            ->join('students', 'student_leaves.student_id', '=', 'students.id')
            ->where('student_leaves.leave_type', 'reprovado_faltas');
        if ($institutionId) {
            $faltasQuery->where('students.institution_id', $institutionId);
        }
        if ($courseId) {
            $faltasQuery->whereIn('students.course_map_id', CourseMap::query()->where('course_id', $courseId)->select('id'));
        }
        if ($startDate) {
            $faltasQuery->whereDate('student_leaves.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $faltasQuery->whereDate('student_leaves.created_at', '<=', $endDate);
        }
        $reprovadosFaltas = $faltasQuery->distinct('student_leaves.student_id')->count('student_leaves.student_id');

        // Baixa de Curso
        $baixaQuery = DB::table('student_leaves')
            ->join('students', 'student_leaves.student_id', '=', 'students.id')
            ->where('student_leaves.leave_type', 'baixa_curso');
        if ($institutionId) {
            $baixaQuery->where('students.institution_id', $institutionId);
        }
        if ($courseId) {
            $baixaQuery->whereIn('students.course_map_id', CourseMap::query()->where('course_id', $courseId)->select('id'));
        }
        if ($startDate) {
            $baixaQuery->whereDate('student_leaves.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $baixaQuery->whereDate('student_leaves.created_at', '<=', $endDate);
        }
        $baixaCurso = $baixaQuery->distinct('student_leaves.student_id')->count('student_leaves.student_id');

        // Build data - only include categories with values > 0
        $labels = [];
        $values = [];
        $colors = [];

        $categories = [
            ['label' => 'Aprovados (Pauta)', 'value' => $aprovados, 'color' => 'rgba(16, 185, 129, 0.9)'],
            ['label' => 'Pendentes', 'value' => $pendentes, 'color' => 'rgba(59, 130, 246, 0.9)'],
            ['label' => 'Reprovados por Notas', 'value' => $reprovadosNotas, 'color' => 'rgba(234, 179, 8, 0.9)'],
            ['label' => 'Reprovados por Faltas', 'value' => $reprovadosFaltas, 'color' => 'rgba(249, 115, 22, 0.9)'],
            ['label' => 'Reprovados/Desistências', 'value' => $reprovadosDesistencia, 'color' => 'rgba(239, 68, 68, 0.9)'],
            ['label' => 'Baixa de curso', 'value' => $baixaCurso, 'color' => 'rgba(107, 114, 128, 0.9)'],
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
        return 'pie';
    }
}
