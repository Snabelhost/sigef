<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Institution;
use App\Models\Student;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CandidateSheetPrintController extends Controller
{
    public function __invoke(Request $request, Candidate $candidate): Response
    {
        $candidate->loadMissing([
            'institution',
            'province',
            'municipalityRelation',
            'provenance',
            'academicYear',
            'recruitmentType',
            'currentRank',
        ]);

        $student = Student::query()
            ->where('candidate_id', $candidate->getKey())
            ->with([
                'institution',
                'provenance',
                'rank',
                'courseMap.course',
                'courseMap.academicYear',
                'classEnrollments.studentClass.courseMap.course',
                'classEnrollments.studentClass.academicYear',
                'classEnrollments.coursePhase',
                'classEnrollments.academicYear',
            ])
            ->latest('id')
            ->first();

        return response()->view('candidates.ficha-print', [
            'candidate' => $candidate,
            'student' => $student,
            'institution' => $this->institutionProfile($candidate, $student),
            'sections' => $this->candidateSections($candidate, $student),
            'summary' => $this->candidateSummary($candidate, $student),
            'printOrientation' => $this->normalizeOrientation($request->string('orientation')->toString()),
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function institutionProfile(Candidate $candidate, ?Student $student): array
    {
        $institutionModel = $student?->institution
            ?? $candidate->institution
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
    private function candidateSummary(Candidate $candidate, ?Student $student): array
    {
        $enrollment = $student?->classEnrollments?->sortByDesc('enrolled_at')->first();
        [$documentLabel, $documentNumber] = $this->regimeDocument($candidate, $student);

        return [
            'Codigo' => 'FORM-'.str_pad((string) $candidate->getKey(), 5, '0', STR_PAD_LEFT),
            'Regime' => $this->regimeLabel($candidate),
            $documentLabel => $documentNumber,
            'Curso' => $student?->courseMap?->course?->name
                ?: $enrollment?->studentClass?->courseMap?->course?->name,
            'Turma' => $enrollment?->studentClass?->name,
            'Tipo' => $student?->student_type ?: $candidate->student_type,
            'Ano academico' => $this->academicYearLabel($candidate, $student),
            'Data da inscricao' => $this->formatDate($student?->enrollment_date ?: $candidate->created_at),
        ];
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function candidateSections(Candidate $candidate, ?Student $student): array
    {
        $province = $candidate->province?->name ?: $candidate->getAttribute('province');
        $municipality = $candidate->municipalityRelation?->name ?: $candidate->getAttribute('municipality');
        [$documentLabel, $documentNumber] = $this->regimeDocument($candidate, $student);

        $sections = [
            'Identificacao pessoal' => [
                'Nome completo' => $candidate->full_name,
                'Regime' => $this->regimeLabel($candidate),
                $documentLabel => $documentNumber,
                'Data de nascimento' => $this->formatDate($candidate->birth_date),
                'Idade' => $this->ageLabel($candidate->birth_date),
                'Genero' => $candidate->gender,
                'Grupo sanguineo' => $candidate->blood_type,
                'Pais de origem' => $candidate->country ?: 'Angola',
                'Estado civil' => $candidate->marital_status,
                'Nome do pai' => $candidate->father_name,
                'Nome da mae' => $candidate->mother_name,
            ],
            'Contacto e localizacao' => [
                'Telefone' => $this->phoneLabel($candidate->phone ?: $student?->phone),
                'E-mail' => $candidate->email,
                'Provincia' => $province,
                'Municipio' => $municipality,
                'Endereco' => $candidate->address,
            ],
        ];

        if ($this->isSpecialRegime($candidate)) {
            $sections['Dados do regime especial'] = [
                'Patente' => $this->rankLabel($candidate, $student),
                'Orgao de colocacao / proveniencia' => $this->provenanceLabel($candidate, $student),
                'Data de entrada na PNA' => $this->formatDate($candidate->pna_entry_date),
                'Instituicao atribuida' => $student?->institution?->name ?: $candidate->institution?->name,
                'Tipo de recrutamento' => $candidate->recruitmentType?->name,
            ];
        } else {
            $sections['Dados do regime geral'] = [
                'Instituicao atribuida' => $student?->institution?->name ?: $candidate->institution?->name,
                'Tipo de recrutamento' => $candidate->recruitmentType?->name,
                'Resultado' => $candidate->status,
                'Nivel de ensino' => $candidate->education_level,
                'Area de formacao' => $candidate->education_area,
            ];
        }

        return $sections;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function regimeDocument(Candidate $candidate, ?Student $student): array
    {
        if ($this->isSpecialRegime($candidate)) {
            return [
                'NIP',
                $candidate->nuri ?: $student?->nuri,
            ];
        }

        return [
            'N.o do BI',
            $candidate->id_number,
        ];
    }

    private function isSpecialRegime(Candidate $candidate): bool
    {
        return ($candidate->staff_type ?? 'regime_geral') === 'regime_especial';
    }

    private function regimeLabel(Candidate $candidate): string
    {
        return $this->isSpecialRegime($candidate) ? 'Regime Especial' : 'Regime Geral';
    }

    private function rankLabel(Candidate $candidate, ?Student $student): ?string
    {
        return $candidate->currentRank?->name ?: $student?->rank?->name;
    }

    private function provenanceLabel(Candidate $candidate, ?Student $student): ?string
    {
        $candidateProvenance = $candidate->provenance;
        $studentProvenance = $student?->provenance;
        $provenance = $candidateProvenance ?: $studentProvenance;

        if (! $provenance) {
            return null;
        }

        return $provenance->acronym
            ? "{$provenance->name} ({$provenance->acronym})"
            : $provenance->name;
    }

    private function academicYearLabel(Candidate $candidate, ?Student $student): string
    {
        $enrollment = $student?->classEnrollments?->sortByDesc('enrolled_at')->first();

        return $candidate->academicYear?->year
            ?: $candidate->academicYear?->name
            ?: $enrollment?->academicYear?->year
            ?: $enrollment?->academicYear?->name
            ?: $student?->courseMap?->academicYear?->year
            ?: $student?->courseMap?->academicYear?->name
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
