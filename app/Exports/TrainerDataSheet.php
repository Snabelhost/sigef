<?php

namespace App\Exports;

use App\Models\Rank;
use App\Models\Provenance;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class TrainerDataSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithEvents
{
    protected $rankCount;
    protected $organCount;

    public function __construct()
    {
        $this->rankCount = Rank::count();
        $this->organCount = Provenance::count();
    }

    public function title(): string
    {
        return 'Dados';
    }

    public function headings(): array
    {
        return [
            'Nome',
            'NIP',
            'BI',
            'Genero',
            'Telefone',
            'Patente',
            'Orgao_Unidade',
            'Nivel_Academico',
            'Tipo',
        ];
    }

    public function array(): array
    {
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data[] = ['', '', '', '', '', '', '', '', ''];
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
                    'startColor' => ['rgb' => '0D47A1'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Ranges para as listas na folha "Listas"
                $genderRange = 'Listas!$A$2:$A$3';
                $typeRange = 'Listas!$B$2:$B$3';
                $rankRange = 'Listas!$C$2:$C$' . ($this->rankCount + 1);
                $organRange = 'Listas!$D$2:$D$' . ($this->organCount + 1);
                $educationRange = 'Listas!$E$2:$E$16';

                for ($row = 2; $row <= 51; $row++) {
                    // Género (coluna D)
                    $this->addDropdown($sheet, "D{$row}", $genderRange, 'Género', 'Selecione o género.');

                    // Patente (coluna F)
                    $this->addDropdown($sheet, "F{$row}", $rankRange, 'Patente', 'Selecione a patente.');

                    // Órgão/Unidade (coluna G)
                    $this->addDropdown($sheet, "G{$row}", $organRange, 'Órgão/Unidade', 'Selecione o órgão.');

                    // Nível Académico (coluna H)
                    $this->addDropdown($sheet, "H{$row}", $educationRange, 'Nível Académico', 'Selecione o nível.');

                    // Tipo (coluna I)
                    $this->addDropdown($sheet, "I{$row}", $typeRange, 'Tipo', 'Selecione o tipo.');
                }

                // Largura das colunas
                $sheet->getColumnDimension('A')->setWidth(40); // Nome
                $sheet->getColumnDimension('B')->setWidth(18); // NIP
                $sheet->getColumnDimension('C')->setWidth(20); // BI
                $sheet->getColumnDimension('D')->setWidth(15); // Género
                $sheet->getColumnDimension('E')->setWidth(15); // Telefone
                $sheet->getColumnDimension('F')->setWidth(25); // Patente
                $sheet->getColumnDimension('G')->setWidth(40); // Órgão/Unidade
                $sheet->getColumnDimension('H')->setWidth(25); // Nível Académico
                $sheet->getColumnDimension('I')->setWidth(20); // Tipo

                // Instruções
                $sheet->getColumnDimension('K')->setWidth(40);
                $sheet->setCellValue('K1', 'INSTRUÇÕES:');
                $sheet->setCellValue('K2', '1. Nome é OBRIGATÓRIO');
                $sheet->setCellValue('K3', '2. Genero, Patente, Órgão, Nível e Tipo');
                $sheet->setCellValue('K4', '   têm lista suspensa (clique na seta ▼)');
                $sheet->setCellValue('K5', '3. NIP é obrigatório para Regime Especial');
                $sheet->setCellValue('K6', '4. BI é obrigatório para Regime Geral');
                $sheet->getStyle('K1')->getFont()->setBold(true);
            },
        ];
    }

    protected function addDropdown($sheet, string $cell, string $range, string $title, string $prompt): void
    {
        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setPromptTitle($title);
        $validation->setPrompt($prompt);
        $validation->setFormula1($range);
    }
}
