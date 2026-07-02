<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\SystemSetting;
use App\Models\Trainer;
use App\Models\TrainerClassAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrainerSheetPrintController extends Controller
{
    public function __invoke(Request $request, Trainer $trainer): Response
    {
        $trainer->loadMissing([
            'rank',
            'institution',
            'classAssignments.academicYear',
            'classAssignments.studentClass.academicYear',
            'classAssignments.studentClass.courseMap.course',
            'classAssignments.subject.phase.course',
        ]);

        $assignments = $trainer->classAssignments;

        return response()->view('trainers.sheet-print', [
            'trainer' => $trainer,
            'institution' => $this->institutionProfile($trainer),
            'photoUrl' => $this->publicStorageUrl($trainer->photo),
            'sections' => $this->trainerSections($trainer, $assignments),
            'teachingSummary' => $this->teachingSummary($assignments),
            'teachingRows' => $this->teachingRows($assignments),
            'printOrientation' => $this->normalizeOrientation($request->string('orientation')->toString()),
            'autoPrint' => $request->boolean('autoprint', false),
            'embeddedMode' => $request->boolean('embedded', false),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function institutionProfile(Trainer $trainer): array
    {
        $institutionModel = $trainer->institution
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
            'name' => trim((string) ($config['name'] ?? $institutionModel?->name ?? 'Sistema Integrado de Gestao de Formacao')),
            'subtitle' => trim((string) ($config['acronym'] ?? $institutionModel?->acronym ?? 'SIGEF')),
            'report_header' => 'Ficha individual do formador',
            'address' => trim((string) ($config['address'] ?? '')),
            'phone' => trim((string) ($config['phone'] ?? '')),
            'email' => trim((string) ($config['email'] ?? '')),
            'director_name' => trim((string) ($config['director_name'] ?? '')),
            'director_title' => trim((string) ($config['director_title'] ?? 'Responsavel')),
            'footer' => trim((string) (($config['footer_text'] ?? '') ?: implode(' | ', array_filter([
                $config['name'] ?? $institutionModel?->name ?? 'SIGEF',
                ...$footerContacts,
            ])))),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TrainerClassAssignment>  $assignments
     * @return array<string, array<string, string|null>>
     */
    private function trainerSections(Trainer $trainer, $assignments): array
    {
        $summary = $this->teachingSummary($assignments);

        return [
            'Identificacao' => [
                'Nome completo' => $trainer->full_name,
                'NIP' => filled($trainer->bilhete) ? null : $trainer->nip,
                'N&ordm; DO BI' => $trainer->bilhete,
                'Telefone' => $this->phoneLabel($trainer->phone),
                'E-mail' => $trainer->email,
                'Regime' => $this->trainerTypeLabel($trainer->trainer_type),
                'Sexo' => $trainer->gender,
                'Pais de origem' => $trainer->country_origin,
                'Provincia' => $trainer->province,
                'Data de nascimento' => $this->formatDate($trainer->birth_date),
                'Idade' => $this->ageLabel($trainer->birth_date),
                'Nome do pai' => $trainer->father_name,
                'Nome da mae' => $trainer->mother_name,
            ],
            'Dados profissionais' => [
                'Patente' => $trainer->rank?->name,
                'Grau academico' => $trainer->education_level,
                'Especializacao' => $trainer->specialization,
                'Funcao' => $trainer->job_function,
                'Departamento' => $trainer->department,
                'Orgao de colocacao / proveniencia' => $trainer->organ,
                'Escola' => $trainer->institution?->name,
                'Data de admissao' => $this->formatDate($trainer->admission_date),
                'Situacao' => $trainer->situation,
                'Estado' => $trainer->is_active ? 'Activo' : 'Inactivo',
            ],
            'Carga docente' => [
                'Disciplinas' => $summary['subjects'],
                'Turmas' => $summary['classes'],
                'Tempos semanais' => $summary['slots'],
                'Carga semanal' => $summary['weekly_load'],
                'Tempos activos' => $summary['active_slots'],
            ],
            'Perfil academico' => [
                'Biografia' => $trainer->biography,
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TrainerClassAssignment>  $assignments
     * @return array<string, string>
     */
    private function teachingSummary($assignments): array
    {
        $activeAssignments = $assignments->filter(fn (TrainerClassAssignment $assignment): bool => (bool) $assignment->is_active);

        return [
            'subjects' => (string) $assignments->pluck('subject_id')->filter()->unique()->count(),
            'classes' => (string) $assignments->pluck('class_id')->filter()->unique()->count(),
            'slots' => (string) $assignments->count(),
            'active_slots' => (string) $activeAssignments->count(),
            'weekly_load' => $this->formatMinutes(
                $activeAssignments->sum(fn (TrainerClassAssignment $assignment): int => $this->assignmentMinutes($assignment))
            ),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TrainerClassAssignment>  $assignments
     * @return array<int, array<string, string>>
     */
    private function teachingRows($assignments): array
    {
        return $assignments
            ->map(fn (TrainerClassAssignment $assignment): array => [
                'academic_year' => $assignment->academicYear?->year
                    ?: $assignment->academicYear?->name
                    ?: $assignment->studentClass?->academicYear?->year
                    ?: '-',
                'course' => $assignment->studentClass?->courseMap?->course?->name
                    ?: $assignment->subject?->phase?->course?->name
                    ?: '-',
                'frequency_year' => $assignment->frequency_year ?: $assignment->subject?->phase?->name ?: '-',
                'shift' => $assignment->shift ?: '-',
                'class' => $assignment->studentClass?->name ?: '-',
                'subject' => $assignment->subject?->name ?: '-',
                'weekday' => $this->weekdayLabel($assignment->day_of_week),
                'time' => $this->timeRange($assignment),
                'lesson_type' => $assignment->lesson_type ?: '-',
                'status' => $assignment->is_active ? 'Activo' : 'Inactivo',
            ])
            ->values()
            ->all();
    }

    private function trainerTypeLabel(?string $type): string
    {
        return match ($type) {
            'Fardado' => 'Regime Especial',
            'Civil' => 'Regime Geral',
            default => filled($type) ? (string) $type : '-',
        };
    }

    private function phoneLabel(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        return Str::startsWith($phone, '+') ? $phone : '+244 '.$phone;
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

    private function assignmentMinutes(TrainerClassAssignment $assignment): int
    {
        if (blank($assignment->start_time) || blank($assignment->end_time)) {
            return 0;
        }

        try {
            $start = Carbon::parse((string) $assignment->start_time);
            $end = Carbon::parse((string) $assignment->end_time);

            return max(0, $start->diffInMinutes($end));
        } catch (\Throwable) {
            return 0;
        }
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 h';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $remaining > 0 ? "{$hours}h {$remaining}m" : "{$hours}h";
    }

    private function timeRange(TrainerClassAssignment $assignment): string
    {
        $start = $this->formatTime($assignment->start_time);
        $end = $this->formatTime($assignment->end_time);

        return $start !== '-' && $end !== '-' ? "{$start} - {$end}" : '-';
    }

    private function weekdayLabel(mixed $weekday): string
    {
        $weekday = trim((string) $weekday);

        if ($weekday === '') {
            return '-';
        }

        return match ($weekday) {
            '1' => 'Segunda-feira',
            '2' => 'Terca-feira',
            '3' => 'Quarta-feira',
            '4' => 'Quinta-feira',
            '5' => 'Sexta-feira',
            '6' => 'Sabado',
            '7' => 'Domingo',
            default => $weekday,
        };
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

    private function formatTime(mixed $time): string
    {
        if (blank($time)) {
            return '-';
        }

        try {
            return Carbon::parse((string) $time)->format('H:i');
        } catch (\Throwable) {
            return (string) $time;
        }
    }

    private function normalizeOrientation(?string $orientation): string
    {
        return in_array($orientation, ['vertical', 'portrait'], true) ? 'vertical' : 'horizontal';
    }
}
