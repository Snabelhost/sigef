<?php

namespace App\Filament\Aluno\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Painel do Aluno';

    protected static ?string $title = 'Painel do Aluno';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Painel do Aluno';
    }
}
