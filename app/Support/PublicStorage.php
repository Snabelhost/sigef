<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PublicStorage
{
    public static function normalizePath(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || str_contains($value, "\0")) {
            return null;
        }

        $value = str_replace('\\', '/', $value);

        if (Str::startsWith($value, ['data:', 'blob:'])) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $urlPath = rawurldecode((string) parse_url($value, PHP_URL_PATH));

            if (! Str::startsWith($urlPath, ['/storage/', '/media/'])) {
                return null;
            }

            $value = $urlPath;
        }

        $value = rawurldecode(strtok($value, '?#') ?: $value);
        $value = ltrim($value, '/');

        foreach ([
            'storage/app/public/',
            'app/public/',
            'public/storage/',
            'storage/',
            'media/',
        ] as $prefix) {
            if (Str::startsWith(Str::lower($value), $prefix)) {
                $value = substr($value, strlen($prefix));
                break;
            }
        }

        $value = trim(preg_replace('#/+#', '/', $value) ?? '', '/');

        if ($value === '' || in_array('..', explode('/', $value), true)) {
            return null;
        }

        return $value;
    }

    public static function url(?string $value, bool $requireExisting = false): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['data:', 'blob:'])) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $urlPath = (string) parse_url($value, PHP_URL_PATH);

            if (! Str::startsWith($urlPath, ['/storage/', '/media/'])) {
                return $value;
            }
        }

        $path = self::normalizePath($value);

        if ($path === null) {
            return null;
        }

        $disk = Storage::disk('public');

        if ($requireExisting && ! $disk->exists($path)) {
            return null;
        }

        return $disk->url($path);
    }

    public static function existingPath(?string $value): ?string
    {
        $path = self::normalizePath($value);

        return $path !== null && Storage::disk('public')->exists($path)
            ? $path
            : null;
    }

    public static function existingDisplayValue(?string $value): ?string
    {
        $value = trim((string) $value);

        if (filter_var($value, FILTER_VALIDATE_URL) && self::normalizePath($value) === null) {
            return $value;
        }

        if (Str::startsWith($value, 'data:')) {
            return $value;
        }

        return self::existingPath($value);
    }
}
