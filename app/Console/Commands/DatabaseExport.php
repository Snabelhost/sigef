<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Symfony\Component\Process\Process;

class DatabaseExport extends Command
{
    protected $signature = 'app:db-export
        {--path= : Output .sql file path}
        {--mysqldump= : Path to the mysqldump executable}
        {--force : Overwrite the output file if it already exists}
        {--timeout=300 : Maximum dump runtime in seconds}';

    protected $description = 'Exports the configured MySQL database with DROP TABLE statements for safe re-import.';

    public function handle(): int
    {
        $connection = (string) config('database.default');

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            $this->error("Database export only supports mysql/mariadb connections. Current connection: {$connection}");

            return self::FAILURE;
        }

        $config = config("database.connections.{$connection}", []);
        $database = (string) ($config['database'] ?? '');

        if ($database === '') {
            $this->error('Database name is not configured.');

            return self::FAILURE;
        }

        $mysqldump = $this->resolveMysqldump();

        if ($mysqldump === null) {
            $this->error('mysqldump was not found. Use --mysqldump="C:\\path\\to\\mysqldump.exe".');

            return self::FAILURE;
        }

        $outputPath = $this->resolveOutputPath((string) $this->option('path'));
        $outputDir = dirname($outputPath);

        if (! is_dir($outputDir) && ! @mkdir($outputDir, 0775, true) && ! is_dir($outputDir)) {
            $this->error("Could not create export directory: {$outputDir}");

            return self::FAILURE;
        }

        if (file_exists($outputPath) && ! $this->option('force')) {
            $this->error("Export file already exists: {$outputPath}");
            $this->line('Run again with --force to overwrite it.');

            return self::FAILURE;
        }

        $optionsFile = $this->writeTemporaryOptionsFile($config);

        try {
            $command = [
                $mysqldump,
                "--defaults-extra-file={$optionsFile}",
                '--default-character-set=utf8mb4',
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--events',
                '--add-drop-table',
                '--no-tablespaces',
                "--result-file={$outputPath}",
            ];

            if (filled($config['host'] ?? null)) {
                $command[] = '--host='.(string) $config['host'];
            }

            if (filled($config['port'] ?? null)) {
                $command[] = '--port='.(string) $config['port'];
            }

            if (filled($config['unix_socket'] ?? null)) {
                $command[] = '--socket='.(string) $config['unix_socket'];
            }

            $command[] = $database;

            $process = new Process($command, base_path());
            $process->setTimeout(max(1, (int) $this->option('timeout')));
            $process->run();

            if (! $process->isSuccessful()) {
                $this->error('Database export failed.');
                $this->line(trim($process->getErrorOutput() ?: $process->getOutput()));

                return self::FAILURE;
            }
        } finally {
            @unlink($optionsFile);
        }

        $this->info("Database export created: {$outputPath}");
        $this->line('The dump includes DROP TABLE IF EXISTS statements before CREATE TABLE statements.');

        return self::SUCCESS;
    }

    private function resolveMysqldump(): ?string
    {
        $configured = (string) ($this->option('mysqldump') ?: env('MYSQLDUMP_BINARY', ''));

        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        foreach ($this->laragonMysqldumpCandidates() as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $finder = PHP_OS_FAMILY === 'Windows'
            ? new Process(['where', 'mysqldump'])
            : new Process(['which', 'mysqldump']);

        $finder->run();

        if ($finder->isSuccessful()) {
            $path = trim(explode(PHP_EOL, $finder->getOutput())[0] ?? '');

            if ($path !== '') {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function laragonMysqldumpCandidates(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        return glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe') ?: [];
    }

    private function resolveOutputPath(string $path): string
    {
        if ($path === '') {
            $path = 'database/exports/sigef-'.Date::now()->format('Ymd-His').'.sql';
        }

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, '\\\\');
        }

        return str_starts_with($path, '/');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function writeTemporaryOptionsFile(array $config): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sigef-mysql-');

        if ($path === false) {
            throw new \RuntimeException('Could not create a temporary MySQL options file.');
        }

        $contents = "[client]\n"
            .'user='.$this->optionFileValue((string) ($config['username'] ?? ''))."\n"
            .'password='.$this->optionFileValue((string) ($config['password'] ?? ''))."\n";

        file_put_contents($path, $contents, LOCK_EX);

        return $path;
    }

    private function optionFileValue(string $value): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }
}
