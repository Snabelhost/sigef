<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================================
// SCHEDULED TASKS
// ============================================================

// Backup diário da BD às 02:00
Schedule::command('backup:run --only-db')->dailyAt('02:00')
    ->name('backup-db')
    ->withoutOverlapping();

// Limpeza de backups antigos (domingos às 03:00)
Schedule::command('backup:clean')->weeklyOn(0, '03:00')
    ->name('backup-clean')
    ->withoutOverlapping();

// Limpeza de audits antigos (mensal, manter 90 dias)
Schedule::command('audits:clean --days=90')->monthlyOn(1, '04:00')
    ->name('audits-clean')
    ->withoutOverlapping();
