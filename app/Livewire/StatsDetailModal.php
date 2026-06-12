<?php

namespace App\Livewire;

use App\Models\Candidate;
use App\Models\CourseMap;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\Institution;
use App\Support\ChartColors;
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
    public function openModal(string $type, ?int $institutionId = null, ?int $courseId = null): void
    {
        $this->modalType = $type;
        $this->chartData = [];
        $this->summaryStats = [];

        match ($type) {
            'alunos' => $this->loadAlunosData($institutionId),
            'alistados' => $this->loadAlistadosData($institutionId),
            'recrutas_instruendos' => $this->loadRecrutasInstruendosData($institutionId),
            'em_formacao_concluidos' => $this->loadEmFormacaoConcluidosData($institutionId),
            'cursos_ano_lectivo' => $this->loadCursosPorAnoLectivoData($institutionId, $courseId),
            'disciplinas_curso' => $this->loadDisciplinasPorCursoData($institutionId, $courseId),
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
                    ['label' => 'Alistados', 'values' => $alistadosValues, 'color' => ChartColors::forLabel('Alistados')],
                    ['label' => 'Recrutas', 'values' => $recrutasValues, 'color' => ChartColors::forLabel('Recrutas')],
                    ['label' => 'Instruendos', 'values' => $instruendosValues, 'color' => ChartColors::forLabel('Instruendos')],
                ],
                'title' => 'Distribuição por Instituição',
            ],
            'doughnut' => [
                'labels' => ['Alistados', 'Recrutas', 'Instruendos'],
                'values' => [$totalAlistados, $totalRecritas, $totalInstruendos],
                'colors' => ChartColors::forLabels(['Alistados', 'Recrutas', 'Instruendos']),
                'title' => 'Transição de Estado',
            ],
        ];
    }

    protected function loadRecrutasInstruendosData(?int $institutionId): void
    {
        $this->modalTitle = 'Recrutas e Instruendos por Instituição';

        $institutions = Institution::query()
            ->when($institutionId, fn ($query) => $query->whereKey($institutionId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $labels = [];
        $recrutasValues = [];
        $instruendosValues = [];

        foreach ($institutions as $institution) {
            $baseQuery = Student::query()
                ->where('institution_id', $institution->id);

            $recrutas = (clone $baseQuery)
                ->where(function ($query) {
                    $query->where('student_type', 'like', '%Recruta%')
                        ->orWhere('student_type', 'like', '%1% Fase%');
                })
                ->count();

            $instruendos = (clone $baseQuery)
                ->where(function ($query) {
                    $query->where('student_type', 'like', '%Instruendo%')
                        ->orWhere('student_type', 'like', '%cadete%')
                        ->orWhere('student_type', 'like', '%praca%')
                        ->orWhere('student_type', 'like', '%praça%')
                        ->orWhere('student_type', 'like', '%2% Fase%');
                })
                ->count();

            if ($recrutas <= 0 && $instruendos <= 0) {
                continue;
            }

            $labels[] = $institution->name;
            $recrutasValues[] = $recrutas;
            $instruendosValues[] = $instruendos;
        }

        if (empty($labels)) {
            $labels = ['Sem dados'];
            $recrutasValues = [0];
            $instruendosValues = [0];
        }

        $this->summaryStats = [];
        $this->chartData = [
            'stacked_bar' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Recrutas', 'values' => $recrutasValues, 'color' => ChartColors::forLabel('Recrutas')],
                    ['label' => 'Instruendos', 'values' => $instruendosValues, 'color' => ChartColors::forLabel('Instruendos')],
                ],
                'title' => 'Total de Recrutas e Instruendos por Instituição',
            ],
        ];
    }

    protected function loadEmFormacaoConcluidosData(?int $institutionId): void
    {
        $this->modalTitle = 'Em formação e Formando Concluído por Instituição';

        $institutions = Institution::query()
            ->when($institutionId, fn ($query) => $query->whereKey($institutionId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $labels = [];
        $emFormacaoValues = [];
        $concluidosValues = [];

        foreach ($institutions as $institution) {
            $baseQuery = Student::query()
                ->where('institution_id', $institution->id);

            $emFormacao = (clone $baseQuery)
                ->where('student_type', 'like', '%Em Forma%')
                ->count();

            $concluidos = (clone $baseQuery)
                ->where(function ($query) {
                    $query->where('student_type', 'like', '%Formando Conclu%')
                        ->orWhere('student_type', 'like', '%Conclu%');
                })
                ->count();

            if ($emFormacao <= 0 && $concluidos <= 0) {
                continue;
            }

            $labels[] = $institution->name;
            $emFormacaoValues[] = $emFormacao;
            $concluidosValues[] = $concluidos;
        }

        if (empty($labels)) {
            $labels = ['Sem dados'];
            $emFormacaoValues = [0];
            $concluidosValues = [0];
        }

        $totalEmFormacao = array_sum($emFormacaoValues);
        $totalConcluidos = array_sum($concluidosValues);

        $this->summaryStats = [];

        if (($totalEmFormacao > 0 && $totalConcluidos <= 0) || ($totalConcluidos > 0 && $totalEmFormacao <= 0)) {
            $this->chartData = [
                'bar' => [
                    'labels' => $labels,
                    'values' => $totalEmFormacao > 0 ? $emFormacaoValues : $concluidosValues,
                    'colors' => ChartColors::forLabels($labels),
                    'title' => $totalEmFormacao > 0
                        ? 'Em formação por Instituição'
                        : 'Formando Concluído por Instituição',
                ],
            ];

            return;
        }

        $this->chartData = [
            'stacked_bar' => [
                'labels' => $labels,
                'stacked' => false,
                'datasets' => [
                    ['label' => 'Em formação', 'values' => $emFormacaoValues, 'color' => ChartColors::forLabel('Em formação')],
                    ['label' => 'Formando Concluído', 'values' => $concluidosValues, 'color' => ChartColors::forLabel('Formando Concluído')],
                ],
                'title' => 'Em formação e Formando Concluído por Instituição',
            ],
        ];
    }

    protected function loadCursosPorAnoLectivoData(?int $institutionId, ?int $courseId = null): void
    {
        $this->modalTitle = 'Cursos por Ano Lectivo';

        $rows = CourseMap::query()
            ->leftJoin('academic_years', 'academic_years.id', '=', 'course_maps.academic_year_id')
            ->when($institutionId, fn ($query) => $query->where('course_maps.institution_id', $institutionId))
            ->when($courseId, fn ($query) => $query->where('course_maps.course_id', $courseId))
            ->selectRaw("COALESCE(NULLIF(academic_years.year, ''), NULLIF(academic_years.name, ''), 'Sem Ano Lectivo') as academic_year_label")
            ->selectRaw('COUNT(DISTINCT course_maps.course_id) as total')
            ->groupByRaw("COALESCE(NULLIF(academic_years.year, ''), NULLIF(academic_years.name, ''), 'Sem Ano Lectivo')")
            ->orderBy('academic_year_label')
            ->get();

        $labels = $rows->pluck('academic_year_label')->map(fn ($label) => (string) $label)->all();
        $values = $rows->pluck('total')->map(fn ($total) => (int) $total)->all();

        if (empty($labels)) {
            $labels = ['Sem dados'];
            $values = [0];
        }

        $this->summaryStats = [];
        $this->chartData = [
            'bar' => [
                'labels' => $labels,
                'values' => $values,
                'colors' => ChartColors::forLabels($labels),
                'title' => 'Total de Cursos por Ano Lectivo',
            ],
        ];
    }

    protected function loadDisciplinasPorCursoData(?int $institutionId, ?int $courseId = null): void
    {
        $this->modalTitle = 'Disciplinas por Curso';

        $rows = DB::table('subjects')
            ->join('course_phases', 'course_phases.id', '=', 'subjects.course_phase_id')
            ->join('courses', 'courses.id', '=', 'course_phases.course_id')
            ->when($institutionId, fn ($query) => $query->where('subjects.institution_id', $institutionId))
            ->when($courseId, fn ($query) => $query->where('courses.id', $courseId))
            ->selectRaw("COALESCE(NULLIF(courses.name, ''), 'Sem Curso') as course_name")
            ->selectRaw('COUNT(DISTINCT subjects.id) as total')
            ->groupByRaw("COALESCE(NULLIF(courses.name, ''), 'Sem Curso')")
            ->orderBy('course_name')
            ->get();

        $labels = $rows->pluck('course_name')->map(fn ($label) => (string) $label)->all();
        $values = $rows->pluck('total')->map(fn ($total) => (int) $total)->all();

        if (empty($labels)) {
            $labels = ['Sem dados'];
            $values = [0];
        }

        $this->summaryStats = [];
        $this->chartData = [
            'bar' => [
                'labels' => $labels,
                'values' => $values,
                'colors' => ChartColors::forLabels($labels),
                'title' => 'Total de Disciplinas por Curso',
            ],
        ];
    }

    protected function loadAlistadosData(?int $institutionId): void
    {
        $this->modalTitle = 'Alistados por Província';

        $rows = Candidate::query()
            ->leftJoin('provinces', 'provinces.id', '=', 'candidates.province_id')
            ->where('candidates.student_type', 'Alistado')
            ->when($institutionId, fn ($query) => $query->where('candidates.institution_id', $institutionId))
            ->selectRaw("COALESCE(NULLIF(provinces.name, ''), NULLIF(candidates.province, ''), 'Sem Província') as province_name, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(provinces.name, ''), NULLIF(candidates.province, ''), 'Sem Província')")
            ->orderByDesc('total')
            ->get();

        $labels = $rows->pluck('province_name')->map(fn ($label) => (string) $label)->all();
        $values = $rows->pluck('total')->map(fn ($total) => (int) $total)->all();

        if (empty($labels)) {
            $labels = ['Sem dados'];
            $values = [0];
        }

        $this->summaryStats = [];
        $this->chartData = [
            'bar' => [
                'labels' => $labels,
                'values' => $values,
                'colors' => ChartColors::forLabels($labels),
                'title' => 'Total de Alistados por Província',
            ],
        ];
    }

    protected function loadFormandosData(?int $institutionId): void
    {
        $this->modalTitle = 'Formandos por Província';

        $rows = Candidate::query()
            ->leftJoin('provinces', 'provinces.id', '=', 'candidates.province_id')
            ->where('candidates.student_type', 'Formando')
            ->when($institutionId, fn ($query) => $query->where('candidates.institution_id', $institutionId))
            ->selectRaw("COALESCE(NULLIF(provinces.name, ''), NULLIF(candidates.province, ''), 'Sem Província') as province_name, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(provinces.name, ''), NULLIF(candidates.province, ''), 'Sem Província')")
            ->orderByDesc('total')
            ->get();

        $labels = $rows->pluck('province_name')->map(fn ($label) => (string) $label)->all();
        $values = $rows->pluck('total')->map(fn ($total) => (int) $total)->all();

        if (empty($labels)) {
            $labels = ['Sem dados'];
            $values = [0];
        }

        $this->summaryStats = [];
        $this->chartData = [
            'bar' => [
                'labels' => $labels,
                'values' => $values,
                'colors' => ChartColors::forLabels($labels),
                'title' => 'Total de Formandos por Província',
            ],
        ];

        return;

        $records = Candidate::query()
            ->with('institution:id,name')
            ->whereIn('student_type', ['Alistado', 'Formando'])
            ->whereIn('status', $approvedRecruitmentStatuses)
            ->when($institutionId, fn ($query) => $query->where('institution_id', $institutionId))
            ->get();

        $groupedByInstitution = $records->groupBy(
            fn (Candidate $candidate): string => $candidate->institution?->name ?: 'Sem Instituição Atribuída'
        );

        $labels = $groupedByInstitution->keys()->values()->all();
        $alistadosValues = [];
        $formandosValues = [];

        foreach ($groupedByInstitution as $institutionCandidates) {
            $alistadosValues[] = $institutionCandidates->where('student_type', 'Alistado')->count();
            $formandosValues[] = $institutionCandidates->where('student_type', 'Formando')->count();
        }

        $totalAlistados = array_sum($alistadosValues);
        $totalFormandos = array_sum($formandosValues);
        $totalGeral = $totalAlistados + $totalFormandos;

        $mappedStatuses = $records
            ->groupBy(fn (Candidate $candidate): string => match (strtolower((string) $candidate->status)) {
                'approved', 'aprovado', 'apurado', 'admitted', 'apto' => 'Apurado',
                default => filled($candidate->status) ? (string) $candidate->status : 'Sem estado',
            })
            ->map(fn ($statusRecords) => $statusRecords->count())
            ->all();

        $statusColors = ChartColors::forLabels(array_keys($mappedStatuses));

        $this->summaryStats = [
            ['label' => 'Total Geral', 'value' => $totalGeral, 'color' => 'primary'],
            ['label' => 'Alistados', 'value' => $totalAlistados, 'color' => 'warning'],
            ['label' => 'Formandos', 'value' => $totalFormandos, 'color' => 'info'],
            ['label' => 'Instituições', 'value' => count($labels), 'color' => 'success'],
        ];

        $this->chartData = [
            'stacked_bar' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Alistados', 'values' => $alistadosValues, 'color' => ChartColors::forLabel('Alistados')],
                    ['label' => 'Formandos', 'values' => $formandosValues, 'color' => ChartColors::forLabel('Formandos')],
                ],
                'title' => 'Formandos por Instituição',
            ],
            'doughnut' => [
                'labels' => array_keys($mappedStatuses),
                'values' => array_values($mappedStatuses),
                'colors' => array_slice($statusColors, 0, max(1, count($mappedStatuses))),
                'title' => 'Distribuição por Estado',
            ],
        ];

        return;

        /*
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
        */
    }

    protected function loadFormadoresData(?int $institutionId): void
    {
        $this->modalTitle = 'Formadores por Instituição';

        $institutions = Institution::query()
            ->when($institutionId, fn ($query) => $query->whereKey($institutionId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $labels = [];
        $values = [];

        foreach ($institutions as $inst) {
            $total = Trainer::query()
                ->where('institution_id', $inst->id)
                ->count();

            if ($total <= 0) {
                continue;
            }

            $labels[] = $inst->name;
            $values[] = $total;
        }

        if (! $institutionId) {
            $semInstituicao = Trainer::query()
                ->whereNull('institution_id')
                ->count();

            if ($semInstituicao > 0) {
                $labels[] = 'Sem Instituição Atribuída';
                $values[] = $semInstituicao;
            }
        }

        if (empty($labels)) {
            $labels = ['Sem dados'];
            $values = [0];
        }

        $this->summaryStats = [];
        $this->chartData = [
            'bar' => [
                'labels' => $labels,
                'values' => $values,
                'colors' => ChartColors::forLabels($labels),
                'title' => 'Total de Formadores por Instituição',
            ],
        ];

        return;

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
                    ['label' => 'Activos', 'values' => $activeValues, 'color' => ChartColors::forLabel('Activos')],
                    ['label' => 'Inactivos', 'values' => $inactiveValues, 'color' => ChartColors::forLabel('Inactivos')],
                ],
                'title' => 'Formadores por Instituição',
            ],
            'doughnut' => [
                'labels' => ['Activos', 'Inactivos'],
                'values' => [$totalActive, $totalInactive],
                'colors' => ChartColors::forLabels(['Activos', 'Inactivos']),
                'title' => 'Estado dos Formadores',
            ],
        ];
    }

    protected function loadEscolasData(): void
    {
        $this->modalTitle = 'Instituições de Ensino por Alunos';

        $institutions = Institution::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $labels = [];
        $values = [];

        foreach ($institutions as $institution) {
            $totalAlunos = Candidate::query()
                ->where('institution_id', $institution->id)
                ->where('student_type', 'like', '%Alistado%')
                ->count();

            $totalAlunos += Student::query()
                ->where('institution_id', $institution->id)
                ->count();

            $labels[] = $institution->name;
            $values[] = $totalAlunos;
        }

        if (empty($labels)) {
            $labels = ['Sem dados'];
            $values = [0];
        }

        $this->summaryStats = [];
        $this->chartData = [
            'bar' => [
                'labels' => $labels,
                'values' => $values,
                'colors' => ChartColors::forLabels($labels),
                'title' => 'Total de Alunos por Instituição',
            ],
        ];

        return;

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

        $institutionColors = ChartColors::forLabels($labels);
        $institutionSecondaryColors = ChartColors::forLabels($labels, 0.55);

        $this->chartData = [
            'stacked_bar' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Alunos/Formandos', 'values' => $alunosValues, 'color' => $institutionColors],
                    ['label' => 'Formadores', 'values' => $formadoresValues, 'color' => $institutionSecondaryColors],
                ],
                'title' => 'Recursos por Instituição',
            ],
            'doughnut' => [
                'labels' => $labels,
                'values' => $alunosValues,
                'colors' => $institutionColors,
                'title' => 'Distribuição de Alunos por Escola',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.stats-detail-modal');
    }
}
