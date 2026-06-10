<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Institution;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class SigaDashboardStatsService
{
    private bool $consultationStudentsUnavailable = false;
    private ?Collection $institutionMatches = null;
    private ?Collection $courseMatches = null;

    public function institutionStudents(array $filters = []): array
    {
        return $this->rememberResult('institution_students', $filters, function () use ($filters): array {
            $rows = $this->fetchEndpointRows('institution_students', $filters, 'institution_name')
                ->map(fn (array $row): array => $this->normalizeInstitutionRow($row))
                ->filter(fn (array $row): bool => filled($row['institution_name']) || $row['total_alunos'] > 0)
                ->values();

            if ($rows->isEmpty()) {
                $rows = $this->consultationStudentsByInstitution($filters);
            }

            return $rows
                ->filter(fn (array $row): bool => $this->matchesInstitutionFilter($row, $filters))
                ->values()
                ->all();
        });
    }

    public function studentsByCourse(array $filters = []): array
    {
        return $this->rememberResult('students_by_course', $filters, function () use ($filters): array {
            $rows = $this->fetchEndpointRows('students_by_course', $filters, 'course_name')
                ->map(fn (array $row): array => $this->normalizeCourseRow($row))
                ->filter(fn (array $row): bool => filled($row['course_name']) || $row['total_alunos'] > 0)
                ->values();

            if ($rows->isEmpty()) {
                $rows = $this->consultationStudentsByCourse($filters);
            }

            return $rows
                ->filter(fn (array $row): bool => $this->matchesCourseFilter($row, $filters))
                ->values()
                ->all();
        });
    }

    public function studentTrainingTypes(array $filters = []): array
    {
        return $this->rememberResult('student_training_types', $filters, fn (): array => $this->consultationStudentRows($filters)
            ->groupBy('training_type_label')
            ->map(fn (Collection $rows, string $label): array => [
                'label' => $label,
                'total_alunos' => $rows->sum('total_alunos'),
            ])
            ->filter(fn (array $row): bool => $row['total_alunos'] > 0)
            ->values()
            ->all());
    }

    public function studentResults(array $filters = []): array
    {
        return $this->rememberResult('student_results', $filters, fn (): array => $this->consultationStudentRows($filters)
            ->groupBy('result_label')
            ->map(fn (Collection $rows, string $label): array => [
                'label' => $label,
                'total_alunos' => $rows->sum('total_alunos'),
            ])
            ->filter(fn (array $row): bool => $row['total_alunos'] > 0)
            ->values()
            ->all());
    }

    private function fetchEndpointRows(string $endpointKey, array $filters, string $labelKey): Collection
    {
        $url = $this->endpointUrl($endpointKey);

        if (! filled($url) || $this->isOwnDashboardEndpoint($url)) {
            return collect();
        }

        $cacheKey = 'siga.dashboard.' . $endpointKey . '.' . md5(json_encode([
            'url' => $url,
            'filters' => $this->normalizeFilters($filters),
        ]));

        $failureKey = $this->failureCacheKey($cacheKey);

        if ($this->hasRecentFailure($failureKey)) {
            return $this->cachedCollection($cacheKey)
                ?? $this->cachedCollection($this->staleCacheKey($cacheKey))
                ?? collect();
        }

        if ($cached = $this->cachedCollection($cacheKey)) {
            return $cached;
        }

        try {
            $response = $this->httpRequest()->get($url, $this->queryParams($filters));

            if (! $response->successful()) {
                Log::warning('SIGA dashboard API returned an error.', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                $this->markFailure($failureKey);

                return $this->cachedCollection($this->staleCacheKey($cacheKey)) ?? collect();
            }

            $rows = collect($this->extractRows($response->json(), $labelKey))
                ->filter(fn ($row): bool => is_array($row))
                ->values();

            $this->storeCollection($cacheKey, $rows, $this->cacheTtl());
            Cache::forget($failureKey);

            return $rows;
        } catch (Throwable $exception) {
            Log::warning('SIGA dashboard API request failed.', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            $this->markFailure($failureKey);

            return $this->cachedCollection($this->staleCacheKey($cacheKey)) ?? collect();
        }
    }

    private function consultationStudentsByInstitution(array $filters): Collection
    {
        $studentRows = $this->consultationStudentRows($filters)
            ->groupBy(fn (array $row): string => $this->statsKey($row['institution_name'] ?? null, $row['institution_id'] ?? null))
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'institution_id' => $first['institution_id'] ?? null,
                    'institution_name' => $first['institution_name'] ?? 'Faculdade SIGA',
                    'institution_acronym' => $first['institution_acronym'] ?? null,
                    'has_institution_identity' => (bool) ($first['has_institution_identity'] ?? false),
                    'total_alunos' => $rows->sum('total_alunos'),
                ];
            })
            ->values();

        if ($studentRows->isNotEmpty()) {
            return $studentRows;
        }

        if ($this->consultationStudentsUnavailable) {
            return collect();
        }

        return $this->hasDateFilter($filters)
            ? collect()
            : $this->consultationProgramsByInstitution($filters);
    }

    private function consultationStudentsByCourse(array $filters): Collection
    {
        $studentRows = $this->consultationStudentRows($filters)
            ->groupBy(fn (array $row): string => $this->statsKey($row['course_name'] ?? null, $row['course_id'] ?? null))
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'course_id' => $first['course_id'] ?? null,
                    'course_name' => $first['course_name'] ?? 'Curso SIGA',
                    'institution_id' => $first['institution_id'] ?? null,
                    'institution_name' => $first['institution_name'] ?? null,
                    'has_course_identity' => (bool) ($first['has_course_identity'] ?? false),
                    'has_institution_identity' => (bool) ($first['has_institution_identity'] ?? false),
                    'total_alunos' => $rows->sum('total_alunos'),
                ];
            })
            ->values();

        if ($studentRows->isNotEmpty()) {
            return $studentRows;
        }

        if ($this->consultationStudentsUnavailable) {
            return collect();
        }

        return $this->hasDateFilter($filters)
            ? collect()
            : $this->consultationProgramsByCourse($filters);
    }

    private function consultationProgramsByInstitution(array $filters): Collection
    {
        return $this->consultationProgramRows()
            ->filter(fn (array $row): bool => $this->matchesCourseFilter($row, $filters))
            ->filter(fn (array $row): bool => $this->matchesInstitutionFilter($row, $filters))
            ->groupBy(fn (array $row): string => $this->statsKey($row['institution_name'] ?? null, $row['institution_id'] ?? null))
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'institution_id' => $first['institution_id'] ?? null,
                    'institution_name' => $first['institution_name'] ?? 'Faculdade SIGA',
                    'institution_acronym' => $first['institution_acronym'] ?? null,
                    'has_institution_identity' => (bool) ($first['has_institution_identity'] ?? false),
                    'total_alunos' => $rows->sum('total_alunos'),
                ];
            })
            ->values();
    }

    private function consultationProgramsByCourse(array $filters): Collection
    {
        return $this->consultationProgramRows()
            ->filter(fn (array $row): bool => $this->matchesCourseFilter($row, $filters))
            ->filter(fn (array $row): bool => $this->matchesInstitutionFilter($row, $filters))
            ->groupBy(fn (array $row): string => $this->statsKey($row['course_name'] ?? null, $row['course_id'] ?? null))
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'course_id' => $first['course_id'] ?? null,
                    'course_name' => $first['course_name'] ?? 'Curso SIGA',
                    'institution_id' => $first['institution_id'] ?? null,
                    'institution_name' => $first['institution_name'] ?? null,
                    'has_course_identity' => (bool) ($first['has_course_identity'] ?? false),
                    'has_institution_identity' => (bool) ($first['has_institution_identity'] ?? false),
                    'total_alunos' => $rows->sum('total_alunos'),
                ];
            })
            ->values();
    }

    private function consultationProgramRows(): Collection
    {
        return $this->fetchConsultationPrograms()
            ->map(fn (array $program): array => $this->normalizeConsultationProgramRow($program))
            ->filter(fn (array $row): bool => $row['total_alunos'] > 0)
            ->values();
    }

    private function consultationStudentRows(array $filters): Collection
    {
        return $this->fetchConsultationStudents()
            ->map(fn (array $student): array => $this->normalizeConsultationStudentRow($student))
            ->filter(fn (array $row): bool => $this->matchesDateFilter($row, $filters))
            ->filter(fn (array $row): bool => $this->matchesCourseFilter($row, $filters))
            ->values();
    }

    private function fetchConsultationStudents(): Collection
    {
        $this->consultationStudentsUnavailable = false;

        $url = $this->endpointUrl('students');

        if (! filled($url) || $this->isOwnDashboardEndpoint($url)) {
            return collect();
        }

        $cacheKey = 'siga.consultation.students.' . md5(json_encode([
            'version' => 2,
            'url' => $url,
            'auth' => md5((string) config('services.siga.api_key') . '|' . (string) config('services.siga.token')),
            'per_page' => $this->perPage(),
            'max_pages' => $this->maxPages(),
        ]));

        $cacheTtl = $this->consultationStudentsCacheTtl();
        $failureKey = $this->failureCacheKey($cacheKey);

        if ($cached = $this->cachedCollection($cacheKey)) {
            return $cached;
        }

        if ($this->hasRecentFailure($failureKey)) {
            $this->consultationStudentsUnavailable = true;

            return $this->cachedCollection($this->staleCacheKey($cacheKey)) ?? collect();
        }

        $loader = function () use ($url): array {
            $students = collect();
            $page = 1;
            $lastPage = 1;
            $maxPages = $this->maxPages();
            $expectedTotal = null;

            try {
                do {
                    $response = $this->httpRequest()->get($url, [
                        'page' => $page,
                        'per_page' => $this->perPage(),
                    ]);

                    if (! $response->successful()) {
                        Log::warning('SIGA consultation students API returned an error.', [
                            'url' => $url,
                            'status' => $response->status(),
                        ]);

                        return [
                            'rows' => $students->values(),
                            'complete' => false,
                        ];
                    }

                    $payload = $response->json();
                    $items = data_get($payload, 'data', []);

                    if (! is_array($items)) {
                        return [
                            'rows' => $students->values(),
                            'complete' => false,
                        ];
                    }

                    $students = $students->merge(
                        collect($items)
                            ->filter(fn ($row): bool => is_array($row))
                            ->values()
                    );

                    $total = data_get($payload, 'meta.total');
                    $expectedTotal = is_numeric($total) ? (int) $total : $expectedTotal;
                    $lastPage = max(1, (int) data_get($payload, 'meta.last_page', 1));
                    $page++;
                } while ($page <= $lastPage && $page <= $maxPages);

                $rows = $students->values();
                $complete = $page > $lastPage;

                if (! $complete) {
                    Log::warning('SIGA consultation students API returned partial pagination.', [
                        'url' => $url,
                        'loaded' => $rows->count(),
                        'expected' => $expectedTotal,
                        'last_page' => $lastPage,
                        'max_pages' => $maxPages,
                    ]);
                } elseif ($expectedTotal !== null && $rows->count() !== $expectedTotal) {
                    Log::info('SIGA consultation students API meta total differs from loaded rows.', [
                        'url' => $url,
                        'loaded' => $rows->count(),
                        'expected' => $expectedTotal,
                    ]);
                }

                return [
                    'rows' => $rows,
                    'complete' => $complete,
                ];
            } catch (Throwable $exception) {
                Log::warning('SIGA consultation students API request failed.', [
                    'url' => $url,
                    'error' => $exception->getMessage(),
                ]);

                return [
                    'rows' => $students->values(),
                    'complete' => false,
                ];
            }
        };

        $result = $loader();

        if ($cacheTtl > 0 && ($result['complete'] ?? false)) {
            $this->storeCollection($cacheKey, $result['rows'], $cacheTtl);
            Cache::forget($failureKey);

            return $result['rows'];
        }

        $this->markFailure($failureKey);
        $this->consultationStudentsUnavailable = true;

        return $this->cachedCollection($this->staleCacheKey($cacheKey)) ?? $result['rows'];
    }

    private function fetchConsultationPrograms(): Collection
    {
        $url = $this->endpointUrl('programs');

        if (! filled($url) || $this->isOwnDashboardEndpoint($url)) {
            return collect();
        }

        $cacheKey = 'siga.consultation.programs.' . md5(json_encode([
            'version' => 2,
            'url' => $url,
            'auth' => md5((string) config('services.siga.api_key') . '|' . (string) config('services.siga.token')),
            'per_page' => $this->perPage(),
            'max_pages' => $this->maxPages(),
        ]));

        $failureKey = $this->failureCacheKey($cacheKey);

        if ($cached = $this->cachedCollection($cacheKey)) {
            return $cached;
        }

        if ($this->hasRecentFailure($failureKey)) {
            return $this->cachedCollection($this->staleCacheKey($cacheKey)) ?? collect();
        }

        $programs = collect();
        $page = 1;
        $lastPage = 1;
        $maxPages = $this->maxPages();

        try {
            do {
                $response = $this->httpRequest()->get($url, [
                    'page' => $page,
                    'per_page' => $this->perPage(),
                ]);

                if (! $response->successful()) {
                    Log::warning('SIGA consultation programs API returned an error.', [
                        'url' => $url,
                        'status' => $response->status(),
                    ]);

                    $this->markFailure($failureKey);

                    return $this->cachedCollection($this->staleCacheKey($cacheKey)) ?? $programs->values();
                }

                $payload = $response->json();
                $items = data_get($payload, 'data', []);

                if (! is_array($items)) {
                    $this->markFailure($failureKey);

                    return $this->cachedCollection($this->staleCacheKey($cacheKey)) ?? $programs->values();
                }

                $programs = $programs->merge(
                    collect($items)
                        ->filter(fn ($row): bool => is_array($row))
                        ->values()
                );

                $lastPage = max(1, (int) data_get($payload, 'meta.last_page', 1));
                $page++;
            } while ($page <= $lastPage && $page <= $maxPages);

            $rows = $programs->values();
            $this->storeCollection($cacheKey, $rows, $this->cacheTtl());
            Cache::forget($failureKey);

            return $rows;
        } catch (Throwable $exception) {
            Log::warning('SIGA consultation programs API request failed.', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            $this->markFailure($failureKey);

            return $this->cachedCollection($this->staleCacheKey($cacheKey)) ?? $programs->values();
        }
    }

    private function endpointUrl(string $endpointKey): ?string
    {
        $endpoint = config("services.siga.endpoints.{$endpointKey}");

        if (! filled($endpoint)) {
            return null;
        }

        $endpoint = trim((string) $endpoint);

        if (Str::startsWith($endpoint, ['http://', 'https://'])) {
            return $endpoint;
        }

        $baseUrl = config('services.siga.base_url');

        if (! filled($baseUrl)) {
            return null;
        }

        return rtrim((string) $baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    private function queryParams(array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);
        $institution = filled($normalized['institution_id'])
            ? Institution::query()->find($normalized['institution_id'])
            : null;
        $course = filled($normalized['course_id'])
            ? Course::query()->find($normalized['course_id'])
            : null;

        return array_filter([
            'institution_id' => $normalized['institution_id'],
            'institution_name' => $institution?->name,
            'institution_acronym' => $institution?->acronym,
            'course_id' => $normalized['course_id'],
            'course_name' => $course?->name,
            'start_date' => $normalized['start_date'],
            'end_date' => $normalized['end_date'],
        ], fn ($value): bool => filled($value));
    }

    private function extractRows(mixed $payload, string $labelKey): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['labels'], $payload['datasets']) && is_array($payload['labels']) && is_array($payload['datasets'])) {
            $data = data_get($payload, 'datasets.0.data', []);

            if (is_array($data)) {
                return collect($payload['labels'])
                    ->map(fn ($label, int $index): array => [
                        $labelKey => $label,
                        'total_alunos' => $data[$index] ?? 0,
                    ])
                    ->all();
            }
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        foreach ([
            'data',
            'items',
            'results',
            'records',
            'rows',
            'institutions',
            'institution_stats',
            'students_by_institution',
            'courses',
            'course_stats',
            'students_by_course',
        ] as $key) {
            $value = data_get($payload, $key);

            if (is_array($value)) {
                return $this->extractRows($value, $labelKey);
            }
        }

        return [];
    }

    private function normalizeInstitutionRow(array $row): array
    {
        $institutionId = $this->intValue($row, [
            'institution_id',
            'instituicao_id',
            'faculdade_id',
            'school_id',
            'id',
            'institution.id',
            'instituicao.id',
        ]);
        $institutionName = $this->stringValue($row, [
            'institution_name',
            'instituicao_nome',
            'institution',
            'instituicao',
            'faculdade',
            'school',
            'name',
            'nome',
            'institution.name',
            'instituicao.name',
        ]);
        $institutionAcronym = $this->stringValue($row, [
            'institution_acronym',
            'instituicao_sigla',
            'acronym',
            'sigla',
            'institution.acronym',
            'instituicao.sigla',
        ]);
        $matchedInstitution = $this->matchingInstitution($institutionId, $institutionName, $institutionAcronym);

        return [
            'institution_id' => $matchedInstitution?->id ?? $institutionId,
            'institution_name' => $matchedInstitution?->name ?? ($institutionName ?: 'Faculdade SIGA'),
            'institution_acronym' => $matchedInstitution?->acronym ?? $institutionAcronym,
            'has_institution_identity' => filled($matchedInstitution) || filled($institutionId) || filled($institutionName) || filled($institutionAcronym),
            'total_alunos' => $this->studentTotal($row),
        ];
    }

    private function normalizeCourseRow(array $row): array
    {
        $courseId = $this->intValue($row, [
            'course_id',
            'curso_id',
            'id_curso',
            'course.id',
            'curso.id',
            'id',
        ]);
        $courseName = $this->stringValue($row, [
            'course_name',
            'curso_nome',
            'course',
            'curso',
            'name',
            'nome',
            'course.name',
            'curso.name',
        ]);
        $institutionId = $this->intValue($row, [
            'institution_id',
            'instituicao_id',
            'faculdade_id',
            'institution.id',
            'instituicao.id',
        ]);
        $institutionName = $this->stringValue($row, [
            'institution_name',
            'instituicao_nome',
            'institution',
            'instituicao',
            'faculdade',
            'institution.name',
            'instituicao.name',
        ]);
        $matchedCourse = $this->matchingCourse($courseId, $courseName);
        $matchedInstitution = $this->matchingInstitution($institutionId, $institutionName, null);

        return [
            'course_id' => $matchedCourse?->id ?? $courseId,
            'course_name' => $matchedCourse?->name ?? ($courseName ?: 'Curso SIGA'),
            'institution_id' => $matchedInstitution?->id ?? $institutionId,
            'institution_name' => $matchedInstitution?->name ?? $institutionName,
            'has_course_identity' => filled($matchedCourse) || filled($courseId) || filled($courseName),
            'has_institution_identity' => filled($matchedInstitution) || filled($institutionId) || filled($institutionName),
            'total_alunos' => $this->studentTotal($row),
        ];
    }

    private function normalizeConsultationStudentInstitutionRow(array $student): array
    {
        $institutionId = $this->intValue($student, [
            'current_enrollment.program.faculty.id',
            'current_enrollment.faculty.id',
            'faculty.id',
            'faculdade.id',
        ]);
        $institutionName = $this->stringValue($student, [
            'current_enrollment.program.faculty.name',
            'current_enrollment.program.faculty.short_name',
            'current_enrollment.faculty.name',
            'faculty.name',
            'faculdade.name',
            'faculdade.nome',
        ]);
        $institutionAcronym = $this->stringValue($student, [
            'current_enrollment.program.faculty.acronym',
            'current_enrollment.program.faculty.code',
            'current_enrollment.faculty.acronym',
            'faculty.acronym',
            'faculty.code',
            'faculdade.sigla',
        ]);
        $matchedInstitution = $this->matchingInstitution(null, $institutionName, $institutionAcronym);

        return [
            'institution_id' => $matchedInstitution?->id ?? $institutionId,
            'institution_name' => $matchedInstitution?->name ?? ($institutionName ?: 'Faculdade SIGA'),
            'institution_acronym' => $matchedInstitution?->acronym ?? $institutionAcronym,
            'has_institution_identity' => filled($matchedInstitution) || filled($institutionId) || filled($institutionName) || filled($institutionAcronym),
            'total_alunos' => 1,
            'reference_date' => $this->studentReferenceDate($student),
        ];
    }

    private function normalizeConsultationStudentCourseRow(array $student): array
    {
        $courseId = $this->intValue($student, [
            'current_enrollment.program.id',
            'current_enrollment.program_id',
            'program.id',
            'curso.id',
        ]);
        $courseName = $this->stringValue($student, [
            'current_enrollment.program.name',
            'current_enrollment.program.code',
            'program.name',
            'curso.name',
            'curso.nome',
        ]);
        $institutionId = $this->intValue($student, [
            'current_enrollment.program.faculty.id',
            'current_enrollment.faculty.id',
            'faculty.id',
            'faculdade.id',
        ]);
        $institutionName = $this->stringValue($student, [
            'current_enrollment.program.faculty.name',
            'current_enrollment.program.faculty.short_name',
            'current_enrollment.faculty.name',
            'faculty.name',
            'faculdade.name',
            'faculdade.nome',
        ]);
        $institutionAcronym = $this->stringValue($student, [
            'current_enrollment.program.faculty.acronym',
            'current_enrollment.program.faculty.code',
            'current_enrollment.faculty.acronym',
            'faculty.acronym',
            'faculty.code',
            'faculdade.sigla',
        ]);
        $matchedCourse = $this->matchingCourse(null, $courseName);
        $matchedInstitution = $this->matchingInstitution(null, $institutionName, $institutionAcronym);

        return [
            'course_id' => $matchedCourse?->id ?? $courseId,
            'course_name' => $matchedCourse?->name ?? ($courseName ?: 'Curso SIGA'),
            'institution_id' => $matchedInstitution?->id ?? $institutionId,
            'institution_name' => $matchedInstitution?->name ?? $institutionName,
            'has_course_identity' => filled($matchedCourse) || filled($courseId) || filled($courseName),
            'has_institution_identity' => filled($matchedInstitution) || filled($institutionId) || filled($institutionName) || filled($institutionAcronym),
            'total_alunos' => 1,
            'reference_date' => $this->studentReferenceDate($student),
        ];
    }

    private function normalizeConsultationStudentRow(array $student): array
    {
        $institution = $this->normalizeConsultationStudentInstitutionRow($student);
        $course = $this->normalizeConsultationStudentCourseRow($student);

        return [
            'institution_id' => $institution['institution_id'] ?? null,
            'institution_name' => $institution['institution_name'] ?? null,
            'institution_acronym' => $institution['institution_acronym'] ?? null,
            'has_institution_identity' => (bool) ($institution['has_institution_identity'] ?? false),
            'course_id' => $course['course_id'] ?? null,
            'course_name' => $course['course_name'] ?? null,
            'has_course_identity' => (bool) ($course['has_course_identity'] ?? false),
            'total_alunos' => 1,
            'reference_date' => $this->studentReferenceDate($student),
            'training_type_label' => $this->studentTrainingTypeLabel($student),
            'result_label' => $this->studentResultLabel($student),
        ];
    }

    private function normalizeConsultationProgramRow(array $program): array
    {
        $courseId = $this->intValue($program, [
            'id',
            'program.id',
            'curso.id',
        ]);
        $courseName = $this->stringValue($program, [
            'name',
            'program.name',
            'curso.name',
            'curso.nome',
            'code',
        ]);
        $institutionId = $this->intValue($program, [
            'faculty.id',
            'faculdade.id',
            'institution.id',
            'instituicao.id',
        ]);
        $institutionName = $this->stringValue($program, [
            'faculty.name',
            'faculty.short_name',
            'faculdade.name',
            'faculdade.nome',
            'institution.name',
            'instituicao.name',
        ]);
        $institutionAcronym = $this->stringValue($program, [
            'faculty.acronym',
            'faculty.code',
            'faculdade.sigla',
            'institution.acronym',
            'instituicao.sigla',
        ]);
        $matchedCourse = $this->matchingCourse(null, $courseName);
        $matchedInstitution = $this->matchingInstitution(null, $institutionName, $institutionAcronym);

        return [
            'course_id' => $matchedCourse?->id ?? $courseId,
            'course_name' => $matchedCourse?->name ?? ($courseName ?: 'Curso SIGA'),
            'institution_id' => $matchedInstitution?->id ?? $institutionId,
            'institution_name' => $matchedInstitution?->name ?? $institutionName,
            'institution_acronym' => $matchedInstitution?->acronym ?? $institutionAcronym,
            'has_course_identity' => filled($matchedCourse) || filled($courseId) || filled($courseName),
            'has_institution_identity' => filled($matchedInstitution) || filled($institutionId) || filled($institutionName) || filled($institutionAcronym),
            'total_alunos' => $this->intValue($program, [
                'stats.students',
                'stats.total_students',
                'stats.alunos',
                'stats.total_alunos',
                'students_count',
                'total_students',
                'enrollments_count',
                'stats.enrollments',
            ]) ?? 0,
        ];
    }

    private function studentTotal(array $row): int
    {
        return $this->intValue($row, [
            'total_alunos',
            'total_students',
            'students_total',
            'students',
            'alunos_total',
            'alunos',
            'total',
            'count',
            'quantidade',
            'qtd',
            'value',
        ]) ?? 0;
    }

    private function matchesInstitutionFilter(array $row, array $filters): bool
    {
        $institutionId = $filters['institution_id'] ?? null;

        if (! filled($institutionId)) {
            return true;
        }

        $institution = Institution::query()->find($institutionId);

        if (! $institution) {
            return true;
        }

        if (! ($row['has_institution_identity'] ?? true)) {
            return true;
        }

        if (filled($row['institution_name'] ?? null) || filled($row['institution_acronym'] ?? null)) {
            return $this->sameText($row['institution_name'] ?? null, $institution->name)
                || $this->sameText($row['institution_acronym'] ?? null, $institution->acronym);
        }

        return (int) ($row['institution_id'] ?? 0) === (int) $institution->id;
    }

    private function matchesCourseFilter(array $row, array $filters): bool
    {
        $courseId = $filters['course_id'] ?? null;

        if (! filled($courseId)) {
            return $this->matchesInstitutionFilter($row, $filters);
        }

        $course = Course::query()->find($courseId);

        if (! $course) {
            return $this->matchesInstitutionFilter($row, $filters);
        }

        if (! ($row['has_course_identity'] ?? true)) {
            return $this->matchesInstitutionFilter($row, $filters);
        }

        $courseMatches = filled($row['course_name'] ?? null)
            ? $this->sameText($row['course_name'] ?? null, $course->name)
            : (int) ($row['course_id'] ?? 0) === (int) $course->id;

        return $courseMatches && $this->matchesInstitutionFilter($row, $filters);
    }

    private function rememberResult(string $key, array $filters, callable $callback): array
    {
        $cacheKey = 'siga.dashboard.result.' . $key . '.' . md5(json_encode([
            'version' => 2,
            'filters' => $this->normalizeFilters($filters),
            'base_url' => config('services.siga.base_url'),
            'auth' => md5((string) config('services.siga.api_key') . '|' . (string) config('services.siga.token')),
            'endpoints' => config('services.siga.endpoints'),
            'per_page' => $this->perPage(),
            'max_pages' => $this->maxPages(),
        ]));
        $freshTtl = $this->cacheTtl();
        $staleTtl = $this->staleCacheTtl();

        if ($freshTtl <= 0) {
            return $this->loadResult($cacheKey, $callback);
        }

        $cached = Cache::get($cacheKey);

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $result = $this->loadResult($cacheKey, $callback);

        if ($result !== []) {
            Cache::put($cacheKey, $result, $freshTtl);
            Cache::put($this->staleCacheKey($cacheKey), $result, $staleTtl);

            return $result;
        }

        $stale = Cache::get($this->staleCacheKey($cacheKey));

        return is_array($stale) ? $stale : [];
    }

    private function loadResult(string $cacheKey, callable $callback): array
    {
        try {
            $result = $callback();

            if ($result === []) {
                $cached = Cache::get($cacheKey) ?: Cache::get($this->staleCacheKey($cacheKey));

                return is_array($cached) ? $cached : [];
            }

            return $result;
        } catch (Throwable $exception) {
            Log::warning('SIGA dashboard result failed to load.', [
                'cache_key' => $cacheKey,
                'error' => $exception->getMessage(),
            ]);

            $cached = Cache::get($cacheKey) ?: Cache::get($this->staleCacheKey($cacheKey));

            return is_array($cached) ? $cached : [];
        }
    }

    private function institutionMatches(): Collection
    {
        return $this->institutionMatches ??= Institution::query()
            ->get(['id', 'name', 'acronym']);
    }

    private function courseMatches(): Collection
    {
        return $this->courseMatches ??= Course::query()
            ->get(['id', 'name']);
    }

    private function matchingInstitution(?int $institutionId, ?string $institutionName, ?string $institutionAcronym): ?Institution
    {
        $institutions = $this->institutionMatches();

        if (filled($institutionName) || filled($institutionAcronym)) {
            $institution = $institutions->first(fn (Institution $institution): bool => $this->sameText($institutionName, $institution->name)
                || $this->sameText($institutionAcronym, $institution->acronym));

            if ($institution) {
                return $institution;
            }
        }

        return filled($institutionId)
            ? $institutions->firstWhere('id', $institutionId)
            : null;
    }

    private function matchingCourse(?int $courseId, ?string $courseName): ?Course
    {
        $courses = $this->courseMatches();

        if (filled($courseName)) {
            $course = $courses->first(fn (Course $course): bool => $this->sameText($courseName, $course->name));

            if ($course) {
                return $course;
            }
        }

        return filled($courseId)
            ? $courses->firstWhere('id', $courseId)
            : null;
    }

    private function intValue(array $row, array $keys): ?int
    {
        $value = $this->value($row, $keys);

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $normalized = preg_replace('/[^\d-]+/', '', $value);

            return is_numeric($normalized) ? (int) $normalized : null;
        }

        return null;
    }

    private function stringValue(array $row, array $keys): ?string
    {
        $value = $this->value($row, $keys);

        if (is_array($value) || is_object($value) || $value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($row, $key);

            if (filled($value)) {
                return $value;
            }

            $snake = Str::snake($key);
            $value = data_get($row, $snake);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function httpRequest(): \Illuminate\Http\Client\PendingRequest
    {
        $timeout = min(60, max(20, (float) config('services.siga.timeout', 20)));
        $connectTimeout = min($timeout, max(5, (float) config('services.siga.connect_timeout', 5)));
        $retries = max(2, (int) config('services.siga.retries', 2));

        $request = Http::acceptJson()
            ->connectTimeout($connectTimeout)
            ->timeout($timeout);

        if ($retries > 0) {
            $request = $request->retry($retries, 250, null, false);
        }

        if (! $this->shouldVerifySsl()) {
            $request = $request->withoutVerifying();
        }

        if (filled(config('services.siga.api_key'))) {
            $request = $request->withHeader('X-API-Key', (string) config('services.siga.api_key'));
        }

        if (filled(config('services.siga.token'))) {
            $request = $request->withToken((string) config('services.siga.token'));
        }

        return $request;
    }

    private function matchesDateFilter(array $row, array $filters): bool
    {
        $startDate = $this->dateString($filters['start_date'] ?? null);
        $endDate = $this->dateString($filters['end_date'] ?? null);

        if (! filled($startDate) && ! filled($endDate)) {
            return true;
        }

        $referenceDate = $this->dateString($row['reference_date'] ?? null);

        if (! filled($referenceDate)) {
            return true;
        }

        $referenceDate = substr($referenceDate, 0, 10);
        $startDate = filled($startDate) ? substr($startDate, 0, 10) : null;
        $endDate = filled($endDate) ? substr($endDate, 0, 10) : null;

        return (! filled($startDate) || $referenceDate >= $startDate)
            && (! filled($endDate) || $referenceDate <= $endDate);
    }

    private function hasDateFilter(array $filters): bool
    {
        return filled($filters['start_date'] ?? null) || filled($filters['end_date'] ?? null);
    }

    private function studentReferenceDate(array $student): ?string
    {
        return $this->stringValue($student, [
            'current_enrollment.enrolled_at',
            'profile.admission_date',
            'admission_date',
            'created_at',
        ]);
    }

    private function studentTrainingTypeLabel(array $student): string
    {
        return $this->isPostLaboralStudent($student)
            ? "P\u{00F3}s-Laboral API SIGA"
            : 'Cadetes API SIGA';
    }

    private function isPostLaboralStudent(array $student): bool
    {
        $shift = $this->stringValue($student, [
            'current_enrollment.shift',
            'current_enrollment.class_group.shift',
            'class_group.shift',
            'shift',
            'turno',
        ]);

        return $this->compactSlug($shift) === 'poslaboral';
    }

    private function studentResultLabel(array $student): string
    {
        $status = $this->compactSlug($this->stringValue($student, [
            'current_enrollment.status',
            'final_validation_status',
            'academic_status',
            'status',
            'result',
            'resultado',
        ]));

        if (in_array($status, [
            'active',
            'activeenrollment',
            'aprovado',
            'approved',
            'reconfirmed',
            'registrationvalidated',
        ], true)) {
            return 'Aprovados API SIGA';
        }

        if (in_array($status, [
            'cancelado',
            'cancelled',
            'failed',
            'inactive',
            'inactivo',
            'reprovado',
            'reproved',
            'yearannulled',
            'yearcancelled',
        ], true)) {
            return 'Reprovados API SIGA';
        }

        return 'Pendentes API SIGA';
    }

    private function statsKey(?string $name, mixed $id): string
    {
        if (filled($name)) {
            return 'name:' . $this->slug($name);
        }

        if (filled($id)) {
            return 'id:' . (string) $id;
        }

        return 'unknown';
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'institution_id' => $filters['institution_id'] ?? null,
            'course_id' => $filters['course_id'] ?? null,
            'start_date' => $this->dateString($filters['start_date'] ?? null),
            'end_date' => $this->dateString($filters['end_date'] ?? null),
        ];
    }

    private function dateString(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    private function sameText(?string $first, ?string $second): bool
    {
        return filled($first)
            && filled($second)
            && $this->slug($first) === $this->slug($second);
    }

    private function slug(?string $value): string
    {
        return Str::of((string) $value)->ascii()->lower()->trim()->squish()->toString();
    }

    private function compactSlug(?string $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    private function cacheTtl(): int
    {
        return max(0, (int) config('services.siga.cache_ttl', 900));
    }

    private function consultationStudentsCacheTtl(): int
    {
        return $this->cacheTtl();
    }

    private function staleCacheTtl(): int
    {
        return max($this->cacheTtl(), (int) config('services.siga.stale_cache_ttl', 86400));
    }

    private function failureBackoffTtl(): int
    {
        return max(15, (int) config('services.siga.failure_backoff', 60));
    }

    private function staleCacheKey(string $cacheKey): string
    {
        return $cacheKey . '.stale';
    }

    private function failureCacheKey(string $cacheKey): string
    {
        return $cacheKey . '.failed';
    }

    private function cachedCollection(string $cacheKey): ?Collection
    {
        $cached = Cache::get($cacheKey);

        if ($cached instanceof Collection) {
            return $cached;
        }

        return is_array($cached) ? collect($cached) : null;
    }

    private function storeCollection(string $cacheKey, Collection $rows, int $ttl): void
    {
        if ($ttl <= 0) {
            return;
        }

        Cache::put($cacheKey, $rows, $ttl);
        Cache::put($this->staleCacheKey($cacheKey), $rows, $this->staleCacheTtl());
    }

    private function hasRecentFailure(string $failureKey): bool
    {
        return Cache::has($failureKey);
    }

    private function markFailure(string $failureKey): void
    {
        Cache::put($failureKey, true, $this->failureBackoffTtl());
    }

    private function maxPages(): int
    {
        return min(100, max(1, (int) config('services.siga.max_pages', 10)));
    }

    private function perPage(): int
    {
        return min(1000, max(50, (int) config('services.siga.per_page', 1000)));
    }

    private function shouldVerifySsl(): bool
    {
        $value = config('services.siga.verify_ssl', true);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
    }

    private function isOwnDashboardEndpoint(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! Str::startsWith($path, '/api/')) {
            return false;
        }

        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = request()?->getHost();
        $urlHost = parse_url($url, PHP_URL_HOST);

        return filled($urlHost)
            && ($this->sameText($urlHost, $configuredHost) || $this->sameText($urlHost, $requestHost));
    }
}
