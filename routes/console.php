<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console & Scheduled Tasks
|--------------------------------------------------------------------------
|
| Penjadwalan tugas otomatis (PRD 13.1, NFR-05, dan Prompt 8):
| - Pembersihan backup kedaluwarsa (> 30 hari) setiap dini hari pukul 01:00
| - Eksekusi backup database & foto setiap dini hari pukul 01:30
|
*/

Schedule::command('backup:clean --disable-notifications')
    ->dailyAt('01:00')
    ->name('bersihkan-backup-lama')
    ->withoutOverlapping();

Schedule::command('backup:run --disable-notifications')
    ->dailyAt('01:30')
    ->name('backup-harian')
    ->withoutOverlapping();
