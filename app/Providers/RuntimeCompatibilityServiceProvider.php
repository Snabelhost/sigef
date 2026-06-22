<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class RuntimeCompatibilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->configureWritableRuntimePaths();
        $this->configurePortableDrivers();
    }

    private function configureWritableRuntimePaths(): void
    {
        $viewsPath = $this->runtimeDirectory(
            'framework/views',
            config('view.compiled'),
            checkExistingFiles: true,
        );
        $sessionsPath = $this->runtimeDirectory('framework/sessions', config('session.files'));
        $cachePath = $this->runtimeDirectory('framework/cache/data', config('cache.stores.file.path'));
        $configuredLogPath = config('logging.channels.single.path');
        $logsPath = $this->runtimeDirectory(
            'logs',
            is_string($configuredLogPath) ? dirname($configuredLogPath) : null,
            checkExistingFiles: true,
        );

        if ($viewsPath) {
            config()->set('view.compiled', $viewsPath);
        }

        if ($sessionsPath) {
            config()->set('session.files', $sessionsPath);
        }

        if ($cachePath) {
            config()->set('cache.stores.file.path', $cachePath);
            config()->set('cache.stores.file.lock_path', $cachePath);
        }

        if ($logsPath) {
            $logFile = $logsPath.DIRECTORY_SEPARATOR.'laravel.log';

            config()->set('logging.channels.single.path', $logFile);
            config()->set('logging.channels.daily.path', $logFile);
            config()->set('logging.channels.emergency.path', $logFile);
        } else {
            config()->set('logging.default', 'errorlog');
            config()->set('logging.channels.stack.channels', ['errorlog']);
        }
    }

    private function configurePortableDrivers(): void
    {
        if (config('session.driver') === 'database' && ! $this->tableExists(
            (string) config('session.table', 'sessions'),
            config('session.connection'),
        )) {
            config()->set('session.driver', 'file');
        }

        if (config('cache.default') === 'database' && ! $this->tableExists(
            (string) config('cache.stores.database.table', 'cache'),
            config('cache.stores.database.connection'),
        )) {
            config()->set('cache.default', 'file');
        }

        if (config('queue.default') === 'database' && ! $this->tableExists(
            (string) config('queue.connections.database.table', 'jobs'),
            config('queue.connections.database.connection'),
        )) {
            config()->set('queue.default', 'sync');
        }
    }

    private function tableExists(string $table, ?string $connection): bool
    {
        try {
            return Schema::connection($connection)->hasTable($table);
        } catch (Throwable $exception) {
            error_log(sprintf(
                'SIGEF runtime compatibility: unable to inspect table [%s]: %s',
                $table,
                $exception->getMessage(),
            ));

            return false;
        }
    }

    private function runtimeDirectory(
        string $relativePath,
        mixed $configuredPath = null,
        bool $checkExistingFiles = false,
    ): ?string
    {
        $preferredPath = storage_path(str_replace('/', DIRECTORY_SEPARATOR, $relativePath));

        $candidatePaths = array_filter(
            [$configuredPath, $preferredPath],
            fn (mixed $path): bool => is_string($path) && $path !== '',
        );

        foreach (array_unique($candidatePaths) as $candidatePath) {
            if ($this->ensureWritableDirectory($candidatePath, $checkExistingFiles)) {
                return $candidatePath;
            }
        }

        $fallbackPath = rtrim(sys_get_temp_dir(), '/\\')
            .DIRECTORY_SEPARATOR.'sigef-runtime-'.substr(sha1(base_path()), 0, 12)
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if ($this->ensureWritableDirectory($fallbackPath)) {
            error_log(sprintf(
                'SIGEF runtime compatibility: [%s] is not writable; using [%s].',
                $preferredPath,
                $fallbackPath,
            ));

            return $fallbackPath;
        }

        return null;
    }

    private function ensureWritableDirectory(string $path, bool $checkExistingFiles = false): bool
    {
        if (! is_dir($path) && ! @mkdir($path, 0775, true) && ! is_dir($path)) {
            return false;
        }

        if (! is_writable($path) || ! $checkExistingFiles) {
            return is_writable($path);
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
}
