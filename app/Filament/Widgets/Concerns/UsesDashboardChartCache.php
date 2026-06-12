<?php

namespace App\Filament\Widgets\Concerns;

use App\Support\ChartColors;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

trait UsesDashboardChartCache
{
    protected function rememberDashboardChart(string $name, array $filters, callable $callback, array $fallback): array
    {
        $cacheKey = 'dashboard.chart.' . $name . '.' . md5(json_encode([
            'version' => 3,
            'filters' => $this->normalizeDashboardChartFilters($filters),
            'siga_base_url' => config('services.siga.base_url'),
            'siga_auth' => md5((string) config('services.siga.api_key') . '|' . (string) config('services.siga.token')),
            'siga_endpoints' => config('services.siga.endpoints'),
        ]));
        $freshTtl = $this->dashboardChartFreshTtl();
        $staleTtl = $this->dashboardChartStaleTtl();
        $staleKey = $cacheKey . '.stale';

        if ($freshTtl <= 0) {
            return $this->loadDashboardChart($name, $cacheKey, $staleKey, $callback, $fallback);
        }

        $cached = Cache::get($cacheKey);

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $data = $this->loadDashboardChart($name, $cacheKey, $staleKey, $callback, $fallback);

        if ($data !== $fallback) {
            Cache::put($cacheKey, $data, $freshTtl);
            Cache::put($staleKey, $data, $staleTtl);
        }

        return $data;
    }

    protected function dashboardChartFilters(): array
    {
        return [
            'institution_id' => $this->filters['institution_id'] ?? null,
            'course_id' => $this->filters['course_id'] ?? null,
            'start_date' => $this->filters['start_date'] ?? null,
            'end_date' => $this->filters['end_date'] ?? null,
        ];
    }

    protected function emptyBarChartData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Sistema',
                    'data' => [0],
                    'backgroundColor' => [ChartColors::forLabel('Sistema', 0.28)],
                    'borderColor' => [ChartColors::forLabel('Sistema', 0.7)],
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
                [
                    'label' => 'API SIGA',
                    'data' => [0],
                    'backgroundColor' => [ChartColors::forLabel('API SIGA', 0.2)],
                    'borderColor' => [ChartColors::forLabel('API SIGA', 0.65)],
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => ['Sem dados'],
        ];
    }

    protected function emptyPieChartData(string $label = 'Formandos'): array
    {
        return [
            'datasets' => [
                [
                    'label' => $label,
                    'data' => [1],
                    'backgroundColor' => [ChartColors::forLabel('Sem dados', 0.8)],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['Sem dados'],
        ];
    }

    private function loadDashboardChart(string $name, string $cacheKey, string $staleKey, callable $callback, array $fallback): array
    {
        try {
            $data = $callback();

            return is_array($data) ? $data : $fallback;
        } catch (Throwable $exception) {
            Log::warning('Dashboard chart failed to load.', [
                'chart' => $name,
                'error' => $exception->getMessage(),
            ]);

            $cached = Cache::get($cacheKey) ?: Cache::get($staleKey);

            return is_array($cached) ? $cached : $fallback;
        }
    }

    private function normalizeDashboardChartFilters(array $filters): array
    {
        return [
            'institution_id' => $filters['institution_id'] ?? null,
            'course_id' => $filters['course_id'] ?? null,
            'start_date' => (string) ($filters['start_date'] ?? ''),
            'end_date' => (string) ($filters['end_date'] ?? ''),
        ];
    }

    private function dashboardChartFreshTtl(): int
    {
        return min(120, max(30, (int) config('services.siga.cache_ttl', 900)));
    }

    private function dashboardChartStaleTtl(): int
    {
        return max($this->dashboardChartFreshTtl(), (int) config('services.siga.stale_cache_ttl', 86400));
    }
}
