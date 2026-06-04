<?php

namespace App\Imports;

use App\Models\Candidate;
use App\Models\Province;
use App\Models\Municipality;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CandidateImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use Importable, SkipsFailures;

    protected $importStats = [
        'imported' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    // Track names and BIs within the current import to prevent duplicates in the same file
    protected $importedBIs = [];
    protected $importedNames = [];

    /**
     * Normaliza o nome da coluna para formato consistente
     */
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
                str_replace(['º', 'ª'], '', Str::lower($name)),
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
        Log::info('Importando alistado:', $row);

        // Obter valores com flexibilidade de nomes de colunas
        $nome = $this->getColumnValue($row, ['nome', 'Nome', 'NOME', 'name', 'nome_completo', 'full_name']);
        $bi = $this->getColumnValue($row, ['bi', 'BI', 'n_bi', 'nbi', 'numero_bi', 'id_number', 'bilhete']);
        $telefone = $this->getColumnValue($row, ['telefone', 'Telefone', 'phone', 'tel']);
        $email = $this->getColumnValue($row, ['email', 'Email', 'EMAIL', 'e_mail', 'e-mail']);
        $genero = $this->getColumnValue($row, ['genero', 'Genero', 'gender', 'sexo']);
        $dataNascimento = $this->getColumnValue($row, ['data_nascimento', 'nascimento', 'birth_date', 'data_nasc']);
        $estadoCivil = $this->getColumnValue($row, ['estado_civil', 'Estado_Civil', 'estadocivil', 'marital_status']);
        $nomePai = $this->getColumnValue($row, ['nome_pai', 'Nome_Pai', 'pai', 'father_name']);
        $nomeMae = $this->getColumnValue($row, ['nome_mae', 'Nome_Mae', 'mae', 'mother_name']);
        $provincia = $this->getColumnValue($row, ['provincia', 'Provincia', 'province']);
        $municipio = $this->getColumnValue($row, ['municipio', 'Municipio', 'municipality']);
        $endereco = $this->getColumnValue($row, ['endereco', 'Endereco', 'address', 'morada']);

        // Validar campos obrigatórios
        if (empty($nome)) {
            $this->importStats['errors'][] = "Nome está vazio na linha";
            return null;
        }

        if (empty($bi)) {
            $this->importStats['errors'][] = "Nº BI está vazio para: {$nome}";
            return null;
        }

        // Verificar duplicado de BI no banco de dados
        $existingByBI = Candidate::where('id_number', $bi)->first();
        if ($existingByBI) {
            $this->importStats['skipped']++;
            $this->importStats['errors'][] = "BI {$bi} já existe no sistema ({$nome}) - ignorado";
            return null;
        }

        // Verificar duplicado de BI dentro do mesmo ficheiro
        if (in_array($bi, $this->importedBIs)) {
            $this->importStats['skipped']++;
            $this->importStats['errors'][] = "BI {$bi} duplicado no ficheiro ({$nome}) - ignorado";
            return null;
        }

        // Verificar duplicado de nome no banco de dados
        $existingByName = Candidate::where('full_name', $nome)->first();
        if ($existingByName) {
            $this->importStats['skipped']++;
            $this->importStats['errors'][] = "Nome \"{$nome}\" já existe no sistema (BI: {$existingByName->id_number}) - ignorado";
            return null;
        }

        // Verificar duplicado de nome dentro do mesmo ficheiro
        $nomeNormalizado = Str::lower(trim($nome));
        if (in_array($nomeNormalizado, $this->importedNames)) {
            $this->importStats['skipped']++;
            $this->importStats['errors'][] = "Nome \"{$nome}\" duplicado no ficheiro - ignorado";
            return null;
        }

        // Registar BI e nome como importados
        $this->importedBIs[] = $bi;
        $this->importedNames[] = $nomeNormalizado;

        // Buscar província pelo nome
        $provinceId = null;
        if (!empty($provincia)) {
            $province = Province::where('name', $provincia)->orWhere('name', 'LIKE', '%' . $provincia . '%')->first();
            $provinceId = $province?->id;
        }

        // Buscar município pelo nome
        $municipalityId = null;
        if (!empty($municipio)) {
            $query = Municipality::where('name', $municipio)->orWhere('name', 'LIKE', '%' . $municipio . '%');
            if ($provinceId) {
                $query->where('province_id', $provinceId);
            }
            $municipality = $query->first();
            $municipalityId = $municipality?->id;
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

        // Normalizar estado civil
        $maritalValue = null;
        if (!empty($estadoCivil)) {
            $estadoLower = Str::lower(str_replace(['(', ')', 'a'], '', $estadoCivil));
            if (Str::contains($estadoLower, 'solteir')) {
                $maritalValue = 'solteiro';
            } elseif (Str::contains($estadoLower, 'casad')) {
                $maritalValue = 'casado';
            } elseif (Str::contains($estadoLower, 'divorci')) {
                $maritalValue = 'divorciado';
            } elseif (Str::contains($estadoLower, ['viuv', 'viúv'])) {
                $maritalValue = 'viuvo';
            }
        }

        // Converter data de nascimento
        $birthDate = null;
        if (!empty($dataNascimento)) {
            try {
                if (is_numeric($dataNascimento)) {
                    // Excel serial date
                    $birthDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dataNascimento);
                } else {
                    $birthDate = \Carbon\Carbon::parse($dataNascimento);
                }
            } catch (\Exception $e) {
                Log::warning("Data de nascimento inválida para {$nome}: {$dataNascimento}");
            }
        }

        $this->importStats['imported']++;

        return new Candidate([
            'full_name' => $nome,
            'id_number' => $bi,
            'phone' => $telefone,
            'email' => $email,
            'gender' => $genderValue,
            'birth_date' => $birthDate,
            'marital_status' => $maritalValue,
            'father_name' => $nomePai,
            'mother_name' => $nomeMae,
            'province_id' => $provinceId,
            'municipality_id' => $municipalityId,
            'address' => $endereco,
            'student_type' => 'Alistado',
            'status' => 'pendente',
        ]);
    }

    public function rules(): array
    {
        return [];
    }

    public function customValidationMessages(): array
    {
        return [
            'nome.required' => 'O campo Nome é obrigatório',
            'bi.required' => 'O campo Nº BI é obrigatório',
        ];
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
