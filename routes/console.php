<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes — Cron-Driven Queue for cPanel Shared Hosting
|--------------------------------------------------------------------------
|
| On shared hosting without supervisor/daemon support, the Laravel scheduler
| (triggered by a single `* * * * * php artisan schedule:run`) drives all
| background work. The click buffer is drained every minute.
|
| cPanel cron entry:
|   * * * * * cd /home/user/public_html && php artisan schedule:run >> /dev/null 2>&1
|
*/

Schedule::command('tracking:process-buffer')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tracking/buffer_cron.log'));
