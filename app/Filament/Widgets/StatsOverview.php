<?php

namespace App\Filament\Widgets;

use App\Models\Candidate;
use App\Models\Course;
use App\Models\CourseMap;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Trainer;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = null;
    protected static bool $isLazy = true;
    protected int | array | null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $institutionId = $this->filters['institution_id'] ?? null;
        $courseId = $this->filters['course_id'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $validInstitutionIds = Institution::pluck('id')->toArray();
        $formandos = Candidate::query()
            ->where('student_type', 'Formando')
            ->when($institutionId, fn($query) => $query->where('institution_id', $institutionId))
            ->when($courseId, fn($query) => $query->whereRaw('1 = 0'))
            ->when($startDate, fn($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($query) => $query->whereDate('created_at', '<=', $endDate))
            ->count();

        $alistadosQuery = Candidate::query();

        if ($institutionId) {
            $alistadosQuery->where('institution_id', $institutionId);
        }
        if ($courseId) {
            $alistadosQuery->whereRaw('1 = 0');
        }
        if ($startDate) {
            $alistadosQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $alistadosQuery->whereDate('created_at', '<=', $endDate);
        }

        $alistados = $alistadosQuery
            ->where('student_type', 'Alistado')
            ->count();

        $studentsQuery = Student::query()
            ->whereNotNull('institution_id');

        if ($institutionId) {
            $studentsQuery->where('institution_id', $institutionId);
        }
        if ($courseId) {
            $studentsQuery->whereHas('courseMap', fn($query) => $query->where('course_id', $courseId));
        }
        if ($startDate) {
            $studentsQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $studentsQuery->whereDate('created_at', '<=', $endDate);
        }

        $recrutas = (clone $studentsQuery)
            ->where(function ($query) {
                $query->where('student_type', 'like', '%Recruta%')
                    ->orWhere('student_type', 'like', '%1% Fase%');
            })
            ->count();

        $instruendos = (clone $studentsQuery)
            ->where(function ($query) {
                $query->where('student_type', 'like', '%Instruendo%')
                    ->orWhere('student_type', 'like', '%cadete%')
                    ->orWhere('student_type', 'like', '%praca%')
                    ->orWhere('student_type', 'like', '%2% Fase%');
            })
            ->count();

        $recrutasInstruendos = $recrutas + $instruendos;

        $emFormacao = (clone $studentsQuery)
            ->where('student_type', 'like', '%Em Forma%')
            ->count();

        $formandosConcluidos = (clone $studentsQuery)
            ->where(function ($query) {
                $query->where('student_type', 'like', '%Formando Conclu%')
                    ->orWhere('student_type', 'like', '%Conclu%');
            })
            ->count();

        $emFormacaoConcluidos = $emFormacao + $formandosConcluidos;

        $formadores = Trainer::where('is_active', true)
            ->when($institutionId, fn($query) => $query->where('institution_id', $institutionId))
            ->when($courseId, fn($query) => $query->whereHas('subjectAuthorizations', fn($subQuery) => $subQuery->where('course_id', $courseId)))
            ->count();

        $escolas = $institutionId ? 1 : count($validInstitutionIds);

        $courseMapsQuery = CourseMap::query()
            ->when($institutionId, fn($query) => $query->where('institution_id', $institutionId))
            ->when($courseId, fn($query) => $query->where('course_id', $courseId))
            ->when($startDate, fn($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($query) => $query->whereDate('created_at', '<=', $endDate));

        $courseMaps = (clone $courseMapsQuery)->count();
        $courseMapsActive = (clone $courseMapsQuery)->where('is_active', true)->count();

        $courses = Course::query()
            ->when($institutionId, fn($query) => $query->where('institution_id', $institutionId))
            ->when($courseId, fn($query) => $query->whereKey($courseId))
            ->when($startDate, fn($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($query) => $query->whereDate('created_at', '<=', $endDate))
            ->count();

        $subjects = Subject::query()
            ->when($institutionId, fn($query) => $query->where('institution_id', $institutionId))
            ->when($courseId, fn($query) => $query->whereHas('coursePhase', fn($phaseQuery) => $phaseQuery->where('course_id', $courseId)))
            ->when($startDate, fn($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($query) => $query->whereDate('created_at', '<=', $endDate))
            ->count();

        return [
            Stat::make('Total de Formandos', number_format($formandos, 0, ',', '.'))
                ->description('Candidatos do tipo Formando')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->chart([5, 6, 7, 8, 9, 10, $formandos > 0 ? $formandos : 1])
                ->extraAttributes([
                    'class' => '!cursor-pointer transition-transform hover:scale-[1.02]',
                    'style' => 'cursor: pointer !important;',
                    'onclick' => "event.stopPropagation(); Livewire.dispatch('openStatDetail', {type: 'formandos', institutionId: " . ($institutionId ?? 'null') . "})",
                ]),

            Stat::make('Total de Alistados', number_format($alistados, 0, ',', '.'))
                ->description('Candidatos do tipo Alistado')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning')
                ->chart([2, 3, 4, 5, 6, 7, $alistados > 0 ? $alistados : 1])
                ->extraAttributes([
                    'class' => '!cursor-pointer transition-transform hover:scale-[1.02]',
                    'style' => 'cursor: pointer !important;',
                    'onclick' => "event.stopPropagation(); Livewire.dispatch('openStatDetail', {type: 'alistados', institutionId: " . ($institutionId ?? 'null') . "})",
                ]),

            Stat::make('Total de Recrutas e Instruendos', number_format($recrutasInstruendos, 0, ',', '.'))
                ->description("Recrutas: {$recrutas} | Instruendos: {$instruendos}")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([3, 4, 5, 6, 7, 8, $recrutasInstruendos > 0 ? $recrutasInstruendos : 1])
                ->extraAttributes([
                    'class' => '!cursor-pointer transition-transform hover:scale-[1.02]',
                    'style' => 'cursor: pointer !important;',
                    'onclick' => "event.stopPropagation(); Livewire.dispatch('openStatDetail', {type: 'recrutas_instruendos', institutionId: " . ($institutionId ?? 'null') . "})",
                ]),

            Stat::make('Em formação e Formando Concluído', number_format($emFormacaoConcluidos, 0, ',', '.'))
                ->description("Em formação: {$emFormacao} | Concluídos: {$formandosConcluidos}")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary')
                ->chart([1, 2, 3, 5, 8, 13, $emFormacaoConcluidos > 0 ? $emFormacaoConcluidos : 1])
                ->extraAttributes([
                    'class' => '!cursor-pointer transition-transform hover:scale-[1.02]',
                    'style' => 'cursor: pointer !important;',
                    'onclick' => "event.stopPropagation(); Livewire.dispatch('openStatDetail', {type: 'em_formacao_concluidos', institutionId: " . ($institutionId ?? 'null') . "})",
                ]),

            Stat::make('Formadores', number_format($formadores, 0, ',', '.'))
                ->description('Corpo docente')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart([3, 4, 5, 6, 7, 8, 9])
                ->extraAttributes([
                    'class' => '!cursor-pointer transition-transform hover:scale-[1.02]',
                    'style' => 'cursor: pointer !important;',
                    'onclick' => "event.stopPropagation(); Livewire.dispatch('openStatDetail', {type: 'formadores', institutionId: " . ($institutionId ?? 'null') . "})",
                ]),

            Stat::make('Cursos', number_format($courses, 0, ',', '.'))
                ->description("Mapas/planos: {$courseMaps} | Activos: {$courseMapsActive}")
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success')
                ->chart([2, 3, 4, 5, 6, 7, $courses > 0 ? $courses : 1])
                ->extraAttributes([
                    'class' => '!cursor-pointer transition-transform hover:scale-[1.02]',
                    'style' => 'cursor: pointer !important;',
                    'onclick' => "event.stopPropagation(); Livewire.dispatch('openStatDetail', {type: 'cursos_ano_lectivo', institutionId: " . ($institutionId ?? 'null') . ", courseId: " . ($courseId ?? 'null') . "})",
                ]),

            Stat::make('Disciplinas', number_format($subjects, 0, ',', '.'))
                ->description('Disciplinas registadas')
                ->descriptionIcon('heroicon-m-bookmark')
                ->color('warning')
                ->chart([1, 3, 4, 6, 7, 9, $subjects > 0 ? $subjects : 1])
                ->extraAttributes([
                    'class' => '!cursor-pointer transition-transform hover:scale-[1.02]',
                    'style' => 'cursor: pointer !important;',
                    'onclick' => "event.stopPropagation(); Livewire.dispatch('openStatDetail', {type: 'disciplinas_curso', institutionId: " . ($institutionId ?? 'null') . ", courseId: " . ($courseId ?? 'null') . "})",
                ]),

            Stat::make('Instituições de Ensino', number_format($escolas, 0, ',', '.'))
                ->description($institutionId ? 'Instituicao selecionada' : 'Todas as instituicoes')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('warning')
                ->chart([1, 2, 3, 4, 5, 6, $escolas])
                ->extraAttributes([
                    'class' => '!cursor-pointer transition-transform hover:scale-[1.02]',
                    'style' => 'cursor: pointer !important;',
                    'onclick' => "event.stopPropagation(); Livewire.dispatch('openStatDetail', {type: 'escolas', institutionId: " . ($institutionId ?? 'null') . "})",
                ]),
        ];
    }
}
