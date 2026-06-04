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
