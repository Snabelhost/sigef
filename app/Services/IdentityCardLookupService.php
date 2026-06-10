<?php

namespace App\Services;

use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class IdentityCardLookupService
{
    public function normalizeDocument(mixed $identityDocument): ?string
    {
        $identityDocument = preg_replace('/\s+/u', '', trim((string) $identityDocument));

        return $identityDocument === '' ? null : Str::upper($identityDocument);
    }

    public function isValidAngolanDocument(?string $identityDocument): bool
    {
        return is_string($identityDocument)
            && preg_match('/^\d{9}[A-Z]{2}\d{3}$/', $identityDocument) === 1;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function lookup(string $identityDocument): ?array
    {
        $cacheKey = 'identity-card-lookup:'.$identityDocument;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $baseUrl = rtrim((string) config('services.identity_card_lookup.url', 'https://consulta.edgarsingui.ao/consultar'), '/');

            $response = Http::acceptJson()
                ->timeout(8)
                ->retry(1, 250)
                ->get($baseUrl.'/'.rawurlencode($identityDocument));

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();

            if (! is_array($data)) {
                return null;
            }

            if (($data['error'] ?? true) === false) {
                Cache::put($cacheKey, $data, now()->addHours(12));
            }

            return $data;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function formatName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?: '';

        return mb_strtoupper($name, 'UTF-8');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function extractGender(array $data): ?string
    {
        $gender = $this->firstValue($data, [
            'sexo',
            'genero',
            'gender',
            'sex',
        ]);

        if ($gender === null) {
            return null;
        }

        return match ($this->normalizeLookupText($gender)) {
            'm', 'masculino', 'male', 'homem' => 'Masculino',
            'f', 'feminino', 'female', 'mulher' => 'Feminino',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function extractProvinceName(array $data): ?string
    {
        $province = $this->firstValue($data, [
            'provincia',
            'province',
            'naturalidade',
            'local_nascimento',
            'local_de_nascimento',
            'provincia_nascimento',
            'provincia_de_nascimento',
            'birth_place',
            'birth_province',
            'province_birth',
        ]);

        return $province === null ? null : $this->matchProvinceName($province);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function extractMunicipalityName(array $data, ?int $provinceId = null): ?string
    {
        $municipality = $this->firstValue($data, [
            'municipio',
            'município',
            'municipality',
            'naturalidade_municipio',
            'municipio_nascimento',
            'municipio_de_nascimento',
            'birth_municipality',
            'localidade',
            'comuna',
        ]);

        return $municipality === null ? null : $this->matchMunicipalityName($municipality, $provinceId);
    }

    public function provinceId(?string $provinceName): ?int
    {
        if (blank($provinceName)) {
            return null;
        }

        return Province::query()->where('name', $provinceName)->value('id');
    }

    public function municipalityId(?string $municipalityName, ?int $provinceId = null): ?int
    {
        if (blank($municipalityName)) {
            return null;
        }

        return Municipality::query()
            ->when($provinceId, fn ($query) => $query->where('province_id', $provinceId))
            ->where('name', $municipalityName)
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    public function firstValue(array $data, array $keys): ?string
    {
        $normalizedKeys = array_flip(array_map($this->normalizeLookupKey(...), $keys));

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nestedValue = $this->firstValue($value, array_keys($normalizedKeys));

                if ($nestedValue !== null) {
                    return $nestedValue;
                }

                continue;
            }

            if (! array_key_exists($this->normalizeLookupKey((string) $key), $normalizedKeys)) {
                continue;
            }

            if (is_scalar($value) && filled((string) $value)) {
                return (string) $value;
            }
        }

        return null;
    }

    public function matchProvinceName(string $province): ?string
    {
        $normalizedProvince = $this->normalizeLookupText($province);
        $normalizedProvince = preg_replace('/\b(provincia|province|prov|de|da|do)\b/u', ' ', $normalizedProvince) ?: $normalizedProvince;
        $normalizedProvince = trim(preg_replace('/\s+/u', ' ', $normalizedProvince) ?: '');

        $aliases = [
            'kwanza norte' => 'cuanza norte',
            'kwanza sul' => 'cuanza sul',
            'kuando kubango' => 'cuando cubango',
        ];

        $normalizedProvince = $aliases[$normalizedProvince] ?? $normalizedProvince;

        if ($normalizedProvince === '') {
            return null;
        }

        $provinces = Province::query()->orderBy('name')->pluck('name')->all();

        foreach ($provinces as $provinceName) {
            if ($this->normalizeLookupText((string) $provinceName) === $normalizedProvince) {
                return (string) $provinceName;
            }
        }

        foreach ($provinces as $provinceName) {
            $normalizedName = $this->normalizeLookupText((string) $provinceName);

            if (strlen($normalizedName) >= 5 && str_contains($normalizedProvince, $normalizedName)) {
                return (string) $provinceName;
            }
        }

        return null;
    }

    public function matchMunicipalityName(string $municipality, ?int $provinceId = null): ?string
    {
        $normalizedMunicipality = $this->normalizeLookupText($municipality);
        $normalizedMunicipality = preg_replace('/\b(municipio|municipality|mun|de|da|do)\b/u', ' ', $normalizedMunicipality) ?: $normalizedMunicipality;
        $normalizedMunicipality = trim(preg_replace('/\s+/u', ' ', $normalizedMunicipality) ?: '');

        if ($normalizedMunicipality === '') {
            return null;
        }

        $municipalities = Municipality::query()
            ->when($provinceId, fn ($query) => $query->where('province_id', $provinceId))
            ->orderBy('name')
            ->pluck('name')
            ->all();

        foreach ($municipalities as $municipalityName) {
            if ($this->normalizeLookupText((string) $municipalityName) === $normalizedMunicipality) {
                return (string) $municipalityName;
            }
        }

        foreach ($municipalities as $municipalityName) {
            $normalizedName = $this->normalizeLookupText((string) $municipalityName);

            if (strlen($normalizedName) >= 4 && str_contains($normalizedMunicipality, $normalizedName)) {
                return (string) $municipalityName;
            }
        }

        return null;
    }

    public function normalizeLookupKey(string $key): string
    {
        $key = mb_strtolower(Str::ascii($key), 'UTF-8');

        return trim(preg_replace('/[^a-z0-9]+/u', '_', $key) ?: '', '_');
    }

    public function normalizeLookupText(string $value): string
    {
        $value = mb_strtolower(Str::ascii($value), 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?: '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?: '');
    }
}
