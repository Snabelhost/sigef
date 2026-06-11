<?php

namespace App\Filament\Resources\Concerns;

use App\Models\Institution;
use App\Models\SystemSetting;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ResolvesInstitutionLogo
{
    public function getInstitutionLogoUrl(): string
    {
        $institution = $this->resolveInstitutionForLogo();
        $config = SystemSetting::getReportInstitutionConfig($institution);

        return $this->publicInstitutionLogoUrl($config['logo_path'] ?? null)
            ?: asset('images/logo-pna.png');
    }

    protected function resolveInstitutionForLogo(): ?Institution
    {
        if (property_exists($this, 'institution_id') && filled($this->institution_id)) {
            return Institution::query()->find((int) $this->institution_id);
        }

        try {
            $tenant = Filament::getTenant();
        } catch (\Throwable) {
            $tenant = null;
        }

        if ($tenant instanceof Institution) {
            return $tenant;
        }

        $userInstitutionId = auth()->user()?->institution_id;

        if (filled($userInstitutionId)) {
            return Institution::query()->find((int) $userInstitutionId);
        }

        return null;
    }

    protected function publicInstitutionLogoUrl(mixed $path): ?string
    {
        $path = $this->normalizeInstitutionLogoPath($path);

        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, '/storage/')) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return null;
    }

    protected function normalizeInstitutionLogoPath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = reset($path) ?: null;
        }

        if (! is_scalar($path)) {
            return null;
        }

        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        $decoded = json_decode($path, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->normalizeInstitutionLogoPath(reset($decoded) ?: null);
        }

        return str_replace('\\', '/', $path);
    }
}
