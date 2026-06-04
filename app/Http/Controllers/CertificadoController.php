<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\Institution;
use App\Models\Evaluation;
use App\Services\GradeCalculator;
use Illuminate\Http\Request;

class CertificadoController extends Controller
{
    /**
     * Filtrar avaliações pela instituição da turma activa do aluno
     */
    protected function filterEvaluationsByInstitution(Student $student): Student
    {
        $enrollment = StudentClassEnrollment::where('student_id', $student->id)
            ->where('is_active', true)
            ->first();

        if ($enrollment) {
            $institutionId = StudentClass::where('id', $enrollment->class_id)->value('institution_id');
            if ($institutionId) {
                $filtered = $student->evaluations->where('institution_id', $institutionId)->values();
                $student->setRelation('evaluations', $filtered);
            }
        }

        return $student;
    }

    public function gerar(Request $request)
    {
        $filters = $request->only(['cia', 'pelotao', 'seccao', 'turma', 'institution_id']);
        
        $query = Student::with(['candidate', 'classes.institution', 'classes.courseMap.course', 'evaluations', 'classEnrollments'])
            ->whereHas('evaluations');
        
        if (!empty($filters['cia'])) {
            $query->where('cia', $filters['cia']);
        }
        if (!empty($filters['pelotao'])) {
            $query->where('pelotao', $filters['pelotao']);
        }
        if (!empty($filters['seccao'])) {
            $query->where('seccao', $filters['seccao']);
        }
        if (!empty($filters['turma'])) {
            $query->whereHas('classes', fn ($q) => $q->where('classes.id', $filters['turma']));
        }
        if (!empty($filters['institution_id'])) {
            $query->whereHas('classes', fn ($q) => $q->where('institution_id', $filters['institution_id']));
        }
        
        $students = $query->get()
            ->map(fn ($s) => $this->filterEvaluationsByInstitution($s))
            ->filter(fn ($s) => GradeCalculator::result($s) === 'Aprovado');
        
        return view('exports.certificados', [
            'alunos' => $students->map(fn ($s) => [
                'numero' => $s->student_number,
                'nome' => $s->candidate?->full_name,
                'bi' => $s->candidate?->id_number,
                'cia' => $s->cia,
                'pelotao' => $s->platoon,
                'seccao' => $s->section,
                'turma' => $s->classes->first()?->name,
                'curso' => $s->classes->first()?->courseMap?->course?->name,
                'instituicao' => $s->classes->first()?->institution?->name,
                'media' => GradeCalculator::generalAverage($s),
            ]),
            'titulo' => 'Certificados de Aprovação',
            'filtros' => $filters,
        ]);
    }

    public function individual(Student $student)
    {
        $student->load(['candidate', 'classes.institution', 'classes.courseMap.course', 'classes.academicYear', 'evaluations.subject', 'classEnrollments']);
        
        // Filtrar avaliações pela instituição da turma activa
        $this->filterEvaluationsByInstitution($student);

        $generalAvg = GradeCalculator::generalAverage($student);
        $resultado = GradeCalculator::result($student);
        
        // Agrupar notas por disciplina usando GradeCalculator
        $subjectIds = $student->evaluations->pluck('subject_id')->unique();
        $disciplinas = [];
        
        foreach ($subjectIds as $subjectId) {
            $subject = $student->evaluations->where('subject_id', $subjectId)->first()?->subject;
            $avg = GradeCalculator::subjectAverage($student, $subjectId);
            
            if ($subject && $avg !== '-') {
                $disciplinas[] = [
                    'nome' => $subject->name,
                    'nota' => floatval($avg),
                ];
            }
        }

        // Obter turma activa
        $activeEnrollment = $student->classEnrollments->where('is_active', true)->first();
        $activeClass = $activeEnrollment ? StudentClass::with(['institution', 'courseMap.course', 'academicYear'])->find($activeEnrollment->class_id) : $student->classes->first();
        
        return view('exports.certificado-individual', [
            'aluno' => [
                'numero' => $student->student_number,
                'numero_registo' => $student->student_number,
                'nome' => $student->candidate?->full_name,
                'bi' => $student->candidate?->id_number,
                'nascimento' => $student->candidate?->birth_date?->format('d/m/Y'),
                'cia' => $student->cia,
                'pelotao' => $student->platoon,
                'seccao' => $student->section,
                'turma' => $activeClass?->name,
                'curso' => $activeClass?->courseMap?->course?->name,
                'instituicao' => $activeClass?->institution?->name,
                'cidade' => $activeClass?->institution?->city ?? 'Luanda',
                'ano_instrucao' => $activeClass?->academicYear?->year ?? date('Y'),
                'media' => $generalAvg !== '-' ? $generalAvg : '0.0',
                'resultado' => $resultado === 'Aprovado' ? 'Apto' : 'Inapto',
                'disciplinas' => $disciplinas,
            ],
        ]);
    }

    public function bulk(Request $request)
    {
        $ids = explode(',', $request->ids);
        
        $students = Student::with(['candidate', 'classes.institution', 'classes.courseMap.course', 'evaluations', 'classEnrollments'])
            ->whereIn('id', $ids)
            ->get()
            ->map(fn ($s) => $this->filterEvaluationsByInstitution($s))
            ->filter(fn ($s) => GradeCalculator::result($s) === 'Aprovado');
        
        return view('exports.certificados', [
            'alunos' => $students->map(fn ($s) => [
                'numero' => $s->student_number,
                'nome' => $s->candidate?->full_name,
                'bi' => $s->candidate?->id_number,
                'cia' => $s->cia,
                'pelotao' => $s->platoon,
                'seccao' => $s->section,
                'turma' => $s->classes->first()?->name,
                'curso' => $s->classes->first()?->courseMap?->course?->name,
                'instituicao' => $s->classes->first()?->institution?->name,
                'media' => GradeCalculator::generalAverage($s),
            ]),
            'titulo' => 'Certificados de Aprovação',
        ]);
    }
}

