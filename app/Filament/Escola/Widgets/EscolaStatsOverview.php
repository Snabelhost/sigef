<?php

namespace App\Filament\Escola\Widgets;

use App\Models\Student;
use App\Models\Candidate;
use App\Models\Evaluation;
use App\Models\Trainer;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EscolaStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $institutionId = $tenant?->id;
        $typeName = strtoupper($tenant?->type?->name ?? '');

        // Determinar se é Instituto/Academia (usa Cadetes) ou Escola/Centro (usa Recrutas/Instruendos)
        $isInstitutoOrAcademia = in_array($typeName, ['INSTITUTO', 'ACADEMIA']);

        // Students base query
        $studentsQuery = Student::where('institution_id', $institutionId);

        if ($isInstitutoOrAcademia) {
            // INSTITUTO/ACADEMIA: Cadetes, Formandos Superiores
            $cadetes = (clone $studentsQuery)
                ->where(function ($q) {
                    $q->where('student_type', 'like', '%cadete%')
                        ->orWhere('student_type', 'like', '%Instruendo%')
                        ->orWhere('student_type', 'like', '%2ª Fase%');
                })
                ->count();

            $formandosSuperior = (clone $studentsQuery)
                ->where('student_type', 'like', '%Em Forma%')
                ->count();

            $formandosSuperiorCandidates = Candidate::where('institution_id', $institutionId)
                ->where('student_type', 'like', '%Em Forma%')
                ->count();

            $totalAlunos = $cadetes + $formandosSuperior + $formandosSuperiorCandidates;

            $alunosDescription = "Cadetes: {$cadetes} | Sup: " . ($formandosSuperior + $formandosSuperiorCandidates);
        } else {
            // ESCOLA/CENTRO/Escola de Formação: Alistados, Recrutas, Instruendos
            $alistados = Candidate::where('institution_id', $institutionId)
                ->where('student_type', 'like', '%Alistado%')
                ->count();

            $recrutas = (clone $studentsQuery)
                ->where(function ($q) {
                    $q->where('student_type', 'like', '%Recruta%')
                        ->orWhere('student_type', 'like', '%1ª Fase%');
                })
                ->count();

            $instruendos = (clone $studentsQuery)
                ->where(function ($q) {
                    $q->where('student_type', 'like', '%Instruendo%')
                        ->orWhere('student_type', 'like', '%2ª Fase%');
                })
                ->count();

            $totalAlunos = $alistados + $recrutas + $instruendos;

            $alunosDescription = "Alist: {$alistados} | Recr: {$recrutas} | Instr: {$instruendos}";
        }

        // Formadores activos
        $formadores = Trainer::where('is_active', true)
            ->where('institution_id', $institutionId)
            ->count();

        // Total de avaliações
        $totalAvaliacoes = Evaluation::where('institution_id', $institutionId)->count();

        // Dispensas activas
        $dispensas = \App\Models\StudentLeave::where('institution_id', $institutionId)
            ->where('status', 'approved')
            ->where('end_date', '>=', now())
            ->count();

        return [
            Stat::make('Total de Alunos', number_format($totalAlunos, 0, ',', '.'))
                ->description($alunosDescription)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 8, 9, 10, 11, 12, $totalAlunos > 0 ? 13 : 1]),

            Stat::make('Formadores Activos', number_format($formadores, 0, ',', '.'))
                ->description('Corpo docente')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart([3, 4, 5, 6, 7, 8, $formadores > 0 ? 9 : 1]),

            Stat::make('Total Avaliações', number_format($totalAvaliacoes, 0, ',', '.'))
                ->description('Registadas nesta instituição')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('warning')
                ->chart([4, 5, 6, 7, 8, 9, $totalAvaliacoes > 0 ? 10 : 1]),

            Stat::make('Dispensas Activas', number_format($dispensas, 0, ',', '.'))
                ->description('Formandos ausentes')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($dispensas > 0 ? 'danger' : 'success')
                ->chart([2, 3, 4, 5, 4, 3, $dispensas > 0 ? 5 : 1]),
        ];
    }
}
