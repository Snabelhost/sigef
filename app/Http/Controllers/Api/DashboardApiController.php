<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Course;
use App\Models\CourseMap;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\Institution;
use App\Models\Evaluation;
use App\Models\Subject;
use App\Services\DashboardCourseStatsService;
use App\Services\GradeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardApiController extends Controller
{
    /**
     * Obter estatísticas gerais do dashboard.
     * Corresponde ao widget StatsOverview.
     */
    public function getStats(Request $request)
    {
        return response()->json($this->resolveStatsOverview($request));
    }

    private function resolveStatsOverview(Request $request): array
    {
        $filters = $this->getFilters($request);
        $institutionId = $filters['institution_id'];
        $courseId = $filters['course_id'];
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $validInstitutionIds = Institution::pluck('id')->toArray();
        $approvedRecruitmentStatuses = ['Apurado', 'approved', 'aprovado', 'admitted', 'apto'];

        $alistadosQuery = Candidate::query()
            ->whereNotNull('institution_id')
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->when($courseId, fn($q) => $q->whereRaw('1 = 0'))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate));

        $alistados = $alistadosQuery
            ->where('student_type', 'like', '%Alistado%')
            ->count();

        $studentsBaseQuery = Student::query()
            ->whereNotNull('institution_id')
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->when($courseId, fn($q) => $q->whereHas('courseMap', fn($courseQuery) => $courseQuery->where('course_id', $courseId)))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate));

        $recrutas = (clone $studentsBaseQuery)
            ->where(function ($q) {
                $q->where('student_type', 'like', '%Recruta%')
                    ->orWhere('student_type', 'like', '%1% Fase%');
            })
            ->count();

        $instruendos = (clone $studentsBaseQuery)
            ->where(function ($q) {
                $q->where('student_type', 'like', '%Instruendo%')
                    ->orWhere('student_type', 'like', '%cadete%')
                    ->orWhere('student_type', 'like', '%praca%')
                    ->orWhere('student_type', 'like', '%pra%a%')
                    ->orWhere('student_type', 'like', '%2% Fase%');
            })
            ->count();

        $recrutasInstruendos = $recrutas + $instruendos;
        $totalAlunos = $alistados + $recrutasInstruendos;

        $emFormacao = (clone $studentsBaseQuery)
            ->where('student_type', 'like', '%Em Forma%')
            ->count();

        $formandosConcluidos = (clone $studentsBaseQuery)
            ->where(function ($q) {
                $q->where('student_type', 'like', '%Formando Conclu%')
                    ->orWhere('student_type', 'like', '%Conclu%');
            })
            ->count();

        $formandos = Candidate::query()
            ->whereIn('student_type', ['Alistado', 'Formando'])
            ->whereIn('status', $approvedRecruitmentStatuses)
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->when($courseId, fn($q) => $q->whereRaw('1 = 0'))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->count();

        $formadores = Trainer::where('is_active', true)
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->when($courseId, fn($q) => $q->whereHas('subjectAuthorizations', fn($subQuery) => $subQuery->where('course_id', $courseId)))
            ->count();

        $escolas = $institutionId ? 1 : count($validInstitutionIds);

        $courseMapsQuery = CourseMap::query()
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->when($courseId, fn($q) => $q->where('course_id', $courseId))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate));

        $courseMaps = (clone $courseMapsQuery)->count();
        $courseMapsActive = (clone $courseMapsQuery)->where('is_active', true)->count();

        $courses = Course::query()
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->when($courseId, fn($q) => $q->whereKey($courseId))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->count();

        $subjects = Subject::query()
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->when($courseId, fn($q) => $q->whereHas('coursePhase', fn($phaseQuery) => $phaseQuery->where('course_id', $courseId)))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->count();

        return [
            'total_alunos' => $totalAlunos,
            'total_alistados' => $alistados,
            'alistados' => $alistados,
            'recrutas' => $recrutas,
            'instruendos' => $instruendos,
            'recrutas_instruendos' => $recrutasInstruendos,
            'formandos' => $formandos,
            'formandos_superior' => $formandos,
            'em_formacao' => $emFormacao,
            'formandos_concluidos' => $formandosConcluidos,
            'em_formacao_concluidos' => $emFormacao + $formandosConcluidos,
            'formadores' => $formadores,
            'formadores_activos' => $formadores,
            'instituicoes_ensino' => $escolas,
            'mapas_planos_curso' => $courseMaps,
            'mapas_planos_curso_activos' => $courseMapsActive,
            'cursos' => $courses,
            'disciplinas' => $subjects,
        ];
    }

    private function getLegacyStats(Request $request)
    {
        $institutionId = $request->query('institution_id');
        $validInstitutionIds = Institution::pluck('id')->toArray();

        // Alistados (Candidates)
        $alistadosQuery = Candidate::query()->whereNotNull('institution_id');
        if ($institutionId) {
            $alistadosQuery->where('institution_id', $institutionId);
        }
        $alistados = $alistadosQuery->where('student_type', 'like', '%Alistado%')->count();

        // Recrutas e Instruendos (Students)
        $studentsBaseQuery = Student::query()->whereNotNull('institution_id');
        if ($institutionId) {
            $studentsBaseQuery->where('institution_id', $institutionId);
        }

        $recrutas = (clone $studentsBaseQuery)
            ->where(function ($q) {
                $q->where('student_type', 'like', '%Recruta%')
                    ->orWhere('student_type', 'like', '%1ª Fase%');
            })
            ->count();

        $instruendos = (clone $studentsBaseQuery)
            ->where(function ($q) {
                $q->where('student_type', 'like', '%Instruendo%')
                    ->orWhere('student_type', 'like', '%cadete%')
                    ->orWhere('student_type', 'like', '%praça%')
                    ->orWhere('student_type', 'like', '%2ª Fase%');
            })
            ->count();

        $totalAlunos = $alistados + $recrutas + $instruendos;

        // Formandos Ensino Superior
        $formandosStudents = (clone $studentsBaseQuery)
            ->where('student_type', 'like', '%Em Forma%')
            ->count();
        $formandosCandidates = Candidate::query()
            ->whereNotNull('institution_id')
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->where('student_type', 'like', '%Em Forma%')
            ->count();
        $formandoSuperior = $formandosStudents + $formandosCandidates;

        // Formadores
        $formadores = Trainer::where('is_active', true)
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->count();

        // Escolas
        $escolas = $institutionId ? 1 : count($validInstitutionIds);

        $stats = [
            'total_alunos' => $totalAlunos,
            'alistados' => $alistados,
            'recrutas' => $recrutas,
            'instruendos' => $instruendos,
            'formandos_superior' => $formandoSuperior,
            'formadores_activos' => $formadores,
            'instituicoes_ensino' => $escolas,
        ];

        return response()->json($stats);
    }

    /**
     * Obter estado de aprovados/reprovados.
     * Corresponde ao widget StudentStatusChart.
     */
    public function getStudentStatus(Request $request)
    {
        $filters = $this->getFilters($request);
        $institutionId = $filters['institution_id'];
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];

        $query = Student::with(['evaluations'])->whereHas('evaluations');
        if ($institutionId) $query->where('institution_id', $institutionId);
        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);

        $students = $query->get();
        $aprovados = 0; $reprovadosNotas = 0; $pendentes = 0;

        foreach ($students as $student) {
            $result = GradeCalculator::result($student);
            if ($result === 'Aprovado') $aprovados++;
            elseif ($result === 'Reprovado') $reprovadosNotas++;
            else $pendentes++;
        }

        // Reprovados por Desistência/Faltas/Baixa
        $leavesData = $this->getLeavesCount($institutionId, $startDate, $endDate);

        return response()->json([
            'aprovados' => $aprovados,
            'pendentes' => $pendentes,
            'reprovados_notas' => $reprovadosNotas,
            'reprovados_faltas' => $leavesData['reprovados_faltas'],
            'reprovados_desistencia' => $leavesData['reprovados_desistencia'],
            'baixa_curso' => $leavesData['baixa_curso'],
        ]);
    }

    /**
     * Obter distribuição por estado de formação.
     * Corresponde ao widget CandidateStatusChart.
     */
    public function getCandidateStatus(Request $request)
    {
        $filters = $this->getFilters($request);
        $institutionId = $filters['institution_id'];
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];

        // Alistados (Candidates)
        $alistados = Candidate::query()
            ->whereNotNull('institution_id')
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->where('student_type', 'like', '%Alistado%')
            ->count();

        // Students counts
        $studentsQuery = Student::query()
            ->whereNotNull('institution_id')
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate));

        return response()->json([
            'alistado' => $alistados,
            'recruta' => (clone $studentsQuery)->where('student_type', 'like', '%Recruta%')->count(),
            'instruendo' => (clone $studentsQuery)->where('student_type', 'like', '%Instruendo%')->count(),
            'formando_superior' => (clone $studentsQuery)->where('student_type', 'like', '%Formando Superior%')->count(),
            'em_formacao' => (clone $studentsQuery)->where('student_type', 'like', '%Em Formação%')->whereNot('student_type', 'like', '%Superior%')->count(),
        ]);
    }

    /**
     * Obter estatísticas por instituição.
     * Corresponde ao widget CandidatesByProvinceChart.
     */
    public function getInstitutionStats(Request $request)
    {
        $filters = $this->getFilters($request);
        $institutionId = $filters['institution_id'];
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];

        $institutions = Institution::when($institutionId, fn($q) => $q->where('id', $institutionId))->get();
        $results = [];

        foreach ($institutions as $inst) {
            $cCount = Candidate::where('institution_id', $inst->id)
                ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
                ->count();
            
            $sCount = Student::where('institution_id', $inst->id)
                ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
                ->count();
            
            $total = $cCount + $sCount;
            if ($total > 0) {
                $results[] = [
                    'institution_id' => $inst->id,
                    'name' => $inst->name,
                    'acronym' => $inst->acronym,
                    'total_alunos' => $total,
                ];
            }
        }

        usort($results, fn($a, $b) => $b['total_alunos'] <=> $a['total_alunos']);

        return response()->json(array_slice($results, 0, 10));
    }

    /**
     * Obter total de alunos do ensino superior por curso.
     */
    public function getStudentsByCourse(Request $request, DashboardCourseStatsService $courseStats)
    {
        return response()->json($courseStats->studentsByCourse($this->getFilters($request)));
    }

    /**
     * Obter lista de formandos recentes.
     * Corresponde ao widget StudentManagement.
     */
    public function getRecentStudents(Request $request)
    {
        $institutionId = $request->query('institution_id');

        $students = Student::query()
            ->with(['institution', 'candidate', 'studentTypeRelation'])
            ->whereNotNull('institution_id')
            ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
            ->where(function ($query) {
                $query->whereNotIn('student_type', ['Alistado', 'Formando'])
                    ->orWhereNull('student_type');
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'nome' => $student->candidate->full_name ?? '-',
                    'instituicao' => $student->institution->name ?? '-',
                    'estado' => $student->student_type ?? '-',
                    'data_inscricao' => $student->enrollment_date ? $student->enrollment_date->format('Y-m-d') : null,
                ];
            });

        return response()->json($students);
    }

    private function getFilters(Request $request)
    {
        return [
            'institution_id' => $request->query('institution_id'),
            'course_id' => $request->query('course_id'),
            'start_date' => $request->query('start_date') ? Carbon::parse($request->query('start_date')) : null,
            'end_date' => $request->query('end_date') ? Carbon::parse($request->query('end_date')) : null,
        ];
    }

    private function getLeavesCount($institutionId, $startDate, $endDate)
    {
        $base = DB::table('student_leaves')
            ->join('students', 'student_leaves.student_id', '=', 'students.id')
            ->when($institutionId, fn($q) => $q->where('students.institution_id', $institutionId))
            ->when($startDate, fn($q) => $q->whereDate('student_leaves.created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('student_leaves.created_at', '<=', $endDate));

        return [
            'reprovados_desistencia' => (clone $base)->where('leave_type', 'reprovado_desistencia')->distinct('student_leaves.student_id')->count(),
            'reprovados_faltas' => (clone $base)->where('leave_type', 'reprovado_faltas')->distinct('student_leaves.student_id')->count(),
            'baixa_curso' => (clone $base)->where('leave_type', 'baixa_curso')->distinct('student_leaves.student_id')->count(),
        ];
    }
}
