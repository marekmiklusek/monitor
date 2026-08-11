<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Enums\IssueStatus;
use App\Models\Occurrence;
use App\Actions\PruneMonitoringData;
use Illuminate\Testing\PendingCommand;

it('deletes resolved and ignored issues past the retention window', function (IssueStatus $status): void {
    $issue = Issue::factory()->create([
        'status' => $status,
        'last_seen_at' => now()->subDays(100),
    ]);

    Occurrence::factory()->count(2)->create(['issue_id' => $issue->id]);

    $pruned = resolve(PruneMonitoringData::class)->execute();

    expect(Issue::query()->count())->toBe(0)
        ->and(Occurrence::query()->count())->toBe(0)
        ->and($pruned['issues'])->toBe(1);
})->with([
    'resolved' => IssueStatus::Resolved,
    'ignored' => IssueStatus::Ignored,
]);

it('keeps open issues regardless of their age', function (): void {
    Issue::factory()->create([
        'status' => IssueStatus::Open,
        'last_seen_at' => now()->subDays(100),
    ]);

    $pruned = resolve(PruneMonitoringData::class)->execute();

    expect(Issue::query()->count())->toBe(1)
        ->and($pruned['issues'])->toBe(0);
});

it('keeps resolved issues inside the retention window', function (): void {
    Issue::factory()->resolved()->create(['last_seen_at' => now()->subDays(10)]);

    resolve(PruneMonitoringData::class)->execute();

    expect(Issue::query()->count())->toBe(1);
});

it('deletes stale occurrences of an open issue', function (): void {
    $issue = Issue::factory()->create([
        'status' => IssueStatus::Open,
        'last_seen_at' => now(),
    ]);

    Occurrence::factory()->count(3)->create([
        'issue_id' => $issue->id,
        'occurred_at' => now()->subDays(40),
    ]);

    Occurrence::factory()->count(2)->create([
        'issue_id' => $issue->id,
        'occurred_at' => now()->subDay(),
    ]);

    $pruned = resolve(PruneMonitoringData::class)->execute();

    expect(Occurrence::query()->count())->toBe(2)
        ->and($pruned['occurrences'])->toBe(3)
        ->and(Issue::query()->count())->toBe(1);
});

it('prunes in chunks until nothing is left', function (): void {
    config()->set('monitoring.prune_chunk_size', 2);

    $issue = Issue::factory()->create(['status' => IssueStatus::Open]);

    Occurrence::factory()->count(7)->create([
        'issue_id' => $issue->id,
        'occurred_at' => now()->subDays(40),
    ]);

    $pruned = resolve(PruneMonitoringData::class)->execute();

    expect($pruned['occurrences'])->toBe(7)
        ->and(Occurrence::query()->count())->toBe(0);
});

it('honours the configured retention windows', function (): void {
    config()->set('monitoring.issue_retention_days', 5);
    config()->set('monitoring.occurrence_retention_days', 2);

    Issue::factory()->resolved()->create(['last_seen_at' => now()->subDays(6)]);

    $open = Issue::factory()->create(['status' => IssueStatus::Open]);

    Occurrence::factory()->create([
        'issue_id' => $open->id,
        'occurred_at' => now()->subDays(3),
    ]);

    $pruned = resolve(PruneMonitoringData::class)->execute();

    expect($pruned['issues'])->toBe(1)
        ->and($pruned['occurrences'])->toBe(1);
});

it('reports the pruned counts through the command', function (): void {
    $issue = Issue::factory()->resolved()->create(['last_seen_at' => now()->subDays(100)]);

    Occurrence::factory()->create(['issue_id' => $issue->id]);

    $command = $this->artisan('monitor:prune');

    expect($command)->toBeInstanceOf(PendingCommand::class);

    if ($command instanceof PendingCommand) {
        $command->expectsOutputToContain('Pruned 1 issues and 0 occurrences.')->run();
    }
});

it('reports zero when there is nothing to prune', function (): void {
    $command = $this->artisan('monitor:prune');

    expect($command)->toBeInstanceOf(PendingCommand::class);

    if ($command instanceof PendingCommand) {
        $command->expectsOutputToContain('Pruned 0 issues and 0 occurrences.')->run();
    }
});
