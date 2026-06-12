<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\Institution;
use App\Models\Evaluation;
use App\Models\SystemSetting;
use App\Services\GradeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificadoController extends Controller
{
    /**
     * Filtrar avaliações pela instituição da turma activa do aluno
     */
    protected function filterEvaluationsByInstitution(Student $student): Student
    {
        $enrollment = $student->classEnrollments->firstWhere('is_active', true)
            ?? StudentClassEnrollment::where('student_id', $student->id)
                ->where('is_active', true)
                ->first();

        if ($enrollment) {
            $institutionId = $enrollment->studentClass?->institution_id
                ?? StudentClass::where('id', $enrollment->class_id)->value('institution_id');

            if ($institutionId) {
                $filtered = $student->evaluations->where('institution_id', $institutionId)->values();
                $student->setRelation('evaluations', $filtered);
            }
        }

        return $student;
    }

    protected function currentClass(Student $student): ?StudentClass
    {
        $student->loadMissing([
            'classEnrollments.studentClass.institution',
            'classEnrollments.studentClass.courseMap.course',
            'classEnrollments.studentClass.courseMap.academicYear',
            'classEnrollments.studentClass.courseMap.institution',
            'classEnrollments.studentClass.academicYear',
        ]);

        $enrollment = $student->classEnrollments->firstWhere('is_active', true)
            ?? $student->classEnrollments->sortByDesc('enrolled_at')->first();

        return $enrollment?->studentClass;
    }

    public function gerar(Request $request)
    {
        $filters = $request->only(['cia', 'pelotao', 'seccao', 'turma', 'institution_id']);
        
        $query = Student::with([
                'candidate',
                'evaluations',
                'classEnrollments.studentClass.institution',
                'classEnrollments.studentClass.courseMap.course',
            ])
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
            $query->whereHas('classEnrollments', fn ($q) => $q->where('class_id', $filters['turma']));
        }
        if (!empty($filters['institution_id'])) {
            $query->whereHas('classEnrollments.studentClass', fn ($q) => $q->where('institution_id', $filters['institution_id']));
        }
        
        $students = $query->get()
            ->map(fn ($s) => $this->filterEvaluationsByInstitution($s))
            ->filter(fn ($s) => GradeCalculator::result($s) === 'Aprovado');
        
        return view('exports.certificados', [
            'alunos' => $students->map(function ($s) {
                $class = $this->currentClass($s);

                return [
                    'numero' => $s->student_number,
                    'nome' => $s->candidate?->full_name,
                    'bi' => $s->candidate?->id_number,
                    'cia' => $s->cia,
                    'pelotao' => $s->platoon,
                    'seccao' => $s->section,
                    'turma' => $class?->name,
                    'curso' => $class?->courseMap?->course?->name,
                    'instituicao' => $class?->institution?->name,
                    'media' => GradeCalculator::generalAverage($s),
                ];
            }),
            'titulo' => 'Certificados de Aprovação',
            'filtros' => $filters,
        ]);
    }

    public function individual(Request $request, Student $student)
    {
        $student->load([
            'candidate',
            'candidate.institution',
            'institution',
            'evaluations.subject',
            'classEnrollments.studentClass.institution',
            'classEnrollments.studentClass.courseMap.course',
            'classEnrollments.studentClass.courseMap.academicYear',
            'classEnrollments.studentClass.courseMap.institution',
            'classEnrollments.studentClass.academicYear',
        ]);
        
        // Filtrar avaliações pela instituição da turma activa
        $this->filterEvaluationsByInstitution($student);

        $generalAvg = GradeCalculator::generalAverage($student);
        $resultado = GradeCalculator::result($student);

        if ($resultado !== 'Aprovado') {
            abort(403, 'Este certificado so pode ser impresso para formandos aprovados.');
        }
        
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

        $activeClass = $this->currentClass($student);
        $institution = $activeClass?->institution
            ?? $activeClass?->courseMap?->institution
            ?? $student->institution
            ?? $student->candidate?->institution;
        $certificateInstitution = $this->certificateInstitutionPayload($institution);
        
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
                'instituicao' => $certificateInstitution['name'],
                'cidade' => $certificateInstitution['location'],
                'ano_instrucao' => $activeClass?->academicYear?->year
                    ?? $activeClass?->courseMap?->academicYear?->year
                    ?? date('Y'),
                'media' => $generalAvg !== '-' ? $generalAvg : '0.0',
                'resultado' => $resultado === 'Aprovado' ? 'Apto' : 'Inapto',
                'disciplinas' => $disciplinas,
            ],
            'certificate' => $certificateInstitution,
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
        ]);
    }

    public function bulk(Request $request)
    {
        $ids = explode(',', $request->ids);
        
        $students = Student::with([
                'candidate',
                'evaluations',
                'classEnrollments.studentClass.institution',
                'classEnrollments.studentClass.courseMap.course',
            ])
            ->whereIn('id', $ids)
            ->get()
            ->map(fn ($s) => $this->filterEvaluationsByInstitution($s))
            ->filter(fn ($s) => GradeCalculator::result($s) === 'Aprovado');
        
        return view('exports.certificados', [
            'alunos' => $students->map(function ($s) {
                $class = $this->currentClass($s);

                return [
                    'numero' => $s->student_number,
                    'nome' => $s->candidate?->full_name,
                    'bi' => $s->candidate?->id_number,
                    'cia' => $s->cia,
                    'pelotao' => $s->platoon,
                    'seccao' => $s->section,
                    'turma' => $class?->name,
                    'curso' => $class?->courseMap?->course?->name,
                    'instituicao' => $class?->institution?->name,
                    'media' => GradeCalculator::generalAverage($s),
                ];
            }),
            'titulo' => 'Certificados de Aprovação',
        ]);
    }

    protected function certificateInstitutionPayload(?Institution $institution): array
    {
        $config = SystemSetting::getReportInstitutionConfig($institution);
        $name = trim((string) ($config['name'] ?? $institution?->name ?? 'Escola de Formação de Polícia'));
        $certificateSchoolName = trim((string) (($config['certificate_school_name'] ?? '') ?: $name));
        $logoUrl = $this->publicAssetUrl($config['logo_path'] ?? null, '');
        $watermarkUrl = $this->publicAssetUrl(
            ($config['watermark_path'] ?? null) ?: ($config['logo_path'] ?? null),
            ''
        );

        $headerLines = array_values(array_filter([
            $config['republic_line'] ?? null,
            $config['ministry_line'] ?? null,
            $config['organ_line'] ?? null,
        ], fn ($line) => filled($line)));

        $normalizedHeaderLines = array_map(fn ($line) => $this->normalizeTextKey($line), $headerLines);

        if (filled($certificateSchoolName) && ! in_array($this->normalizeTextKey($certificateSchoolName), $normalizedHeaderLines, true)) {
            $headerLines[] = $certificateSchoolName;
        }

        $footerContacts = array_filter([
            $config['address'] ?? null,
            filled($config['phone'] ?? null) ? 'Tel.: '.$config['phone'] : null,
            $config['email'] ?? null,
            $config['website'] ?? null,
        ]);

        return [
            'name' => $certificateSchoolName,
            'header_lines' => $headerLines,
            'logo_url' => $logoUrl,
            'watermark_url' => $watermarkUrl,
            'director_name' => trim((string) ($config['director_name'] ?? '')),
            'director_title' => trim((string) (($config['director_title'] ?? '') ?: 'O Director')),
            'left_signature_title' => trim((string) (($config['certificate_left_signature_title'] ?? '') ?: 'O Director Adj. P/ Instrução e Ensino')),
            'left_signature_subtitle' => trim((string) (($config['certificate_left_signature_subtitle'] ?? '') ?: '*Subcomissário*')),
            'right_signature_title' => trim((string) (($config['certificate_right_signature_title'] ?? '') ?: 'O Director da Escola')),
            'right_signature_subtitle' => trim((string) (($config['certificate_right_signature_subtitle'] ?? '') ?: '**Comissário**')),
            'location' => trim((string) (($config['municipality'] ?? '') ?: ($config['province'] ?? '') ?: 'Luanda')),
            'footer_text' => trim((string) (($config['footer_text'] ?? '') ?: implode(' | ', array_filter([
                $certificateSchoolName,
                ...$footerContacts,
            ])))),
        ];
    }

    protected function publicAssetUrl(mixed $path, string $fallback): string
    {
        $path = $this->normalizeStoredFilePath($path);

        if (blank($path)) {
            return $fallback;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:', '/'])) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return $fallback;
    }

    protected function normalizeStoredFilePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = reset($path) ?: null;
        }

        if (! is_scalar($path)) {
            return null;
        }

        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        $decoded = json_decode($path, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->normalizeStoredFilePath(reset($decoded) ?: null);
        }

        if (Str::startsWith($path, '/storage/')) {
            return ltrim(substr($path, strlen('/storage/')), '/');
        }

        return str_replace('\\', '/', $path);
    }

    protected function normalizeTextKey(mixed $value): string
    {
        return Str::of((string) $value)->ascii()->lower()->squish()->toString();
    }
}

