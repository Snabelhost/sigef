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

class TrainerDataSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithEvents
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
            'Tipo_Formador',
            'Escola',
            'Nome_Completo',
            'NIP',
            'Bilhete_Identidade',
            'Sexo',
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
                    'country' => 'Listas!$D$2:$D$8',
                    'province' => 'Listas!$E$2:$E$' . ($this->provinceCount + 1),
                    'rank' => 'Listas!$F$2:$F$' . ($this->rankCount + 1),
                    'education' => 'Listas!$G$2:$G$14',
                    'situation' => 'Listas!$H$2:$H$6',
                    'organ' => 'Listas!$I$2:$I$' . ($this->organCount + 1),
                    'active' => 'Listas!$J$2:$J$3',
                ];

                for ($row = 2; $row <= 101; $row++) {
                    $this->addDropdown($sheet, "A{$row}", $ranges['type'], 'Tipo de Formador', 'Selecione Fardado ou Civil.');
                    $this->addDropdown($sheet, "B{$row}", $ranges['institution'], 'Escola', 'Selecione a escola.');
                    $this->addDropdown($sheet, "F{$row}", $ranges['gender'], 'Sexo', 'Selecione o sexo.');
                    $this->addDropdown($sheet, "G{$row}", $ranges['country'], 'Pais de Origem', 'Selecione o pais.');
                    $this->addDropdown($sheet, "H{$row}", $ranges['province'], 'Provincia', 'Selecione a provincia.');
                    $this->addDropdown($sheet, "J{$row}", $ranges['rank'], 'Patente', 'Selecione a patente.');
                    $this->addDropdown($sheet, "K{$row}", $ranges['education'], 'Grau Academico', 'Selecione o grau academico.');
                    $this->addDropdown($sheet, "L{$row}", $ranges['situation'], 'Situacao', 'Selecione a situacao.');
                    $this->addDropdown($sheet, "P{$row}", $ranges['organ'], 'Orgao de Proveniencia', 'Selecione o orgao.');
                    $this->addDropdown($sheet, "V{$row}", $ranges['active'], 'Activo', 'Selecione Sim ou Nao.');
                }

                foreach ($this->columnWidths() as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:W101');
                $sheet->getStyle('A1:W101')->getAlignment()->setVertical('center');
                $sheet->getStyle('W2:W101')->getAlignment()->setWrapText(true);

                $sheet->getColumnDimension('Y')->setWidth(54);
                $sheet->setCellValue('Y1', 'INSTRUCOES:');
                $sheet->setCellValue('Y2', '1. Nome_Completo e Tipo_Formador sao obrigatorios.');
                $sheet->setCellValue('Y3', '2. Para Regime Especial/Fardado informe o NIP.');
                $sheet->setCellValue('Y4', '3. Para Regime Geral/Civil informe o Bilhete_Identidade.');
                $sheet->setCellValue('Y5', '4. Use as listas suspensas sempre que existirem.');
                $sheet->setCellValue('Y6', '5. Datas podem ser AAAA-MM-DD ou DD/MM/AAAA.');
                $sheet->setCellValue('Y7', '6. Activo aceita Sim/Nao; vazio fica como Sim.');
                $sheet->getStyle('Y1')->getFont()->setBold(true);
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
            'G' => 20,
            'H' => 20,
            'I' => 18,
            'J' => 28,
            'K' => 24,
            'L' => 18,
            'M' => 30,
            'N' => 28,
            'O' => 24,
            'P' => 34,
            'Q' => 18,
            'R' => 28,
            'S' => 28,
            'T' => 28,
            'U' => 18,
            'V' => 12,
            'W' => 40,
        ];
    }

    protected function addDropdown(Worksheet $sheet, string $cell, string $range, string $title, string $prompt): void
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
