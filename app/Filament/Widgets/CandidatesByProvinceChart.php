<?php

namespace App\Filament\Widgets;

use App\Models\Candidate;
use App\Models\Student;
use App\Models\Institution;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class CandidatesByProvinceChart extends ChartWidget
{
    use InteractsWithPageFilters;
    
    protected ?string $heading = 'Alunos por Instituição de Ensino';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';
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
        
        $labels = [];
        $totals = [];
        
        foreach ($institutions as $institution) {
            // Query candidatos
            $candidateQuery = Candidate::where('institution_id', $institution->id);
            if ($courseId) {
                $candidateQuery->whereRaw('1 = 0');
            }
            if ($startDate) {
                $candidateQuery->whereDate('created_at', '>=', Carbon::parse($startDate));
            }
            if ($endDate) {
                $candidateQuery->whereDate('created_at', '<=', Carbon::parse($endDate));
            }
            $candidateCount = $candidateQuery->count();
            
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
            
            $total = $candidateCount + $studentCount;
            
            if ($total > 0) {
                $labels[] = $institution->name;
                $totals[] = $total;
            }
        }
        
        // Ordenar por total decrescente e limitar a 10
        if (!empty($labels)) {
            $combined = array_combine($labels, $totals);
            arsort($combined);
            $combined = array_slice($combined, 0, 10, true);
        } else {
            $combined = ['Sem dados' => 0];
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Alunos',
                    'data' => array_values($combined),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(20, 184, 166, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_keys($combined),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
