<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('db-backup:run')
    ->dailyAt('02:00')
    ->runInBackground();

Schedule::command('monitor:check-heartbeats')
    ->everyFiveMinutes();

Schedule::command('monitor:flush-issue-notifications')
    ->everyFiveMinutes();

Schedule::command('monitor:check-queue-health')
    ->everyFiveMinutes();

Schedule::command('monitor:prune')
    ->dailyAt('03:00')
    ->runInBackground();

Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(2);
