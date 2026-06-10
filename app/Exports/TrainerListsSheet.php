<?php

namespace App\Exports;

use App\Models\Institution;
use App\Models\Provenance;
use App\Models\Province;
use App\Models\Rank;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
            'Tipo_Formador',
            'Escola',
            'Sexo',
            'Pais_Origem',
            'Provincia',
            'Patente',
            'Grau_Academico',
            'Situacao',
            'Orgao_Proveniencia',
            'Activo',
        ];
    }

    public function array(): array
    {
        $types = ['Fardado', 'Civil'];
        $institutions = Institution::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Institution $institution): string => $institution->acronym
                ? "{$institution->name} ({$institution->acronym})"
                : $institution->name)
            ->values()
            ->toArray();
        $genders = ['Masculino', 'Feminino'];
        $countries = [
            'Angola',
            'Brasil',
            'Cabo Verde',
            'Guiné-Bissau',
            'Moçambique',
            'Portugal',
            'São Tomé e Príncipe',
        ];
        $provinces = Province::query()->orderBy('name')->pluck('name')->toArray();
        $ranks = Rank::query()->orderBy('name')->pluck('name')->toArray();
        $education = [
            'Ensino Primário',
            '7ª Classe',
            '8ª Classe',
            '9ª Classe',
            '10ª Classe',
            '11ª Classe',
            '12ª Classe',
            'Ensino Médio Técnico',
            'Bacharelato',
            'Licenciatura',
            'Pós-Graduação',
            'Mestrado',
            'Doutoramento',
        ];
        $situations = ['Efectivo', 'Contratado', 'Convidado', 'Reformado', 'Inactivo'];
        $organs = Provenance::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Provenance $provenance): string => $provenance->acronym
                ? "{$provenance->name} ({$provenance->acronym})"
                : $provenance->name)
            ->values()
            ->toArray();
        $active = ['Sim', 'Nao'];

        $lists = [
            $types,
            $institutions,
            $genders,
            $countries,
            $provinces,
            $ranks,
            $education,
            $situations,
            $organs,
            $active,
        ];

        $maxRows = max(array_map('count', $lists));
        $data = [];

        for ($i = 0; $i < $maxRows; $i++) {
            $data[] = array_map(fn (array $items): string => $items[$i] ?? '', $lists);
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4B5563'],
                ],
            ],
        ];
    }
}
