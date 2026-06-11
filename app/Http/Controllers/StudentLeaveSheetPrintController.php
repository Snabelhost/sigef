<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentLeave;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentLeaveSheetPrintController extends Controller
{
    public function __invoke(Request $request, StudentLeave $studentLeave): Response
    {
        $studentLeave->loadMissing([
            'approver',
            'institution',
            'student.candidate.institution',
            'student.candidate.province',
            'student.candidate.municipalityRelation',
            'student.candidate.provenance',
            'student.candidate.academicYear',
            'student.institution',
            'student.provenance',
            'student.rank',
            'student.courseMap.course',
            'student.courseMap.academicYear',
            'student.currentPhase',
            'student.studentTypeRelation',
        ]);

        $student = $studentLeave->student;

        abort_if(! $student, 404);

        $enrollment = $this->latestEnrollment($student);
        $occurrences = StudentLeave::query()
            ->with(['approver'])
            ->where('student_id', $student->getKey())
            ->latest('start_date')
            ->latest('id')
            ->get();

        $academicYear = $this->academicYearLabel($student, $enrollment);
        $titleYear = filled($academicYear) && $academicYear !== '-'
            ? ' DO ANO: '.$academicYear
            : '';
        $studentName = $student->candidate?->full_name ?: $student->full_name;

        return response()->view('students.sheet-print', [
            'student' => $student,
            'candidate' => $student->candidate,
            'enrollment' => $enrollment,
            'institution' => $this->institutionProfile($student),
            'photoUrl' => $this->photoUrl($student),
            'summary' => $this->summary($studentLeave, $student, $enrollment, $occurrences->count()),
            'sections' => $this->sections($studentLeave, $student, $enrollment),
            'subjectRows' => $this->occurrenceRows($occurrences),
            'printOrientation' => $this->normalizeOrientation($request->string('orientation')->toString()),
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
            'documentPageTitle' => 'Ficha de Dispensa - '.$studentName,
            'documentTitle' => 'Ficha de Dispensa'.$titleYear,
            'documentIntro' => 'Vimos por este meio emitir a presente ficha de dispensa/falta do(a) formando(a) abaixo identificado(a), contendo o resumo da ocorrencia selecionada e o historico registado no SIGEF.',
            'recordsTitle' => 'Historico de Dispensas e Faltas',
            'recordColumns' => [
                ['key' => 'index', 'label' => '#', 'width' => '6%', 'class' => 'center'],
                ['key' => 'type', 'label' => 'Tipo', 'width' => '24%'],
                ['key' => 'period', 'label' => 'Periodo', 'width' => '20%', 'class' => 'center'],
                ['key' => 'status', 'label' => 'Estado', 'width' => '14%', 'class' => 'center'],
                ['key' => 'reason', 'label' => 'Motivo/Justificacao'],
            ],
            'recordEmptyText' => 'Nenhuma ocorrencia registada.',
            'signatureBlocks' => [
                ['Assinatura do Formando'],
                ['Chefe / Responsavel'],
            ],
        ]);
    }

    private function latestEnrollment(Student $student): ?StudentClassEnrollment
    {
        return $student->classEnrollments()
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
    }

    /**
     * @param \Illuminate\Support\Collection<int, StudentLeave> $occurrences
     * @return array<int, array<string, string>>
     */
    private function occurrenceRows($occurrences): array
    {
        return $occurrences
            ->values()
            ->map(fn (StudentLeave $leave, int $index): array => [
                'index' => (string) ($index + 1),
                'type' => $this->leaveTypeLabel($leave->leave_type),
                'period' => $this->dateRangeLabel($leave),
                'status' => $this->leaveStatusLabel($leave->status),
                'reason' => trim((string) $leave->reason) ?: '-',
            ])
            ->all();
    }

    /**
     * @return array<string, string|null>
     */
    private function summary(StudentLeave $leave, Student $student, ?StudentClassEnrollment $enrollment, int $totalOccurrences): array
    {
        return [
            'Codigo' => 'DISP-'.str_pad((string) $leave->getKey(), 5, '0', STR_PAD_LEFT),
            'NURI/NIP' => $this->studentIdentifier($student),
            'Tipo' => $this->leaveTypeLabel($leave->leave_type),
            'Estado' => $this->leaveStatusLabel($leave->status),
            'Periodo' => $this->dateRangeLabel($leave),
            'Duracao' => $this->durationLabel($leave),
            'Total ocorrencias' => (string) $totalOccurrences,
            'Ano academico' => $this->academicYearLabel($student, $enrollment),
        ];
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function sections(StudentLeave $leave, Student $student, ?StudentClassEnrollment $enrollment): array
    {
        $candidate = $student->candidate;
        $province = $candidate?->province?->name ?: $candidate?->getAttribute('province');
        $municipality = $candidate?->municipalityRelation?->name ?: $candidate?->getAttribute('municipality');

        return [
            'Identificacao pessoal' => [
                'Nome completo' => $candidate?->full_name ?: $student->full_name,
                'N.o do BI' => $student->bilhete_identidade ?: $candidate?->id_number,
                'NURI/NIP' => $this->studentIdentifier($student),
                'Telefone' => $this->phoneLabel($student->phone ?: $candidate?->phone),
                'Provincia' => $province,
                'Municipio' => $municipality,
            ],
            'Enquadramento' => [
                'Instituicao' => $student->institution?->name ?: $candidate?->institution?->name,
                'Curso' => $enrollment?->studentClass?->courseMap?->course?->name ?: $student->courseMap?->course?->name,
                'Turma' => $enrollment?->studentClass?->name,
                'Estado do formando' => $student->student_type ?: $student->status,
                'CIA' => $this->formatUnitLabel($student->cia, 'CIA', 'ª CIA'),
                'Pelotao' => $this->formatUnitLabel($student->platoon, 'PELOT', 'º Pelotao'),
                'Seccao' => $this->formatUnitLabel($student->section, 'SEC', 'ª Seccao'),
            ],
            'Dados da ocorrencia' => [
                'Tipo' => $this->leaveTypeLabel($leave->leave_type),
                'Estado' => $this->leaveStatusLabel($leave->status),
                'Data de inicio' => $this->formatDate($leave->start_date),
                'Data de fim' => $this->formatDate($leave->end_date),
                'Duracao' => $this->durationLabel($leave),
                'Registado em' => $this->formatDate($leave->created_at),
                'Aprovado por' => $leave->approver?->name,
                'Motivo/Justificacao' => $leave->reason,
            ],
        ];
    }

    private function studentIdentifier(Student $student): string
    {
        $identifier = trim((string) (
            $student->nuri
            ?: $student->candidate?->nuri
            ?: $student->student_number
            ?: ''
        ));

        return $identifier !== '' ? $identifier : '-';
    }

    private function leaveTypeLabel(?string $type): string
    {
        return match ($type) {
            'dispensa_saude' => 'Dispensa - Saude',
            'dispensa_pessoal' => 'Dispensa - Pessoal',
            'dispensa_servico' => 'Dispensa - Servico',
            'dispensa_falecimento' => 'Dispensa - Falecimento Familiar',
            'dispensa_outro' => 'Dispensa - Outro',
            'falta_justificada' => 'Falta Justificada',
            'falta_injustificada' => 'Falta Injustificada',
            'reprovado_faltas' => 'Reprovado por Faltas',
            'reprovado_desistencia' => 'Reprovado por Desistencia',
            'baixa_curso' => 'Baixa de curso',
            default => filled($type) ? (string) $type : '-',
        };
    }

    private function leaveStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pendente',
            'approved' => 'Aprovada',
            'rejected' => 'Rejeitada',
            default => filled($status) ? (string) $status : '-',
        };
    }

    private function dateRangeLabel(StudentLeave $leave): string
    {
        $start = $this->formatDate($leave->start_date);
        $end = $this->formatDate($leave->end_date);

        return $start === $end ? $start : "{$start} a {$end}";
    }

    private function durationLabel(StudentLeave $leave): string
    {
        if (blank($leave->start_date) || blank($leave->end_date)) {
            return '-';
        }

        try {
            $start = $leave->start_date instanceof \DateTimeInterface
                ? Carbon::instance($leave->start_date)
                : Carbon::parse($leave->start_date);
            $end = $leave->end_date instanceof \DateTimeInterface
                ? Carbon::instance($leave->end_date)
                : Carbon::parse($leave->end_date);

            $days = (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1;

            return $days.' dia'.($days === 1 ? '' : 's');
        } catch (\Throwable) {
            return '-';
        }
    }

    private function formatUnitLabel(mixed $value, string $needle, string $suffix): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return str_contains(strtoupper($value), $needle) ? $value : $value.$suffix;
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
