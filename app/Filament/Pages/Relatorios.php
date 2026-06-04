<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Relatorios extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-document-chart-bar';
    protected static ?string $navigationLabel = 'Relatórios';
    protected static ?string $title = 'Central de Relatórios';
    protected static string|\UnitEnum|null $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.relatorios';

    public function getViewData(): array
    {
        $institutions = \App\Models\Institution::orderBy('name')->pluck('name', 'id')->toArray();
        $classes = \App\Models\StudentClass::orderBy('name')->pluck('name', 'id')->toArray();
        $academicYears = \App\Models\AcademicYear::orderBy('name', 'desc')->pluck('name', 'id')->toArray();

        // Distinct CIAs from all students, grouped by institution
        $ciasGrouped = \App\Models\Student::whereNotNull('cia')
            ->where('cia', '!=', '')
            ->select('institution_id', 'cia')
            ->distinct()
            ->orderBy('cia')
            ->get()
            ->groupBy('institution_id')
            ->map(fn($items) => $items->pluck('cia')->unique()->values()->toArray())
            ->toArray();

        return [
            'institutions' => $institutions,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'ciasGrouped' => $ciasGrouped,
        ];
    }
}
