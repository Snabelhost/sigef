<?php

namespace App\Filament\Escola\Resources\StudentClassEnrollmentResource\Pages;

use App\Filament\Escola\Resources\StudentClassEnrollmentResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentClassEnrollments extends ListRecords
{
    protected function authorizeAccess(): void
    {
        // Bypass policy authorization in Escola panel
    }

    protected static string $resource = StudentClassEnrollmentResource::class;

    /**
     * Forçar ordenação numérica do NURI no carregamento inicial
     */
    protected function applyDefaultSorting(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        if (empty($this->getTableSortColumn())) {
            return $query->orderByRaw('nuri IS NULL OR nuri = "" OR nuri = "-", nuri + 0 ASC');
        }

        return parent::applyDefaultSorting($query);
    }
}
