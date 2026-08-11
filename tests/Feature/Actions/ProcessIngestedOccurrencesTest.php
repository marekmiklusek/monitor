<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
use App\Models\Occurrence;
use App\Enums\OccurrenceType;
use App\Actions\ProcessIngestedOccurrences;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function exceptionOccurrence(array $overrides = []): array
{
    return [
        'type' => OccurrenceType::Exception->value,
        'occurred_at' => now()->toIso8601String(),
        'exception_class' => 'RuntimeException',
        'message' => 'Order 1234 failed',
        'file' => '/app/Http/Controllers/OrderController.php',
        'line' => 42,
        'stack' => [],
        'context' => [],
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function logOccurrence(array $overrides = []): array
{
    return [
        'type' => OccurrenceType::Log->value,
        'occurred_at' => now()->toIso8601String(),
        'channel' => 'stack',
        'message' => 'Payment failed',
        ...$overrides,
    ];
}

it('groups two identical exceptions into a single issue', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        exceptionOccurrence(['message' => 'Order 1234 failed']),
        exceptionOccurrence(['message' => 'Order 9876 failed']),
    ]);

    expect(Issue::query()->count())->toBe(1);

    $issue = Issue::query()->sole();

    expect($issue->occurrences_count)->toBe(2)
        ->and($issue->project_id)->toBe($project->id)
        ->and($issue->type)->toBe(OccurrenceType::Exception)
        ->and($issue->title)->toBe('RuntimeException')
        ->and($issue->occurrences()->count())->toBe(2);
});

it('creates separate issues for occurrences differing only in line number', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        exceptionOccurrence(['line' => 42]),
        exceptionOccurrence(['line' => 99]),
    ]);

    expect(Issue::query()->count())->toBe(2);
});

it('reopens a resolved issue on a new occurrence', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [exceptionOccurrence()]);

    $issue = Issue::query()->sole();
    $issue->fill(['status' => IssueStatus::Resolved])->save();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [exceptionOccurrence()]);

    expect($issue->refresh()->status)->toBe(IssueStatus::Open)
        ->and($issue->occurrences_count)->toBe(2);
});

it('keeps an ignored issue ignored on a new occurrence', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [exceptionOccurrence()]);

    $issue = Issue::query()->sole();
    $issue->fill(['status' => IssueStatus::Ignored])->save();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [exceptionOccurrence()]);

    expect($issue->refresh()->status)->toBe(IssueStatus::Ignored);
});

it('updates the project heartbeat instead of creating an issue', function (): void {
    $project = Project::factory()->create(['last_heartbeat_at' => null]);

    $occurredAt = now()->subMinute();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        [
            'type' => OccurrenceType::Heartbeat->value,
            'occurred_at' => $occurredAt->toIso8601String(),
        ],
    ]);

    expect(Issue::query()->count())->toBe(0)
        ->and(Occurrence::query()->count())->toBe(0)
        ->and($project->refresh()->last_heartbeat_at?->toIso8601String())->toBe($occurredAt->toIso8601String());
});

it('keeps at most fifty occurrences per issue', function (): void {
    $project = Project::factory()->create();

    $occurrences = array_map(
        fn (int $minute): array => exceptionOccurrence(['occurred_at' => now()->subMinutes(60 - $minute)->toIso8601String()]),
        range(1, 55),
    );

    resolve(ProcessIngestedOccurrences::class)->execute($project, $occurrences);

    $issue = Issue::query()->sole();

    expect($issue->occurrences_count)->toBe(55)
        ->and($issue->occurrences()->count())->toBe(50)
        ->and($issue->occurrences()->min('occurred_at'))->toBe(now()->subMinutes(54)->format('Y-m-d H:i:s'));
});

it('groups logs whose messages differ only in volatile values', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        logOccurrence(['message' => 'Payment 4711 for john@example.com failed']),
        logOccurrence(['message' => 'Payment 998 for jane@example.com failed']),
        logOccurrence(['message' => 'Payment 3 for 0d1a2b3c-4d5e-6f70-8192-a3b4c5d6e7f8 failed']),
    ]);

    expect(Issue::query()->count())->toBe(2);

    $issue = Issue::query()->where('message', 'Payment 998 for jane@example.com failed')->sole();

    expect($issue->occurrences_count)->toBe(2)
        ->and($issue->type)->toBe(OccurrenceType::Log)
        ->and($issue->title)->toBe('stack');
});

it('creates separate issues for logs from different channels', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        logOccurrence(['channel' => 'stack']),
        logOccurrence(['channel' => 'payments']),
    ]);

    expect(Issue::query()->count())->toBe(2);
});

it('falls back to the message when the occurrence has no exception class', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        exceptionOccurrence(['exception_class' => null, 'message' => 'Query took 9 seconds']),
    ]);

    expect(Issue::query()->sole()->title)->toBe('Query took 9 seconds');
});

it('falls back to the occurrence type when there is no exception class and no message', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        exceptionOccurrence(['exception_class' => null, 'message' => null]),
    ]);

    expect(Issue::query()->sole()->title)->toBe(OccurrenceType::Exception->value);
});

it('truncates a long title to the column length', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        exceptionOccurrence(['exception_class' => str_repeat('a', 300)]),
    ]);

    expect(Issue::query()->sole()->title)->toHaveLength(255);
});

it('stores breadcrumbs inside the occurrence payload', function (): void {
    $project = Project::factory()->create();

    $breadcrumbs = [
        [
            'level' => 'info',
            'message' => 'Checkout started',
            'context' => ['cart_id' => 12],
            'logged_at' => now()->subMinute()->toIso8601String(),
        ],
    ];

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        exceptionOccurrence(['breadcrumbs' => $breadcrumbs]),
    ]);

    $occurrence = Occurrence::query()->sole();

    expect($occurrence->payload['breadcrumbs'])->toEqualCanonicalizing($breadcrumbs);
});
