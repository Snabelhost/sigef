<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardCourseStatsService
{
    public function studentsByCourse(array $filters = []): array
    {
        $cacheKey = 'dashboard.students_by_course.' . md5(json_encode($this->normalizeFilters($filters)));

        return Cache::remember($cacheKey, 300, function () use ($filters): array {
            $institutionId = $filters['institution_id'] ?? null;
            $courseId = $filters['course_id'] ?? null;
            $startDate = $this->dateOrNull($filters['start_date'] ?? null);
            $endDate = $this->dateOrNull($filters['end_date'] ?? null);

            $localItems = Student::query()
                ->whereNotNull('students.institution_id')
                ->whereNotNull('students.course_map_id')
                ->when($institutionId, fn ($query) => $query->where('students.institution_id', $institutionId))
                ->when($courseId, fn ($query) => $query->where('course_maps.course_id', $courseId))
                ->when($startDate, fn ($query) => $query->whereDate('students.created_at', '>=', $startDate))
                ->when($endDate, fn ($query) => $query->whereDate('students.created_at', '<=', $endDate))
                ->join('course_maps', 'students.course_map_id', '=', 'course_maps.id')
                ->join('courses', 'course_maps.course_id', '=', 'courses.id')
                ->select([
                    'courses.id as course_id',
                    'courses.name as course_name',
                    DB::raw('COUNT(DISTINCT students.id) as total_alunos'),
                ])
                ->groupBy('courses.id', 'courses.name')
                ->orderByDesc('total_alunos')
                ->limit(10)
                ->get()
                ->map(fn ($item): array => [
                    'course_id' => (int) $item->course_id,
                    'course_name' => $item->course_name ?: 'Curso nao identificado',
                    'sistema_alunos' => (int) $item->total_alunos,
                    'api_alunos' => 0,
                    'total_alunos' => (int) $item->total_alunos,
                ])
                ->all();

            $apiItems = app(SigaDashboardStatsService::class)->studentsByCourse($filters);

            return $this->mergeCourseStats($localItems, $apiItems);
        });
    }

    private function mergeCourseStats(array $localItems, array $apiItems): array
    {
        $merged = [];

        foreach ($localItems as $item) {
            $key = $this->courseKey($item);
            $merged[$key] = [
                'course_id' => $item['course_id'] ?? null,
                'course_name' => $item['course_name'] ?? 'Curso nao identificado',
                'sistema_alunos' => (int) ($item['sistema_alunos'] ?? $item['total_alunos'] ?? 0),
                'api_alunos' => 0,
                'total_alunos' => (int) ($item['total_alunos'] ?? 0),
            ];
        }

        foreach ($apiItems as $item) {
            $key = $this->courseKey($item);

            if (! isset($merged[$key])) {
                $merged[$key] = [
                    'course_id' => $item['course_id'] ?? null,
                    'course_name' => $item['course_name'] ?? 'Curso SIGA',
                    'sistema_alunos' => 0,
                    'api_alunos' => 0,
                    'total_alunos' => 0,
                ];
            }

            $merged[$key]['api_alunos'] += (int) ($item['total_alunos'] ?? 0);
            $merged[$key]['total_alunos'] = $merged[$key]['sistema_alunos'] + $merged[$key]['api_alunos'];
        }

        usort($merged, fn (array $first, array $second): int => $second['total_alunos'] <=> $first['total_alunos']);

        return array_slice(array_values($merged), 0, 10);
    }

    private function courseKey(array $item): string
    {
        if (filled($item['course_name'] ?? null)) {
            return 'name:' . Str::of((string) $item['course_name'])->ascii()->lower()->trim()->squish()->toString();
        }

        return 'id:' . (string) ($item['course_id'] ?? 'unknown');
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'institution_id' => $filters['institution_id'] ?? null,
            'course_id' => $filters['course_id'] ?? null,
            'start_date' => (string) ($filters['start_date'] ?? ''),
            'end_date' => (string) ($filters['end_date'] ?? ''),
        ];
    }

    private function dateOrNull(mixed $value): ?Carbon
    {
        return filled($value) ? Carbon::parse($value) : null;
    }
}
