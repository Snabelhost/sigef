<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;

class DynamicMailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Only override mail config if the system_settings table exists
        // and has mail configuration stored
        try {
            if (!Schema::hasTable('system_settings')) {
                return;
            }

            $mailConfig = SystemSetting::getMailConfig();

            // Only override if there's actual configuration in the DB
            if (empty($mailConfig['host']) && empty($mailConfig['from_address'])) {
                return;
            }

            // Override Laravel mail configuration dynamically
            if (!empty($mailConfig['mailer'])) {
                Config::set('mail.default', $mailConfig['mailer']);
            }

            if (!empty($mailConfig['host'])) {
                Config::set('mail.mailers.smtp.host', $mailConfig['host']);
            }

            if (!empty($mailConfig['port'])) {
                Config::set('mail.mailers.smtp.port', (int) $mailConfig['port']);
            }

            if (!empty($mailConfig['username'])) {
                Config::set('mail.mailers.smtp.username', $mailConfig['username']);
            }

            if (!empty($mailConfig['password'])) {
                Config::set('mail.mailers.smtp.password', $mailConfig['password']);
            }

            if (!empty($mailConfig['encryption'])) {
                Config::set('mail.mailers.smtp.encryption', $mailConfig['encryption']);
                // Laravel uses 'smtps' scheme for SSL, null for TLS (STARTTLS)
                $scheme = match ($mailConfig['encryption']) {
                    'ssl' => 'smtps',
                    'tls' => null,
                    default => null,
                };
                Config::set('mail.mailers.smtp.scheme', $scheme);
            }

            if (!empty($mailConfig['from_address'])) {
                Config::set('mail.from.address', $mailConfig['from_address']);
            }

            if (!empty($mailConfig['from_name'])) {
                Config::set('mail.from.name', $mailConfig['from_name']);
            }
        } catch (\Exception $e) {
            // Silently fail - DB might not be available during migrations
        }
    }
}
