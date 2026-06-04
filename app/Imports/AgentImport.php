<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Candidate;
use App\Models\Institution;
use App\Models\Provenance;
use App\Models\Rank;
use App\Models\RecruitmentType;
use App\Models\StudentType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AgentImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use Importable, SkipsFailures;

    protected $importStats = [
        'imported' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    /**
     * Normaliza o nome da coluna para formato consistente
     */
    protected function getColumnValue(array $row, array $possibleNames, $default = null)
    {
        foreach ($possibleNames as $name) {
            // Tenta várias variações
            $variations = [
                $name,
                Str::lower($name),
                Str::snake($name),
                Str::camel($name),
                str_replace(' ', '_', Str::lower($name)),
                str_replace('_', '', Str::lower($name)),
            ];

            foreach ($variations as $variation) {
                if (isset($row[$variation]) && !empty($row[$variation])) {
                    return $row[$variation];
                }
            }
        }
        return $default;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Log para debug
        Log::info('Importando linha:', $row);

        // Obter valores com flexibilidade de nomes de colunas
        $nome = $this->getColumnValue($row, ['nome', 'Nome', 'NOME', 'name', 'Nome Completo', 'nome_completo']);
        $nip = $this->getColumnValue($row, ['nip', 'NIP', 'Nip', 'numero_identificacao', 'nuri']);
        $telefone = $this->getColumnValue($row, ['telefone', 'Telefone', 'TELEFONE', 'phone', 'tel']);
        $patente = $this->getColumnValue($row, ['patente', 'Patente', 'PATENTE', 'rank', 'graduacao']);
        $proveniencia = $this->getColumnValue($row, ['proveniencia', 'Proveniencia', 'Proveniência', 'PROVENIENCIA', 'orgao', 'unidade']);
        $nrOrdem = $this->getColumnValue($row, ['nr_ordem', 'Nr_Ordem', 'NR_ORDEM', 'numero_ordem', 'ordem', 'nr ordem']);

        // Validar campos obrigatórios
        if (empty($nome)) {
            $this->importStats['errors'][] = "Nome está vazio na linha";
            Log::warning('Nome vazio na importação', $row);
            return null;
        }

        if (empty($nip)) {
            $this->importStats['errors'][] = "NIP está vazio para: {$nome}";
            Log::warning('NIP vazio na importação', $row);
            return null;
        }

        // Verificar se o NIP já existe no Student
        $existingStudent = Student::where('nuri', $nip)->first();
        if ($existingStudent) {
            $this->importStats['skipped']++;
            $this->importStats['errors'][] = "NIP {$nip} já existe como cadete ({$nome})";
            Log::info("NIP {$nip} já existe em students, pulando");
            return null;
        }

        // Verificar se o NIP já existe no Candidate (id_number)
        $existingCandidate = Candidate::where('id_number', $nip)->first();
        $candidateAlreadyExists = $existingCandidate !== null;

        // Obter o primeiro tipo de recrutamento disponível
        $recruitmentTypeId = RecruitmentType::first()?->id;

        try {
            // Buscar ou criar o candidato
            $candidate = Candidate::firstOrCreate(
                ['id_number' => $nip],
                [
                    'full_name' => $nome,
                    'recruitment_type_id' => $recruitmentTypeId,
                    'student_type' => 'Formando',
                    'status' => 'aprovado',
                    'phone' => $telefone,
                ]
            );

            // Atualizar dados do candidato existente se necessário
            if ($candidateAlreadyExists) {
                $candidate->update([
                    'phone' => $telefone ?? $candidate->phone,
                ]);
            }

            // Buscar patente pelo nome (se fornecido)
            $rankId = null;
            if (!empty($patente)) {
                // Primeiro tenta correspondência exata
                $rank = Rank::where('name', $patente)->first();
                // Se não encontrar, tenta correspondência parcial
                if (!$rank) {
                    $rank = Rank::where('name', 'LIKE', '%' . $patente . '%')->first();
                }
                $rankId = $rank?->id;
            }

            // Buscar proveniência pelo nome (se fornecido)
            $provenanceId = null;
            if (!empty($proveniencia)) {
                // Primeiro tenta correspondência exata
                $provenance = Provenance::where('name', $proveniencia)->first();
                // Se não encontrar, tenta correspondência parcial
                if (!$provenance) {
                    $provenance = Provenance::where('name', 'LIKE', '%' . $proveniencia . '%')->first();
                }
                $provenanceId = $provenance?->id;
            }

            // Obter o student_type_id
            $studentTypeId = StudentType::getIdByName('Formando');

            $this->importStats['imported']++;

            return new Student([
                'candidate_id' => $candidate->id,
                'student_number' => $nrOrdem ?? $nip,
                'nuri' => $nip,
                'phone' => $telefone,
                'rank_id' => $rankId,
                'provenance_id' => $provenanceId,
                'student_type' => 'Formando',
                'student_type_id' => $studentTypeId,
                'status' => 'em_formacao',
                'enrollment_date' => now(),
            ]);
        } catch (\Exception $e) {
            $this->importStats['errors'][] = "Erro ao importar {$nome}: " . $e->getMessage();
            Log::error('Erro na importação', ['nome' => $nome, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Regras de validação - mais flexíveis
     */
    public function rules(): array
    {
        return [
            // Remover validação estrita para permitir diferentes formatos de cabeçalho
        ];
    }

    /**
     * Mensagens de validação personalizadas
     */
    public function customValidationMessages(): array
    {
        return [
            'nome.required' => 'O campo Nome é obrigatório',
            'nip.required' => 'O campo NIP é obrigatório',
        ];
    }

    /**
     * Retorna estatísticas de importação
     */
    public function getImportStats(): array
    {
        return $this->importStats;
    }

    /**
     * Retorna erros detalhados
     */
    public function getDetailedErrors(): array
    {
        return $this->importStats['errors'];
    }
}
