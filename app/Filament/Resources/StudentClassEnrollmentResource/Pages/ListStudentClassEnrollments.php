<?php

namespace App\Filament\Resources\StudentClassEnrollmentResource\Pages;

use App\Filament\Resources\StudentClassEnrollmentResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentClassEnrollments extends ListRecords
{
    protected static string $resource = StudentClassEnrollmentResource::class;
    
    /**
     * Remover botão de criar - usar bulk action "Atribuir em Massa"
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
    
    /**
     * Quantidade de registros por página
     */
    protected function getDefaultPaginationPageOption(): int|string|null
    {
        return 25;
    }
    
    /**
     * Opções de paginação disponíveis
     */
    protected function getPaginationPageOptions(): array
    {
        return [10, 25, 50, 100, 'all'];
    }
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
