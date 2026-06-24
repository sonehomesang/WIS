<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily borrow-return reminders (08:00 Vientiane). Production needs a cron
// entry: `* * * * * php artisan schedule:run` (see deploy / Phase 6.12).
Schedule::command('borrow:remind')->dailyAt('08:00')->timezone('Asia/Vientiane');
