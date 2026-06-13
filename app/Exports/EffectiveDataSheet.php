<?php

namespace App\Exports;

use App\Models\Institution;
use App\Models\Provenance;
use App\Models\Province;
use App\Models\Rank;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EffectiveDataSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithEvents
{
    protected int $institutionCount;

    protected int $provinceCount;

    protected int $rankCount;

    protected int $organCount;

    public function __construct()
    {
        $this->institutionCount = max(Institution::count(), 1);
        $this->provinceCount = max(Province::count(), 1);
        $this->rankCount = max(Rank::count(), 1);
        $this->organCount = max(Provenance::count(), 1);
    }

    public function title(): string
    {
        return 'Dados';
    }

    public function headings(): array
    {
        return [
            'Tipo_Efectivo',
            'Escola',
            'Nome_Completo',
            'NIP',
            'Bilhete_Identidade',
            'Sexo',
            'Grupo_Sanguineo',
            'Pais_Origem',
            'Provincia',
            'Data_Nascimento',
            'Patente',
            'Grau_Academico',
            'Situacao',
            'Especializacao',
            'Funcao',
            'Departamento',
            'Orgao_Proveniencia',
            'Telefone',
            'Email',
            'Nome_Pai',
            'Nome_Mae',
            'Data_Admissao',
            'Activo',
            'Biografia',
        ];
    }

    public function array(): array
    {
        $columns = count($this->headings());
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $data[] = array_fill(0, $columns, '');
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
                    'startColor' => ['rgb' => '00245C'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $ranges = [
                    'type' => 'Listas!$A$2:$A$3',
                    'institution' => 'Listas!$B$2:$B$' . ($this->institutionCount + 1),
                    'gender' => 'Listas!$C$2:$C$3',
                    'blood' => 'Listas!$D$2:$D$9',
                    'country' => 'Listas!$E$2:$E$8',
                    'province' => 'Listas!$F$2:$F$' . ($this->provinceCount + 1),
                    'rank' => 'Listas!$G$2:$G$' . ($this->rankCount + 1),
                    'education' => 'Listas!$H$2:$H$14',
                    'situation' => 'Listas!$I$2:$I$6',
                    'organ' => 'Listas!$J$2:$J$' . ($this->organCount + 1),
                    'active' => 'Listas!$K$2:$K$3',
                ];

                for ($row = 2; $row <= 101; $row++) {
                    $this->addDropdown($sheet, "A{$row}", $ranges['type'], 'Tipo de Efectivo', 'Selecione Regime Especial ou Regime Geral.', false);
                    $this->addDropdown($sheet, "B{$row}", $ranges['institution'], 'Escola', 'Selecione a escola.', false);
                    $this->addDropdown($sheet, "F{$row}", $ranges['gender'], 'Sexo', 'Selecione o sexo.');
                    $this->addDropdown($sheet, "G{$row}", $ranges['blood'], 'Grupo Sanguineo', 'Selecione o grupo sanguineo.');
                    $this->addDropdown($sheet, "H{$row}", $ranges['country'], 'Pais de Origem', 'Selecione o pais.');
                    $this->addDropdown($sheet, "I{$row}", $ranges['province'], 'Provincia', 'Selecione a provincia.');
                    $this->addDropdown($sheet, "K{$row}", $ranges['rank'], 'Patente', 'Selecione a patente.');
                    $this->addDropdown($sheet, "L{$row}", $ranges['education'], 'Grau Academico', 'Selecione o grau academico.');
                    $this->addDropdown($sheet, "M{$row}", $ranges['situation'], 'Situacao', 'Selecione a situacao.');
                    $this->addDropdown($sheet, "Q{$row}", $ranges['organ'], 'Orgao de Proveniencia', 'Selecione o orgao.');
                    $this->addDropdown($sheet, "W{$row}", $ranges['active'], 'Activo', 'Selecione Sim ou Nao.');
                }

                foreach ($this->columnWidths() as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:X101');
                $sheet->getStyle('A1:X101')->getAlignment()->setVertical('center');
                $sheet->getStyle('X2:X101')->getAlignment()->setWrapText(true);

                $sheet->getColumnDimension('Z')->setWidth(56);
                $sheet->setCellValue('Z1', 'INSTRUCOES:');
                $sheet->setCellValue('Z2', '1. Nome_Completo, Tipo_Efectivo e Escola sao obrigatorios.');
                $sheet->setCellValue('Z3', '2. Para Regime Especial informe o NIP.');
                $sheet->setCellValue('Z4', '3. Para Regime Geral informe o Bilhete_Identidade.');
                $sheet->setCellValue('Z5', '4. No Admin, escolha a Escola pela lista suspensa.');
                $sheet->setCellValue('Z6', '5. Datas podem ser AAAA-MM-DD ou DD/MM/AAAA.');
                $sheet->setCellValue('Z7', '6. Activo aceita Sim/Nao; vazio fica como Sim.');
                $sheet->getStyle('Z1')->getFont()->setBold(true);
            },
        ];
    }

    protected function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 34,
            'C' => 34,
            'D' => 18,
            'E' => 24,
            'F' => 14,
            'G' => 18,
            'H' => 20,
            'I' => 20,
            'J' => 18,
            'K' => 28,
            'L' => 24,
            'M' => 18,
            'N' => 30,
            'O' => 28,
            'P' => 24,
            'Q' => 34,
            'R' => 18,
            'S' => 28,
            'T' => 28,
            'U' => 28,
            'V' => 18,
            'W' => 12,
            'X' => 40,
        ];
    }

    protected function addDropdown(Worksheet $sheet, string $cell, string $range, string $title, string $prompt, bool $allowBlank = true): void
    {
        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank($allowBlank);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setPromptTitle($title);
        $validation->setPrompt($prompt);
        $validation->setFormula1($range);
    }
}
