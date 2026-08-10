<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
use App\Queries\IssueInboxQuery;

it('returns every issue when no filter is given', function (): void {
    foreach (IssueStatus::cases() as $status) {
        Issue::factory()->create(['status' => $status]);
    }

    $issues = resolve(IssueInboxQuery::class)->builder(null, null)->get();

    expect($issues)->toHaveCount(3);
});

it('filters by status', function (IssueStatus $status): void {
    foreach (IssueStatus::cases() as $case) {
        Issue::factory()->create(['status' => $case]);
    }

    $issues = resolve(IssueInboxQuery::class)->builder($status, null)->get();

    expect($issues)->toHaveCount(1)
        ->and($issues->sole()->status)->toBe($status);
})->with(IssueStatus::cases());

it('filters by project', function (): void {
    $project = Project::factory()->create();

    Issue::factory()->create(['project_id' => $project->id]);
    Issue::factory()->create();

    $issues = resolve(IssueInboxQuery::class)->builder(null, $project->id)->get();

    expect($issues)->toHaveCount(1)
        ->and($issues->sole()->project_id)->toBe($project->id);
});

it('combines the status and project filters', function (): void {
    $project = Project::factory()->create();

    Issue::factory()->create(['project_id' => $project->id, 'status' => IssueStatus::Open]);
    Issue::factory()->create(['project_id' => $project->id, 'status' => IssueStatus::Resolved]);
    Issue::factory()->create(['status' => IssueStatus::Open]);

    $issues = resolve(IssueInboxQuery::class)
        ->builder(IssueStatus::Open, $project->id)
        ->get();

    expect($issues)->toHaveCount(1)
        ->and($issues->sole()->project_id)->toBe($project->id)
        ->and($issues->sole()->status)->toBe(IssueStatus::Open);
});

it('orders issues by last seen descending', function (): void {
    Issue::factory()->create(['title' => 'Older', 'last_seen_at' => now()->subHour()]);
    Issue::factory()->create(['title' => 'Newer', 'last_seen_at' => now()]);

    $issues = resolve(IssueInboxQuery::class)->builder(null, null)->get();

    expect($issues->pluck('title')->all())->toBe(['Newer', 'Older']);
});

it('eager loads the project of every issue', function (): void {
    Issue::factory()->count(2)->create();

    $issues = resolve(IssueInboxQuery::class)->builder(null, null)->get();

    expect($issues->every(fn (Issue $issue): bool => $issue->relationLoaded('project')))
        ->toBeTrue();
});
