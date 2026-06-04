<?php

namespace App\Exports;

use App\Models\Rank;
use App\Models\Provenance;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AgentListsSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    protected $ranks;
    protected $provenances;

    public function __construct()
    {
        $this->ranks = Rank::orderBy('name')->pluck('name')->toArray();
        $this->provenances = Provenance::orderBy('name')->pluck('name')->toArray();
    }

    public function title(): string
    {
        return 'Listas';
    }

    public function headings(): array
    {
        return ['Patentes', 'Proveniencias'];
    }

    public function array(): array
    {
        $data = [];
        $maxRows = max(count($this->ranks), count($this->provenances));
        
        for ($i = 0; $i < $maxRows; $i++) {
            $data[] = [
                $this->ranks[$i] ?? '',
                $this->provenances[$i] ?? '',
            ];
        }
        
        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        // Cabeçalho em negrito
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(50);
        
        return [];
    }
}
