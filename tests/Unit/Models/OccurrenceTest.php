<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Occurrence;

it('relates an occurrence back to its issue', function (): void {
    $occurrence = Occurrence::factory()->create();

    expect($occurrence->issue->id)->toBe($occurrence->issue_id);
});

it('casts the payload to an array', function (): void {
    $payload = ['type' => 'exception', 'message' => 'Boom', 'context' => ['id' => 1]];

    $occurrence = Occurrence::factory()->create(['payload' => $payload]);

    expect($occurrence->refresh()->payload)->toEqualCanonicalizing($payload);
});

it('casts the occurred at timestamp', function (): void {
    $occurrence = Occurrence::factory()->create(['occurred_at' => now()->subHour()]);

    expect($occurrence->refresh()->occurred_at->toIso8601String())
        ->toBe(now()->subHour()->toIso8601String());
});

it('is removed together with its issue', function (): void {
    $issue = Issue::factory()->create();

    Occurrence::factory()->create(['issue_id' => $issue->id]);

    $issue->delete();

    expect(Occurrence::query()->count())->toBe(0);
});
