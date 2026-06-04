<?php

namespace App\Imports;

use App\Models\Trainer;
use App\Models\Rank;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TrainerImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use Importable, SkipsFailures;

    protected $importStats = [
        'imported' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    protected function getColumnValue(array $row, array $possibleNames, $default = null)
    {
        foreach ($possibleNames as $name) {
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

    public function model(array $row)
    {
        Log::info('Importando formador:', $row);

        $nome = $this->getColumnValue($row, ['nome', 'Nome', 'NOME', 'name', 'nome_completo', 'full_name']);
        $nip = $this->getColumnValue($row, ['nip', 'NIP', 'numero_nip']);
        $bilhete = $this->getColumnValue($row, ['bi', 'BI', 'bilhete', 'bilhete_identidade', 'id_number']);
        $genero = $this->getColumnValue($row, ['genero', 'Genero', 'gender', 'sexo']);
        $telefone = $this->getColumnValue($row, ['telefone', 'Telefone', 'phone', 'tel']);
        $patente = $this->getColumnValue($row, ['patente', 'Patente', 'rank', 'posto']);
        $orgao = $this->getColumnValue($row, ['orgao', 'Orgao', 'organ', 'orgao_unidade', 'unidade']);
        $nivelAcademico = $this->getColumnValue($row, ['nivel_academico', 'habilitacao', 'education_level', 'escolaridade']);
        $tipo = $this->getColumnValue($row, ['tipo', 'Tipo', 'trainer_type', 'tipo_formador']);

        // Validar campos obrigatórios
        if (empty($nome)) {
            $this->importStats['errors'][] = "Nome está vazio na linha";
            return null;
        }

        // Verificar duplicado por nome
        $existing = Trainer::where('full_name', $nome)->first();
        if ($existing) {
            $this->importStats['skipped']++;
            $this->importStats['errors'][] = "Formador '{$nome}' já existe";
            return null;
        }

        // Verificar duplicado por NIP
        if (!empty($nip)) {
            $existingNip = Trainer::where('nip', $nip)->first();
            if ($existingNip) {
                $this->importStats['skipped']++;
                $this->importStats['errors'][] = "NIP {$nip} já existe ({$nome})";
                return null;
            }
        }

        // Buscar patente pelo nome
        $rankId = null;
        if (!empty($patente)) {
            $rank = Rank::where('name', $patente)->orWhere('name', 'LIKE', '%' . $patente . '%')->first();
            $rankId = $rank?->id;
        }

        // Normalizar género
        $genderValue = null;
        if (!empty($genero)) {
            $generoLower = Str::lower($genero);
            if (in_array($generoLower, ['m', 'masculino', 'male'])) {
                $genderValue = 'Masculino';
            } elseif (in_array($generoLower, ['f', 'feminino', 'female'])) {
                $genderValue = 'Feminino';
            }
        }

        // Normalizar tipo de formador
        $trainerType = 'Fardado';
        if (!empty($tipo)) {
            $tipoLower = Str::lower($tipo);
            if (in_array($tipoLower, ['civil', 'regime geral', 'geral'])) {
                $trainerType = 'Civil';
            }
        }

        $this->importStats['imported']++;

        return new Trainer([
            'full_name' => $nome,
            'nip' => $nip,
            'bilhete' => $bilhete,
            'gender' => $genderValue,
            'phone' => $telefone,
            'rank_id' => $rankId,
            'organ' => $orgao,
            'education_level' => $nivelAcademico,
            'trainer_type' => $trainerType,
            'is_active' => true,
        ]);
    }

    public function rules(): array
    {
        return [];
    }

    public function getImportStats(): array
    {
        return $this->importStats;
    }

    public function getDetailedErrors(): array
    {
        return $this->importStats['errors'];
    }
}
