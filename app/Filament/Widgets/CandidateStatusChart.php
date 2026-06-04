<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Candidate;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class CandidateStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;
    
    protected ?string $heading = 'Estado de Formandos';
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;
    
    protected function getData(): array
    {
        // Obter filtros do dashboard
        $institutionId = $this->filters['institution_id'] ?? null;
        $courseId = $this->filters['course_id'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        
        // Converter datas se existirem
        $startDate = $startDate ? Carbon::parse($startDate) : null;
        $endDate = $endDate ? Carbon::parse($endDate) : null;
        
        // Definir os estados que queremos mostrar com cores fixas
        $estados = [
            'Alistado' => [
                'color' => 'rgba(59, 130, 246, 0.9)',   // Azul
                'count' => 0,
            ],
            'Recruta' => [
                'color' => 'rgba(16, 185, 129, 0.9)',    // Verde
                'count' => 0,
            ],
            'Instruendo' => [
                'color' => 'rgba(245, 158, 11, 0.9)',    // Amarelo
                'count' => 0,
            ],
            'Formando Superior' => [
                'color' => 'rgba(139, 92, 246, 0.9)',    // Roxo
                'count' => 0,
            ],
            'Em Formação' => [
                'color' => 'rgba(236, 72, 153, 0.9)',    // Rosa
                'count' => 0,
            ],
        ];
        
        // Contar Alistados (de Candidates)
        $alistadosQuery = Candidate::query()->whereNotNull('institution_id');
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
        $estados['Alistado']['count'] = (clone $alistadosQuery)->where('student_type', 'like', '%Alistado%')->count();
        
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
