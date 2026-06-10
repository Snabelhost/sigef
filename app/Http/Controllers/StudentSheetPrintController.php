<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentSheetPrintController extends Controller
{
    public function __invoke(Request $request, Student $student): Response
    {
        $student->loadMissing([
            'candidate.institution',
            'candidate.province',
            'candidate.municipalityRelation',
            'candidate.provenance',
            'candidate.academicYear',
            'institution',
            'provenance',
            'rank',
            'courseMap.course',
            'courseMap.academicYear',
            'currentPhase',
            'studentTypeRelation',
        ]);

        $enrollment = $student->classEnrollments()
            ->with([
                'studentClass.courseMap.course',
                'studentClass.academicYear',
                'studentClass.courseMap.academicYear',
                'coursePhase',
                'academicYear',
            ])
            ->latest('enrolled_at')
            ->latest('id')
            ->first();

        $subjectRows = $this->subjectRows($student, $enrollment);

        return response()->view('students.sheet-print', [
            'student' => $student,
            'candidate' => $student->candidate,
            'enrollment' => $enrollment,
            'institution' => $this->institutionProfile($student),
            'photoUrl' => $this->photoUrl($student),
            'summary' => $this->summary($student, $enrollment),
            'sections' => $this->sections($student, $enrollment),
            'subjectRows' => $subjectRows,
            'printOrientation' => $this->normalizeOrientation($request->string('orientation')->toString()),
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function institutionProfile(Student $student): array
    {
        $institutionModel = $student->institution
            ?? $student->candidate?->institution
            ?? Institution::query()->where('is_active', true)->orderBy('id')->first()
            ?? Institution::query()->orderBy('id')->first();

        $config = SystemSetting::getReportInstitutionConfig($institutionModel);
        $logoUrl = $this->publicStorageUrl($config['logo_path'] ?? null);

        if (! $logoUrl && file_exists(public_path('images/logo-policia.png'))) {
            $logoUrl = asset('images/logo-policia.png');
        }

        if (! $logoUrl && file_exists(public_path('images/logo-sigef.png'))) {
            $logoUrl = asset('images/logo-sigef.png');
        }

        $footerContacts = array_filter([
            $config['address'] ?? null,
            filled($config['phone'] ?? null) ? 'Tel.: '.$config['phone'] : null,
            $config['email'] ?? null,
            $config['website'] ?? null,
        ]);

        return [
            'logo_url' => $logoUrl,
            'header_lines' => array_values(array_filter([
                $config['republic_line'] ?? null,
                $config['ministry_line'] ?? null,
                $config['organ_line'] ?? null,
                $config['department_line'] ?? null,
            ])),
            'name' => trim((string) ($config['name'] ?? $institutionModel?->name ?? 'SIGEF')),
            'subtitle' => trim((string) ($config['acronym'] ?? $institutionModel?->acronym ?? 'SIGEF')),
            'director_name' => trim((string) ($config['director_name'] ?? '')),
            'director_title' => trim((string) ($config['director_title'] ?? 'Responsavel')),
            'footer' => trim((string) (($config['footer_text'] ?? '') ?: implode(' | ', array_filter([
                $config['name'] ?? $institutionModel?->name ?? 'SIGEF',
                ...$footerContacts,
            ])))),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function summary(Student $student, ?StudentClassEnrollment $enrollment): array
    {
        return [
            'Codigo' => $student->student_number ?: 'FORM-'.str_pad((string) $student->getKey(), 5, '0', STR_PAD_LEFT),
            'NURI/NIP' => $student->nuri ?: $student->candidate?->nuri,
            'Curso' => $enrollment?->studentClass?->courseMap?->course?->name ?: $student->courseMap?->course?->name,
            'Turma' => $enrollment?->studentClass?->name,
            'Estado' => $student->student_type ?: $student->status,
            'Ano academico' => $this->academicYearLabel($student, $enrollment),
            'Data da inscricao' => $this->formatDate($enrollment?->enrolled_at ?: $student->enrollment_date ?: $student->created_at),
        ];
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function sections(Student $student, ?StudentClassEnrollment $enrollment): array
    {
        $candidate = $student->candidate;
        $province = $candidate?->province?->name ?: $candidate?->getAttribute('province');
        $municipality = $candidate?->municipalityRelation?->name ?: $candidate?->getAttribute('municipality');

        return [
            'Identificacao pessoal' => [
                'Nome completo' => $candidate?->full_name ?: $student->full_name,
                'N.o do BI' => $student->bilhete_identidade ?: $candidate?->id_number,
                'Data de nascimento' => $this->formatDate($candidate?->birth_date),
                'Idade' => $this->ageLabel($candidate?->birth_date),
                'Genero' => $candidate?->gender,
                'Estado civil' => $candidate?->marital_status,
                'Nome do pai' => $candidate?->father_name,
                'Nome da mae' => $candidate?->mother_name,
            ],
            'Contacto e localizacao' => [
                'Telefone' => $this->phoneLabel($student->phone ?: $candidate?->phone),
                'E-mail' => $candidate?->email,
                'Provincia' => $province,
                'Municipio' => $municipality,
                'Endereco' => $candidate?->address,
                'Proveniencia' => $student->provenance?->name ?: $candidate?->provenance?->name,
            ],
            'Inscricao e enquadramento' => [
                'Instituicao' => $student->institution?->name ?: $candidate?->institution?->name,
                'Curso' => $enrollment?->studentClass?->courseMap?->course?->name ?: $student->courseMap?->course?->name,
                'Turma' => $enrollment?->studentClass?->name,
                'Fase' => $enrollment?->coursePhase?->name ?: $student->currentPhase?->name,
                'Ano lectivo' => $this->academicYearLabel($student, $enrollment),
                'Estado' => $student->student_type ?: $student->status,
                'CIA' => $student->cia ? $student->cia.'ª CIA' : null,
                'Pelotao' => $student->platoon ? $student->platoon.'º Pelotao' : null,
                'Seccao' => $student->section ? $student->section.'ª Seccao' : null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function subjectRows(Student $student, ?StudentClassEnrollment $enrollment): array
    {
        $courseId = $this->courseId($student, $enrollment);

        if ($courseId) {
            $courseSubjects = Subject::query()
                ->with('coursePhase')
                ->whereHas('coursePhase', fn ($query) => $query->where('course_id', $courseId))
                ->get()
                ->sortBy(fn (Subject $subject): string => sprintf(
                    '%02d-%s',
                    $this->subjectPhaseOrder($subject),
                    Str::ascii(Str::lower($subject->name ?? '')),
                ))
                ->values();

            if ($courseSubjects->isNotEmpty()) {
                return $courseSubjects
                    ->map(fn (Subject $subject, int $index): array => [
                        'index' => (string) ($index + 1),
                        'academic_year' => $this->academicYearLabel($student, $enrollment),
                        'phase' => $this->subjectPhaseLabel($subject),
                        'class' => $enrollment?->studentClass?->name ?: '-',
                        'subject' => trim((string) $subject->name) ?: '-',
                    ])
                    ->all();
            }
        }

        return StudentSubjectEnrollment::query()
            ->with(['subject.coursePhase'])
            ->where('student_id', $student->getKey())
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (StudentSubjectEnrollment $subjectEnrollment): string => sprintf(
                '%02d-%s',
                $this->subjectPhaseOrder($subjectEnrollment->subject),
                Str::ascii(Str::lower($subjectEnrollment->subject?->name ?? '')),
            ))
            ->values()
            ->map(fn (StudentSubjectEnrollment $subjectEnrollment, int $index): array => [
                'index' => (string) ($index + 1),
                'academic_year' => $this->academicYearLabel($student, $enrollment),
                'phase' => $this->subjectPhaseLabel($subjectEnrollment->subject)
                    ?: $enrollment?->coursePhase?->name
                    ?: '-',
                'class' => $enrollment?->studentClass?->name ?: '-',
                'subject' => $subjectEnrollment->subject?->name ?: '-',
            ])
            ->all();
    }

    private function courseId(Student $student, ?StudentClassEnrollment $enrollment): ?int
    {
        return $enrollment?->studentClass?->courseMap?->course_id
            ?: $student->courseMap?->course_id;
    }

    private function subjectPhaseLabel(?Subject $subject): string
    {
        if (! $subject) {
            return '-';
        }

        $phases = collect($subject->phases)
            ->filter(fn ($phase): bool => filled($phase))
            ->map(fn ($phase): string => trim((string) $phase))
            ->unique()
            ->values();

        if ($phases->isNotEmpty()) {
            return $phases->implode(' / ');
        }

        return $subject->coursePhase?->name ?: '-';
    }

    private function subjectPhaseOrder(?Subject $subject): int
    {
        $label = $this->subjectPhaseLabel($subject);
        $slug = Str::of($label)->ascii()->lower()->toString();

        return match (true) {
            str_contains($slug, '1') => 1,
            str_contains($slug, '2') => 2,
            default => 99,
        };
    }

    private function photoUrl(Student $student): ?string
    {
        return $this->publicStorageUrl($student->photo)
            ?: $this->publicStorageUrl($student->candidate?->photo);
    }

    private function academicYearLabel(Student $student, ?StudentClassEnrollment $enrollment): string
    {
        return $enrollment?->academicYear?->year
            ?: $enrollment?->academicYear?->name
            ?: $enrollment?->studentClass?->academicYear?->year
            ?: $enrollment?->studentClass?->academicYear?->name
            ?: $enrollment?->studentClass?->courseMap?->academicYear?->year
            ?: $enrollment?->studentClass?->courseMap?->academicYear?->name
            ?: $student->courseMap?->academicYear?->year
            ?: $student->courseMap?->academicYear?->name
            ?: $student->candidate?->academicYear?->year
            ?: $student->candidate?->academicYear?->name
            ?: '-';
    }

    private function publicStorageUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function phoneLabel(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        return Str::startsWith($phone, '+') ? $phone : '+244 '.$phone;
    }

    private function ageLabel(mixed $date): string
    {
        if (blank($date)) {
            return '-';
        }

        try {
            $carbonDate = $date instanceof \DateTimeInterface
                ? Carbon::instance($date)
                : Carbon::parse($date);

            return $carbonDate->age.' anos';
        } catch (\Throwable) {
            return '-';
        }
    }

    private function formatDate(mixed $date): string
    {
        if (blank($date)) {
            return '-';
        }

        try {
            $carbonDate = $date instanceof \DateTimeInterface
                ? Carbon::instance($date)
                : Carbon::parse($date);

            return $carbonDate->format('d/m/Y');
        } catch (\Throwable) {
            return '-';
        }
    }

    private function normalizeOrientation(?string $orientation): string
    {
        return in_array($orientation, ['horizontal', 'landscape'], true) ? 'horizontal' : 'vertical';
    }
}
