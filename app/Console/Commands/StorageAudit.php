<?php

namespace App\Console\Commands;

use App\Support\PublicStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageAudit extends Command
{
    protected $signature = 'app:storage-audit
        {--json : Output the report as JSON}
        {--limit=20 : Maximum number of missing or invalid references to show}';

    protected $description = 'Audits public file delivery and checks database file references against storage/app/public.';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $root = (string) config('filesystems.disks.public.root');
        $limit = max(1, (int) $this->option('limit'));
        $report = [
            'configuration' => [
                'driver' => config('filesystems.disks.public.driver'),
                'root' => $root,
                'url' => config('filesystems.disks.public.url'),
                'root_exists' => is_dir($root),
                'root_readable' => is_dir($root) && is_readable($root),
                'root_writable' => is_dir($root) && is_writable($root),
                'media_route' => Route::has('media.files'),
                'legacy_storage_route' => Route::has('storage.files'),
                'public_link' => $this->publicLinkStatus($root),
            ],
            'references' => [
                'total' => 0,
                'existing' => 0,
                'missing' => 0,
                'external' => 0,
                'invalid' => 0,
                'missing_samples' => [],
                'invalid_samples' => [],
            ],
            'sources' => [],
        ];

        foreach ($this->referenceSources() as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn($table, $column),
            ));

            if ($columns === [] || ! Schema::hasColumn($table, 'id')) {
                continue;
            }

            $source = ['total' => 0, 'existing' => 0, 'missing' => 0, 'external' => 0, 'invalid' => 0];

            DB::table($table)
                ->select(['id', ...$columns])
                ->orderBy('id')
                ->chunkById(500, function ($records) use ($disk, $table, $columns, $limit, &$report, &$source): void {
                    foreach ($records as $record) {
                        foreach ($columns as $column) {
                            $value = trim((string) ($record->{$column} ?? ''));

                            if ($value === '') {
                                continue;
                            }

                            $report['references']['total']++;
                            $source['total']++;

                            $path = PublicStorage::normalizePath($value);

                            if ($path === null && $this->isExternalValue($value)) {
                                $report['references']['external']++;
                                $source['external']++;

                                continue;
                            }

                            if ($path === null) {
                                $report['references']['invalid']++;
                                $source['invalid']++;
                                $this->addSample($report['references']['invalid_samples'], $limit, $table, $record->id, $column, $value);

                                continue;
                            }

                            if ($disk->exists($path)) {
                                $report['references']['existing']++;
                                $source['existing']++;

                                continue;
                            }

                            $report['references']['missing']++;
                            $source['missing']++;
                            $this->addSample($report['references']['missing_samples'], $limit, $table, $record->id, $column, $path);
                        }
                    }
                });

            $report['sources'][$table] = $source;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->renderReport($report);
        }

        $configuration = $report['configuration'];

        return ! $configuration['root_exists']
            || ! $configuration['root_readable']
            || ! $configuration['media_route']
            || $report['references']['missing'] > 0
            || $report['references']['invalid'] > 0
                ? self::FAILURE
                : self::SUCCESS;
    }

    private function renderReport(array $report): void
    {
        $configuration = $report['configuration'];

        $this->table(['Check', 'Value'], [
            ['Public disk driver', $configuration['driver']],
            ['Public disk root', $configuration['root']],
            ['Public files URL', $configuration['url']],
            ['Root exists/readable/writable', $this->yesNo($configuration['root_exists']).' / '.$this->yesNo($configuration['root_readable']).' / '.$this->yesNo($configuration['root_writable'])],
            ['Canonical /media route', $this->yesNo($configuration['media_route'])],
            ['Legacy /storage route', $this->yesNo($configuration['legacy_storage_route'])],
            ['public/storage link', $configuration['public_link']],
        ]);

        $references = $report['references'];

        $this->table(['References', 'Count'], [
            ['Total', $references['total']],
            ['Existing', $references['existing']],
            ['Missing files', $references['missing']],
            ['External URLs', $references['external']],
            ['Invalid paths', $references['invalid']],
        ]);

        if ($report['sources'] !== []) {
            $this->table(
                ['Source', 'Total', 'Existing', 'Missing', 'External', 'Invalid'],
                collect($report['sources'])->map(fn (array $source, string $table): array => [
                    $table,
                    $source['total'],
                    $source['existing'],
                    $source['missing'],
                    $source['external'],
                    $source['invalid'],
                ])->values()->all(),
            );
        }

        foreach (['missing_samples' => 'Missing file samples', 'invalid_samples' => 'Invalid path samples'] as $key => $heading) {
            if ($references[$key] === []) {
                continue;
            }

            $this->newLine();
            $this->warn($heading.':');

            foreach ($references[$key] as $sample) {
                $this->line(' - '.$sample);
            }
        }
    }

    private function referenceSources(): array
    {
        return [
            'candidates' => ['photo', 'bilhete_identidade', 'certificado_doc', 'curriculum'],
            // In legacy data, the student document fields may contain the
            // document number itself, so only photo is a reliable file path.
            'students' => ['photo'],
            'trainers' => ['photo'],
            'effectives' => ['photo', 'file_identity_card', 'file_contract', 'file_cv', 'file_certificate', 'file_other_document'],
            'institutions' => ['logo'],
            'card_templates' => ['front_background_path', 'back_background_path', 'logo_path', 'signature_image_path', 'sample_photo_path', 'fallback_photo_path'],
            'document_attachments' => ['file_path'],
        ];
    }

    private function isExternalValue(string $value): bool
    {
        return Str::startsWith($value, ['http://', 'https://', 'data:', 'blob:']);
    }

    private function addSample(array &$samples, int $limit, string $table, mixed $id, string $column, string $value): void
    {
        if (count($samples) >= $limit) {
            return;
        }

        $samples[] = "{$table}#{$id}.{$column}: {$value}";
    }

    private function publicLinkStatus(string $root): string
    {
        $link = public_path('storage');

        if (! file_exists($link) && ! is_link($link)) {
            return 'missing (optional because /media is available)';
        }

        $actualTarget = realpath($link);
        $expectedTarget = realpath($root);

        if ($actualTarget !== false && $expectedTarget !== false && $actualTarget === $expectedTarget) {
            return 'valid';
        }

        return 'present but points to a different location';
    }

    private function yesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }
}
