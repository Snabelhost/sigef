<?php

namespace App\Livewire;

use App\Models\Candidate;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;

class StatsDetailModal extends Component
{
    public bool $showModal = false;
    public string $modalType = '';
    public string $modalTitle = '';
    public array $chartData = [];
    public array $summaryStats = [];

    #[On('openStatDetail')]
    public function openModal(string $type, ?int $institutionId = null): void
    {
        $this->modalType = $type;
        $this->chartData = [];
        $this->summaryStats = [];

        match ($type) {
            'alunos' => $this->loadAlunosData($institutionId),
            'formandos' => $this->loadFormandosData($institutionId),
            'formadores' => $this->loadFormadoresData($institutionId),
            'escolas' => $this->loadEscolasData(),
            default => null,
        };

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    protected function loadAlunosData(?int $institutionId): void
    {
        $this->modalTitle = 'Detalhes - Alistados, Recrutas e Instruendos';

        $institutions = Institution::all();
        $labels = [];
        $alistadosValues = [];
        $recrutasValues = [];
        $instruendosValues = [];

        $totalAlistados = 0;
        $totalRecritas = 0;
        $totalInstruendos = 0;

        foreach ($institutions as $inst) {
            // Alistados (Candidates)
            $alistados = Candidate::where('institution_id', $inst->id)
                ->where('student_type', 'like', '%Alistado%')
                ->count();

            // Recrutas (Students)
            $recrutas = Student::where('institution_id', $inst->id)
                ->where(function ($q) {
                    $q->where('student_type', 'like', '%Recruta%')
                        ->orWhere('student_type', 'like', '%1ª Fase%');
                })
                ->count();

            // Instruendos (Students)
            $instruendos = Student::where('institution_id', $inst->id)
                ->where(function ($q) {
                    $q->where('student_type', 'like', '%Instruendo%')
                        ->orWhere('student_type', 'like', '%cadete%')
                        ->orWhere('student_type', 'like', '%praça%')
                        ->orWhere('student_type', 'like', '%2ª Fase%');
                })
                ->count();

            if ($alistados > 0 || $recrutas > 0 || $instruendos > 0) {
                $labels[] = $inst->name;
                $alistadosValues[] = $alistados;
                $recrutasValues[] = $recrutas;
                $instruendosValues[] = $instruendos;
                $totalAlistados += $alistados;
                $totalRecritas += $recrutas;
                $totalInstruendos += $instruendos;
            }
        }

        $totalGeral = $totalAlistados + $totalRecritas + $totalInstruendos;

        $this->summaryStats = [
            ['label' => 'Total Geral', 'value' => $totalGeral, 'color' => 'primary'],
            ['label' => 'Alistados', 'value' => $totalAlistados, 'color' => 'warning'],
            ['label' => 'Recrutas', 'value' => $totalRecritas, 'color' => 'info'],
            ['label' => 'Instruendos', 'value' => $totalInstruendos, 'color' => 'success'],
        ];

        $this->chartData = [
            'stacked_bar' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Alistados', 'values' => $alistadosValues, 'color' => 'rgba(245, 158, 11, 0.85)'],
                    ['label' => 'Recrutas', 'values' => $recrutasValues, 'color' => 'rgba(59, 130, 246, 0.85)'],
                    ['label' => 'Instruendos', 'values' => $instruendosValues, 'color' => 'rgba(16, 185, 129, 0.85)'],
                ],
                'title' => 'Distribuição por Instituição',
            ],
            'doughnut' => [
                'labels' => ['Alistados', 'Recrutas', 'Instruendos'],
                'values' => [$totalAlistados, $totalRecritas, $totalInstruendos],
                'colors' => [
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(59, 130, 246, 0.85)',
                    'rgba(16, 185, 129, 0.85)',
                ],
                'title' => 'Transição de Estado',
            ],
        ];
    }

    protected function loadFormandosData(?int $institutionId): void
    {
        $this->modalTitle = 'Detalhes - Formandos';

        $institutions = Institution::all();
        $labels = [];
        $formandosStudentsValues = [];
        $formandosCandidatesValues = [];

        $totalFormandosStudents = 0;
        $totalFormandosCandidates = 0;

        foreach ($institutions as $inst) {
            // Formandos (Students com tipo 'Em Formação')
            $fStudents = Student::where('institution_id', $inst->id)
                ->where('student_type', 'like', '%Em Forma%')
                ->count();

            // Formandos (Candidates com tipo 'Em Formação')
            $fCandidates = Candidate::where('institution_id', $inst->id)
                ->where('student_type', 'like', '%Em Forma%')
                ->count();

            if ($fStudents > 0 || $fCandidates > 0) {
                $labels[] = $inst->name;
                $formandosStudentsValues[] = $fStudents;
                $formandosCandidatesValues[] = $fCandidates;
                $totalFormandosStudents += $fStudents;
                $totalFormandosCandidates += $fCandidates;
            }
        }

        $totalGeral = $totalFormandosStudents + $totalFormandosCandidates;

        // Por status (de Students com tipo Em Formação)
        $statuses = Student::whereNotNull('institution_id')
            ->where('student_type', 'like', '%Em Forma%')
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusLabels = [
            'em_formacao' => 'Em Formação',
            'alistado' => 'Alistado',
            'frequenta' => 'Frequenta',
            'aprovado' => 'Aprovado',
            'reprovado' => 'Reprovado',
            'desistente' => 'Desistente',
            'transferido' => 'Transferido',
        ];

        $mappedStatuses = [];
        foreach ($statuses as $key => $val) {
            $mappedStatuses[$statusLabels[$key] ?? ucfirst($key)] = $val;
        }

        // Adicionar os Candidates Em Formação ao doughnut
        if ($totalFormandosCandidates > 0) {
            $mappedStatuses['Candidatos Em Formação'] = $totalFormandosCandidates;
        }

        $statusColors = [
            'rgba(59, 130, 246, 0.85)',
            'rgba(16, 185, 129, 0.85)',
            'rgba(139, 92, 246, 0.85)',
            'rgba(245, 158, 11, 0.85)',
            'rgba(239, 68, 68, 0.85)',
            'rgba(236, 72, 153, 0.85)',
            'rgba(107, 114, 128, 0.85)',
        ];

        $this->summaryStats = [
            ['label' => 'Total Geral', 'value' => $totalGeral, 'color' => 'primary'],
            ['label' => 'Formandos', 'value' => $totalFormandosStudents, 'color' => 'info'],
            ['label' => 'Em Formação', 'value' => $totalFormandosCandidates, 'color' => 'warning'],
            ['label' => 'Instituições', 'value' => count($labels), 'color' => 'success'],
        ];

        $this->chartData = [
            'stacked_bar' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Formandos', 'values' => $formandosStudentsValues, 'color' => 'rgba(59, 130, 246, 0.85)'],
                    ['label' => 'Em Formação', 'values' => $formandosCandidatesValues, 'color' => 'rgba(245, 158, 11, 0.85)'],
                ],
                'title' => 'Formandos por Instituição',
            ],
            'doughnut' => [
                'labels' => array_keys($mappedStatuses),
                'values' => array_values($mappedStatuses),
                'colors' => array_slice($statusColors, 0, count($mappedStatuses)),
                'title' => 'Distribuição por Estado',
            ],
        ];
    }

    protected function loadFormadoresData(?int $institutionId): void
    {
        $this->modalTitle = 'Detalhes - Formadores';

        $institutions = Institution::all();
        $labels = [];
        $activeValues = [];
        $inactiveValues = [];

        $totalActive = 0;
        $totalInactive = 0;

        // Contar formadores por instituição
        foreach ($institutions as $inst) {
            $active = Trainer::where('institution_id', $inst->id)->where('is_active', true)->count();
            $inactive = Trainer::where('institution_id', $inst->id)->where('is_active', false)->count();
            if ($active > 0 || $inactive > 0) {
                $labels[] = $inst->name;
                $activeValues[] = $active;
                $inactiveValues[] = $inactive;
                $totalActive += $active;
                $totalInactive += $inactive;
            }
        }

        // Contar formadores sem instituição atribuída
        $activeSemInst = Trainer::whereNull('institution_id')->where('is_active', true)->count();
        $inactiveSemInst = Trainer::whereNull('institution_id')->where('is_active', false)->count();
        if ($activeSemInst > 0 || $inactiveSemInst > 0) {
            $labels[] = 'Sem Instituição Atribuída';
            $activeValues[] = $activeSemInst;
            $inactiveValues[] = $inactiveSemInst;
            $totalActive += $activeSemInst;
            $totalInactive += $inactiveSemInst;
        }

        $this->summaryStats = [
            ['label' => 'Total Geral', 'value' => $totalActive + $totalInactive, 'color' => 'primary'],
            ['label' => 'Activos', 'value' => $totalActive, 'color' => 'success'],
            ['label' => 'Inactivos', 'value' => $totalInactive, 'color' => 'danger'],
            ['label' => 'Instituições', 'value' => count($labels), 'color' => 'info'],
        ];

        $this->chartData = [
            'stacked_bar' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Activos', 'values' => $activeValues, 'color' => 'rgba(16, 185, 129, 0.85)'],
                    ['label' => 'Inactivos', 'values' => $inactiveValues, 'color' => 'rgba(239, 68, 68, 0.85)'],
                ],
                'title' => 'Formadores por Instituição',
            ],
            'doughnut' => [
                'labels' => ['Activos', 'Inactivos'],
                'values' => [$totalActive, $totalInactive],
                'colors' => ['rgba(16, 185, 129, 0.85)', 'rgba(239, 68, 68, 0.85)'],
                'title' => 'Estado dos Formadores',
            ],
        ];
    }

    protected function loadEscolasData(): void
    {
        $this->modalTitle = 'Detalhes — Escolas de Formação';

        $institutions = Institution::all();
        $labels = [];
        $alunosValues = [];
        $formadoresValues = [];

        $totalAlunos = 0;
        $totalFormadores = 0;
        foreach ($institutions as $inst) {
            $alunos = Candidate::where('institution_id', $inst->id)
                ->where('student_type', 'like', '%Alistado%')
                ->count();
            $alunos += Student::where('institution_id', $inst->id)->count();
            $formadores = Trainer::where('institution_id', $inst->id)->where('is_active', true)->count();

            $labels[] = $inst->name;
            $alunosValues[] = $alunos;
            $formadoresValues[] = $formadores;
            $totalAlunos += $alunos;
            $totalFormadores += $formadores;
        }

        // Contar formadores sem instituição atribuída
        $formadoresSemInst = Trainer::whereNull('institution_id')->where('is_active', true)->count();
        $totalFormadores += $formadoresSemInst;

        $this->summaryStats = [
            ['label' => 'Total Instituições', 'value' => count($labels), 'color' => 'primary'],
            ['label' => 'Total Alunos', 'value' => $totalAlunos, 'color' => 'info'],
            ['label' => 'Total Formadores', 'value' => $totalFormadores, 'color' => 'success'],
            ['label' => 'Rácio Aluno/Formador', 'value' => $totalFormadores > 0 ? round($totalAlunos / $totalFormadores) : 0, 'color' => 'warning'],
        ];

        $barColors = [
            'rgba(59, 130, 246, 0.85)',
            'rgba(16, 185, 129, 0.85)',
            'rgba(245, 158, 11, 0.85)',
            'rgba(139, 92, 246, 0.85)',
            'rgba(236, 72, 153, 0.85)',
            'rgba(20, 184, 166, 0.85)',
            'rgba(99, 102, 241, 0.85)',
            'rgba(244, 63, 94, 0.85)',
        ];

        $formadorColors = [
            'rgba(34, 197, 94, 0.85)',
            'rgba(6, 182, 212, 0.85)',
            'rgba(251, 146, 60, 0.85)',
            'rgba(168, 85, 247, 0.85)',
            'rgba(249, 115, 22, 0.85)',
            'rgba(14, 165, 233, 0.85)',
            'rgba(217, 70, 239, 0.85)',
            'rgba(234, 88, 12, 0.85)',
        ];

        $this->chartData = [
            'stacked_bar' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Alunos/Formandos', 'values' => $alunosValues, 'color' => array_slice($barColors, 0, count($labels))],
                    ['label' => 'Formadores', 'values' => $formadoresValues, 'color' => array_slice($formadorColors, 0, count($labels))],
                ],
                'title' => 'Recursos por Instituição',
            ],
            'doughnut' => [
                'labels' => $labels,
                'values' => $alunosValues,
                'colors' => [
                    'rgba(59, 130, 246, 0.85)',
                    'rgba(16, 185, 129, 0.85)',
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(139, 92, 246, 0.85)',
                    'rgba(236, 72, 153, 0.85)',
                    'rgba(20, 184, 166, 0.85)',
                ],
                'title' => 'Distribuição de Alunos por Escola',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.stats-detail-modal');
    }
}
