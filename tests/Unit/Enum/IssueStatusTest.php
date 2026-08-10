<?php

declare(strict_types=1);

use App\Enums\IssueStatus;

it('exposes every issue state', function (): void {
    expect(array_column(IssueStatus::cases(), 'value'))
        ->toBe(['open', 'resolved', 'ignored']);
});

it('resolves a case from its value', function (string $value): void {
    expect(IssueStatus::from($value)->value)->toBe($value);
})->with(['open', 'resolved', 'ignored']);
