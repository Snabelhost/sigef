<?php

namespace App\Exports;

use App\Models\Rank;
use App\Models\Provenance;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrainerTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Dados' => new TrainerDataSheet(),
            'Listas' => new TrainerListsSheet(),
        ];
    }
}
