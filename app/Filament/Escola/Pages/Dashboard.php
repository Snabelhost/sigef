<?php

namespace App\Filament\Escola\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'fas-tachometer-alt';

    protected static ?string $navigationLabel = 'Painel de Controlo';

    protected static ?string $title = 'Painel de Controlo';

    public static function getNavigationLabel(): string
    {
        return 'Painel de Controlo';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Painel de Controlo';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Visão geral da instituição';
    }
}
