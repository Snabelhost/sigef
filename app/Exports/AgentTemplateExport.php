<?php

namespace App\Exports;

use App\Models\Rank;
use App\Models\Provenance;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AgentTemplateExport implements WithMultipleSheets
{
    /**
     * Retorna múltiplas folhas
     */
    public function sheets(): array
    {
        return [
            'Dados' => new AgentDataSheet(),
            'Listas' => new AgentListsSheet(),
        ];
    }
}
