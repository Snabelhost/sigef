<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    private const ENCRYPTED_PREFIX = 'encrypted:';

    private const SENSITIVE_KEYS = [
        'mail_password',
        'sms_api_key',
        'sms_api_secret',
    ];

    private const REPORT_INSTITUTION_DEFAULTS = [
        'republic_line' => 'Republica de Angola',
        'ministry_line' => 'Ministerio do Interior',
        'organ_line' => 'Policia Nacional de Angola',
        'department_line' => 'Sistema Integrado de Gestao Escolar e Formacao',
        'name' => 'SIGEF',
        'acronym' => 'SIGEF',
        'director_name' => '',
        'director_title' => 'Director',
        'nif' => '',
        'phone' => '',
        'email' => '',
        'website' => '',
        'country' => 'Angola',
        'province' => '',
        'municipality' => '',
        'address' => '',
        'logo_path' => '',
        'watermark_path' => '',
        'certificate_school_name' => '',
        'certificate_left_signature_title' => 'O Director Adj. P/ Instrução e Ensino',
        'certificate_left_signature_subtitle' => '*Subcomissário*',
        'certificate_right_signature_title' => 'O Director da Escola',
        'certificate_right_signature_subtitle' => '**Comissário**',
        'footer_text' => '',
    ];

    private const REPORT_INSTITUTION_MODEL_FIELDS = [
        'name' => 'name',
        'acronym' => 'acronym',
        'phone' => 'phone',
        'email' => 'email',
        'country' => 'country',
        'province' => 'province',
        'municipality' => 'municipality',
        'address' => 'address',
        'logo_path' => 'logo',
        'certificate_school_name' => 'name',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting.{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting
                ? static::decodeValue($key, $setting->value, $default)
                : $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => static::encodeValue($key, $value), 'group' => $group]
        );

        Cache::forget("system_setting.{$key}");
    }

    private static function encodeValue(string $key, mixed $value): mixed
    {
        if (! static::isSensitiveKey($key) || blank($value)) {
            return $value;
        }

        return self::ENCRYPTED_PREFIX . Crypt::encryptString((string) $value);
    }

    private static function decodeValue(string $key, mixed $value, mixed $default = null): mixed
    {
        if (
            ! static::isSensitiveKey($key) ||
            ! is_string($value) ||
            ! str_starts_with($value, self::ENCRYPTED_PREFIX)
        ) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(self::ENCRYPTED_PREFIX)));
        } catch (DecryptException) {
            return $default;
        }
    }

    private static function isSensitiveKey(string $key): bool
    {
        return in_array($key, self::SENSITIVE_KEYS, true);
    }

    /**
     * Get all mail configuration settings.
     */
    public static function getMailConfig(): array
    {
        return [
            'mailer'        => static::get('mail_mailer', 'smtp'),
            'host'          => static::get('mail_host', ''),
            'port'          => static::get('mail_port', '587'),
            'encryption'    => static::get('mail_encryption', 'tls'),
            'username'      => static::get('mail_username', ''),
            'password'      => static::get('mail_password', ''),
            'from_address'  => static::get('mail_from_address', ''),
            'from_name'     => static::get('mail_from_name', 'SIGEF'),
        ];
    }

    /**
     * Get report institution data used in report headers and footers.
     */
    public static function getReportInstitutionConfig(Institution|int|null $institution = null): array
    {
        $institutionModel = static::resolveReportInstitution($institution);
        $config = [];

        foreach (self::REPORT_INSTITUTION_DEFAULTS as $key => $default) {
            $config[$key] = static::getReportInstitutionValue($key, $default, $institutionModel);
        }

        return $config;
    }

    public static function getReportInstitutionKeys(): array
    {
        return array_keys(self::REPORT_INSTITUTION_DEFAULTS);
    }

    public static function setReportInstitutionConfigValue(string $key, mixed $value, Institution|int|null $institution = null): void
    {
        if (! array_key_exists($key, self::REPORT_INSTITUTION_DEFAULTS)) {
            return;
        }

        $institutionId = static::reportInstitutionId($institution);
        $group = $institutionId ? "report_institution_{$institutionId}" : 'report_institution';

        static::set(static::reportInstitutionSettingKey($key, $institutionId), $value, $group);
    }

    private static function getReportInstitutionValue(string $key, mixed $default, ?Institution $institution = null): mixed
    {
        if ($institution) {
            $scopedKey = static::reportInstitutionSettingKey($key, (int) $institution->getKey());

            if (static::where('key', $scopedKey)->exists()) {
                $scopedValue = static::get($scopedKey, null);

                if (static::isReportInstitutionFilePath($key)) {
                    return static::normalizeReportInstitutionValue($key, $scopedValue);
                }

                if (filled($scopedValue) || ! array_key_exists($key, self::REPORT_INSTITUTION_MODEL_FIELDS)) {
                    return static::normalizeReportInstitutionValue($key, $scopedValue);
                }
            }

            $modelField = self::REPORT_INSTITUTION_MODEL_FIELDS[$key] ?? null;
            $modelValue = $modelField ? $institution->getAttribute($modelField) : null;

            if (filled($modelValue)) {
                return static::normalizeReportInstitutionValue($key, $modelValue);
            }
        }

        return static::normalizeReportInstitutionValue(
            $key,
            static::get(static::reportInstitutionSettingKey($key), $default)
        );
    }

    private static function normalizeReportInstitutionValue(string $key, mixed $value): mixed
    {
        if (! static::isReportInstitutionFilePath($key)) {
            return $value;
        }

        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        if (! is_scalar($value)) {
            return '';
        }

        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return static::normalizeReportInstitutionValue($key, reset($decoded) ?: '');
        }

        if (str_starts_with($value, '/storage/')) {
            return ltrim(substr($value, strlen('/storage/')), '/');
        }

        if (str_starts_with($value, 'storage/')) {
            return ltrim(substr($value, strlen('storage/')), '/');
        }

        return $value;
    }

    private static function isReportInstitutionFilePath(string $key): bool
    {
        return in_array($key, ['logo_path', 'watermark_path'], true);
    }

    private static function resolveReportInstitution(Institution|int|null $institution = null): ?Institution
    {
        if ($institution instanceof Institution) {
            return $institution;
        }

        if (filled($institution)) {
            return Institution::query()->find((int) $institution);
        }

        try {
            $tenant = \Filament\Facades\Filament::getTenant();
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

    private static function reportInstitutionId(Institution|int|null $institution = null): ?int
    {
        if ($institution instanceof Institution) {
            return (int) $institution->getKey();
        }

        return filled($institution) ? (int) $institution : null;
    }

    private static function reportInstitutionSettingKey(string $key, ?int $institutionId = null): string
    {
        return $institutionId
            ? "report_institution_{$institutionId}_{$key}"
            : "report_institution_{$key}";
    }

    /**
     * Check if mail is properly configured (not using log driver and has essential fields).
     */
    public static function isMailConfigured(): bool
    {
        $config = static::getMailConfig();

        if ($config['mailer'] === 'log') {
            return false;
        }

        return !empty($config['host']) && !empty($config['from_address']);
    }
}
