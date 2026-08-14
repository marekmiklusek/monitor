<?php

declare(strict_types=1);

use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Foundation\Testing\RefreshDatabase;

// pest()->tia()->locally();

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        config()->set('monitoring.channels', ['mail']);
        config()->set('services.telegram-bot-api.token');
        config()->set('monitoring.telegram_chat_id');

        Date::setTestNow(Date::now());
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void
{
    // ..
}
