<?php

declare(strict_types=1);

use App\Enums\OccurrenceType;

it('exposes every occurrence type of the ingest contract', function (): void {
    expect(array_column(OccurrenceType::cases(), 'value'))
        ->toBe(['exception', 'failed_job', 'slow_query', 'heartbeat', 'log']);
});

it('resolves a case from its value', function (string $value): void {
    expect(OccurrenceType::from($value)->value)->toBe($value);
})->with(['exception', 'failed_job', 'slow_query', 'heartbeat', 'log']);
