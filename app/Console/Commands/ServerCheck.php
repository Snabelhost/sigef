<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ServerCheck extends Command
{
    protected $signature = 'app:server-check {--json : Output the report as JSON}';

    protected $description = 'Checks whether the server can run SIGEF without bootstrap, storage, database, or PHP extension errors.';

    public function handle(): int
    {
        $checks = [];

        $this->addCheck(
            $checks,
            'PHP',
            version_compare(PHP_VERSION, '8.2.0', '>=') && version_compare(PHP_VERSION, '8.5.0', '<'),
            PHP_VERSION,
            'Use PHP 8.2, 8.3, or 8.4.',
        );

        foreach ($this->requiredExtensions() as $extension) {
            $this->addCheck(
                $checks,
                'ext-'.$extension,
                extension_loaded($extension),
                extension_loaded($extension) ? 'loaded' : 'missing',
                'Enable the PHP extension in the web and CLI SAPIs.',
            );
        }

        $databaseExtension = $this->databaseExtension();

        if ($databaseExtension) {
            $this->addCheck(
                $checks,
                'ext-'.$databaseExtension,
                extension_loaded($databaseExtension),
                extension_loaded($databaseExtension) ? 'loaded' : 'missing',
                'Enable the PDO extension required by DB_CONNECTION.',
            );
        }

        $this->addCheck(
            $checks,
            'APP_KEY',
            filled(config('app.key')),
            filled(config('app.key')) ? 'configured' : 'missing',
            'Run php artisan key:generate.',
        );

        foreach ($this->runtimeDirectories() as $label => $path) {
            $checkExistingFiles = in_array($label, ['Blade views', 'Logs'], true);

            $this->addCheck(
                $checks,
                $label,
                $this->directoryIsWritable($path, $checkExistingFiles),
                $path,
                'Grant the web server write access to this directory.',
                severity: 'warning',
            );
        }

        try {
            DB::connection()->getPdo();
            $this->addCheck($checks, 'Database connection', true, (string) config('database.default'));

            foreach (['migrations', 'users'] as $table) {
                $exists = Schema::hasTable($table);

                $this->addCheck(
                    $checks,
                    'Table '.$table,
                    $exists,
                    $exists ? 'present' : 'missing',
                    'Run php artisan migrate --force.',
                );
            }

            $this->checkDriverTable($checks, 'Session', config('session.driver'), (string) config('session.table', 'sessions'));
            $this->checkDriverTable($checks, 'Cache', config('cache.default'), (string) config('cache.stores.database.table', 'cache'));
            $this->checkDriverTable($checks, 'Queue', config('queue.default'), (string) config('queue.connections.database.table', 'jobs'));
        } catch (Throwable $exception) {
            $this->addCheck(
                $checks,
                'Database connection',
                false,
                (string) config('database.default'),
                $this->safeExceptionMessage($exception),
            );
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Check', 'Status', 'Value', 'Action'],
                array_map(fn (array $check): array => [
                    $check['name'],
                    strtoupper($check['status']),
                    $check['value'],
                    $check['action'],
                ], $checks),
            );
        }

        $failed = collect($checks)->contains(fn (array $check): bool => $check['status'] === 'failed');

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function checkDriverTable(array &$checks, string $label, mixed $driver, string $table): void
    {
        if ($driver !== 'database') {
            $this->addCheck($checks, $label.' driver', true, (string) $driver);

            return;
        }

        $exists = Schema::hasTable($table);

        $this->addCheck(
            $checks,
            $label.' table',
            $exists,
            $table,
            'Run php artisan migrate --force or use the portable file/sync driver.',
        );
    }

    private function addCheck(
        array &$checks,
        string $name,
        bool $passes,
        string $value,
        string $action = '',
        string $severity = 'failed',
    ): void {
        $checks[] = [
            'name' => $name,
            'status' => $passes ? 'ok' : $severity,
            'value' => $value,
            'action' => $passes ? '' : $action,
        ];
    }

    private function directoryIsWritable(string $path, bool $checkExistingFiles = false): bool
    {
        if (! is_dir($path) && ! @mkdir($path, 0775, true) && ! is_dir($path)) {
            return false;
        }

        $probe = $path.DIRECTORY_SEPARATOR.'.sigef-server-check-'.bin2hex(random_bytes(4));
        $written = @file_put_contents($probe, 'ok', LOCK_EX);

        if ($written === false) {
            return false;
        }

        @unlink($probe);

        if (! $checkExistingFiles) {
            return true;
        }

        $checkedFiles = 0;

        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (! is_writable($file->getPathname())) {
                return false;
            }

            if (++$checkedFiles >= 10) {
                break;
            }
        }

        return true;
    }

    private function databaseExtension(): ?string
    {
        return match (config('database.default')) {
            'mysql', 'mariadb' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            'sqlsrv' => 'pdo_sqlsrv',
            default => null,
        };
    }

    private function requiredExtensions(): array
    {
        return [
            'ctype',
            'dom',
            'fileinfo',
            'filter',
            'gd',
            'hash',
            'iconv',
            'intl',
            'json',
            'libxml',
            'openssl',
            'pcre',
            'session',
            'simplexml',
            'tokenizer',
            'xml',
            'xmlreader',
            'xmlwriter',
            'zip',
            'zlib',
        ];
    }

    private function runtimeDirectories(): array
    {
        return [
            'Blade views' => storage_path('framework/views'),
            'Sessions' => storage_path('framework/sessions'),
            'File cache' => storage_path('framework/cache/data'),
            'Logs' => storage_path('logs'),
            'Bootstrap cache' => base_path('bootstrap/cache'),
        ];
    }

    private function safeExceptionMessage(Throwable $exception): string
    {
        $message = preg_replace('/password=[^\s;]+/i', 'password=[hidden]', $exception->getMessage());

        return mb_substr((string) $message, 0, 240);
    }
}
