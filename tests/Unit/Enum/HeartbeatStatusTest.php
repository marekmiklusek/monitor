<?php

declare(strict_types=1);

use App\Enums\HeartbeatStatus;

it('exposes every heartbeat state', function (): void {
    expect(array_column(HeartbeatStatus::cases(), 'value'))
        ->toBe(['ok', 'stale', 'missing']);
});

it('resolves a case from its value', function (string $value): void {
    expect(HeartbeatStatus::from($value)->value)->toBe($value);
})->with(['ok', 'stale', 'missing']);
