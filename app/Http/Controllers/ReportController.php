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
    AcademicYear
};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
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

        $pdf = Pdf::loadView('reports.users', [
            'records' => $records,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
        return $pdf->stream('relatorio-utilizadores.pdf');
    }

    // ═══════════════════════════════════════
    // CURRÍCULO
    // ═══════════════════════════════════════

    public function courseMaps(Request $request)
    {
        $this->checkAccess();
        $records = CourseMap::with(['course', 'institution', 'academicYear'])->orderBy('id')->get();
        $pdf = Pdf::loadView('reports.course-maps', ['records' => $records]);
        return $pdf->stream('relatorio-mapas-curso.pdf');
    }

    public function coursePlans(Request $request)
    {
        $this->checkAccess();
        $records = CoursePlan::with(['course', 'academicYear', 'subjects'])->orderBy('id')->get();
        $pdf = Pdf::loadView('reports.course-plans', ['records' => $records]);
        return $pdf->stream('relatorio-planos-curso.pdf');
    }

    public function courses(Request $request)
    {
        $this->checkAccess();
        $records = Course::with(['institution'])->orderBy('name')->get();
        $pdf = Pdf::loadView('reports.courses', ['records' => $records]);
        return $pdf->stream('relatorio-cursos.pdf');
    }

    public function subjects(Request $request)
    {
        $this->checkAccess();
        $records = Subject::with(['institution', 'coursePhase'])->orderBy('name')->get();
        $pdf = Pdf::loadView('reports.subjects', ['records' => $records]);
        return $pdf->stream('relatorio-disciplinas.pdf');
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

        $pdf = Pdf::loadView('reports.trainers', [
            'records' => $records,
            'institution' => $institution,
        ]);
        return $pdf->stream('relatorio-formadores.pdf');
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

        $pdf = Pdf::loadView('reports.cadetes', [
            'records' => $records,
            'institution' => $institution,
            'class' => $class,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
        return $pdf->stream('relatorio-cadetes.pdf');
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

        $pdf = Pdf::loadView('reports.alistados', [
            'records' => $records,
            'academicYear' => $academicYear,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
        return $pdf->stream('relatorio-alistados.pdf');
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

        $pdf = Pdf::loadView('reports.enrollments', [
            'records' => $records,
            'class' => $class,
            'institution' => $institution,
        ]);
        return $pdf->stream('relatorio-gestao-formandos.pdf');
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

        $pdf = Pdf::loadView('reports.equipment', [
            'records' => $records,
            'institution' => $institution,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
        return $pdf->stream('relatorio-atribuicao-meios.pdf');
    }

    public function transfers(Request $request)
    {
        $this->checkAccess();
        $query = StudentTransferHistory::with(['student.candidate', 'fromInstitution', 'toInstitution']);
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);
        $records = $query->orderBy('created_at', 'desc')->get();

        $pdf = Pdf::loadView('reports.transfers', [
            'records' => $records,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
        return $pdf->stream('relatorio-transferencias.pdf');
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

        $pdf = Pdf::loadView('reports.leaves', [
            'records' => $records,
            'institution' => $institution,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
        return $pdf->stream('relatorio-dispensas-faltas.pdf');
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

        $pdf = Pdf::loadView('reports.evaluations', [
            'records' => $records,
            'class' => $class,
            'institution' => $institution,
        ]);
        return $pdf->stream('relatorio-avaliacoes.pdf');
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

        $pdf = Pdf::loadView('reports.mini-pauta', [
            'records' => $records,
            'class' => $class,
            'institution' => $institution,
        ]);
        return $pdf->stream('mini-pauta.pdf');
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

        $pdf = Pdf::loadView('reports.pauta-geral', [
            'records' => $records,
            'class' => $class,
            'subjects' => $subjects,
            'institution' => $institution,
        ])->setPaper('a4', 'landscape');
        return $pdf->stream('pauta-geral.pdf');
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

        $pdf = Pdf::loadView('reports.certificados', [
            'records' => $records,
            'class' => $class,
            'institution' => $institution,
        ]);
        return $pdf->stream('relatorio-certificados.pdf');
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

        $pdf = Pdf::loadView('reports.attendance', [
            'students' => $students,
            'cia' => $cia,
            'institution' => $institution,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'days' => $days,
            'totalDays' => $totalDays,
            'dayNames' => $dayNames,
            'attendanceMap' => $attendanceMap,
        ]);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream('ponto-presencas.pdf');
    }

    // ═══════════════════════════════════════
    // INSTITUIÇÕES & DOCUMENTOS
    // ═══════════════════════════════════════

    public function institutions(Request $request)
    {
        $this->checkAccess();
        $records = Institution::with(['institutionType'])->orderBy('name')->get();
        $pdf = Pdf::loadView('reports.institutions', ['records' => $records]);
        return $pdf->stream('relatorio-instituicoes.pdf');
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

        $pdf = Pdf::loadView('reports.documents', [
            'records' => $records,
            'institution' => $institution,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
        return $pdf->stream('relatorio-documentos.pdf');
    }
}
