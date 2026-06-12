<?php

namespace App\Filament\Escola\Pages;

use App\Filament\Escola\Widgets\EscolaCandidateStatusChart;
use App\Filament\Escola\Widgets\EscolaStatsOverview;
use App\Filament\Escola\Widgets\EscolaStudentManagement;
use App\Filament\Escola\Widgets\EscolaStudentsByCourseChart;
use App\Filament\Escola\Widgets\EscolaStudentStatusChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'fas-tachometer-alt';

    protected static ?string $navigationLabel = 'Painel de Controlo';

    protected static ?string $title = 'Painel de Controlo';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->can('AccessPanel:Escola') || $user?->can('View:Dashboard');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return 'Painel de Controlo';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Painel de Controlo';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Visão geral da instituição';
    }

    public function getWidgets(): array
    {
        return [
            EscolaStatsOverview::class,
            EscolaCandidateStatusChart::class,
            EscolaStudentStatusChart::class,
            EscolaStudentsByCourseChart::class,
            EscolaStudentManagement::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }
}
