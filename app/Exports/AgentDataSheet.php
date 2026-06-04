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

class AgentDataSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithEvents
{
    protected $rankCount;
    protected $provenanceCount;

    public function __construct()
    {
        $this->rankCount = Rank::count();
        $this->provenanceCount = Provenance::count();
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
            'Telefone',
            'Patente',
            'Proveniencia',
        ];
    }

    public function array(): array
    {
        // Retornar linhas vazias para preenchimento
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data[] = ['', '', '', '', ''];
        }
        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Cabeçalho em negrito com fundo azul
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
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Referência para a folha de listas
                $rankRange = 'Listas!$A$2:$A$' . ($this->rankCount + 1);
                $provenanceRange = 'Listas!$B$2:$B$' . ($this->provenanceCount + 1);
                
                // Aplicar validação para 50 linhas (D2:D51 - Patente)
                for ($row = 2; $row <= 51; $row++) {
                    $validation = $sheet->getCell("D{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Patente inválida');
                    $validation->setError('Por favor, selecione uma patente da lista.');
                    $validation->setPromptTitle('Patente');
                    $validation->setPrompt('Clique na seta para selecionar a patente.');
                    $validation->setFormula1($rankRange);
                }
                
                // Aplicar validação para 50 linhas (E2:E51 - Proveniência)
                for ($row = 2; $row <= 51; $row++) {
                    $validation = $sheet->getCell("E{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Proveniência inválida');
                    $validation->setError('Por favor, selecione uma proveniência da lista.');
                    $validation->setPromptTitle('Proveniência');
                    $validation->setPrompt('Clique na seta para selecionar a proveniência.');
                    $validation->setFormula1($provenanceRange);
                }
                
                // Ajustar largura das colunas
                $sheet->getColumnDimension('A')->setWidth(40); // Nome
                $sheet->getColumnDimension('B')->setWidth(15); // NIP
                $sheet->getColumnDimension('C')->setWidth(15); // Telefone
                $sheet->getColumnDimension('D')->setWidth(25); // Patente
                $sheet->getColumnDimension('E')->setWidth(50); // Proveniência
                
                // Adicionar instruções
                $sheet->setCellValue('G1', 'INSTRUÇÕES:');
                $sheet->setCellValue('G2', '1. Nome e NIP são OBRIGATÓRIOS');
                $sheet->setCellValue('G3', '2. Clique na célula de Patente/Proveniência');
                $sheet->setCellValue('G4', '3. Uma seta aparecerá - clique nela');
                $sheet->setCellValue('G5', '4. Selecione da lista suspensa');
                $sheet->setCellValue('G6', '5. Salve o arquivo e importe no sistema');
                
                // Estilo das instruções
                $sheet->getStyle('G1')->getFont()->setBold(true);
                $sheet->getStyle('G1:G6')->getFont()->setSize(10);
                $sheet->getColumnDimension('G')->setWidth(45);
            },
        ];
    }
}
