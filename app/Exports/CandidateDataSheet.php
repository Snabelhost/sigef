<?php

namespace App\Exports;

use App\Models\Province;
use App\Models\Municipality;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class CandidateDataSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithEvents
{
    protected $provinceCount;
    protected $municipalityCount;
    protected $genderCount = 2;
    protected $maritalStatusCount = 4;

    public function __construct()
    {
        $this->provinceCount = Province::count();
        $this->municipalityCount = Municipality::count();
    }

    public function title(): string
    {
        return 'Dados';
    }

    public function headings(): array
    {
        return [
            'Nome',           // A
            'BI',             // B
            'Data_Nascimento', // C
            'Genero',         // D
            'Estado_Civil',   // E
            'Nome_Pai',       // F
            'Nome_Mae',       // G
            'Provincia',      // H
            'Municipio',      // I
            'Endereco',       // J
            'Telefone',       // K
            'Email',          // L
        ];
    }

    public function array(): array
    {
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data[] = ['', '', '', '', '', '', '', '', '', '', '', ''];
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

                // Referências para as listas
                $genderRange = 'Listas!$A$2:$A$' . ($this->genderCount + 1);
                $maritalRange = 'Listas!$B$2:$B$' . ($this->maritalStatusCount + 1);
                $provinceRange = 'Listas!$C$2:$C$' . ($this->provinceCount + 1);
                $municipalityRange = 'Listas!$D$2:$D$' . ($this->municipalityCount + 1);

                for ($row = 2; $row <= 51; $row++) {
                    // Género (coluna D)
                    $validation = $sheet->getCell("D{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setPromptTitle('Género');
                    $validation->setPrompt('Selecione o género.');
                    $validation->setFormula1($genderRange);

                    // Estado Civil (coluna E)
                    $validation = $sheet->getCell("E{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setPromptTitle('Estado Civil');
                    $validation->setPrompt('Selecione o estado civil.');
                    $validation->setFormula1($maritalRange);

                    // Província (coluna H)
                    $validation = $sheet->getCell("H{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setPromptTitle('Província');
                    $validation->setPrompt('Selecione a província.');
                    $validation->setFormula1($provinceRange);

                    // Município (coluna I)
                    $validation = $sheet->getCell("I{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setPromptTitle('Município');
                    $validation->setPrompt('Selecione o município.');
                    $validation->setFormula1($municipalityRange);
                }

                // Ajustar largura das colunas
                $sheet->getColumnDimension('A')->setWidth(40); // Nome
                $sheet->getColumnDimension('B')->setWidth(18); // BI
                $sheet->getColumnDimension('C')->setWidth(18); // Data Nascimento
                $sheet->getColumnDimension('D')->setWidth(15); // Género
                $sheet->getColumnDimension('E')->setWidth(18); // Estado Civil
                $sheet->getColumnDimension('F')->setWidth(30); // Nome Pai
                $sheet->getColumnDimension('G')->setWidth(30); // Nome Mãe
                $sheet->getColumnDimension('H')->setWidth(20); // Província
                $sheet->getColumnDimension('I')->setWidth(25); // Município
                $sheet->getColumnDimension('J')->setWidth(30); // Endereço
                $sheet->getColumnDimension('K')->setWidth(15); // Telefone
                $sheet->getColumnDimension('L')->setWidth(25); // Email

                // Instruções
                $sheet->setCellValue('N1', 'INSTRUÇÕES:');
                $sheet->setCellValue('N2', '1. Nome e BI são OBRIGATÓRIOS');
                $sheet->setCellValue('N3', '2. Data Nascimento: dd/mm/aaaa');
                $sheet->setCellValue('N4', '3. Genero, Estado Civil, Provincia');
                $sheet->setCellValue('N5', '   e Municipio têm lista suspensa');
                $sheet->setCellValue('N6', '4. Clique na seta para ver opções');
                $sheet->setCellValue('N7', '5. Duplicados (BI ou Nome) são ignorados');

                $sheet->getStyle('N1')->getFont()->setBold(true);
                $sheet->getColumnDimension('N')->setWidth(38);
            },
        ];
    }
}
