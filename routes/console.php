<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('db-backup:run')
    ->dailyAt('02:00')
    ->onOneServer()
    ->runInBackground();

Schedule::command('monitor:check-heartbeats')
    ->everyFiveMinutes()
    ->onOneServer();

Schedule::command('monitor:flush-issue-notifications')
    ->everyFiveMinutes()
    ->onOneServer();

Schedule::command('monitor:prune')
    ->dailyAt('03:00')
    ->onOneServer()
    ->runInBackground();

Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
