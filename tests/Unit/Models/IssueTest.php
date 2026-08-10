<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Enums\IssueStatus;
use App\Models\Occurrence;
use App\Enums\OccurrenceType;

it('relates an issue to its project', function (): void {
    $issue = Issue::factory()->create();

    expect($issue->project->id)->toBe($issue->project_id);
});

it('relates an issue to its occurrences', function (): void {
    $issue = Issue::factory()->create();
    $occurrence = Occurrence::factory()->create(['issue_id' => $issue->id]);

    expect($issue->occurrences->pluck('id')->all())->toBe([$occurrence->id]);
});

it('casts the type and status to enums', function (): void {
    $issue = Issue::factory()->create([
        'type' => OccurrenceType::Log,
        'status' => IssueStatus::Ignored,
    ]);

    expect($issue->refresh()->type)->toBe(OccurrenceType::Log)
        ->and($issue->status)->toBe(IssueStatus::Ignored);
});

it('casts the counter to an integer', function (): void {
    $issue = Issue::factory()->create(['occurrences_count' => 7]);

    expect($issue->refresh()->occurrences_count)->toBe(7);
});

it('casts the seen timestamps', function (): void {
    $issue = Issue::factory()->create([
        'first_seen_at' => now()->subDay(),
        'last_seen_at' => now(),
    ]);

    expect($issue->refresh()->first_seen_at->toIso8601String())
        ->toBe(now()->subDay()->toIso8601String())
        ->and($issue->last_seen_at->toIso8601String())
        ->toBe(now()->toIso8601String());
});

it('marks a resolved issue through the factory state', function (): void {
    expect(Issue::factory()->resolved()->create()->status)->toBe(IssueStatus::Resolved);
});
