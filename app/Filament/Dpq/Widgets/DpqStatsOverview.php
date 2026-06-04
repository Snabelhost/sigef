<?php

namespace App\Filament\Dpq\Widgets;

use App\Models\Candidate;
use App\Models\SelectionTest;
use App\Models\RecruitmentType;
use App\Models\Institution;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DpqStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null; // Desativa polling automático
    protected static bool $isLazy = true;
    
    protected function getStats(): array
    {
        // Cache por 5 minutos para evitar queries repetidas
        return Cache::remember('dpq_dashboard_stats', 300, function () {
            return [
                Stat::make('Total de Alistados', Candidate::count())
                    ->description('Candidatos registados')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('primary'),

                Stat::make('Alistados Pendentes', Candidate::where('status', 'pending')->count())
                    ->description('Aguardando aprovação')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),

                Stat::make('Alistados Aprovados', Candidate::where('status', 'approved')->count())
                    ->description('Aprovados para formação')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Provas de Seleção', SelectionTest::count())
                    ->description('Provas registadas')
                    ->descriptionIcon('heroicon-m-clipboard-document-list')
                    ->color('info'),

                Stat::make('Tipos de Recrutamento', RecruitmentType::count())
                    ->description('Tipos disponíveis')
                    ->descriptionIcon('heroicon-m-briefcase')
                    ->color('primary'),

                Stat::make('Instituições', Institution::count())
                    ->description('Escolas de formação')
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color('warning'),
            ];
        });
    }
}
