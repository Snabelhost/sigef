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
    ActivityLog,
    Effective,
    TrainerSubjectAuthorization
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
        $query = User::with('roles');

        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);

        $users = $query->orderBy('name')->get();
        $roles = $users
            ->flatMap(fn (User $user) => $user->roles)
            ->unique('id')
            ->sortBy('name')
            ->values();

        $records = $roles
            ->map(function ($role) use ($users): array {
                $roleUsers = $users->filter(fn (User $user): bool => $user->roles->contains('id', $role->id));

                return [
                    'profile' => $this->roleLabel((string) $role->name),
                    'total' => $roleUsers->count(),
                    'active' => $roleUsers->where('is_active', true)->count(),
                    'inactive' => $roleUsers->where('is_active', false)->count(),
                ];
            })
            ->values();

        $withoutRole = $users->filter(fn (User $user): bool => $user->roles->isEmpty());

        if ($withoutRole->isNotEmpty()) {
            $records->push([
                'profile' => 'Sem perfil',
                'total' => $withoutRole->count(),
                'active' => $withoutRole->where('is_active', true)->count(),
                'inactive' => $withoutRole->where('is_active', false)->count(),
            ]);
        }

        $summary = [
            'assignments' => $records->sum('total'),
            'unique' => $users->count(),
            'active' => $users->where('is_active', true)->count(),
            'inactive' => $users->where('is_active', false)->count(),
        ];

        return $this->renderReport($request, 'reports.users', [
            'records' => $records,
            'summary' => $summary,
            'reportTotal' => $summary['unique'],
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-utilizadores.pdf');
    }

    public function accessLogs(Request $request)
    {
        $this->checkAccess();

        $activityQuery = ActivityLog::with('user')
            ->whereIn('action', ['login', 'logout']);

        if ($request->user) {
            $activityQuery->where('user_id', $request->user);
        }

        if ($request->action) {
            $activityQuery->where('action', $request->action);
        }

        if ($request->date_from) {
            $activityQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $activityQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $activityQuery->latest('created_at')->get();
        $lastActivities = $activities
            ->filter(fn (ActivityLog $log): bool => filled($log->user_id))
            ->groupBy('user_id')
            ->map(fn ($items) => $items->first());

        $userQuery = User::with('roles')->orderBy('name');

        if ($request->user) {
            $userQuery->whereKey($request->user);
        } elseif ($request->action || $request->date_from || $request->date_to) {
            $userQuery->whereIn('id', $lastActivities->keys()->all());
        }

        $users = $userQuery->get();
        $records = $users->map(function (User $user) use ($lastActivities): array {
            $activity = $lastActivities->get($user->id);

            return [
                'name' => $user->name,
                'email' => $user->email,
                'profile' => $user->roles->map(fn ($role) => $this->roleLabel((string) $role->name))->implode(', ') ?: 'Sem perfil',
                'state' => $user->is_active ? 'Activo' : 'Inactivo',
                'last_access' => $activity?->created_at ?? $user->last_login_at,
            ];
        });
        $selectedUser = $request->user ? User::find($request->user) : null;

        $summary = [
            'total' => $records->count(),
            'active' => $records->where('state', 'Activo')->count(),
            'inactive' => $records->where('state', 'Inactivo')->count(),
            'with_access' => $records->filter(fn (array $row): bool => filled($row['last_access']))->count(),
        ];

        return $this->renderReport($request, 'reports.access-logs', [
            'records' => $records,
            'summary' => $summary,
            'selectedUser' => $selectedUser,
            'action' => $request->action,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-acessos.pdf');
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
        $maps = CourseMap::with(['course.phases', 'institution', 'academicYear'])->orderBy('id')->get();
        $records = $this->curriculumRowsFromCourseMaps($maps);

        return $this->renderReport($request, 'reports.course-maps', ['records' => $records], 'relatorio-mapas-curso.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
    }

    public function coursePlans(Request $request)
    {
        $this->checkAccess();
        $plans = CoursePlan::with(['course.institution', 'academicYear', 'subjects.coursePhase'])->orderBy('id')->get();
        $records = $this->curriculumRowsFromCoursePlans($plans);

        return $this->renderReport($request, 'reports.course-plans', ['records' => $records], 'relatorio-planos-curso.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
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
        ], 'relatorio-formadores.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
    }

    public function trainerSubjects(Request $request)
    {
        $this->checkAccess();

        $query = TrainerSubjectAuthorization::with(['trainer.rank', 'trainer.institution', 'subject.coursePhase', 'course', 'institution'])
            ->whereHas('trainer', fn ($query) => $query->whereNotNull('full_name')->where('full_name', '!=', ''))
            ->whereHas('subject', fn ($query) => $query->whereNotNull('name')->where('name', '!=', ''))
            ->orderBy('trainer_id');

        $institution = null;

        if ($request->institution) {
            $query->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }

        $records = $this->cleanTrainerSubjectReportRecords($query->get());

        if ($records->isEmpty()) {
            $records = Trainer::with(['rank', 'institution', 'classAssignments.subject.coursePhase', 'classAssignments.studentClass.courseMap.course'])
                ->when($request->institution, fn ($query) => $query->where('institution_id', $request->institution))
                ->orderBy('full_name')
                ->get()
                ->flatMap(fn (Trainer $trainer) => $trainer->classAssignments->map(fn ($assignment) => (object) [
                    'trainer' => $trainer,
                    'subject' => $assignment->subject,
                    'course' => $assignment->studentClass?->courseMap?->course,
                    'institution' => $trainer->institution,
                ]));

            $records = $this->cleanTrainerSubjectReportRecords($records);
        }

        return $this->renderReport($request, 'reports.trainer-subjects', [
            'records' => $records,
            'institution' => $institution,
        ], 'mapa-formadores-disciplinas.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
    }

    public function effectives(Request $request)
    {
        $this->checkAccess();

        $query = Effective::with('institution')->orderBy('full_name');
        $institution = null;

        if ($request->institution) {
            $query->where('institution_id', $request->institution);
            $institution = Institution::find($request->institution);
        }

        $records = $query->get();

        return $this->renderReport($request, 'reports.effectives', [
            'records' => $records,
            'institution' => $institution,
        ], 'relatorio-efectivos.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
    }

    public function studentsByProvenance(Request $request)
    {
        $this->checkAccess();

        $studentQuery = Student::with([
            'candidate.provenance',
            'institution',
            'courseMap.course',
            'classEnrollments.studentClass.courseMap.course',
            'classEnrollments.academicYear',
            'rank',
            'provenance',
        ]);

        if ($request->institution) {
            $studentQuery->where('institution_id', $request->institution);
        }

        if ($request->class) {
            $studentQuery->whereHas('classEnrollments', fn ($query) => $query->where('class_id', $request->class));
        }

        $records = $studentQuery
            ->orderBy('student_number')
            ->get()
            ->map(fn (Student $student) => $this->provenanceReportRow($student))
            ->sortBy(['province', 'provenance', 'name'])
            ->values();

        $institution = $request->institution ? Institution::find($request->institution) : null;
        $class = $request->class ? StudentClass::find($request->class) : null;

        return $this->renderReport($request, 'reports.students-by-provenance', [
            'records' => $records,
            'institution' => $institution,
            'class' => $class,
        ], 'lista-por-orgao-proveniencia.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
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
            ->filter(fn (array $record): bool => $this->hasReportPersonName($record['name'] ?? null))
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
        ], 'relatorio-'.(Str::slug($reportLabel) ?: 'tipo-aluno').'.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
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
        $query = Student::with([
            'candidate',
            'institution',
            'courseMap.course',
            'courseMap.academicYear',
            'rank',
            'classEnrollments.studentClass.courseMap.course',
            'classEnrollments.studentClass.courseMap.academicYear',
            'classEnrollments.studentClass.academicYear',
            'classEnrollments.academicYear',
            'candidate.academicYear',
        ]);
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
        ], 'relatorio-gestao-formandos.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
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

        $records = $this->equipmentReportRows($query->get());

        return $this->renderReport($request, 'reports.equipment', [
            'records' => $records,
            'institution' => $institution,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-atribuicao-meios.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
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
        ], 'relatorio-transferencias.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
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
        $records = $this->leaveReportRows($query->orderBy('start_date', 'desc')->get());

        return $this->renderReport($request, 'reports.leaves', [
            'records' => $records,
            'institution' => $institution,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-dispensas-faltas.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
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
        $records = $this->evaluationReportRows($query->get());

        return $this->renderReport($request, 'reports.evaluations', [
            'records' => $records,
            'class' => $class,
            'institution' => $institution,
        ], 'relatorio-avaliacoes.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
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
        $records = $this->miniPautaReportStudents($query->orderBy('student_number')->get());

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

        $records = $this->miniPautaReportStudents($query->orderBy('student_number')->get());

        // Fallback: get subjects from evaluations if not found via course plan
        if ($subjects->isEmpty() && $records->isNotEmpty()) {
            $evaluations = $records->flatMap(fn (Student $student) => $student->evaluations);

            if ($request->institution) {
                $evaluations = $evaluations->where('institution_id', (int) $request->institution);
            }

            $subjectIds = $evaluations->pluck('subject_id')->filter()->unique();
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
        $records = $this->miniPautaReportStudents($query->orderBy('student_number')->get());

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
        $rawStudents = $studentsQuery->orderBy('cia')->orderBy('id')->get();
        $students = $this->attendanceReportStudents($rawStudents);
        $studentSourceIds = $students->mapWithKeys(fn (Student $student): array => [
            $student->getKey() => $student->getAttribute('attendance_source_ids') ?: [$student->getKey()],
        ]);
        $sourceStudentIds = $studentSourceIds->flatten()->filter()->unique()->values();
        $sourceToDisplayStudent = [];

        foreach ($studentSourceIds as $displayStudentId => $sourceIds) {
            foreach ($sourceIds as $sourceId) {
                $sourceToDisplayStudent[(int) $sourceId] = (int) $displayStudentId;
            }
        }

        // Build attendance map: student_id => [date => status]
        $attendanceQuery = Attendance::whereBetween('date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ]);
        if ($institution) {
            $attendanceQuery->where('institution_id', $institution->id);
        }
        if ($sourceStudentIds->isNotEmpty()) {
            $attendanceQuery->whereIn('student_id', $sourceStudentIds);
        }
        $attendanceRecords = $attendanceQuery->get();

        $attendanceMap = [];
        foreach ($attendanceRecords as $record) {
            $sid = $sourceToDisplayStudent[(int) $record->student_id] ?? (int) $record->student_id;
            $dateKey = Carbon::parse($record->date)->format('Y-m-d');
            $attendanceMap[$sid][$dateKey] = $this->mergeAttendanceStatus($attendanceMap[$sid][$dateKey] ?? null, $record->status);
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
            'reportTotal' => $students->count(),
        ], 'ponto-presencas.pdf', ['size' => 'a4', 'orientation' => 'landscape']);
    }

    // ═══════════════════════════════════════
    // INSTITUIÇÕES & DOCUMENTOS
    // ═══════════════════════════════════════

    public function institutions(Request $request)
    {
        $this->checkAccess();
        $records = $this->institutionReportRows(Institution::with(['institutionType'])->orderBy('name')->get());

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
        $records = $this->documentReportRows($query->orderBy('created_at', 'desc')->get());

        return $this->renderReport($request, 'reports.documents', [
            'records' => $records,
            'institution' => $institution,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ], 'relatorio-documentos.pdf');
    }

    private function cleanTrainerSubjectReportRecords($records)
    {
        return collect($records)
            ->filter(function ($record): bool {
                $trainerName = trim((string) data_get($record, 'trainer.full_name', ''));
                $subjectName = trim((string) data_get($record, 'subject.name', ''));

                return $trainerName !== '' && $subjectName !== '';
            })
            ->unique(function ($record): string {
                $trainerKey = data_get($record, 'trainer.id')
                    ?: Str::of((string) data_get($record, 'trainer.full_name', ''))->ascii()->lower()->squish()->toString();
                $subjectKey = data_get($record, 'subject.id')
                    ?: Str::of((string) data_get($record, 'subject.name', ''))->ascii()->lower()->squish()->toString();
                $phaseKey = data_get($record, 'subject.coursePhase.id')
                    ?: Str::of((string) data_get($record, 'subject.coursePhase.name', ''))->ascii()->lower()->squish()->toString();

                return implode('|', [$trainerKey, $subjectKey, $phaseKey]);
            })
            ->sortBy(fn ($record): string => Str::of(
                (string) data_get($record, 'trainer.full_name', '').'|'.(string) data_get($record, 'subject.name', '')
            )->ascii()->lower()->squish()->toString())
            ->values();
    }

    private function equipmentReportRows($assignments)
    {
        return collect($assignments)
            ->filter(function (EquipmentAssignment $assignment): bool {
                $student = $assignment->student;
                $name = $student?->candidate?->full_name ?? $student?->full_name;

                return $this->hasReportPersonName($name);
            })
            ->groupBy(function (EquipmentAssignment $assignment): string {
                $student = $assignment->student;

                return (string) ($student?->getKey()
                    ?: ($student?->nuri ?: $student?->student_number ?: $student?->full_name ?: $assignment->getKey()));
            })
            ->map(function ($studentAssignments): array {
                $first = $studentAssignments->first();
                $student = $first->student;
                $candidate = $student?->candidate;

                $equipmentGroups = $studentAssignments
                    ->groupBy(fn (EquipmentAssignment $assignment): string => Str::of((string) ($assignment->equipment_name ?: '-'))->squish()->lower()->toString())
                    ->map(function ($items): array {
                        $firstItem = $items->first();
                        $name = Str::of((string) ($firstItem->equipment_name ?: '-'))->squish()->toString();
                        $quantity = (int) $items->sum(fn (EquipmentAssignment $assignment): int => (int) ($assignment->quantity ?: 1));

                        return [
                            'name' => $name,
                            'quantity' => max($quantity, 1),
                        ];
                    })
                    ->sortBy('name')
                    ->values();

                $dates = $studentAssignments
                    ->map(function (EquipmentAssignment $assignment): ?string {
                        $date = $assignment->assigned_at ?: $assignment->created_at;

                        return $date ? Carbon::parse($date)->format('d/m/Y') : null;
                    })
                    ->filter()
                    ->unique()
                    ->values();

                $activeCount = $studentAssignments->whereNull('returned_at')->count();
                $returnedCount = $studentAssignments->whereNotNull('returned_at')->count();

                return [
                    'name' => $candidate?->full_name ?? $student?->full_name ?? '-',
                    'number' => $student?->nuri ?? $student?->nip ?? $student?->student_number ?? '-',
                    'equipment' => $equipmentGroups,
                    'quantity' => $equipmentGroups->sum('quantity'),
                    'dates' => $dates,
                    'status' => match (true) {
                        $activeCount > 0 && $returnedCount > 0 => 'Activo / Devolvido',
                        $returnedCount > 0 => 'Devolvido',
                        default => 'Activo',
                    },
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function leaveReportRows($leaves)
    {
        return collect($leaves)
            ->filter(function (StudentLeave $leave): bool {
                $student = $leave->student;
                $name = $student?->candidate?->full_name ?? $student?->full_name;

                return $this->hasReportPersonName($name);
            })
            ->groupBy(function (StudentLeave $leave): string {
                $student = $leave->student;

                return (string) ($student?->getKey()
                    ?: ($student?->nuri ?: $student?->student_number ?: $student?->full_name ?: $leave->getKey()));
            })
            ->map(function ($studentLeaves): array {
                $first = $studentLeaves->first();
                $student = $first->student;
                $candidate = $student?->candidate;

                $occurrences = $studentLeaves
                    ->sortByDesc('start_date')
                    ->values()
                    ->map(function (StudentLeave $leave): array {
                        $start = $leave->start_date ? Carbon::parse($leave->start_date)->startOfDay() : null;
                        $end = $leave->end_date ? Carbon::parse($leave->end_date)->startOfDay() : null;
                        $days = $start && $end ? (int) $start->diffInDays($end) + 1 : null;
                        $reason = trim((string) $leave->reason);

                        return [
                            'type' => Str::headline((string) ($leave->leave_type ?: '-')),
                            'reason' => $reason !== '' ? $reason : '-',
                            'period' => $this->leavePeriodLabel($start, $end),
                            'days' => $days,
                            'status' => Str::headline((string) ($leave->status ?: '-')),
                        ];
                    });

                return [
                    'name' => $candidate?->full_name ?? $student?->full_name ?? '-',
                    'number' => $student?->nuri ?? $student?->nip ?? $student?->student_number ?? '-',
                    'occurrences' => $occurrences,
                    'occurrence_count' => $occurrences->count(),
                    'total_days' => $occurrences->sum(fn (array $occurrence): int => (int) ($occurrence['days'] ?? 0)),
                    'statuses' => $occurrences->pluck('status')->filter()->unique()->implode(' / ') ?: '-',
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function leavePeriodLabel(?Carbon $start, ?Carbon $end): string
    {
        if (! $start && ! $end) {
            return '-';
        }

        if ($start && $end && $start->isSameDay($end)) {
            return $start->format('d/m/Y');
        }

        return ($start?->format('d/m/Y') ?? '-').' a '.($end?->format('d/m/Y') ?? '-');
    }

    private function evaluationReportRows($evaluations)
    {
        return collect($evaluations)
            ->filter(function (Evaluation $evaluation): bool {
                $student = $evaluation->student;
                $name = $student?->candidate?->full_name ?? $student?->full_name;

                return $this->hasReportPersonName($name);
            })
            ->groupBy(function (Evaluation $evaluation): string {
                $student = $evaluation->student;

                return (string) ($student?->getKey()
                    ?: ($student?->nuri ?: $student?->student_number ?: $student?->full_name ?: $evaluation->getKey()));
            })
            ->map(function ($studentEvaluations): array {
                $first = $studentEvaluations->first();
                $student = $first->student;
                $candidate = $student?->candidate;

                $items = $studentEvaluations
                    ->sortBy([
                        fn (Evaluation $evaluation): string => (string) ($evaluation->subject?->name ?? ''),
                        fn (Evaluation $evaluation): string => (string) ($evaluation->evaluated_at ?? $evaluation->created_at ?? ''),
                    ])
                    ->values()
                    ->map(function (Evaluation $evaluation): array {
                        $score = $evaluation->score;
                        $date = $evaluation->evaluated_at ?: $evaluation->created_at;

                        return [
                            'subject' => $evaluation->subject?->name ?? '-',
                            'type' => Str::headline((string) ($evaluation->evaluation_type ?: '-')),
                            'score' => is_numeric($score) ? number_format((float) $score, 2) : '-',
                            'date' => $date ? Carbon::parse($date)->format('d/m/Y') : '-',
                        ];
                    });

                $scores = $studentEvaluations
                    ->pluck('score')
                    ->filter(fn ($score): bool => is_numeric($score))
                    ->map(fn ($score): float => (float) $score);

                return [
                    'name' => $candidate?->full_name ?? $student?->full_name ?? '-',
                    'number' => $student?->nuri ?? $student?->nip ?? $student?->student_number ?? '-',
                    'evaluations' => $items,
                    'evaluation_count' => $items->count(),
                    'average' => $scores->isNotEmpty() ? number_format($scores->avg(), 2) : '-',
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function miniPautaReportStudents($students)
    {
        return collect($students)
            ->filter(fn (Student $student): bool => $this->hasReportPersonName($this->studentReportName($student)))
            ->groupBy(fn (Student $student): string => Str::of($this->studentReportName($student))->ascii()->lower()->squish()->toString())
            ->map(function ($studentGroup): Student {
                $student = $studentGroup
                    ->sortByDesc(fn (Student $candidate): int => $candidate->evaluations->count())
                    ->first();

                $student->setRelation(
                    'evaluations',
                    $studentGroup
                        ->flatMap(fn (Student $candidate) => $candidate->evaluations)
                        ->unique('id')
                        ->values()
                );

                return $student;
            })
            ->sortBy(fn (Student $student): string => Str::of($this->studentReportName($student))->ascii()->lower()->squish()->toString())
            ->values();
    }

    private function attendanceReportStudents($students)
    {
        return collect($students)
            ->filter(fn (Student $student): bool => $this->hasReportPersonName($this->studentReportName($student)))
            ->groupBy(fn (Student $student): string => Str::of($this->studentReportName($student))->ascii()->lower()->squish()->toString())
            ->map(function ($studentGroup): Student {
                $student = $studentGroup->sortBy('id')->first();
                $student->setAttribute('attendance_source_ids', $studentGroup->pluck('id')->filter()->unique()->values()->all());

                return $student;
            })
            ->sortBy(fn (Student $student): string => Str::of($this->studentReportName($student))->ascii()->lower()->squish()->toString())
            ->values();
    }

    private function mergeAttendanceStatus(?string $current, ?string $incoming): string
    {
        $incoming = trim((string) $incoming);

        if ($incoming === '') {
            return (string) $current;
        }

        if (! $current) {
            return $incoming;
        }

        $priority = [
            Attendance::STATUS_ABSENT => 4,
            Attendance::STATUS_JUSTIFIED => 3,
            Attendance::STATUS_LATE => 2,
            Attendance::STATUS_PRESENT => 1,
        ];

        return ($priority[$incoming] ?? 0) > ($priority[$current] ?? 0)
            ? $incoming
            : $current;
    }

    private function institutionReportRows($institutions)
    {
        return collect($institutions)
            ->filter(fn (Institution $institution): bool => trim((string) $institution->name) !== '')
            ->groupBy(fn (Institution $institution): string => $this->institutionReportKey($institution->name))
            ->map(function ($institutionGroup): array {
                $name = $this->bestReportValue($institutionGroup->pluck('name')->all());
                $typeValues = $institutionGroup
                    ->map(fn (Institution $institution): ?string => $institution->institutionType?->name ?? $institution->getAttribute('type'))
                    ->all();
                $locationValues = $institutionGroup
                    ->map(fn (Institution $institution): ?string => $institution->getAttribute('location') ?? $institution->address)
                    ->all();
                $contactValues = $institutionGroup
                    ->map(fn (Institution $institution): ?string => $institution->phone ?: $institution->email)
                    ->all();

                return [
                    'name' => $name,
                    'acronym' => $this->bestReportValue($institutionGroup->pluck('acronym')->all()),
                    'type' => $this->bestReportValue($typeValues),
                    'location' => $this->bestReportValue($locationValues),
                    'contact' => $this->bestReportValue($contactValues),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function bestReportValue(array $values): string
    {
        $cleanValues = collect($values)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '' && $value !== '-')
            ->unique(fn (string $value): string => Str::of($value)->ascii()->lower()->squish()->toString())
            ->values();

        if ($cleanValues->isEmpty()) {
            return '-';
        }

        return $cleanValues
            ->sortByDesc(function (string $value): int {
                $hasLowercase = preg_match('/[a-záàâãéèêíìóòôõúùç]/u', $value) ? 1000 : 0;

                return $hasLowercase + mb_strlen($value);
            })
            ->first();
    }

    private function institutionReportKey(mixed $name): string
    {
        $normalized = Str::of((string) $name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        $words = array_filter(
            explode(' ', $normalized),
            fn (string $word): bool => ! in_array($word, ['de', 'da', 'do', 'das', 'dos'], true)
        );

        return implode(' ', $words);
    }

    private function documentReportRows($documents)
    {
        $priorityLabels = Document::getPriorityOptions();
        $statusLabels = Document::getStatusOptions();

        $seenTitles = [];
        $seenReferences = [];

        return collect($documents)
            ->filter(fn (Document $document): bool => trim((string) $document->title) !== '')
            ->sortByDesc(fn (Document $document) => $document->sent_at ?? $document->created_at)
            ->filter(function (Document $document) use (&$seenTitles, &$seenReferences): bool {
                $titleKey = Str::of((string) $document->title)->ascii()->lower()->squish()->toString();
                $referenceKey = Str::of((string) $document->reference_number)->ascii()->lower()->squish()->toString();

                if (($titleKey !== '' && isset($seenTitles[$titleKey]))
                    || ($referenceKey !== '' && isset($seenReferences[$referenceKey]))) {
                    return false;
                }

                if ($titleKey !== '') {
                    $seenTitles[$titleKey] = true;
                }

                if ($referenceKey !== '') {
                    $seenReferences[$referenceKey] = true;
                }

                return true;
            })
            ->map(function (Document $document) use ($priorityLabels, $statusLabels): array {

                return [
                    'title' => trim((string) $document->title) ?: '-',
                    'reference' => trim((string) $document->reference_number) ?: '-',
                    'origin' => $document->senderInstitution?->name ?? $document->senderInstitution?->acronym ?? '-',
                    'priority' => $priorityLabels[$document->priority] ?? Str::headline((string) ($document->priority ?: '-')),
                    'status' => $statusLabels[$document->status] ?? Str::headline((string) ($document->status ?: '-')),
                    'date' => $document->sent_at?->format('d/m/Y') ?? ($document->created_at?->format('d/m/Y') ?? '-'),
                ];
            })
            ->sortByDesc(fn (array $record): string => $record['date'])
            ->values();
    }

    private function curriculumRowsFromCourseMaps($maps)
    {
        return $maps->flatMap(function (CourseMap $map) {
            $subjects = Subject::with('coursePhase')
                ->whereHas('coursePhase', fn ($query) => $query->where('course_id', $map->course_id))
                ->orderBy('course_phase_id')
                ->orderBy('name')
                ->get();

            if ($subjects->isEmpty()) {
                return [[
                    'course' => $map->course?->name,
                    'year' => $map->academicYear?->year ?? $map->academicYear?->name,
                    'phase' => '-',
                    'subject' => '-',
                    'workload' => '-',
                    'mandatory' => '-',
                    'minimum_grade' => '-',
                    'institution' => $map->institution?->name,
                    'state' => $map->is_active ? 'Activo' : 'Inactivo',
                ]];
            }

            return $subjects->map(fn (Subject $subject): array => [
                'course' => $map->course?->name,
                'year' => $map->academicYear?->year ?? $map->academicYear?->name,
                'phase' => $subject->coursePhase?->name ?? '-',
                'subject' => $subject->name,
                'workload' => $subject->workload_hours ?: '-',
                'mandatory' => 'Sim',
                'minimum_grade' => data_get($subject, 'minimum_grade') ?? data_get($subject, 'min_grade') ?? data_get($subject, 'passing_grade') ?? '10,00',
                'institution' => $map->institution?->name,
                'state' => $map->is_active ? 'Activo' : 'Inactivo',
            ]);
        })->values();
    }

    private function curriculumRowsFromCoursePlans($plans)
    {
        return $plans->flatMap(function (CoursePlan $plan) {
            $subjects = $plan->subjects->sortBy([
                ['course_phase_id', 'asc'],
                ['name', 'asc'],
            ]);

            if ($subjects->isEmpty()) {
                return [[
                    'course' => $plan->course?->name,
                    'year' => $plan->academicYear?->year ?? $plan->academicYear?->name,
                    'phase' => '-',
                    'subject' => '-',
                    'workload' => '-',
                    'mandatory' => '-',
                    'minimum_grade' => '-',
                    'institution' => $plan->course?->institution?->name,
                    'state' => $plan->is_active ? 'Activo' : 'Inactivo',
                ]];
            }

            return $subjects->map(fn (Subject $subject): array => [
                'course' => $plan->course?->name,
                'year' => $plan->academicYear?->year ?? $plan->academicYear?->name,
                'phase' => $subject->coursePhase?->name ?? '-',
                'subject' => $subject->name,
                'workload' => $subject->workload_hours ?: '-',
                'mandatory' => 'Sim',
                'minimum_grade' => data_get($subject, 'minimum_grade') ?? data_get($subject, 'min_grade') ?? data_get($subject, 'passing_grade') ?? '10,00',
                'institution' => $plan->course?->institution?->name,
                'state' => $plan->is_active ? 'Activo' : 'Inactivo',
            ]);
        })->values();
    }

    private function provenanceReportRow(Student $student): array
    {
        $enrollment = $student->classEnrollments->firstWhere('is_active', true)
            ?? $student->classEnrollments->sortByDesc('enrolled_at')->first();

        return [
            'province' => $student->candidate?->getAttribute('province') ?: 'Sem Provincia',
            'provenance' => $student->candidate?->provenance?->name ?? $student->provenance?->name ?? 'Sem Orgao',
            'name' => $student->candidate?->full_name ?? $student->full_name ?? '-',
            'gender' => $student->candidate?->gender ?? $student->gender ?? '-',
            'age' => $student->candidate?->birth_date ? $student->candidate->birth_date->age : ($student->birth_date?->age ?? '-'),
            'nip' => $student->nuri ?: $student->student_number,
            'rank' => $student->rank?->name ?? $student->candidate?->rank?->name ?? '-',
            'course' => $enrollment?->studentClass?->courseMap?->course?->name
                ?: $student->courseMap?->course?->name
                ?: '-',
            'frequency' => $enrollment?->studentClass?->courseMap?->academicYear?->name
                ?: $enrollment?->academicYear?->name
                ?: '-',
            'shift' => $enrollment?->studentClass?->shift ?? '-',
            'class' => $enrollment?->studentClass?->name ?? '-',
        ];
    }

    private function roleLabel(string $role): string
    {
        return Str::of($role)
            ->replace(['_', '-'], ' ')
            ->headline()
            ->replace('Super Admin', 'Administrador')
            ->replace('Panel User', 'Utilizador do Painel')
            ->replace('Escola Admin', 'Gestao Academica')
            ->replace('Admin', 'Administrador')
            ->toString();
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

    private function hasReportPersonName(mixed $name): bool
    {
        $name = trim((string) $name);

        return $name !== '' && $name !== '-';
    }

    private function studentReportName(Student $student): string
    {
        return trim((string) ($student->candidate?->full_name ?: $student->full_name ?: ''));
    }
}
