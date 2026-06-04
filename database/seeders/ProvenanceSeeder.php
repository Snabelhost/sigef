<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Provenance;

class ProvenanceSeeder extends Seeder
{
    public function run(): void
    {
        $provenances = [
    ['name' => 'ACADEMIA DE POLÍCIA', 'acronym' => 'ACADEPOL'],
    ['name' => 'CENTRO DE FORMAÇÃO E ADESTRAMENTO DE CAVALERIA E CINOTECNIA', 'acronym' => 'CFACC'],
    ['name' => 'COLÉGIO DE POLÍCIA', 'acronym' => 'COPOL'],
    ['name' => 'CORPO DE CONSELHEIROS', 'acronym' => 'C.C'],
    ['name' => 'DIRECÇÃO DE ADMINISTRAÇÃO E SERVIÇOS', 'acronym' => 'DAS'],
    ['name' => 'DIRECÇÃO DE ASSESSORIA JURÍDICA', 'acronym' => 'DAJ'],
    ['name' => 'DIRECÇÃO DE COMUNICAÇÃO INSTITUCIONAL E IMPRENSA', 'acronym' => 'DCII'],
    ['name' => 'DIRECÇÃO DE DOUTRINA E ENSINO POLICIAL', 'acronym' => 'DEPOL'],
    ['name' => 'DIRECÇÃO DE EDUCAÇÃO PATRIÓTICA', 'acronym' => 'DEPAT'],
    ['name' => 'DIRECÇÃO DE ESTUDOS E PLANEAMENTO', 'acronym' => 'DEP'],
    ['name' => 'DIRECÇÃO DE FINANÇAS', 'acronym' => 'DIF'],
    ['name' => 'DIRECÇÃO DE INFORMAÇÕES POLICIAIS', 'acronym' => 'DINFOP'],
    ['name' => 'DIRECÇÃO DE INFRA-ESTRUTURAS E EQUIPAMENTOS', 'acronym' => 'DIE'],
    ['name' => 'DIRECÇÃO DE INTERCÂMBIO E COOPERAÇÃO', 'acronym' => 'DIC'],
    ['name' => 'DIRECÇÃO DE INVESTIGAÇÃO DE ILÍCITOS PENAIS', 'acronym' => 'DIIP'],
    ['name' => 'DIRECÇÃO DE LOGÍSTICA', 'acronym' => 'DL'],
    ['name' => 'DIRECÇÃO DE PESSOAL E QUADROS', 'acronym' => 'DPQ'],
    ['name' => 'DIRECÇÃO DE SEGURANÇA PÚBLICA E OPERAÇÕES', 'acronym' => 'DISPO'],
    ['name' => 'DIRECÇÃO DE SERVIÇOS DE SAÚDE', 'acronym' => 'DSS'],
    ['name' => 'DIRECÇÃO DE TELECOMUNICAÇÕES E TECNOLOGIAS DE INFORMAÇÃO', 'acronym' => 'DTTI'],
    ['name' => 'DIRECÇÃO DE TRÂNSITO E SEGURANÇA RODOVIÁRIA', 'acronym' => 'DTSER'],
    ['name' => 'DIRECÇÃO DE TRANSPORTES', 'acronym' => 'DT'],
    ['name' => 'ESCOLA PRÁTICA DE POLÍCIA', 'acronym' => 'EPP'],
    ['name' => 'GABINETE DO 2.º COMANDANTE GERAL I', 'acronym' => 'Gab. 2.º CGPNA'],
    ['name' => 'GABINETE DO 2.º COMANDANTE GERAL II', 'acronym' => 'Gab. 2.º CGPNA.'],
    ['name' => 'GABINETE DO COMANDANTE GERAL', 'acronym' => 'Gab. CGPNA'],
    ['name' => 'INSPECÇÃO DA PNA', 'acronym' => 'IPNA'],
    ['name' => 'INSTITUTO SUPERIOR DE CIÊNCIAS POLICIAIS E CRIMINAIS', 'acronym' => 'ISCPC'],
    ['name' => 'POLÍCIA DE GUARDA FRONTEIRAS', 'acronym' => 'PGF'],
    ['name' => 'POLÍCIA DE INTERVENÇÃO RÁPIDA', 'acronym' => 'PIR'],
    ['name' => 'POLÍCIA DE SEGURANÇA DE OBJECTIVOS ESTRATÉGICOS', 'acronym' => 'PSOE'],
    ['name' => 'POLÍCIA DE SEGURANÇA PESSOAL E DE ENTIDADES PROTOCOLARES', 'acronym' => 'PSPEP'],
    ['name' => 'POLÍCIA FISCAL ADUANEIRA', 'acronym' => 'PFA'],
    ['name' => 'UNIDADE AEROPORTUÁRIA', 'acronym' => 'UA'],
    ['name' => 'UNIDADE DE AVIAÇÃO', 'acronym' => 'UAv'],
    ['name' => 'UNIDADE PORTUÁRIA', 'acronym' => 'UP'],
]; 

        foreach ($provenances as $provenance) {
            Provenance::updateOrCreate(
                ['acronym' => $provenance['acronym']],
                $provenance
            );
        }
    }
}
