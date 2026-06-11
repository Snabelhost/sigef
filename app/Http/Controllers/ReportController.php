<?php

namespace App\Http\Controllers;

use App\Models\{
    User,
    CourseMap,
    CoursePlan,
    Course,
    Subject,
    Trainer,
    Student,
    Candidate,
    StudentClassEnrollment,
    EquipmentAssignment,
    StudentTransferHistory,
    StudentLeave,
    Evaluation,
    StudentClass,
    Attendance,
    Institution,
    Document,
    AcademicYear,
    StudentType,
    ActivityLog
};
use App\Support\AuditReportFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;
use Carbon\Carbon;

class ReportController extends Controller
{
    private function renderReport(Request $request, string $view, array $data, string $filename, array $paper = [])
    {
        $data = array_merge($data, [
            'embeddedMode' => $request->boolean('embedded'),
            'autoPrint' => $request->boolean('autoprint'),
            'paperSize' => $paper['size'] ?? 'a4',
            'paperOrientation' => $paper['orientation'] ?? 'portrait',
        ]);

        if ($request->boolean('embedded')) {
            return response()
                ->view($view, $data)
                ->header('X-Frame-Options', 'SAMEORIGIN');
        }

        $pdf = Pdf::loadView($view, $data);

        if (! empty($paper)) {
            $pdf->setPaper($paper['size'] ?? 'a4', $paper['orientation'] ?? 'portrait');
        }

        return $pdf->stream($filename);
    }

    private function checkAccess()
    {
        $user = auth()->user();
        if (!$user || !($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('panel_user') || $user->hasRole('escola_admin'))) {
            abort(403, 'Sem permissão para aceder aos relatórios.');
        }
    }

    // ═══════════════════════════════════════
    // GESTÃO DE ACESSO
    // ═══════════════════════════════════════

    public function users(Request $request)
    {
        $this->checkAccess();
        $query = User::query();
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);
        $records = $query->orderBy('name')->get();

        return $this->renderReport($request, 'reports.users', [
            'records' => $records,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-utilizadores.pdf');
    }

    public function accessLogs(Request $request)
    {
        $this->checkAccess();

        $query = ActivityLog::with('user')
            ->whereIn('action', ['login', 'logout']);

        if ($request->user) {
            $query->where('user_id', $request->user);
        }

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $records = $query->latest('created_at')->get();
        $selectedUser = $request->user ? User::find($request->user) : null;

        $summary = [
            'total' => $records->count(),
            'logins' => $records->where('action', 'login')->count(),
            'logouts' => $records->where('action', 'logout')->count(),
            'users' => $records->pluck('user_id')->filter()->unique()->count(),
        ];

        return $this->renderReport($request, 'reports.access-logs', [
            'records' => $records,
            'summary' => $summary,
            'selectedUser' => $selectedUser,
            'action' => $request->action,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-acessos.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
    }

    public function auditLogs(Request $request)
    {
        $this->checkAccess();

        $query = Audit::with('user');

        if ($request->user) {
            $query->where('user_id', $request->user);
        }

        if ($request->event) {
            $query->where('event', $request->event);
        }

        if ($request->model) {
            $query->where('auditable_type', $request->model);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $records = $query
            ->latest('created_at')
            ->get()
            ->reject(fn ($audit): bool => AuditReportFormatter::isTechnicalSessionAudit($audit))
            ->values();

        $selectedUser = $request->user ? User::find($request->user) : null;

        $summary = [
            'total' => $records->count(),
            'created' => $records->where('event', 'created')->count(),
            'updated' => $records->where('event', 'updated')->count(),
            'deleted' => $records->where('event', 'deleted')->count(),
            'users' => $records->pluck('user_id')->filter()->unique()->count(),
        ];

        return $this->renderReport($request, 'reports.audit-logs', [
            'records' => $records,
            'summary' => $summary,
            'selectedUser' => $selectedUser,
            'event' => $request->event,
            'model' => $request->model,
            'modelLabel' => $request->model ? AuditReportFormatter::auditModelLabel($request->model) : null,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-auditoria.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
    }

    // ═══════════════════════════════════════
    // CURRÍCULO
    // ═══════════════════════════════════════

    public function courseMaps(Request $request)
    {
        $this->checkAccess();
        $records = CourseMap::with(['course', 'institution', 'academicYear'])->orderBy('id')->get();
        return $this->renderReport($request, 'reports.course-maps', ['records' => $records], 'relatorio-mapas-curso.pdf');
    }

    public function coursePlans(Request $request)
    {
        $this->checkAccess();
        $records = CoursePlan::with(['course', 'academicYear', 'subjects'])->orderBy('id')->get();
        return $this->renderReport($request, 'reports.course-plans', ['records' => $records], 'relatorio-planos-curso.pdf');
    }

    public function courses(Request $request)
    {
        $this->checkAccess();
        $records = Course::with(['institution'])->orderBy('name')->get();
        return $this->renderReport($request, 'reports.courses', ['records' => $records], 'relatorio-cursos.pdf');
    }

    public function subjects(Request $request)
    {
        $this->checkAccess();
        $records = Subject::with(['institution', 'coursePhase'])->orderBy('name')->get();
        return $this->renderReport($request, 'reports.subjects', ['records' => $records], 'relatorio-disciplinas.pdf');
    }

    // ═══════════════════════════════════════
    // GESTÃO ESCOLAR
    // ═══════════════════════════════════════

    public function trainers(Request $request)
    {
        $this->checkAccess();
        $query = Trainer::with(['rank', 'institution', 'institutions']);
        $institution = null;
        if ($request->institution) {
            $query->where(function ($q) use ($request) {
                $q->where('institution_id', $request->institution)
                    ->orWhereHas('institutions', fn($q2) => $q2->where('institutions.id', $request->institution));
            });
            $institution = Institution::find($request->institution);
        }
        $records = $query->orderBy('full_name')->get();

        return $this->renderReport($request, 'reports.trainers', [
            'records' => $records,
            'institution' => $institution,
        ], 'relatorio-formadores.pdf');
    }

    public function cadetes(Request $request)
    {
        $this->checkAccess();
        $query = Student::with(['candidate', 'institution', 'courseMap']);
        $institution = null;
        $class = null;

        if ($request->institution) {
            $query->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }
        if ($request->class) {
            $query->whereHas('classEnrollments', fn($q) => $q->where('class_id', $request->class));
            $class = StudentClass::find($request->class);
        }
        if ($request->date_from) $query->whereDate('enrollment_date', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('enrollment_date', '<=', $request->date_to);

        $records = $query->orderBy('student_number')->get();

        return $this->renderReport($request, 'reports.cadetes', [
            'records' => $records,
            'institution' => $institution,
            'class' => $class,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-cadetes.pdf');
    }

    public function studentsByType(Request $request)
    {
        $this->checkAccess();

        $studentType = trim((string) $request->query('student_type', ''));

        if ($studentType === '') {
            abort(404);
        }

        $institution = null;
        $class = null;
        $academicYear = null;

        $studentQuery = Student::with([
            'candidate',
            'institution',
            'courseMap.course',
            'classEnrollments.studentClass.courseMap.course',
            'classEnrollments.academicYear',
            'studentTypeRelation',
        ]);

        $this->applyStudentTypeFilter($studentQuery, $studentType);

        if ($request->institution) {
            $studentQuery->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }

        if ($request->class) {
            $studentQuery->whereHas('classEnrollments', fn ($q) => $q->where('class_id', $request->class));
            $class = StudentClass::find($request->class);
        }

        if ($request->academic_year) {
            $studentQuery->whereHas('classEnrollments', fn ($q) => $q->where('academic_year_id', $request->academic_year));
            $academicYear = AcademicYear::find($request->academic_year);
        }

        if ($request->date_from) {
            $studentQuery->whereDate('enrollment_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $studentQuery->whereDate('enrollment_date', '<=', $request->date_to);
        }

        $studentRecords = $studentQuery
            ->orderBy('student_number')
            ->get();

        $candidateQuery = Candidate::with(['institution', 'academicYear', 'provenance'])
            ->where(function ($query) use ($studentType) {
                $query->where('student_type', $studentType);

                foreach ($this->studentTypeLikeTerms($studentType) as $term) {
                    $query->orWhere('student_type', 'like', '%'.$term.'%');
                }
            });

        if ($request->institution) {
            $candidateQuery->where('institution_id', $request->institution);
        }

        if ($request->academic_year) {
            $candidateQuery->where('academic_year_id', $request->academic_year);
        }

        if ($request->date_from) {
            $candidateQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $candidateQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $candidateRecords = $candidateQuery
            ->whereNotIn('id', $studentRecords->pluck('candidate_id')->filter()->all())
            ->orderBy('full_name')
            ->get();

        $records = $studentRecords
            ->map(fn (Student $student) => $this->studentTypeReportRowFromStudent($student))
            ->merge($candidateRecords->map(fn (Candidate $candidate) => $this->studentTypeReportRowFromCandidate($candidate)))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $reportLabel = $this->studentTypeReportLabel($studentType);

        return $this->renderReport($request, 'reports.students-by-type', [
            'records' => $records,
            'studentType' => $studentType,
            'reportLabel' => $reportLabel,
            'institution' => $institution,
            'class' => $class,
            'academicYear' => $academicYear,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-'.(Str::slug($reportLabel) ?: 'tipo-aluno').'.pdf');
    }

    public function alistados(Request $request)
    {
        $this->checkAccess();
        $query = Candidate::with(['provenance'])
            ->where('student_type', 'Alistado');
        $academicYear = null;

        if ($request->academic_year) {
            $query->where('academic_year_id', $request->academic_year);
            $academicYear = AcademicYear::find($request->academic_year);
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);

        $records = $query->orderBy('full_name')->get();

        return $this->renderReport($request, 'reports.alistados', [
            'records' => $records,
            'academicYear' => $academicYear,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-alistados.pdf');
    }

    public function enrollments(Request $request)
    {
        $this->checkAccess();
        $query = Student::with(['candidate', 'institution', 'courseMap', 'rank']);
        $class = null;
        $institution = null;

        if ($request->institution) {
            $query->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }
        if ($request->class) {
            $query->whereHas('classEnrollments', fn($q) => $q->where('class_id', $request->class));
            $class = StudentClass::find($request->class);
        }
        $records = $query->orderBy('student_number')->get();

        return $this->renderReport($request, 'reports.enrollments', [
            'records' => $records,
            'class' => $class,
            'institution' => $institution,
        ], 'relatorio-gestao-formandos.pdf');
    }

    public function equipment(Request $request)
    {
        $this->checkAccess();
        $query = EquipmentAssignment::with(['student.candidate']);
        $institution = null;

        if ($request->institution) {
            $query->whereHas('student', fn($q) => $q->where('institution_id', $request->institution));
            $institution = Institution::find($request->institution);
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);

        $records = $query->get();

        return $this->renderReport($request, 'reports.equipment', [
            'records' => $records,
            'institution' => $institution,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-atribuicao-meios.pdf');
    }

    public function transfers(Request $request)
    {
        $this->checkAccess();
        $query = StudentTransferHistory::with(['student.candidate', 'fromInstitution', 'toInstitution']);
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);
        $records = $query->orderBy('created_at', 'desc')->get();

        return $this->renderReport($request, 'reports.transfers', [
            'records' => $records,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-transferencias.pdf');
    }

    public function leaves(Request $request)
    {
        $this->checkAccess();
        $query = StudentLeave::with(['student.candidate']);
        $institution = null;

        if ($request->institution) {
            $query->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }
        if ($request->date_from) $query->whereDate('start_date', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('start_date', '<=', $request->date_to);
        $records = $query->orderBy('start_date', 'desc')->get();

        return $this->renderReport($request, 'reports.leaves', [
            'records' => $records,
            'institution' => $institution,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-dispensas-faltas.pdf');
    }

    // ═══════════════════════════════════════
    // AVALIAÇÃO
    // ═══════════════════════════════════════

    public function evaluations(Request $request)
    {
        $this->checkAccess();
        $query = Evaluation::with(['student.candidate', 'subject']);
        $class = null;
        $institution = null;

        if ($request->institution) {
            $query->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }
        if ($request->class) {
            $query->whereHas('student.classEnrollments', fn($q) => $q->where('class_id', $request->class));
            $class = StudentClass::find($request->class);
        }
        $records = $query->get();

        return $this->renderReport($request, 'reports.evaluations', [
            'records' => $records,
            'class' => $class,
            'institution' => $institution,
        ], 'relatorio-avaliacoes.pdf');
    }

    public function miniPauta(Request $request)
    {
        $this->checkAccess();
        $class = null;
        $institution = null;
        $query = Student::with(['candidate', 'evaluations']);

        if ($request->institution) {
            $query->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }
        if ($request->class) {
            $query->whereHas('classEnrollments', fn($q) => $q->where('class_id', $request->class));
            $class = StudentClass::find($request->class);
        }
        $records = $query->orderBy('student_number')->get();

        return $this->renderReport($request, 'reports.mini-pauta', [
            'records' => $records,
            'class' => $class,
            'institution' => $institution,
        ], 'mini-pauta.pdf');
    }

    public function pautaGeral(Request $request)
    {
        $this->checkAccess();
        $class = null;
        $institution = null;
        $query = Student::with(['candidate', 'evaluations.subject']);
        $subjects = collect();

        if ($request->institution) {
            $query->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }
        if ($request->class) {
            $query->whereHas('classEnrollments', fn($q) => $q->where('class_id', $request->class));
            $class = StudentClass::find($request->class);
            // Get subjects for this class via course plan
            $subjects = Subject::whereHas('coursePhase.coursePlan.courseMaps.studentClasses', function ($q) use ($request) {
                $q->where('classes.id', $request->class);
            })->orderBy('name')->get();
        }

        $records = $query->orderBy('student_number')->get();

        // Fallback: get subjects from evaluations if not found via course plan
        if ($subjects->isEmpty() && $records->isNotEmpty()) {
            $studentIds = $records->pluck('id');
            $evalQuery = Evaluation::whereIn('student_id', $studentIds);
            if ($request->institution) {
                $evalQuery->where('institution_id', $request->institution);
            }
            $subjectIds = $evalQuery->distinct()->pluck('subject_id');
            $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
        }

        return $this->renderReport($request, 'reports.pauta-geral', [
            'records' => $records,
            'class' => $class,
            'subjects' => $subjects,
            'institution' => $institution,
        ], 'pauta-geral.pdf', ['size' => 'a3', 'orientation' => 'landscape']);
    }

    public function certificados(Request $request)
    {
        $this->checkAccess();
        $class = null;
        $institution = null;
        $query = Student::with(['candidate', 'courseMap', 'evaluations']);

        if ($request->institution) {
            $query->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }
        if ($request->class) {
            $query->whereHas('classEnrollments', fn($q) => $q->where('class_id', $request->class));
            $class = StudentClass::find($request->class);
        }
        $records = $query->orderBy('student_number')->get();

        return $this->renderReport($request, 'reports.certificados', [
            'records' => $records,
            'class' => $class,
            'institution' => $institution,
        ], 'relatorio-certificados.pdf');
    }

    public function attendance(Request $request)
    {
        $this->checkAccess();
        $institution = null;

        // Determine dates - default to current month (30 days)
        $startDate = $request->date_from
            ? Carbon::parse($request->date_from)
            : Carbon::now()->startOfMonth();
        $endDate = $request->date_to
            ? Carbon::parse($request->date_to)
            : (clone $startDate)->addDays(29);

        // Limit to 31 days max for readability
        if ($startDate->diffInDays($endDate) > 31) {
            $endDate = (clone $startDate)->addDays(30);
        }

        // Generate day columns
        $days = [];
        $current = clone $startDate;
        while ($current <= $endDate) {
            $days[] = clone $current;
            $current->addDay();
        }
        $totalDays = count($days);

        // Day name abbreviations (Portuguese)
        $dayNames = [
            '1' => 'Seg',
            '2' => 'Ter',
            '3' => 'Qua',
            '4' => 'Qui',
            '5' => 'Sex',
            '6' => 'Sáb',
            '7' => 'Dom',
        ];

        // Institution filter
        if ($request->institution) {
            $institution = Institution::find($request->institution);
        }

        // CIA filter (cia is a field on students table)
        $cia = $request->cia;

        // Get students
        $studentsQuery = Student::with('candidate');
        if ($institution) {
            $studentsQuery->where('institution_id', $institution->id);
        }
        if ($cia) {
            $studentsQuery->where('cia', $cia);
        }
        $students = $studentsQuery->orderBy('cia')->orderBy('id')->get();

        // Build attendance map: student_id => [date => status]
        $attendanceQuery = Attendance::whereBetween('date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ]);
        if ($institution) {
            $attendanceQuery->where('institution_id', $institution->id);
        }
        if ($students->isNotEmpty()) {
            $attendanceQuery->whereIn('student_id', $students->pluck('id'));
        }
        $attendanceRecords = $attendanceQuery->get();

        $attendanceMap = [];
        foreach ($attendanceRecords as $record) {
            $sid = $record->student_id;
            $dateKey = Carbon::parse($record->date)->format('Y-m-d');
            $attendanceMap[$sid][$dateKey] = $record->status;
        }

        return $this->renderReport($request, 'reports.attendance', [
            'students' => $students,
            'cia' => $cia,
            'institution' => $institution,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'days' => $days,
            'totalDays' => $totalDays,
            'dayNames' => $dayNames,
            'attendanceMap' => $attendanceMap,
        ], 'ponto-presencas.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
    }

    // ═══════════════════════════════════════
    // INSTITUIÇÕES & DOCUMENTOS
    // ═══════════════════════════════════════

    public function institutions(Request $request)
    {
        $this->checkAccess();
        $records = Institution::with(['institutionType'])->orderBy('name')->get();
        return $this->renderReport($request, 'reports.institutions', ['records' => $records], 'relatorio-instituicoes.pdf');
    }

    public function documents(Request $request)
    {
        $this->checkAccess();
        $query = Document::with(['senderInstitution']);
        $institution = null;

        if ($request->institution) {
            $query->where('sender_institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);
        $records = $query->orderBy('created_at', 'desc')->get();

        return $this->renderReport($request, 'reports.documents', [
            'records' => $records,
            'institution' => $institution,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-documentos.pdf');
    }

    private function applyStudentTypeFilter($query, string $studentType): void
    {
        $studentTypeId = StudentType::query()->where('name', $studentType)->value('id');

        $query->where(function ($query) use ($studentType, $studentTypeId) {
            $query->where('student_type', $studentType);

            if ($studentTypeId) {
                $query->orWhere('student_type_id', $studentTypeId);
            }

            foreach ($this->studentTypeLikeTerms($studentType) as $term) {
                $query->orWhere('student_type', 'like', '%'.$term.'%');
            }
        });
    }

    private function studentTypeLikeTerms(string $studentType): array
    {
        $normalized = Str::of(Str::ascii($studentType))->lower()->toString();

        return match (true) {
            str_contains($normalized, 'recruta') => ['Recruta'],
            str_contains($normalized, 'instruendo') => ['Instruendo'],
            str_contains($normalized, 'em formacao') => ['Em Forma', 'Em Formação', 'Em Formacao'],
            str_contains($normalized, 'conclu') => ['Conclu'],
            default => [],
        };
    }

    private function studentTypeReportLabel(string $studentType): string
    {
        $normalized = Str::of(Str::ascii($studentType))->lower()->toString();

        return match (true) {
            $normalized === 'formando' => 'Formandos',
            str_contains($normalized, 'recruta') => 'Recrutas',
            str_contains($normalized, 'instruendo') => 'Instruendos',
            str_contains($normalized, 'conclu') => 'Formandos Concluídos',
            default => $studentType,
        };
    }

    private function studentTypeReportRowFromStudent(Student $student): array
    {
        $enrollment = $student->classEnrollments->firstWhere('is_active', true)
            ?? $student->classEnrollments->sortByDesc('enrolled_at')->first();

        return [
            'origin' => 'Gestão de Formandos',
            'name' => $student->candidate?->full_name ?? $student->full_name ?? '-',
            'number' => $student->nuri ?: $student->student_number,
            'bi' => $student->candidate?->id_number ?? $student->bilhete_identidade,
            'institution' => $student->institution?->acronym ?: $student->institution?->name,
            'course' => $enrollment?->studentClass?->courseMap?->course?->name
                ?: $student->courseMap?->course?->name
                ?: $student->courseMap?->name,
            'class' => $enrollment?->studentClass?->name,
            'cia' => $student->cia,
            'platoon' => $student->platoon,
            'section' => $student->section,
            'type' => $student->student_type,
            'date' => $student->enrollment_date,
        ];
    }

    private function studentTypeReportRowFromCandidate(Candidate $candidate): array
    {
        return [
            'origin' => 'Formandos',
            'name' => $candidate->full_name ?? '-',
            'number' => $candidate->nuri ?: $candidate->student_number,
            'bi' => $candidate->id_number,
            'institution' => $candidate->institution?->acronym ?: $candidate->institution?->name,
            'course' => null,
            'class' => null,
            'cia' => $candidate->cia,
            'platoon' => $candidate->platoon,
            'section' => $candidate->section,
            'type' => $candidate->student_type,
            'date' => $candidate->created_at,
        ];
    }
}
