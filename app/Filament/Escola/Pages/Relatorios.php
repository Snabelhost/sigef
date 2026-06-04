<?php

namespace App\Filament\Escola\Pages;

use Filament\Pages\Page;
use Filament\Facades\Filament;

class Relatorios extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-document-chart-bar';
    protected static ?string $navigationLabel = 'Relatórios';
    protected static ?string $title = 'Central de Relatórios';
    protected static string|\UnitEnum|null $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.escola.pages.relatorios';

    public function getViewData(): array
    {
        $tenant = Filament::getTenant();

        $institutions = \App\Models\Institution::orderBy('name')->pluck('name', 'id')->toArray();
        $classes = \App\Models\StudentClass::where('institution_id', $tenant?->id)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
        $academicYears = \App\Models\AcademicYear::orderBy('name', 'desc')->pluck('name', 'id')->toArray();

        // Distinct CIAs from students in this institution
        $cias = \App\Models\Student::where('institution_id', $tenant?->id)
            ->whereNotNull('cia')
            ->where('cia', '!=', '')
            ->distinct()
            ->orderBy('cia')
            ->pluck('cia', 'cia')
            ->toArray();

        return [
            'institutions' => $institutions,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'cias' => $cias,
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
