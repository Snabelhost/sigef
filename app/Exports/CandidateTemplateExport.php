<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CandidateTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Dados' => new CandidateDataSheet(),
            'Listas' => new CandidateListsSheet(),
        ];
    }
}
