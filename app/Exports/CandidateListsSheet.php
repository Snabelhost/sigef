<?php

namespace App\Exports;

use App\Models\Province;
use App\Models\Municipality;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CandidateListsSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    protected $provinces;
    protected $municipalities;
    protected $genders;
    protected $maritalStatuses;

    public function __construct()
    {
        $this->provinces = Province::orderBy('name')->pluck('name')->toArray();
        $this->municipalities = Municipality::orderBy('name')->pluck('name')->toArray();
        $this->genders = ['Masculino', 'Feminino'];
        $this->maritalStatuses = ['Solteiro(a)', 'Casado(a)', 'Divorciado(a)', 'Viúvo(a)'];
    }

    public function title(): string
    {
        return 'Listas';
    }

    public function headings(): array
    {
        return ['Genero', 'Estado_Civil', 'Provincias', 'Municipios'];
    }

    public function array(): array
    {
        $data = [];
        $maxRows = max(count($this->provinces), count($this->municipalities), count($this->genders), count($this->maritalStatuses));

        for ($i = 0; $i < $maxRows; $i++) {
            $data[] = [
                $this->genders[$i] ?? '',
                $this->maritalStatuses[$i] ?? '',
                $this->provinces[$i] ?? '',
                $this->municipalities[$i] ?? '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(30);

        return [];
    }
}
