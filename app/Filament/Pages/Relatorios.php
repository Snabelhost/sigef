<?php

namespace App\Filament\Pages;

use App\Support\AuditReportFormatter;
use App\Support\StudentTypeReportOptions;
use Filament\Actions\Action;
use Filament\Pages\Page;
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

    protected string $view = 'filament.pages.relatorios';

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
        $institutions = \App\Models\Institution::orderBy('name')->pluck('name', 'id')->toArray();
        $classes = \App\Models\StudentClass::orderBy('name')->pluck('name', 'id')->toArray();
        $academicYears = \App\Models\AcademicYear::orderBy('name', 'desc')->pluck('name', 'id')->toArray();
        $users = \App\Models\User::orderBy('name')->pluck('name', 'id')->toArray();
        $auditModels = \OwenIt\Auditing\Models\Audit::query()
            ->whereNotNull('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->mapWithKeys(fn (string $model): array => [$model => AuditReportFormatter::auditModelLabel($model)])
            ->toArray();

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
            'users' => $users,
            'auditModels' => $auditModels,
            'ciasGrouped' => $ciasGrouped,
            'studentTypeReports' => StudentTypeReportOptions::make(),
        ];
    }
}
