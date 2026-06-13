<?php

namespace App\Filament\Escola\Pages;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\User;
use App\Support\AuditReportFormatter;
use App\Support\StudentTypeReportOptions;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class Relatorios extends \App\Filament\Pages\Relatorios
{
    protected string $view = 'filament.pages.relatorios';

    public function getViewData(): array
    {
        $tenant = Filament::getTenant();
        $institutionId = $tenant?->id;

        $institutions = $tenant
            ? [$tenant->id => $tenant->name]
            : Institution::orderBy('name')->pluck('name', 'id')->toArray();

        $classes = StudentClass::query()
            ->when($institutionId, fn (Builder $query, int $id): Builder => $query->where('institution_id', $id))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $academicYears = AcademicYear::orderBy('name', 'desc')->pluck('name', 'id')->toArray();

        $users = User::query()
            ->when($institutionId, fn (Builder $query, int $id): Builder => $query->where('institution_id', $id))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $auditModels = \OwenIt\Auditing\Models\Audit::query()
            ->whereNotNull('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->mapWithKeys(fn (string $model): array => [$model => AuditReportFormatter::auditModelLabel($model)])
            ->toArray();

        $ciasGrouped = Student::query()
            ->when($institutionId, fn (Builder $query, int $id): Builder => $query->where('institution_id', $id))
            ->whereNotNull('cia')
            ->where('cia', '!=', '')
            ->select('institution_id', 'cia')
            ->distinct()
            ->orderBy('cia')
            ->get()
            ->groupBy('institution_id')
            ->map(fn ($items) => $items->pluck('cia')->unique()->values()->toArray())
            ->toArray();

        return [
            'institutions' => $institutions,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'users' => $users,
            'auditModels' => $auditModels,
            'ciasGrouped' => $ciasGrouped,
            'studentTypeReports' => StudentTypeReportOptions::make(),
            'reportTenantId' => $institutionId,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:Relatorios') ?? false;
    }
}
