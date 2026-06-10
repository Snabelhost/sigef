<?php

namespace App\Http\Controllers;

use App\Models\Effective;
use App\Models\Institution;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EffectiveSheetPrintController extends Controller
{
    public function __invoke(Request $request, Effective $effective): Response
    {
        $effective->loadMissing(['institution']);

        $identifierLabel = $this->identifierLabel($effective);
        $identifierNumber = $this->identifierNumber($effective);

        return response()->view('effectives.sheet-print', [
            'effective' => $effective,
            'institution' => $this->institutionProfile($effective),
            'photoUrl' => $this->publicStorageUrl($effective->photo),
            'identifierLabel' => $identifierLabel,
            'identifierNumber' => $identifierNumber,
            'summary' => $this->summary($effective, $identifierLabel, $identifierNumber),
            'sections' => $this->sections($effective, $identifierLabel, $identifierNumber),
            'printOrientation' => $this->normalizeOrientation($request->string('orientation')->toString()),
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function institutionProfile(Effective $effective): array
    {
        $institutionModel = $effective->institution
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
            'director_title' => trim((string) ($config['director_title'] ?? 'Director')),
            'footer' => trim((string) (($config['footer_text'] ?? '') ?: implode(' | ', array_filter([
                $config['name'] ?? $institutionModel?->name ?? 'SIGEF',
                ...$footerContacts,
            ])))),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function summary(Effective $effective, string $identifierLabel, string $identifierNumber): array
    {
        return [
            $identifierLabel => $identifierNumber,
            'Data de admissao' => $this->formatDate($effective->hire_date),
            'Funcao' => $effective->job_function ?: $effective->position_label,
        ];
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function sections(Effective $effective, string $identifierLabel, string $identifierNumber): array
    {
        return [
            'Identificacao' => [
                'Nome completo' => $effective->full_name,
                'Codigo do utilizador' => 'EF-'.str_pad((string) $effective->getKey(), 5, '0', STR_PAD_LEFT),
                $identifierLabel => $identifierNumber,
                'NAS' => $effective->nas,
                'N.o do BI' => $effective->identity_document ?: $effective->document_number,
                'Telefone' => $this->phoneLabel($effective->phone),
                'E-mail' => $effective->email,
                'Regime' => Effective::staffTypeOptions()[$effective->staff_type] ?? $effective->staff_type,
                'Sexo' => $effective->gender,
                'Pais de origem' => $effective->country,
                'Provincia' => $effective->province,
                'Data de nascimento' => $this->formatDate($effective->birth_date),
                'Idade' => $this->ageLabel($effective->birth_date),
                'Nome do pai' => $effective->father_name,
                'Nome da mae' => $effective->mother_name,
                'Grupo sanguineo' => $effective->blood_type,
            ],
            'Dados profissionais' => [
                'Posto / categoria' => $effective->position_label,
                'Funcao' => $effective->job_function,
                'Departamento' => $effective->department,
                'Unidade' => $effective->unit,
                'Orgao de proveniencia' => $effective->placement_organ,
                'Grau academico' => $effective->education_level,
                'Especializacao' => $effective->specialization,
                'Situacao' => $effective->situation,
                'Data de admissao' => $this->formatDate($effective->hire_date),
                'Estado' => $effective->is_active ? 'Activo' : 'Inactivo',
            ],
            'Horario e expediente' => [
                'Turno' => $effective->work_shift,
                'Hora de inicio' => $this->formatTime($effective->work_start_time),
                'Hora de fim' => $this->formatTime($effective->work_end_time),
                'Horas semanais' => filled($effective->weekly_hours) ? rtrim(rtrim((string) $effective->weekly_hours, '0'), '.').' h' : null,
                'Dias de trabalho' => $this->workDaysLabel($effective->work_days),
                'Observacoes' => $effective->work_schedule_notes,
            ],
        ];
    }

    private function identifierLabel(Effective $effective): string
    {
        return $effective->staff_type === 'regime_geral' ? 'N.o DO BI' : 'NIP';
    }

    private function identifierNumber(Effective $effective): string
    {
        $value = $effective->staff_type === 'regime_geral'
            ? ($effective->identity_document ?: $effective->document_number ?: $effective->nas)
            : ($effective->employee_number ?: $effective->document_number);

        return trim((string) $value) !== '' ? trim((string) $value) : '-';
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

    private function ageLabel(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->age.' anos';
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDate(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatTime(mixed $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        try {
            return Carbon::parse((string) $time)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function workDaysLabel(mixed $days): ?string
    {
        if (blank($days)) {
            return null;
        }

        if (is_array($days)) {
            return implode(', ', array_filter($days));
        }

        return (string) $days;
    }

    private function normalizeOrientation(?string $orientation): string
    {
        return $orientation === 'horizontal' ? 'horizontal' : 'vertical';
    }
}
