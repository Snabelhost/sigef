<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Municipality;
use App\Models\Province;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class RecruitmentPortalCandidateSyncService
{
    public function sync(?string $endpoint = null): array
    {
        $endpoint = $endpoint ?: config('services.recruitment_portal.candidates_url', 'http://10.110.2.18/api/candidates');

        $portalData = $this->fetchPortalRecords($endpoint);
        $records = $portalData['records'];

        $stats = [
            'received' => count($records),
            'pages' => $portalData['pages'],
            'approved' => 0,
            'rejected' => 0,
            'pending' => 0,
            'other' => 0,
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        $seen = [];

        foreach ($records as $record) {
            $data = $this->normalizeRecord($record);

            if (! filled($data['full_name'])) {
                $stats['skipped']++;
                continue;
            }

            $signature = $this->duplicateSignature($data);

            if ($signature && isset($seen[$signature])) {
                $stats['skipped']++;
                continue;
            }

            if ($signature) {
                $seen[$signature] = true;
            }

            $candidate = $this->findCandidate($data);
            $payload = Arr::except($data, ['lookup_keys']);
            $payload['student_type'] = 'Alistado';
            $payload['status'] = $data['status'] ?: 'Pendente';

            if ($candidate) {
                $candidate->restore();
                $candidate->fill(array_filter(
                    $payload,
                    fn ($value): bool => $value !== null && $value !== '',
                ));
                $candidate->save();
                $stats['updated']++;
                $stats['synced']++;
                $this->incrementStatusStats($stats, $payload['status']);
                continue;
            }

            Candidate::query()->create($payload);
            $stats['created']++;
            $stats['synced']++;
            $this->incrementStatusStats($stats, $payload['status']);
        }

        return $stats;
    }

    protected function fetchPortalRecords(string $endpoint): array
    {
        $records = [];
        $pages = 0;
        $visited = [];
        $nextEndpoint = $endpoint;
        $maxPages = max(1, (int) config('services.recruitment_portal.max_pages', 100));

        while (filled($nextEndpoint) && $pages < $maxPages) {
            if (isset($visited[$nextEndpoint])) {
                break;
            }

            $visited[$nextEndpoint] = true;

            $response = Http::acceptJson()
                ->timeout((int) config('services.recruitment_portal.timeout', 25))
                ->get($nextEndpoint);

            if (! $response->successful()) {
                throw new \RuntimeException("Portal respondeu com HTTP {$response->status()}.");
            }

            $payload = $response->json();
            $pages++;
            $records = array_merge($records, $this->extractRecords($payload));
            $nextEndpoint = $this->nextPageUrl($payload, $nextEndpoint);
        }

        return [
            'records' => array_values(array_filter($records, 'is_array')),
            'pages' => $pages,
        ];
    }

    protected function extractRecords(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            return array_filter($payload, 'is_array');
        }

        foreach (['data', 'candidates', 'items', 'results', 'records'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $this->extractRecords($payload[$key]);
            }
        }

        if ($this->looksLikeCandidateRecord($payload)) {
            return [$payload];
        }

        return [];
    }

    protected function looksLikeCandidateRecord(array $payload): bool
    {
        foreach (['full_name', 'nome_completo', 'nomeCompleto', 'name', 'nome', 'candidate_name', 'candidato'] as $key) {
            if (filled(data_get($payload, $key))) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeRecord(array $record): array
    {
        $portalId = $this->stringValue($record, [
            'id', 'candidate_id', 'candidato_id', 'portal_id', 'uuid', 'codigo', 'code', 'numero_inscricao', 'inscricao_id',
        ]);
        $fullName = $this->stringValue($record, [
            'full_name', 'nome_completo', 'nomeCompleto', 'name', 'nome', 'candidate_name', 'candidato.nome',
        ]);
        $idNumber = $this->stringValue($record, [
            'id_number', 'bi', 'n_bi', 'nbi', 'numero_bi', 'numeroBilhete', 'bilhete', 'document_number', 'bi_number',
        ]);
        $idNumber = $idNumber ?: $this->portalReferenceNumber($portalId);
        $phone = $this->stringValue($record, ['phone', 'telefone', 'telemovel', 'contacto', 'contact', 'mobile']);
        $email = $this->stringValue($record, ['email', 'e_mail', 'mail']);
        $provinceName = $this->stringValue($record, ['province', 'provincia', 'província', 'naturalidade.provincia']);
        $municipalityName = $this->stringValue($record, ['municipality', 'municipio', 'município', 'naturalidade.municipio']);
        $status = $this->recordResultStatus($record);
        $provinceId = $this->provinceId($provinceName);

        return [
            'staff_type' => 'regime_geral',
            'full_name' => $fullName,
            'id_number' => $idNumber,
            'blood_type' => $this->stringValue($record, ['blood_type', 'grupo_sanguineo', 'grupo_sanguíneo', 'tipo_sangue']),
            'country' => $this->stringValue($record, ['country', 'pais', 'país', 'pais_origem', 'país_origem']) ?: 'Angola',
            'gender' => $this->normalizeGender($this->stringValue($record, ['gender', 'sexo', 'genero', 'género'])),
            'birth_date' => $this->normalizeDate($this->value($record, ['birth_date', 'data_nascimento', 'nascimento', 'dataNascimento'])),
            'marital_status' => $this->normalizeMaritalStatus($this->stringValue($record, ['marital_status', 'estado_civil', 'estadoCivil'])),
            'education_level' => $this->stringValue($record, ['education_level', 'grau_academico', 'habilitacoes', 'habilitações']),
            'education_area' => $this->stringValue($record, ['education_area', 'area_formacao', 'areaFormacao']),
            'phone' => $phone,
            'email' => $email,
            'father_name' => $this->stringValue($record, ['father_name', 'nome_pai', 'pai']),
            'mother_name' => $this->stringValue($record, ['mother_name', 'nome_mae', 'nome_mãe', 'mae', 'mãe']),
            'province_id' => $provinceId,
            'municipality_id' => $this->municipalityId($municipalityName, $provinceId),
            'address' => $this->stringValue($record, ['address', 'endereco', 'endereço', 'morada']),
            'student_type' => 'Alistado',
            'status' => $status ?: 'Pendente',
            'photo' => $this->stringValue($record, ['photo', 'foto', 'avatar', 'photo_url', 'foto_url']),
            'lookup_keys' => [
                'portal_id' => $portalId,
                'id_number' => $idNumber,
                'email' => $email,
                'phone' => $phone,
                'full_name' => $fullName,
            ],
        ];
    }

    protected function findCandidate(array $data): ?Candidate
    {
        $keys = $data['lookup_keys'] ?? [];

        foreach (['id_number', 'email'] as $key) {
            if (filled($keys[$key] ?? null)) {
                $candidate = Candidate::withTrashed()->where($key, $keys[$key])->first();

                if ($candidate) {
                    return $candidate;
                }
            }
        }

        if (filled($keys['phone'] ?? null)) {
            $phoneDigits = preg_replace('/\D+/', '', (string) $keys['phone']);
            $candidate = Candidate::withTrashed()
                ->where('phone', $keys['phone'])
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', '') = ?", [$phoneDigits])
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', '') = ?", ['244' . $phoneDigits])
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        if (filled($keys['full_name'] ?? null) && filled($data['birth_date'] ?? null)) {
            $candidate = Candidate::withTrashed()
                ->where('full_name', $keys['full_name'])
                ->whereDate('birth_date', $data['birth_date'])
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        if (filled($keys['full_name'] ?? null)) {
            return Candidate::withTrashed()
                ->where('full_name', $keys['full_name'])
                ->first();
        }

        return null;
    }

    protected function duplicateSignature(array $data): ?string
    {
        $keys = $data['lookup_keys'] ?? [];

        foreach (['portal_id', 'id_number', 'email', 'phone'] as $key) {
            if (filled($keys[$key] ?? null)) {
                return $key . ':' . $this->duplicateKeyValue($key, $keys[$key]);
            }
        }

        if (filled($keys['full_name'] ?? null) && filled($data['birth_date'] ?? null)) {
            return 'person:' . $this->slug($keys['full_name']) . ':' . $data['birth_date'];
        }

        if (filled($keys['full_name'] ?? null)) {
            return 'name:' . $this->slug($keys['full_name']);
        }

        return null;
    }

    protected function duplicateKeyValue(string $key, string $value): string
    {
        if ($key === 'phone') {
            return preg_replace('/\D+/', '', $value) ?: $this->slug($value);
        }

        if ($key === 'email') {
            return Str::lower(trim($value));
        }

        return $this->slug($value);
    }

    protected function portalReferenceNumber(?string $portalId): ?string
    {
        if (! filled($portalId)) {
            return null;
        }

        $reference = preg_replace('/[^A-Za-z0-9-]+/', '-', trim($portalId));
        $reference = trim((string) $reference, '-');

        return $reference !== ''
            ? 'PORTAL-' . Str::upper(Str::limit($reference, 180, ''))
            : null;
    }

    protected function incrementStatusStats(array &$stats, ?string $status): void
    {
        $status = $this->normalizeStatus($status) ?: 'Pendente';

        match ($status) {
            'Apurado' => $stats['approved']++,
            'Reprovado' => $stats['rejected']++,
            'Pendente' => $stats['pending']++,
            default => $stats['other']++,
        };
    }

    protected function nextPageUrl(mixed $payload, string $currentEndpoint): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        foreach ([
            'next_page_url',
            'links.next',
            'pagination.next_page_url',
            'pagination.next',
            'meta.next_page_url',
            'meta.links.next',
        ] as $key) {
            $next = data_get($payload, $key);

            if (filled($next) && is_string($next)) {
                return $this->absoluteUrl($currentEndpoint, $next);
            }
        }

        $currentPage = (int) (data_get($payload, 'current_page')
            ?: data_get($payload, 'meta.current_page')
            ?: data_get($payload, 'pagination.current_page')
            ?: 0);
        $lastPage = (int) (data_get($payload, 'last_page')
            ?: data_get($payload, 'meta.last_page')
            ?: data_get($payload, 'pagination.last_page')
            ?: 0);

        if ($currentPage > 0 && $lastPage > $currentPage) {
            return $this->urlWithQuery($currentEndpoint, ['page' => $currentPage + 1]);
        }

        return null;
    }

    protected function absoluteUrl(string $currentEndpoint, string $next): string
    {
        if (Str::startsWith($next, ['http://', 'https://'])) {
            return $next;
        }

        $current = parse_url($currentEndpoint);

        if (! filled($current['scheme'] ?? null) || ! filled($current['host'] ?? null)) {
            return $next;
        }

        $base = $current['scheme'] . '://' . $current['host'] . (isset($current['port']) ? ':' . $current['port'] : '');

        if (Str::startsWith($next, '?')) {
            return $base . ($current['path'] ?? '/') . $next;
        }

        if (Str::startsWith($next, '/')) {
            return $base . $next;
        }

        $path = $current['path'] ?? '/';
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');

        return $base . ($directory ? $directory . '/' : '/') . ltrim($next, '/');
    }

    protected function urlWithQuery(string $url, array $query): string
    {
        $parts = parse_url($url);
        $params = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $params);
        }

        $params = array_merge($params, $query);
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';

        return $scheme . $host . $port . $path . '?' . http_build_query($params);
    }

    protected function value(array $record, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($record, $key);

            if (filled($value)) {
                return $value;
            }

            $snake = Str::snake($key);
            $value = data_get($record, $snake);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    protected function stringValue(array $record, array $keys): ?string
    {
        $value = $this->value($record, $keys);

        if (is_array($value) || is_object($value) || $value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function normalizeGender(?string $value): ?string
    {
        $value = $this->slug($value);

        return match (true) {
            in_array($value, ['m', 'masculino', 'male'], true) => 'Masculino',
            in_array($value, ['f', 'feminino', 'female'], true) => 'Feminino',
            default => null,
        };
    }

    protected function normalizeStatus(?string $value): ?string
    {
        $slug = $this->slug($value);

        return match (true) {
            str_contains($slug, 'apurad') || str_contains($slug, 'aprovad') || str_contains($slug, 'approved') || str_contains($slug, 'admitted') || $slug === 'apto' => 'Apurado',
            str_contains($slug, 'reprov') || str_contains($slug, 'reject') || str_contains($slug, 'failed') || $slug === 'inapto' => 'Reprovado',
            str_contains($slug, 'pend') || str_contains($slug, 'pending') => 'Pendente',
            filled($value) => Str::title(trim($value)),
            default => null,
        };
    }

    protected function isSyncableResultRecord(array $record): bool
    {
        $status = $this->normalizeStatus($this->stringValue($record, [
            'status', 'resultado', 'result', 'situacao', 'situação', 'estado',
        ]));

        if (filled($status)) {
            return in_array($status, ['Apurado', 'Reprovado'], true);
        }

        $slug = $this->slug($this->stringValue($record, [
            'student_type', 'tipo', 'type', 'categoria', 'tipo_candidato', 'classificacao', 'classificação',
        ]));

        return str_contains($slug, 'apurad')
            || str_contains($slug, 'aprovad')
            || str_contains($slug, 'approved')
            || str_contains($slug, 'admitted')
            || str_contains($slug, 'reprov')
            || str_contains($slug, 'reject')
            || str_contains($slug, 'failed')
            || $slug === 'apto'
            || $slug === 'inapto';
    }

    protected function recordResultStatus(array $record): ?string
    {
        $status = $this->normalizeStatus($this->stringValue($record, [
            'status', 'resultado', 'result', 'situacao', 'situação', 'estado',
        ]));

        if (filled($status)) {
            return $status;
        }

        return $this->normalizeStatus($this->stringValue($record, [
            'student_type', 'tipo', 'type', 'categoria', 'tipo_candidato', 'classificacao', 'classificação',
        ]));
    }

    protected function normalizeStudentType(?string $value, ?string $status): string
    {
        return 'Alistado';
    }

    protected function normalizeMaritalStatus(?string $value): ?string
    {
        $slug = $this->slug($value);

        return match (true) {
            str_contains($slug, 'solteir') => 'solteiro',
            str_contains($slug, 'casad') => 'casado',
            str_contains($slug, 'divorci') => 'divorciado',
            str_contains($slug, 'viuv') => 'viuvo',
            default => null,
        };
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::createFromTimestamp(((int) $value - 25569) * 86400)->toDateString();
            }

            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function provinceId(?string $name): ?int
    {
        if (! filled($name)) {
            return null;
        }

        return Province::query()
            ->where('name', $name)
            ->orWhere('name', 'like', "%{$name}%")
            ->value('id');
    }

    protected function municipalityId(?string $name, ?int $provinceId): ?int
    {
        if (! filled($name)) {
            return null;
        }

        return Municipality::query()
            ->when($provinceId, fn ($query) => $query->where('province_id', $provinceId))
            ->where(function ($query) use ($name): void {
                $query->where('name', $name)
                    ->orWhere('name', 'like', "%{$name}%");
            })
            ->value('id');
    }

    protected function slug(?string $value): string
    {
        return Str::of((string) $value)->ascii()->lower()->trim()->toString();
    }
}
