<?php

namespace App\Filament\Professores\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Painel dos Professores';

    protected static ?string $title = 'Painel dos Professores';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Painel dos Professores';
    }
}
