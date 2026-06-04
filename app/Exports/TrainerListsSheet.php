<?php

namespace App\Exports;

use App\Models\Rank;
use App\Models\Provenance;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainerListsSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    public function title(): string
    {
        return 'Listas';
    }

    public function headings(): array
    {
        return [
            'Genero',
            'Tipo',
            'Patente',
            'Orgao_Unidade',
            'Nivel_Academico',
        ];
    }

    public function array(): array
    {
        $genders = ['Masculino', 'Feminino'];
        $types = ['Fardado', 'Civil'];
        $ranks = Rank::orderBy('name')->pluck('name')->toArray();
        $organs = Provenance::orderBy('name')->pluck('name')->toArray();
        $education = [
            'Ensino Primário',
            '7ª Classe',
            '8ª Classe',
            '9ª Classe',
            '10ª Classe',
            '11ª Classe',
            '12ª Classe',
            'Técnico Médio',
            'Técnico Profissional',
            'Bacharelato',
            'Licenciatura',
            'Pós-Graduação',
            'Mestrado',
            'Doutoramento',
        ];

        $maxRows = max(count($genders), count($types), count($ranks), count($organs), count($education));

        $data = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $data[] = [
                $genders[$i] ?? '',
                $types[$i] ?? '',
                $ranks[$i] ?? '',
                $organs[$i] ?? '',
                $education[$i] ?? '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '666666'],
                ],
            ],
        ];
    }
}
