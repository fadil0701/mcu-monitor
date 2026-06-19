<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    app(\App\Services\CkgParticipantSyncService::class)->syncWithLog(
        now()->subDay(),
        'schedule',
    );
})->name('mcu-sync-participants-from-ckg-delta')
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command('mcu:backup-database')
    ->name('mcu-backup-database')
    ->dailyAt('03:00')
    ->withoutOverlapping();
