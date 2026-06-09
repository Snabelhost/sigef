<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EffectiveTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Dados' => new EffectiveDataSheet(),
            'Listas' => new EffectiveListsSheet(),
        ];
    }
}
