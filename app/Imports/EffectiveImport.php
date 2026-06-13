<?php

namespace App\Imports;

use App\Models\Effective;
use App\Models\Institution;
use App\Models\Provenance;
use App\Models\Province;
use App\Models\Rank;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class EffectiveImport implements ToModel, WithHeadingRow, WithMultipleSheets, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public function __construct(protected ?int $forcedInstitutionId = null) {}

    protected array $importStats = [
        'imported' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    public function sheets(): array
    {
        return [0 => $this];
    }

    public function model(array $row)
    {
        if ($this->isBlankRow($row)) {
            return null;
        }

        $name = $this->getColumnValue($row, ['nome_completo', 'nome', 'name', 'full_name']);
        $employeeNumber = $this->normalizeDocument($this->getColumnValue($row, ['nip', 'numero_nip', 'employee_number']));
        $identityDocument = $this->normalizeDocument($this->getColumnValue($row, ['bilhete_identidade', 'bi', 'bilhete', 'identity_document', 'document_number']));
        $institutionValue = $this->getColumnValue($row, ['escola', 'instituicao', 'institution']);
        $staffTypeInput = $this->getColumnValue($row, ['tipo_efectivo', 'tipo_efetivo', 'tipo', 'regime', 'staff_type']);
        $staffType = $this->normalizeStaffType($staffTypeInput, $employeeNumber, $identityDocument);

        if (blank($name) && blank($employeeNumber) && blank($identityDocument) && blank($staffTypeInput) && blank($institutionValue)) {
            return null;
        }

        if (blank($name)) {
            $this->skip('Nome_Completo esta vazio.');

            return null;
        }

        if ($staffType === 'regime_especial' && blank($employeeNumber)) {
            $this->skip("NIP e obrigatorio para Regime Especial ({$name}).");

            return null;
        }

        if ($staffType === 'regime_geral' && blank($identityDocument)) {
            $this->skip("Bilhete_Identidade e obrigatorio para Regime Geral ({$name}).");

            return null;
        }

        if ($staffType === 'regime_especial' && Effective::withTrashed()->where('employee_number', $employeeNumber)->exists()) {
            $this->skip("NIP {$employeeNumber} ja existe ({$name}).");

            return null;
        }

        if ($staffType === 'regime_geral' && Effective::withTrashed()->where('identity_document', $identityDocument)->exists()) {
            $this->skip("Bilhete de Identidade {$identityDocument} ja existe ({$name}).");

            return null;
        }

        $institutionId = $this->forcedInstitutionId ?: $this->findInstitutionId($institutionValue);

        if (blank($institutionId)) {
            $this->skip(blank($institutionValue)
                ? "Escola e obrigatoria ({$name})."
                : "Escola '{$institutionValue}' nao encontrada ({$name}).");

            return null;
        }

        $this->importStats['imported']++;

        return new Effective([
            'institution_id' => $institutionId,
            'staff_type' => $staffType,
            'full_name' => $name,
            'employee_number' => $staffType === 'regime_especial' ? $employeeNumber : null,
            'identity_document' => $staffType === 'regime_geral' ? $identityDocument : null,
            'document_type' => $staffType === 'regime_geral' ? 'Bilhete de Identidade' : 'NIP',
            'document_number' => $staffType === 'regime_geral' ? $identityDocument : $employeeNumber,
            'gender' => $this->normalizeGender($this->getColumnValue($row, ['sexo', 'genero', 'gender'])),
            'blood_type' => $this->normalizeBloodType($this->getColumnValue($row, ['grupo_sanguineo', 'grupo', 'blood_type'])),
            'country' => $this->normalizeCountry($this->getColumnValue($row, ['pais_origem', 'pais_de_origem', 'country', 'country_origin'])),
            'province' => $this->matchProvince($this->getColumnValue($row, ['provincia', 'province'])),
            'birth_date' => $this->parseDate($this->getColumnValue($row, ['data_nascimento', 'data_de_nascimento', 'birth_date'])),
            'father_name' => $this->getColumnValue($row, ['nome_pai', 'nome_do_pai', 'father_name']),
            'mother_name' => $this->getColumnValue($row, ['nome_mae', 'nome_da_mae', 'mother_name']),
            'position' => $staffType === 'regime_especial'
                ? $this->matchRankName($this->getColumnValue($row, ['patente', 'rank', 'posto', 'position']))
                : null,
            'education_level' => $this->normalizeEducationLevel($this->getColumnValue($row, ['grau_academico', 'nivel_academico', 'habilitacao', 'education_level', 'escolaridade'])),
            'situation' => $this->normalizeSituation($this->getColumnValue($row, ['situacao', 'situation'])),
            'specialization' => $this->getColumnValue($row, ['especializacao', 'specialization']),
            'department' => $this->getColumnValue($row, ['departamento', 'department']),
            'placement_organ' => $staffType === 'regime_especial'
                ? $this->findProvenanceName($this->getColumnValue($row, ['orgao_proveniencia', 'orgao_colocacao', 'orgao', 'organ', 'unidade']))
                : null,
            'job_function' => $this->getColumnValue($row, ['funcao', 'job_function', 'function']),
            'phone' => $this->getColumnValue($row, ['telefone', 'phone', 'tel']),
            'email' => $this->getColumnValue($row, ['email', 'e_mail']),
            'hire_date' => $this->parseDate($this->getColumnValue($row, ['data_admissao', 'data_de_admissao', 'hire_date', 'admission_date'])),
            'notes' => $this->getColumnValue($row, ['biografia', 'notas', 'notes', 'biography']),
            'is_active' => $this->normalizeActive($this->getColumnValue($row, ['activo', 'ativo', 'active', 'is_active'])),
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

    protected function getColumnValue(array $row, array $possibleNames, mixed $default = null): mixed
    {
        $normalizedRow = [];

        foreach ($row as $key => $value) {
            $normalizedRow[$this->normalizeColumnName((string) $key)] = $value;
        }

        foreach ($possibleNames as $name) {
            $key = $this->normalizeColumnName($name);

            if (array_key_exists($key, $normalizedRow)) {
                return $this->cleanValue($normalizedRow[$key]) ?? $default;
            }
        }

        return $default;
    }

    protected function cleanValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->cleanValue($value) !== null) {
                return false;
            }
        }

        return true;
    }

    protected function normalizeColumnName(string $name): string
    {
        $name = Str::ascii($name);
        $name = Str::lower($name);
        $name = preg_replace('/[^a-z0-9]+/', '_', $name) ?: $name;

        return trim($name, '_');
    }

    protected function normalizeLookupText(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = Str::lower($value);
        $value = preg_replace('/\([^)]*\)/', ' ', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?: $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?: $value);
    }

    protected function normalizeDocument(mixed $value): ?string
    {
        $value = $this->cleanValue($value);

        if ($value === null) {
            return null;
        }

        return Str::upper(preg_replace('/\s+/', '', (string) $value) ?: (string) $value);
    }

    protected function normalizeStaffType(mixed $value, ?string $employeeNumber, ?string $identityDocument): string
    {
        $type = $this->normalizeLookupText($this->cleanValue($value));

        if ($type === '' && filled($identityDocument) && blank($employeeNumber)) {
            return 'regime_geral';
        }

        if (str_contains($type, 'civil') || str_contains($type, 'geral')) {
            return 'regime_geral';
        }

        return 'regime_especial';
    }

    protected function normalizeGender(mixed $value): ?string
    {
        $gender = $this->normalizeLookupText($this->cleanValue($value));

        return match ($gender) {
            'm', 'masculino', 'male' => 'Masculino',
            'f', 'feminino', 'female' => 'Feminino',
            default => null,
        };
    }

    protected function normalizeBloodType(mixed $value): ?string
    {
        $bloodType = $this->cleanValue($value);

        if ($bloodType === null) {
            return null;
        }

        $normalized = Str::upper(preg_replace('/\s+/', '', Str::ascii((string) $bloodType)) ?: (string) $bloodType);
        $aliases = [
            'APOSITIVO' => 'A+',
            'ANEGATIVO' => 'A-',
            'BPOSITIVO' => 'B+',
            'BNEGATIVO' => 'B-',
            'ABPOSITIVO' => 'AB+',
            'ABNEGATIVO' => 'AB-',
            'OPOSITIVO' => 'O+',
            'ONEGATIVO' => 'O-',
        ];

        return Effective::bloodTypeOptions()[$normalized] ?? $aliases[$normalized] ?? null;
    }

    protected function normalizeCountry(mixed $value): ?string
    {
        $country = $this->cleanValue($value);

        if ($country === null) {
            return 'Angola';
        }

        $options = [
            'Angola',
            'Brasil',
            'Cabo Verde',
            'Guiné-Bissau',
            'Moçambique',
            'Portugal',
            'São Tomé e Príncipe',
        ];

        return $this->matchOption($country, $options) ?? $country;
    }

    protected function normalizeEducationLevel(mixed $value): ?string
    {
        $educationLevel = $this->cleanValue($value);

        if ($educationLevel === null) {
            return null;
        }

        $options = [
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

        $aliases = [
            'ensino primario' => 'Ensino Primário',
            '7a classe' => '7ª Classe',
            '7 classe' => '7ª Classe',
            '8a classe' => '8ª Classe',
            '8 classe' => '8ª Classe',
            '9a classe' => '9ª Classe',
            '9 classe' => '9ª Classe',
            '10a classe' => '10ª Classe',
            '10 classe' => '10ª Classe',
            '11a classe' => '11ª Classe',
            '11 classe' => '11ª Classe',
            '12a classe' => '12ª Classe',
            '12 classe' => '12ª Classe',
            'tecnico medio' => 'Ensino Médio Técnico',
            'tecnico profissional' => 'Ensino Médio Técnico',
            'ensino medio tecnico' => 'Ensino Médio Técnico',
            'pos graduacao' => 'Pós-Graduação',
        ];

        $matched = $this->matchOption($educationLevel, $options);

        return $matched ?? $aliases[$this->normalizeLookupText($educationLevel)] ?? $educationLevel;
    }

    protected function normalizeSituation(mixed $value): string
    {
        $situation = $this->cleanValue($value);

        if ($situation === null) {
            return 'Efectivo';
        }

        $options = ['Efectivo', 'Contratado', 'Convidado', 'Reformado', 'Inactivo'];

        return $this->matchOption($situation, $options) ?? $situation;
    }

    protected function normalizeActive(mixed $value): bool
    {
        $active = $this->normalizeLookupText($this->cleanValue($value));

        if ($active === '') {
            return true;
        }

        return ! in_array($active, ['0', 'nao', 'no', 'false', 'inactivo', 'inativo'], true);
    }

    protected function parseDate(mixed $value): ?string
    {
        $value = $this->cleanValue($value);

        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            try {
                return Carbon::createFromFormat($format, (string) $value)->format('Y-m-d');
            } catch (Throwable) {
                // Try the next accepted format.
            }
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    protected function findInstitutionId(mixed $value): ?int
    {
        $institution = $this->findByNameOrAcronym(Institution::query(), $this->cleanValue($value), true);

        return $institution?->id;
    }

    protected function matchRankName(mixed $value): ?string
    {
        $rankName = $this->cleanValue($value);

        if ($rankName === null) {
            return null;
        }

        $rank = $this->findByNameOrAcronym(Rank::query(), $rankName, true);

        return $rank?->name ?? $rankName;
    }

    protected function findProvenanceName(mixed $value): ?string
    {
        $value = $this->cleanValue($value);
        $provenance = $this->findByNameOrAcronym(Provenance::query(), $value, true);

        return $provenance?->name ?? $value;
    }

    protected function matchProvince(mixed $value): ?string
    {
        $province = $this->cleanValue($value);

        if ($province === null) {
            return null;
        }

        $matched = Province::query()
            ->orderBy('name')
            ->pluck('name')
            ->first(fn (string $name): bool => $this->normalizeLookupText($name) === $this->normalizeLookupText($province));

        return $matched ?? $province;
    }

    protected function findByNameOrAcronym($query, ?string $value, bool $hasAcronym): mixed
    {
        if ($value === null) {
            return null;
        }

        [$name, $acronym] = $this->splitNameAndAcronym($value);

        $record = (clone $query)
            ->where('name', $value)
            ->when($name !== $value, fn ($query) => $query->orWhere('name', $name))
            ->when($hasAcronym && $acronym !== null, fn ($query) => $query->orWhere('acronym', $acronym))
            ->first();

        if ($record !== null) {
            return $record;
        }

        $normalizedValue = $this->normalizeLookupText($value);

        return (clone $query)
            ->get()
            ->first(function ($record) use ($hasAcronym, $normalizedValue): bool {
                $normalizedName = $this->normalizeLookupText($record->name ?? '');
                $normalizedAcronym = $hasAcronym ? $this->normalizeLookupText($record->acronym ?? '') : '';

                return $normalizedName === $normalizedValue
                    || $normalizedAcronym === $normalizedValue
                    || ($normalizedValue !== '' && str_contains($normalizedName, $normalizedValue))
                    || ($normalizedName !== '' && str_contains($normalizedValue, $normalizedName));
            });
    }

    protected function splitNameAndAcronym(string $value): array
    {
        if (preg_match('/^(.*?)\s*\(([^)]+)\)\s*$/', $value, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return [$value, null];
    }

    protected function matchOption(string $value, array $options): ?string
    {
        $normalizedValue = $this->normalizeLookupText($value);

        foreach ($options as $option) {
            if ($this->normalizeLookupText($option) === $normalizedValue) {
                return $option;
            }
        }

        return null;
    }

    protected function skip(string $message): void
    {
        $this->importStats['skipped']++;
        $this->importStats['errors'][] = $message;
    }
}
