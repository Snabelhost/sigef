<?php

use App\Models\SystemSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================================
// SCHEDULED TASKS
// ============================================================

$backupSetting = static function (string $key, mixed $default = null): mixed {
    try {
        return SystemSetting::get($key, $default);
    } catch (Throwable) {
        return $default;
    }
};

$backupTime = static function () use ($backupSetting): string {
    $time = (string) $backupSetting('backup_time', '02:00');

    return preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '02:00';
};

$backupEnabled = static fn (): bool => filter_var(
    $backupSetting('backup_enabled', false),
    FILTER_VALIDATE_BOOLEAN
);

$backupFrequencyIs = static fn (string $frequency): bool => $backupSetting('backup_frequency', 'daily') === $frequency;

// Backup da BD conforme configurado em Configuracoes > Backup da BD
Schedule::command('backup:run --only-db --disable-notifications')
    ->dailyAt($backupTime())
    ->name('backup-db-daily')
    ->when(fn (): bool => $backupEnabled() && $backupFrequencyIs('daily'))
    ->withoutOverlapping();

Schedule::command('backup:run --only-db --disable-notifications')
    ->weeklyOn(0, $backupTime())
    ->name('backup-db-weekly')
    ->when(fn (): bool => $backupEnabled() && $backupFrequencyIs('weekly'))
    ->withoutOverlapping();

Schedule::command('backup:run --only-db --disable-notifications')
    ->monthlyOn(1, $backupTime())
    ->name('backup-db-monthly')
    ->when(fn (): bool => $backupEnabled() && $backupFrequencyIs('monthly'))
    ->withoutOverlapping();

// Limpeza de backups antigos
Schedule::command('backup:clean')
    ->weeklyOn(0, '03:00')
    ->name('backup-clean')
    ->when(fn (): bool => $backupEnabled())
    ->withoutOverlapping();

// Limpeza de audits antigos (mensal, manter 90 dias)
Schedule::command('audits:clean --days=90')->monthlyOn(1, '04:00')
    ->name('audits-clean')
    ->withoutOverlapping();
