<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting.{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("system_setting.{$key}");
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
    public static function getReportInstitutionConfig(): array
    {
        return [
            'republic_line' => static::get('report_institution_republic_line', 'República de Angola'),
            'ministry_line' => static::get('report_institution_ministry_line', 'Ministério do Interior'),
            'organ_line' => static::get('report_institution_organ_line', 'Polícia Nacional de Angola'),
            'department_line' => static::get('report_institution_department_line', 'Sistema Integrado de Gestão Escolar e Formação'),
            'name' => static::get('report_institution_name', 'SIGEF'),
            'acronym' => static::get('report_institution_acronym', 'SIGEF'),
            'director_name' => static::get('report_institution_director_name', ''),
            'director_title' => static::get('report_institution_director_title', 'Director'),
            'nif' => static::get('report_institution_nif', ''),
            'phone' => static::get('report_institution_phone', ''),
            'email' => static::get('report_institution_email', ''),
            'website' => static::get('report_institution_website', ''),
            'country' => static::get('report_institution_country', 'Angola'),
            'province' => static::get('report_institution_province', ''),
            'municipality' => static::get('report_institution_municipality', ''),
            'address' => static::get('report_institution_address', ''),
            'logo_path' => static::get('report_institution_logo_path', ''),
            'footer_text' => static::get('report_institution_footer_text', ''),
        ];
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
