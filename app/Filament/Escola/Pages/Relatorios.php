<?php

namespace App\Filament\Escola\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;

class Relatorios extends Page
{
    public string $reportPreviewUrl = '';

    public string $reportPreviewTitle = '';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-document-chart-bar';
    protected static ?string $navigationLabel = 'Relatórios';
    protected static ?string $title = 'Central de Relatórios';
    protected static string|\UnitEnum|null $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.escola.pages.relatorios';

    public function openReportPreview(string $url, string $title): void
    {
        $this->reportPreviewUrl = $url;
        $this->reportPreviewTitle = $title;
        $this->mountAction('previewReport');
    }

    public function previewReportAction(): Action
    {
        return Action::make('previewReport')
            ->modalHeading('Pré-visualização do Relatório')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Action $action): Action => $action
                ->label('Fechar Pré-visualização')
                ->color('danger')
                ->icon('heroicon-o-x-mark'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->closeModalByClickingAway(false)
            ->modalContent(fn (): View => view('reports.preview-modal', [
                'previewUrl' => $this->reportPreviewUrl,
                'title' => $this->reportPreviewTitle,
            ]));
    }

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
