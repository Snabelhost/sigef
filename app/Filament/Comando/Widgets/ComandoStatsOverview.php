<?php

namespace App\Filament\Comando\Widgets;

use App\Models\Student;
use App\Models\Trainer;
use App\Models\Evaluation;
use App\Models\Institution;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ComandoStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null; // Desativa polling automático
    protected static bool $isLazy = true;
    
    protected function getStats(): array
    {
        // Cache por 5 minutos para evitar queries repetidas
        return Cache::remember('comando_dashboard_stats', 300, function () {
            return [
                Stat::make('Total de Formandos', Student::count())
                    ->description('Formandos registados')
                    ->descriptionIcon('heroicon-m-academic-cap')
                    ->color('primary'),

                Stat::make('Formandos em Formação', Student::where('status', 'frequenta')->count())
                    ->description('A frequentar curso')
                    ->descriptionIcon('heroicon-m-book-open')
                    ->color('success'),

                Stat::make('Formadores Activos', Trainer::where('is_active', true)->count())
                    ->description('Corpo docente')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('info'),

                Stat::make('Total de Avaliações', Evaluation::count())
                    ->description('Avaliações registadas')
                    ->descriptionIcon('heroicon-m-clipboard-document-check')
                    ->color('warning'),

                Stat::make('Média Geral', number_format(Evaluation::avg('score') ?? 0, 1))
                    ->description('Média das avaliações')
                    ->descriptionIcon('heroicon-m-chart-bar')
                    ->color('primary'),

                Stat::make('Escolas', Institution::whereHas('type', fn($q) => $q->where('name', 'like', '%Escola%'))->count())
                    ->description('Instituições de formação')
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color('info'),
            ];
        });
    }
}
